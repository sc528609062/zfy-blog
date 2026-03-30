<template>
  <zfy-blog-frame active-nav="/blog/about" :plugin-context="{ techStack }">
    <div class="zfy-card zfy-content-card">
      <h2 class="zfy-page-title">关于本站</h2>
      <div class="zfy-post-summary">
        <p>
          zfy-blog 采用前后端分离架构，前台主题系统参考 WordPress 主题机制，支持可视化切换主题、动态样式变量与插件扩展。
        </p>
        <p>
          当前实现包含三层能力：主题注册器（主题定义 + 自定义字段）、插件注册器（钩子 + 侧边栏组件）、配置中心（持久化到 blog_config）。
        </p>
      </div>

      <div class="zfy-card zfy-widget-card" style="margin-top:14px;">
        <h3 class="zfy-widget-title">技术栈</h3>
        <ul class="zfy-simple-list">
          <li v-for="item in techStack" :key="item.name">
            <span>{{ item.name }}</span>
            <span>{{ item.version }}</span>
          </li>
        </ul>
      </div>

      <div class="zfy-card zfy-widget-card" style="margin-top:14px;">
        <h3 class="zfy-widget-title">核心能力</h3>
        <ul class="zfy-simple-list">
          <li><span>主题管理</span><span>动态变量 / 自定义字段</span></li>
          <li><span>插件管理</span><span>启停控制 / 参数配置 / Hook</span></li>
          <li><span>内容系统</span><span>文章 / 分类 / 标签 / 归档</span></li>
          <li><span>后台管理</span><span>RuoYi 权限与菜单体系</span></li>
        </ul>
      </div>
    </div>

    <template #sidebar>
      <div class="zfy-card zfy-widget-card">
        <h3 class="zfy-widget-title">联系信息</h3>
        <ul class="zfy-simple-list">
          <li><span>邮箱</span><span>admin@example.com</span></li>
          <li><span>QQ</span><span>123456789</span></li>
          <li><span>地区</span><span>中国</span></li>
        </ul>
      </div>
    </template>
  </zfy-blog-frame>
</template>

<script>
import ZfyBlogFrame from './components/ZfyBlogFrame'

export default {
  name: 'BlogAbout',
  components: {
    ZfyBlogFrame
  },
  data() {
    return {
      techStack: [
        { name: '前端', version: 'Vue 2 + Element UI' },
        { name: '后端', version: 'Spring Boot + MyBatis' },
        { name: '数据库', version: 'MySQL' },
        { name: '权限模型', version: 'RuoYi RBAC' }
      ]
    }
  }
}
</script>
