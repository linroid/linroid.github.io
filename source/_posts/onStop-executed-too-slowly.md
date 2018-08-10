title: Activity 的 onStop 居然需要 10s 才会被执行？
date: 2018-08-10 10:15:27
tags:
---

由于我们的应用依赖 Activity 的 onStop 来停止播放器，近期发现退出直播间后，声音居然残留10s 左右，通过日志发现 Activity 的 onStop 在退出界面后需要10s左右才会被执行，该如何定位这个问题呢？
<!--more-->
Activity 的 onStop 是放到 `IdleHandler` 执行的，所以退出界面后 onStop 不会立即执行，而是等到主线程中当前没有消息要执行的时候才会执行，具体可见我的另一篇文章分析：[Activity 销毁的延迟](https://linroid.com/2017/05/24/Pit-of-Activity-destory/)。但必现 10s 左右才被执行肯定是异常的，可能有消息导致主线程一直没有 处理 IdleHandler，为验证这一点，可以在 onPause 的时候添加一个 IdleHandler 到主线程消息队列中

```java
@Override
protected void onPause() {
    super.onPause();
    Looper.myQueue().addIdleHandler(new MessageQueue.IdleHandler() {
        @Override
        public boolean queueIdle() {
            Log.i(TAG, "queueIdle after onPause");
            return false;
        }
    });
}
```

结果果然是在 onStop 之后才会被执行，所以可以确认是由于主线程一直有没有执行 IdleHandler 导致的，初步怀疑是有地方一直在往主线程 Looper 中 添加消息，接下来就需要定位是什么消息导致主线程一直处于非空闲状态。

我们可以通过 `Looper.setMessageLogging()` 来打印出主线程中执行的消息:

```java
@Override
protected void onPause() {
    Looper.getMainLooper().setMessageLogging(new Printer() {
        @Override
        public void println(String x) {
            if (x.startsWith(">>>>>")) {
                Log.d(TAG, x);
            }
        }
    });
}
```

发现有个动画组件的消息不停地被执行，移除这个动画组件后，问题依然存在，并且未发现其他比较明显的异常消息。

![](https://cdn.linroid.com/WX20180723-105051@2x.png)

既然主线程中一直非空闲状态，那么我们就把主线程消息队列中的所有消息打印出来看看？

通过反射，可在每一帧的时候打印出主线程消息队列中的所有消息：

```java
private Field messagesField;
private Field nextField;
{
    try {
        messagesField = MessageQueue.class.getDeclaredField("mMessages");
        messagesField.setAccessible(true);
        nextField = Message.class.getDeclaredField("next");
        nextField.setAccessible(true);
    } catch (NoSuchFieldException e) {
        e.printStackTrace();
    }
}

@Override
protected void onPause() {        
	Choreographer.getInstance().postFrameCallback(new Choreographer.FrameCallback() {
            @Override
            public void doFrame(long frameTimeNanos) {
                Choreographer.getInstance().postFrameCallback(this);
                Log.d(TAG, "doFrame");
                printMessages();
            }
        });
    }
}

private void printMessages() {
    MessageQueue queue = Looper.myQueue();
    try {
        Message msg = (Message) messagesField.get(queue);
        StringBuilder sb = new StringBuilder();
        while (msg != null) {
            sb.append(msg.toString());
            sb.append("\n");
            msg = (Message) nextField.get(msg);
        }
        Log.i(TAG, sb.toString());
    } catch (IllegalAccessException e) {
        e.printStackTrace();
    }
}
```
得到如下日志：
![](https://cdn.linroid.com/blog/WX20180723-105818@2x.png)
可以看到消息队列的头部始终是一个 SyncBarrier 消息，有这个消息存在的时候，MessageQueue 只会取下一个 异步消息，让系统的 UI 事件消息得到优先处理。由此猜测，有地方不停地向 MessageQueue 中添加 SyncBarrier，通过对 `MessageQueue#postSyncBarrier()  `方法打调试断点后，终于发现了罪魁祸首。

![](https://cdn.linroid.com/blog/WX20180723-193912@2x.png)

查看代码后发现，虽然这位童鞋在收到 onGlobalLayout() 回调时的确调用了`View.getViewTreeObserver().removeOnGlobalLayoutListener()` ，但在上面截图中的 `fixBtnLayout`方法中又把监听器重新添加上了，所以实际并没有成功移除，相当于这里出现一个“异步的死循环“。

在 OnGlobalLayoutListener 中更新了布局的 LayoutParams 导致触发了 requestLayout()，而 requestLayout 会调用 `MessageQueue#postSyncBarrier() `，至于为什么主线程中有 SyncBarrier 消息时，IdleHandler 没有被执行的原因可以看 `MessageQueue#next()`的源码：

```java
// If first time idle, then get the number of idlers to run.
// Idle handles only run if the queue is empty or if the first message
// in the queue (possibly a barrier) is due to be handled in the future.
if (pendingIdleHandlerCount < 0
    && (mMessages == null || now < mMessages.when)) {
    pendingIdleHandlerCount = mIdleHandlers.size();
}
if (pendingIdleHandlerCount <= 0) {
    // No idle handlers to run.  Loop and wait some more.
    mBlocked = true;
    continue;
}
```

由于这个时候是有消息的，所以 mMessages != null，由上面的日志可以看到 消息队列头部消息始终是 when 为负值，导致 `now < mMessages.when` 也不成立，所以 pendingIdleHandlerCount 会一直为0，直到调用了 `MessageQueue#removeSyncBarrier()` 来移除 SyncBarrier 消息。

那么为什么 Activity 还是能回调 `onStop()` 呢？过滤掉 ActivityManager 的消息后可以看到一条这样的日志：

```
 W/ActivityManager: Launch timeout has expired, giving up wake lock!
```

这是 ActivityManagerService 的超时机制，而这个时间正好是10s，具体可见 ActivityStack 中 `STOP_TIMEOUT`。超时后会把 Activity 强制置为 stop 状态，这时候不会再触发 onGlobalLayout，从而不会再有 SyncBarrier 消息，所以最终 IdleHandler 得到执行的机会。



## 相关源码

- [ActivityStack.java](https://android.googlesource.com/platform/frameworks/base.git/+/master/services/core/java/com/android/server/am/ActivityStack.java)
- [MessageQueue.java](https://android.googlesource.com/platform/frameworks/base/+/master/core/java/android/os/MessageQueue.java)