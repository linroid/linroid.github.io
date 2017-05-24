title: ArrayList 导致的内存泄露
date: 2017-05-24 14:25:54
tags:
 - 内存泄露
---

前段时间在排查一处内存泄露时，发现是注册的一个监听器导致的。但检查了这个监听器在该取消注册的地方的确取消注册了，那内存中为什么还有它的引用呢？

<!--more-->

分析内存发现是 ArrayList 对它进行了持有，但的确调用了 remove 来移除这个监听器呀。打断点发现注册监听器的方法被调用了两次，即调用了两次 ArrayList 的 add 方法。

```java
public boolean add(E e) {
    ensureCapacityInternal(size + 1);  // Increments modCount!!
    elementData[size++] = e;
    return true;
}
```

ArrayList 的 add 方法并没有进行去重操作，所以两次 add 都会成功。但 remove 方法却只调用了一次，来看看 remove 的源码：

```java
public boolean remove(Object o) {
    if (o == null) {
        for (int index = 0; index < size; index++)
            if (elementData[index] == null) {
                fastRemove(index);
                return true;
            }
    } else {
        for (int index = 0; index < size; index++)
            if (o.equals(elementData[index])) {
                fastRemove(index);
                return true;
            }
    }
    return false;
}
```

可以看出，remove 方法会遍历数组中的元素，一旦找到这个监听器，就会 return，即一次 remove 只会移除一个引用。但我们调用了两次 add 方法，所以 ArrayList 依然持有 这个监听器的引用。

# 解决

  - 在 add 的时候判断下 ArrayList 中是否已经存在这个对象，如果有则忽略这次操作
  - 使用 HashSet 代替 ArrayList，HashSet 在添加的时候会进行去重
 
# 反思

  其实使用 ArrayList 来作为存放监听器的集合是很常见的，比如在 `RecyclerView`中 `mItemDecorations`、`mOnItemTouchListeners`、`mOnChildAttachStateListeners`、
  `mPendingAccessibilityImportanceChange`、`mScrollListeners` 等属性都是使用 ArrayList 来保存的，并且没有做去重处理。如果这个 ArrayList 放在单例中并且只 remove 了一次，重复的添加就会导致内存泄露；而在使用时的遍历又会因重复调用导致性能或其他问题。

  所以我们平时在遇到往 ArrayList 中添加对象时一定要注意这点，当然我个人更建议使用 HashSet 来保存，这样可以更好地避免团队里其他成员在使用时出现问题。