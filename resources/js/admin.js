import { createApp } from 'vue';
import ElementPlus from 'element-plus';
import zhCn from 'element-plus/es/locale/lang/zh-cn';
import 'element-plus/dist/index.css';
import '../css/admin.css';
import AdminApp from './admin/AdminApp.vue';
import { createPinia } from 'pinia';
import { adminRouter } from './admin/router';
import 'element-plus/theme-chalk/dark/css-vars.css';
import '../css/admin-art.css';

const payloadElement = document.getElementById('admin-payload');
const payload = payloadElement ? JSON.parse(payloadElement.textContent || '{}') : {};

createApp(AdminApp, { payload }).use(createPinia()).use(adminRouter).use(ElementPlus, { locale: zhCn }).mount('#admin-app');
