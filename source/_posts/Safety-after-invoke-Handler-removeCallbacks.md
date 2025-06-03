---
title: '调用完 Handler#removeCallbacks() 就安全了吗?'
date: 2016-11-25 11:51:31
tags: 
 - Handler
 - 内存

---
　　`Handler` 容易引起内存泄露，这是大家都知道的，所以你应该会在适当的时候调用 `removeCallbacks()` 方法来移除消息。但当以下使用场景时，依然可能会出现内存泄露。
 
<!-- more -->
 
```java
// ...
private HandlerThread mDaemonHandler;

Runnable mTimerRunnable = new Runnable() {
	@Override
	public void run() {
	    longTimeOperation();
	    Daemon.handler().postDelayed(this, DELAY_INTERVAL);
	}
};
// ...
@Override
public void onDestory() {
	// ...
	mDaemonHandler.removeCallbacks(mTimerRunnable)
}
// ...
```
　　`mTimerRunnable` 是一个在非主线程中运行的循环操作，一旦启动了它，就会每隔 `DELAY_INTERVAL` 时间被执行一次，所以在 Activity 退出时，应该停止它，否则就会出现内存泄露。停止它的方式就是调用 `removeCallbacks()` 把它从`mDaemonHandler` 的消息队列中移除。__但这一操作如果处在 `mDaemonHandler` 线程正在执行 `longTimeOperation()`时，`mTimerRunnable`之后还是会被添加到 `mDaemonHandler` 线程的消息队列中。__
　　
　　要保证消息能够移除掉，可以这样写：
```java
 mDaemonHandler.post(new Runnable() {
    @Override
    public void run() {
        mDaemonHandler.removeCallbacks(mTimerRunnable);
    }
});
```
　　这样就保证了移除 `mTimerRunnable` 的操作和 `mDaemonHandler` 在同一线程中，不会和 `mTimerRunnable` 『并发执行』。写成更通用的：
```java
import android.os.Handler;
import android.os.Looper;

/**
 * @author linroid <linroid@gmail.com>
 * @since 25/11/2016
 */
public class SafetyUtils {
    public static void removeCallbacks(final Handler handler, final Runnable callback) {
        if (handler.getLooper() == Looper.myLooper()) {
            handler.removeCallbacks(callback);
        } else {
            handler.post(new Runnable() {
                @Override
                public void run() {
                    handler.removeCallbacks(callback);
                }
            });
        }
    }

    public static void removeMessages(final Handler handler, final int what) {
        removeMessages(handler, what, null);
    }

    public static void removeMessages(final Handler handler, final int what, final Object obj) {
        if (handler.getLooper() == Looper.myLooper()) {
            handler.removeMessages(what, obj);
        } else {
            handler.post(new Runnable() {
                @Override
                public void run() {
                    handler.removeMessages(what, obj);
                }
            });
        }
    }
}
```