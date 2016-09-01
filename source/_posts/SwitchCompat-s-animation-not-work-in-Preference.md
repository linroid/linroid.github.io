title: "设置界面中 SwitchCompat 无动画效果"
date: 2016-02-26 13:48:27
tags:
categories: 遇过的坑
---
## 问题分析
 Support library 的 `SwitchCompat.java` :
 ```java
 @Override
public void setChecked(boolean checked) {
    super.setChecked(checked);

    // Calling the super method may result in setChecked() getting called
    // recursively with a different value, so load the REAL value...
    checked = isChecked();

    if (getWindowToken() != null && ViewCompat.isLaidOut(this) && isShown()) {
        animateThumbToCheckedState(checked);
    } else {
        // Immediately move the thumb to the new position.
        cancelPositionAnimator();
        setThumbPosition(checked ? 1 : 0);
    }
}
 ```
<!--more-->
 Framework 的 `Switch.java`
 ```java
     @Override
    public void setChecked(boolean checked) {
        super.setChecked(checked);

        // Calling the super method may result in setChecked() getting called
        // recursively with a different value, so load the REAL value...
        checked = isChecked();

        if (isAttachedToWindow() && isLaidOut()) {
            animateThumbToCheckedState(checked);
        } else {
            // Immediately move the thumb to the new position.
            cancelPositionAnimator();
            setThumbPosition(checked ? 1 : 0);
        }
    }
 ```

 可以看到 SwitchCompat 多了一个 `isShown()` 的判定条件

 ```java
     /**
     * Returns the visibility of this view and all of its ancestors
     *
     * @return True if this view and all of its ancestors are {@link #VISIBLE}
     */
    public boolean isShown() {
        View current = this;
        //noinspection ConstantConditions
        do {
            if ((current.mViewFlags & VISIBILITY_MASK) != VISIBLE) {
                return false;
            }
            ViewParent parent = current.mParent;
            if (parent == null) {
                return false; // We are not attached to the view root
            }
            if (!(parent instanceof View)) {
                return true;
            }
            current = (View) parent;
        } while (current != null);

        return false;
    }
```

`isShown()` 会向上递归，如果 parent 为 null 就返回 false。
而 Preference 中，如果点击了，就会调用 `notifyDataSetChanged()` 刷新整个 RecyclerView，SwitchCompat 的 `setChecked()` 是在 `onBindViewHolder` 时调用的，这个时候还没有添加到 parent 中，所以 `isShown()` 就会 return false，从而动画不执行。

## 解决方法
  因为 Preference 的特殊性，所有状态改变都通过 `notifyDataSetChanged()` 来生效，所以这里通过以下 hack 的方式来解决，其他地方使用到 `SwitchCompat`立即 setChecked() 就不会出现这个问题

  创建 `SwitchCompatFixed`继承 SwitchCompat 重写`isShown()`方法
  ```java
  	@Override
	public boolean isShown() {
		ViewParent parent = getParent();
		if (parent != null && parent instanceof ViewGroup) {
			ViewGroup widgetFrame = (ViewGroup) parent;
			if (widgetFrame.getId() == android.R.id.widget_frame) {
				return true;
			}
		}
		return super.isShown();
	}
  ```
   主题中修改 SwitchPreferenceCompat 的样式
   ```xml
   <item name="switchPreferenceCompatStyle">@style/Preference.SwitchPreferenceCompatFixed</item>
   ```
   定义样式
```xml   
	<style name="Preference.SwitchPreferenceCompatFixed" parent="Preference.SwitchPreferenceCompat">
		<item name="android:layout">@layout/preference_material</item>
		<item name="android:widgetLayout">@layout/preference_widget_switch_fixed</item>
	</style>
```
创建布局文件`preference_widget_switch_fixed.xml`
```xml
	<android.support.v7.widget.SwitchCompat xmlns:android="http://schemas.android.com/apk/res/android"
	    android:id="@+id/switchWidget"
	    android:layout_width="wrap_content"
	    android:layout_height="wrap_content"
	    android:focusable="false"
	    android:clickable="false"
	    android:background="@null" />
 ```
