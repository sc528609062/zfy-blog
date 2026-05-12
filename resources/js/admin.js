import { createApp } from 'vue';
import ElementPlus from 'element-plus';
import 'element-plus/dist/index.css';
import '../css/admin.css';
import AdminApp from './admin/AdminApp.vue';

const payloadElement = document.getElementById('admin-payload');
const payload = payloadElement ? JSON.parse(payloadElement.textContent || '{}') : {};

createApp(AdminApp, { payload }).use(ElementPlus).mount('#admin-app');
