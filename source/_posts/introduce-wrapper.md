title: 「Wrapper」让你更愉快地调用 Listener
date: 2017-03-19 14:11:22
tags: 
 - Android
 - 造轮子
---

 > 在 [GitHub](https://github.com/linroid/Wrapper) 查看
 
# 背景
Android 开发中，会有很多情况下用到观察者模式，如果你自定义了一个 Listener，在需要调用他们来通知观察者时，或许会遇到以下几点头疼的问题：

 - 这个 Listener 是 Nullable 的，那么每次调用前都需要作判空处理

	```java
	if (listener != null) {
		listener.foo();
	}
	```

 - 事件是在其他线程中发出，而观察者需要在另一个线程中（通常是 UI 线程）处理，所以需要在每次调用的时候做 `Handler#post`操作

	```java
	if(Looper.myLooper() != Looper.getMainLooper()) {
		handler.post(new Runnable() {
			@Override
			public void run() {
				listener.foo();
			}
		});
	} else {
		listener.foo();
	}
  ```
	
 - 如果观察者可以注册多个，那每次在调用的时候的时候都需要遍历一下所有 Listener

	```java
	for(final FooListener listener : listener) {
	   listener.foo();
	}
	```

这三种情况可能会同时出现，比如在做与 Service 通信时是很常见的，如果回调方法更多时，代码写起来那是相当痛苦😂
<!-- more --> 

# 造轮子

 基于上面的原因，于是我撸一个轮子，就叫「Wrapper」。它利用 AnnotationProcessor 在构建过程中自动生成指定 Listener 的 Wrapper 类，在需要时只需简简单单的调用一下方法即可：
 
 ```java
 listener.foo();
 ```
 Wrapper 会根据需要生成判空处理、post、遍历等代码，从编写繁琐无趣的代码中解放出来。

```java
@WrapperClass
@WrapperMultiple
public interface SomeListener {
    @UiThread
    void onFoo(View view);

    boolean onUserLeave();
}
```

经过「Wrapper」的处理，会生成如下代码：

```java
package com.linroid.wrapper;

import android.os.Handler;
import android.os.Looper;
import android.view.View;

import java.util.HashSet;

public class SomeListenerMultiWrapper implements SomeListener {
    private HashSet<SomeListener> _delegates = new HashSet<>();

    private Handler _handler;

    public SomeListenerMultiWrapper() {
        this._handler = new Handler(Looper.getMainLooper());
    }

    public SomeListenerMultiWrapper(Handler _handler) {
        this._handler = _handler;
    }

    public synchronized boolean addWrapper(SomeListener _delegate) {
        if (_delegate != null) {
            return this._delegates.add(_delegate);
        }
        return false;
    }

    public synchronized boolean removeWrapper(SomeListener _delegate) {
        return this._delegates.remove(_delegate);
    }

    @Override
    public boolean onUserLeave() {
        for (final SomeListener _delegate : _delegates) {
            if (_delegate != null) {
                _delegate.onUserLeave();
            }
        }
        return false;
    }

    @Override
    public void onFoo(final View view) {
        if (Looper.myLooper() == null || Looper.myLooper() != _handler.getLooper()) {
            _handler.post(new Runnable() {
                @Override
                public void run() {
                    for (final SomeListener _delegate : _delegates) {
                        if (_delegate != null) {
                            _delegate.onFoo(view);
                        }
                    }
                }
            });
        } else {
            for (final SomeListener _delegate : _delegates) {
                if (_delegate != null) {
                    _delegate.onFoo(view);
                }
            }
        }
    }
}
```

# 使用「Wrapper」

## 引入
在你的 `build.gradle`：
	```groovy
	dependencies {
	    annotationProcessor 'com.linroid.wrapper:compiler:0.0.1'
	    compile 'com.linroid.wrapper:library:0.0.1'
	}
	```

## 可以使用的注解：

 - `@WrapperClass` 对单个接口 / 类进行处理，默认只会进行判空处理

	```java
	@WrapperClass
	public interface SomeListener {
		void onFoo(View view);
	}
	```

 - `@UiThread` 需要进行 `Handler#post` 处理，可以用在方法或者类 / 接口上，如果用在类 / 接口，会对所有方法进行处理
	
	```java
	
	// @UiThread // 会对所有方法生效
	@WrapperClass
	public interface SomeListener {
		@UiThread // 只对指定方法生效
		void onFoo(View view);
		
		boolean onUserLeave();
	}
	```

 - `@WrapperMultiple` 支持多个 Listener

	```java
	@WrapperClass
	@WrapperMultiple
	public interface SomeListener {
	    void onFoo(View view);
	}
	```

 - `@WrapperGenerator` 与`@WrapperClass` 不同，你可以创建一个空的 Class，将所有需要处理的接口 / 类添加进来（这样就可以处理你无法修改的一些 Listener 了，比如 Android SDK 中的）。

	```java
	@WrapperGenerator(
	        values = {
	                View.OnClickListener.class,
	                View.OnLongClickListener.class,
	                MenuItem.OnMenuItemClickListener.class,
	                View.OnScrollChangeListener.class
	        }
	)
	@UiThread
	@WrapperMultiple
	public class SomeGenerator {
	}
	```

## 调用
经过 Wrapper 的处理，会生成一个包名相同的 `XXXWrapper` 的类，如果添加了 `@ WrapperMultiple ` 注解，会额外生成一个 `XXXMultiWrapper` 类。

需要注意的是，如果处理的是一个接口，那么生成的 Wrapper 会实现这个接口；而如果处理的是一个类，那么生成的 Wrapper 不会继承这个类。

添加完注解在执行一次 build 后，Wrapper 就会生成好相应的 `XXXWrapper` 类，使用它们非常简单：

```java
SomeListenerWrapper wrapper = new SomeListenerWrapper(listener);
// SomeListener wrapper = new SomeListenerWrapper(handler, listener); // 自己指定一个 Handler
wrapper.setWrapper(listener); // 设置你实现的 listener
wrapper.onFoo(view); // 调用方法
	
SomeListenerMultiWrapper multiWrapper = new SomeListenerMultiWrapper();
// SomeListenerMultiWrapper multiWrapper = new SomeListenerMultiWrapper(handler); // 自己指定一个 Handler
multiWrapper.addWrapper(listener); // 添加 listener
multiWrapper.onFoo(view); // 调用方法
```

# 最后
如果你有什么好的建议可以在评论留言，也可以提 PR :)