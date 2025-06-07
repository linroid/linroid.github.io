---
title: Android Orientation 的坑
date: 2016-11-09 10:41:40
tags:
  - Android
  - Orientation
  - 屏幕适配
  - 踩坑
categories:
  - Android开发
  - UI组件
---
　　最近在做 [Bigo Live](http://www.bigo.sg/index_pc.html) 直播间的横屏适配，横屏和竖屏下会有一些状态的差异。但我们的应用在横屏下，切换后台再回来后，发现一些状态显示不对。
<!--more-->
　　猜想问题出现在了横竖屏状态判断上，先看下代码：
	```java
    public boolean isOrientationPortrait() {
        return getResources().getConfiguration().orientation == Configuration.ORIENTATION_PORTRAIT;
    }
	```
　　通过调试发现应用切后台之后， `isOrientationPortrait()` 会返回 true，于是换上另一种横竖屏判断：
	```java
	public boolean isOrientationPortrait() {
	    return getRequestedOrientation() == ActivityInfo.SCREEN_ORIENTATION_PORTRAIT;
	}
	```
　　这两种方式的区别在于 `Configuration.orientation` 拿到的是设备的方向，而 `getRequestedOrientation()`拿到的是 Activity 请求的方向（通过`AndroidManifes.xml`中设置或者通过 `setRequestOrientation()` 改变)。
　　修改之后，应用处于后台时方向能判断正常，但一些后台时 `inflate` 的 View 却不正常或是抛出异常。于是写了一个 `Demo` 测试一下：[Gist](https://gist.github.com/linroid/0c8086db0bcdf0abc7c1220cac4eb7da)。
```
 V/MainActivity: ⇢ onPause()
 D/MainActivity: getRequestedOrientation: landscape
 D/MainActivity: Configuration.orientation: landscape
 D/MainActivity: Layout View: landscape
 V/MainActivity: ⇠ onPause [2ms]

 V/MainActivity: ⇢ onStop()
 D/MainActivity: getRequestedOrientation: landscape
 D/MainActivity: Configuration.orientation: portrait
 D/MainActivity: Layout View: portrait
 V/MainActivity: ⇠ onStop [2ms]
```
　　从中可以发现，从应用横屏状态切到后台 `Configuration.orientation` 会在 `onStop()`发生改变，而 `LayoutInflater#inflate()` 加载的 View 取决于`Configuration.orientation`，与 `getRequestedOrientation()`无关。

## 验证
　　由 `LayoutInflater#inflate()` 可以找到布局文件路径是在 `Resources#loadXmlResourceParser()` 中拿到的：
  ```java
	XmlResourceParser loadXmlResourceParser(@AnyRes int id, @NonNull String type)
	        throws NotFoundException {
	    final TypedValue value = obtainTempTypedValue();
	    try {
	        final ResourcesImpl impl = mResourcesImpl;
	        impl.getValue(id, value, true);
	        // ...
	    } finally {
	        releaseTempTypedValue(value);
	    }
	}
  ```

`ResourcesImpl#getValue()`：
  ```java
	void getValue(@AnyRes int id, TypedValue outValue, boolean resolveRefs)
	        throws NotFoundException {
	    boolean found = mAssets.getResourceValue(id, 0, outValue, resolveRefs);
	    if (found) {
	        return;
	    }
	    throw new NotFoundException("Resource ID #0x" + Integer.toHexString(id));
	}
    ```
`AssetsManager#getResourceValue()` 会调用 `AssetsManager#loadResourceValue()`，这是一个 Native 方法，那么 Native 层是怎样获取方向的呢？
`ResourcesImpl#updateConfiguration()`：
   ```java
	public void updateConfiguration(Configuration config, DisplayMetrics metrics,
	                        CompatibilityInfo compat) {
	// ...
	mAssets.setConfiguration(mConfiguration.mcc, mConfiguration.mnc,
	                    adjustLanguageTag(mConfiguration.getLocales().get(0).toLanguageTag()),
	                    mConfiguration.orientation,
	                    mConfiguration.touchscreen,
	                    mConfiguration.densityDpi, mConfiguration.keyboard,
	                    keyboardHidden, mConfiguration.navigation, width, height,
	                    mConfiguration.smallestScreenWidthDp,
	                    mConfiguration.screenWidthDp, mConfiguration.screenHeightDp,
	                    mConfiguration.screenLayout, mConfiguration.uiMode,
	                    Build.VERSION.RESOURCES_SDK_INT);
	// ...
	}
   ```
`AssetsManager#setConfiguration()` 是 Native 层的方法，由此可以得出结论，Native 找到布局文件路径是通过 Configuration.orientation 来判断方向的，所以应用后台时会加载竖屏的资源。

## 解决
 - 横竖屏的判断：
　　可以通过给 View 设置 Tag 或者判断某个 View 是否存在，来判断加载的是哪个状态的布局文件：
	```java
	public boolean isOrientationPortrait() {
	    if (mRootView == null) {
	        return getRequestedOrientation() == ActivityInfo.SCREEN_ORIENTATION_PORTRAIT;
	    }
	    return "port" .equals(mRootView.getTag());
	}
	```
 - 后台时资源的加载：
   如果你的应用处于横屏状态， 尽量不要在应用后台时加载与屏幕方向有关的资源，如果非要加载可以采取以下方法：
   - 将资源文件改为不同的名称（记得要都放到没有land标识的文件夹下，否则会出现 Resources#NotFoundException），然后根据方向判断加载哪一个。
   - 通过反射方式修改 Native 层的屏幕方向：
   ```java
	/**
	 * 指定屏幕方向来加载资源
	 * @author linroid <linroid@gmail.com>
	 * @since 09/11/2016
	 */
	public class OrientationResourceLoader {
	    public static void load(Activity activity, Callback callback) {
	        load(activity, activity.getRequestedOrientation() == ActivityInfo.SCREEN_ORIENTATION_PORTRAIT, callback);
	    }

	    public static void load(Context context, boolean isPortrait, @NonNull Callback callback) {
	        Resources resources = context.getResources();
	        if (isPortrait || context.getResources().getConfiguration().orientation == Configuration.ORIENTATION_LANDSCAPE) {
	            callback.onLoad(context, resources);
	            return;
	        }
	        try {
	            Method updateConfiguration = resources.getClass()
	                    .getMethod("updateConfiguration", Configuration.class, DisplayMetrics.class);
	            Configuration configuration = new Configuration(resources.getConfiguration());
	            DisplayMetrics displayMetrics = resources.getDisplayMetrics();
	            configuration.orientation = Configuration.ORIENTATION_LANDSCAPE;
	            updateConfiguration.invoke(resources, configuration, displayMetrics);
	            callback.onLoad(context, resources);
	            configuration.orientation = Configuration.ORIENTATION_PORTRAIT;
	            updateConfiguration.invoke(resources, resources.getConfiguration(), displayMetrics);
	        } catch (Exception error) {
	            error.printStackTrace();
	            callback.onLoad(context, resources);
	        }
	    }

	    public interface Callback {
	        void onLoad(Context context, Resources resources);
	    }
	}
   ```