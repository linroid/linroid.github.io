title: "调用 FragmentPagerAdapter 的 notifyDataSetChanged() 方法视图未更新"
date: 2015-01-29 21:54:39
tags: Fragment
categories: 遇过的坑
---

学校在考完试后给我们加了一周的 Android 课，本来考完试很累了，还一天不让休息，天天起早去上课π__π。最后交课程设计, 模仿彩虹天气写一个天气应用,下面是我们组做的:
<img  src="http://7u2rtn.com1.z0.glb.clouddn.com/media/view-not-updated-after-call-FragmentPagerAdapter-notifyDataSetChanged/device-2015-01-26-105356.png" width="300px" />
<!--more-->
城市切换使用的 ViewPager，以下是 FragmentPagerAdapter 里的一个方法，当 Loader 加载了新的 Cursor 后调用该方法。
```java
    public void setCursor(Cursor cursor){
        int count = cursor==null ? 0 : cursor.getCount();
        Timber.d("cursor count : %d", count);
        weathers.clear();
        if(count==0){
            return;
        }
        for (int i=0; i<count; i++){
            cursor.moveToPosition(i);
            Weather weather = Weather.fromCursor(cursor);
            weathers.add(weather);
        }
        notifyDataSetChanged();
    }
```

想当然地像使用 ListView 的适配器一样调用 `notifyDataSetChanged()`,用但当添加删除城市后 ViewPager 里的视图并没有得到更新。Google 了下，Stackoverflow 里的一个回答让重写 getItemPosition() 方法:

```java
    @Override
    public int getItemPosition(Object object) {
        return POSITION_NONE;
    }
   ```
   
加了之后，Fragment 的数目的确增加/删除了，但 Fragment 里的内容并没有更新，会出现两个一模一样的城市(因为我设置新增的城市显示在前面,不然就不会重复了)。
想了一下，既然是 fragment 应该不会不会像普通视图一样在数据更新后直接销毁掉，于是查看了 FragmentPagerAdapter 的源码。PagerAdapter 通过 `instantiateItem(ViewGroup container, int position)` 来获得一个视图, FragmentPagerAdapter 中的实现如下:
   
```java
@Override
public Object instantiateItem(ViewGroup container, int position) {
    if (mCurTransaction == null) {
        mCurTransaction = mFragmentManager.beginTransaction();
    }

    final long itemId = getItemId(position);

    // Do we already have this fragment?
    String name = makeFragmentName(container.getId(), itemId);
    Fragment fragment = mFragmentManager.findFragmentByTag(name);
    if (fragment != null) {
        if (DEBUG) Log.v(TAG, "Attaching item #" + itemId + ": f=" + fragment);
        mCurTransaction.attach(fragment);
    } else {
        fragment = getItem(position);
        if (DEBUG) Log.v(TAG, "Adding item #" + itemId + ": f=" + fragment);
        mCurTransaction.add(container.getId(), fragment,
                makeFragmentName(container.getId(), itemId));
    }
    if (fragment != mCurrentPrimaryItem) {
        fragment.setMenuVisibility(false);
        fragment.setUserVisibleHint(false);
    }

    return fragment;
}
```

从中可以看出当在实例化 position 位置的 fragment 时，首先从 FragmentManager 查找在该 position 位置是否已经创建了 fragment，如果存在直接使用这个 fragment，从而达到复用.
假如现在有了三个城市A、B、C，position 分别为0、1、2.然后添加了一个城市 Z，Z 的 position为 0，C 为 3。FragmentManager 里已经存在了 position 为0,1,2的 fragment，所以前三个视图没有改变，但现在需要的 fragment 的数目变了，增加1。会创建 position 为3的 fragment，而此时是城市C，所以这样就会导致显示两次C，而新增的Z城市没有显示。
想起之前写 FragmentPagerAdapter 还会手动在 getItem() 方法里判断 Fragment 是否已经创建，完全没必要嘛, FragmentPagerAdapter 已经为我们做了这点-.-
解决的办法:
在通知数据更新之前从 FragmentManager 里移除所有 Fragment：

```java
private void removeALlFragments(){
    FragmentTransaction transaction = fm.beginTransaction();
    for (int i=0; i<fragments.size(); i++){
        Fragment fg = fragments.get(i);
        transaction.remove(fg);
    }
    transaction.commit();
    fragments.clear();
}
```
这样有些暴力了，毕竟创建一个 fragment 需要消耗较大的资源.决定重写 instantiateItem() 方法,当数据更新时，复用已有的 fragment，更新里面的数据。

  - 重写 `instantiateItem()` 方法:
	```java
	    @Override
	    public Object instantiateItem(ViewGroup container, int position) {
	        WeatherFragment fragment = (WeatherFragment) super.instantiateItem(container, position);
	        Weather weather = weathers.get(position);
	        fragment.setWeatherData(weather);
	        return fragment;
	    }
	```
  - 在 WeatherFragment 里添加下面的方法:
	```java
	public void setWeatherData(final Weather weather){
	    this.weather = weather;
	    if(rootView==null){
	        return ;
	    }
	    //...
	}
	```