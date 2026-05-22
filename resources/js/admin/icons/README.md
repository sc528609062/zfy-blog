# ZfyIcon

后台本地图标库，优先用于需要长期复用的图标。

## 使用方式

```vue
<script setup lang="ts">
import ZfyIcon from '../icons/ZfyIcon.vue';
</script>

<template>
    <ZfyIcon name="component" />
</template>
```

## 从 iconfont.cn 补充图标

1. 在 iconfont.cn 项目中下载 SVG。
2. 打开 SVG，复制 `viewBox` 和 `path d`。
3. 添加到 `zfyIconSet.ts`。
4. 业务组件只传 `name`，不要直接依赖在线脚本。
