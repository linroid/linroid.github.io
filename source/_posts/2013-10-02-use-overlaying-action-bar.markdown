---
layout: post
title: "[Android学习笔记] Overlaying Action Bar"
date: 2013-10-01 23:16
comments: true
tags: Android
categories: 学习笔记
---
默认情况下，`Action Bar` 出现在窗口顶部。有时为了让屏幕显示更多的内容，需要隐藏 `Action Bar`（如向下滑动列表时隐藏，向上滑动列表时显示）。  

如果直接调用 `Action Bar`的`hide()`、收 `show()` 方法，会让`activity`根据新的区域大小重新计算并重新绘制布局。  

为了避免这种情况，可以使用 `Overlaying Action Bar`，即整个屏幕都显示内容，而 `Action Bar` 覆盖这上面，这样隐藏/显示Action Bar的时候就不会重新计算布局大小并重新绘制布局。  
<!--more-->
 
开启Action Bar的Overlaying模式需要在主题中设置 `android:windowActionBarOverlaying` 属性为`true`,为了兼容2.1-3.1版本，设置`windowActionBarOverLaying` 属性为 `true`  
```xml
<item name="android:windowActionBarOverlay">true</item>
 
<!--For Support Library Compatibility-->
<item name="windowActionBarOverlay">true</item>
```
使用Overlaying Action Bar会挡住内容区域一种方法是将Action Bar设为透明，另外一种方法是当显示ActrionBar时将内容区域的MarginTop/PaddingTop设置为Action Bar 的高度,当隐藏ActionBar时将内容区域的MarginTop/PaddingTop设置为0;  
```java
    ActionBar mActionBar;
    ToggleButton toggleButton;
    View rootView;
    @Override
    public void onCreate(Bundle savedInstanceState){
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_setting);
        mActionBar = getSupportActionBar();
        mActionBar.setDisplayHomeAsUpEnabled(true);
        findViews();
        setListeners();
    }

    private void findViews(){
        toggleButton = (ToggleButton)findViewById(R.id.toggleActionBar);
        rootView = findViewById(R.id.setting_root);
    }

    private void setListeners(){
        toggleButton.setOnCheckedChangeListener(new CompoundButton.OnCheckedChangeListener() {
            @Override
            public void onCheckedChanged(CompoundButton buttonView, boolean isChecked) {
                if(isChecked){
                    mActionBar.show();
                    rootView.setPadding(0, mActionBar.getHeight(), 0, 0);
                }else{
                    mActionBar.hide();
                    rootView.setPadding(0, 0, 0, 0);
                }
            }
        });
    }
```
![Action Bar is showing](/images/posts/overlaying-action-bar/normal.png)
![Action Bar is hiding](/images/posts/overlaying-action-bar/overlaying.png)
