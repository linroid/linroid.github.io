title: Activity 销毁的延迟
tags:
  - 踩坑
categories: []
date: 2017-05-24 16:32:00
---

先看一张按下 Back 键之后，Activity 生命周期回调的日志截图：
![ActivityBackPressed](http://7u2rtn.com1.z0.glb.clouddn.com/activity_back_pressed.png)
从日志中可以看到当按下 Back 键时，当前的 Activity 会马上回调 onPause() 方法，而 onStop() 是在 MainActivity 的 onResume()之后才调用，onPause() 与 onStop()之间相隔了大约 300ms，也就是说 Activity 不是马上被销毁的。

再看另一个快速重新打开 `LifeActivity` 的 Case：
![QuickReopenActivity](http://7u2rtn.com1.z0.glb.clouddn.com/quick_reopen_actiivty.png)
> 这里我采用代码模拟快速重新打开 `LifeActivity`，finish() 后延迟 300ms 再启动 `LifeActivity`。因为我们的 Activity 里没做什么事，所以很难手动重现快速重新打开 Activity 的异常 Case，而实际项目因为逻辑复杂，往往在1~2s或者更长的时间里很容易复现这种情况。

出现了诡异的事：LifeActivity[33732136] 是旧的 Activity，但它却在新的 LifeActivity[212157058] 显示之后才被销毁的，看到这个可能你已经心头一凉。这会导致什么问题呢？这会让我们依赖 Activity 生命周期回调来做资源回收的代码变得不可靠。

举个栗子：如果我们在 onStart() 中启动相机在 onStop() 中关闭相机，正常重新打开这个页面时相机的状态操作：打开 -> 关闭 -> 打开，快速重新打开这个 Activity 时就可能不是这个顺序了：打开->打开->关闭。然后用户就遭殃了，他将无法正常使用你的应用了，而用户只是因为手速过快。
<!--more-->

# 分析

那么为什么 onPause() 是马上被调用，而 onStop() 和 onDestroy() 却被延迟这么久呢？
> 关于 Activity 销毁流程的源码我不会做详细分析，具体可以查看相关源码或者搜索其他人的文章看下

调用 finish 方法后会经过以下流程向 `ActivityManagerService` 请求销毁当前 Activity：

```
MyActivity.finish() 
Activity.finish() 
ActivityManagerNative.getDefault().finishActivity() 
ActivityManagerService.finishActivity() 
ActivityStack.requestFinishActivityLocked() 
ActivityStack.finishActivityLocked() 
ActivityStack.startPausingLocked() 
```

然后 `ActivityManagerService` 就会请求执行当前 Activity 的 onPause() 方法：

```
IApplicationThread.schedulePauseActivity() 
ActivityThread.schedulePauseActivity() 
ActivityThread.sendMessage() 
ActivityThread.H.sendMessage() 
ActivityThread.H.handleMessage() 
ActivityThread.handlePauseActivity() 
ActivityThread.performPauseActivity() 
Instrumentation.callActivityOnPause() 
Activity.performPause() 
Activity.onPause() 
ActivityManagerNative.getDefault().activityPaused() 
ActivityManagerService.activityPaused() 
ActivityStack.activityPausedLocked() 
ActivityStack.completePauseLocked() 
```
所以 onPause() 是立即被执行的，执行完 onPause() 后并没有马上销毁 Activity，而是先让一个 Activity 显示出来，这个 Activity 可能是当前应用 Activity 栈中的一个 Activity 也可能是 Launcher 或者其他应用的 Activity，不管是哪个都大同小异，在上面的 Case 中就是 MainActivity。

执行上一个 Activity 即 MainActivity 的 onResume()

```
ActivityStack.resumeTopActivityLocked() 
ActivityStack.resumeTopInnerLocked() 
IApplicationThread.scheduleResumeActivity() 
ActivityThread.scheduleResumeActivity() 
ActivityThread.sendMessage() 
ActivityTherad.H.sendMessage() 
ActivityThread.H.handleMessage() 
ActivityThread.handleResumeActivity() 
Activity.performResume() 
Activity.performRestart() 
Instrumentation.callActivityOnRestart() 
Activity.onRestart() 
Activity.performStart() 
Instrumentation.callActivityOnStart() 
Activity.onStart() 
Instrumentation.callActivityOnResume() 
Activity.onResume() 
```

执行完上一个 Activity 的 onResume 之后，该进行 Activity 的销毁操作了吧？
通过反向分析，发现 Activity 的销毁时通过请求 ActivityManagerService 的 activityIdle() 方法，销毁流程如下：

```java
Looper.myQueue().addIdleHandler(new Idler()) 
ActivityManagerNative.getDefault().activityIdle() 
ActivityManagerService.activityIdle() 
ActivityStackSupervisor.activityIdleInternalLocked() 
ActivityStack.destroyActivityLocked() 
IApplicationThread.scheduleDestoryActivity() 
ActivityThread.scheduleDestoryActivity() 
ActivityThread.sendMessage() 
ActivityThread.H.sendMessage() 
ActivityThread.H.handleMessage() 
ActivityThread.handleDestoryActivity() 
ActivityThread.performDestoryActivity() 
Activity.performStop() 
Instrumentation.callActivityOnStop() 
Activity.onStop() 
Instrumentation.callActivityOnDestory() 
Activity.performDestory() 
Acitivity.onDestory() 
ActivityManagerNative.getDefault().activityDestoryed() 
ActivityManagerService.activityDestoryed() 
ActivityStack.activityDestoryedLocked() 
```

这个 Idler 实现的是 `MessageQueue.IdleHandler`，IdleHandler 会等到 MessageQueue 中当前没有可执行的消息时才会执行，也就是说 Activity 会一直等待主线程消息队列中当前消息都处理完毕了才会进行销毁，这也就是 Activity 的销毁不是立即执行的根本原因。

# 如何避免

手速快并不是用户的锅，要避免这种情况，可以用个静态变量保存当前 Activity，并且在销毁的时候判断下是不是与保存的一致，以下给出示例代码，如果你有更好的方案，欢迎告诉我 :)

```java
public class LifeActivity extends AppCompatActivity {

    private static LifeActivity sCurrentLifeActivity;
    
    // ...

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        sCurrentLifeActivity = this;
    }
    
    @Override
    protected void onStart() {
        super.onStart();
        someResource.open();
    }

    @Override
    protected void onStop() {
        super.onStop();
        if (sCurrentLifeActivity == this) {
            someResource.close();
        }
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        if (sCurrentLifeActivity == this) {
            sCurrentLifeActivity = null;
        }
    }

}
```
