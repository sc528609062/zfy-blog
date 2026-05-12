import { createApp } from 'vue';
import ElementPlus from 'element-plus';
import 'element-plus/dist/index.css';
import '../css/install.css';
import InstallApp from './install/InstallApp.vue';

const payloadElement = document.getElementById('install-payload');
const payload = payloadElement ? JSON.parse(payloadElement.textContent || '{}') : {};

createApp(InstallApp, { payload }).use(ElementPlus).mount('#install-app');
