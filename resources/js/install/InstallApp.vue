<script setup lang="ts">
import { computed, reactive, shallowRef } from 'vue';
import { Check, CircleCheck, DataLine, Setting, User } from '@element-plus/icons-vue';
import InstallCheckList from './components/InstallCheckList.vue';
import InstallDatabaseForm from './components/InstallDatabaseForm.vue';
import InstallSiteForm from './components/InstallSiteForm.vue';

interface InstallCheck {
    key: string;
    label: string;
    ok: boolean;
    detail: string;
}

interface InstallPayload {
    checks: InstallCheck[];
    defaults: {
        db: Record<string, string | number>;
        site: Record<string, string>;
        admin: Record<string, string>;
    };
    routes: {
        install: string;
        login: string;
        admin: string;
    };
}

const props = defineProps<{
    payload: InstallPayload;
}>();

const step = shallowRef(0);
const installing = shallowRef(false);
const installed = shallowRef(false);
const errorMessage = shallowRef('');

const form = reactive({
    db: {
        host: props.payload.defaults.db.host || '127.0.0.1',
        port: Number(props.payload.defaults.db.port || 3306),
        database: props.payload.defaults.db.database || 'zfy_blog',
        username: props.payload.defaults.db.username || 'zfy_blog',
        password: props.payload.defaults.db.password || 'zfy_blog',
    },
    site: {
        name: props.payload.defaults.site.name || 'zfy-blog',
        url: props.payload.defaults.site.url || window.location.origin,
    },
    admin: {
        name: props.payload.defaults.admin.name || '站长',
        username: props.payload.defaults.admin.username || 'admin',
        email: props.payload.defaults.admin.email || 'admin@zfy-blog.test',
        password: '',
        password_confirmation: '',
    },
});

const checksOk = computed(() => props.payload.checks.every((item) => item.ok));
function hasText(value: unknown) {
    return String(value ?? '').trim().length > 0;
}

const canSubmit = computed(() =>
    validationErrors.value.length === 0,
);

const validationErrors = computed(() => {
    const errors: string[] = [];

    if (!hasText(form.db.host)) {
        errors.push('请填写数据库地址。');
    }

    if (Number(form.db.port) <= 0) {
        errors.push('请填写有效的数据库端口。');
    }

    if (!hasText(form.db.database)) {
        errors.push('请填写数据库名。');
    }

    if (!hasText(form.db.username)) {
        errors.push('请填写数据库用户名。');
    }

    if (!hasText(form.site.name)) {
        errors.push('请填写站点名称。');
    }

    if (!hasText(form.admin.name)) {
        errors.push('请填写管理员昵称。');
    }

    if (!hasText(form.admin.username)) {
        errors.push('请填写管理员用户名。');
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(form.admin.email))) {
        errors.push('请填写有效的管理员邮箱。');
    }

    if (form.admin.password.length < 8) {
        errors.push('登录密码至少需要 8 位。');
    }

    if (form.admin.password !== form.admin.password_confirmation) {
        errors.push('两次输入的密码不一致。');
    }

    return errors;
});

const stepItems = [
    { title: '环境检查', icon: Check },
    { title: '数据库', icon: DataLine },
    { title: '站点账号', icon: User },
    { title: '完成', icon: CircleCheck },
];

function nextStep() {
    if (step.value < 2) {
        step.value += 1;
    }
}

function previousStep() {
    if (step.value > 0 && !installing.value) {
        step.value -= 1;
    }
}

async function submitInstall() {
    if (installing.value) {
        return;
    }

    form.site.url = window.location.origin;

    if (!canSubmit.value) {
        errorMessage.value = validationErrors.value[0] || '请检查安装信息。';
        return;
    }

    installing.value = true;
    errorMessage.value = '';

    try {
        const response = await fetch(props.payload.routes.install, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(form),
        });
        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || '安装失败，请检查数据库配置。');
        }

        installed.value = true;
        step.value = 3;
    } catch (error) {
        errorMessage.value = error instanceof Error ? error.message : '安装失败，请稍后重试。';
    } finally {
        installing.value = false;
    }
}

function goLogin() {
    window.location.href = props.payload.routes.login;
}
</script>

<template>
    <main class="zfy-install-shell">
        <section class="zfy-install-hero">
            <div class="zfy-install-brand">
                <span>Z</span>
                <div>
                    <strong>zfy-blog</strong>
                    <small>安装向导</small>
                </div>
            </div>
            <h1>配置你的内容与商城系统</h1>
            <p>填写 MySQL、站点和管理员信息后，安装器会清空当前数据表并写入全新的核心结构。</p>
        </section>

        <section class="zfy-install-panel">
            <el-steps :active="step" finish-status="success" class="zfy-install-steps">
                <el-step
                    v-for="item in stepItems"
                    :key="item.title"
                    :icon="item.icon"
                    :title="item.title"
                />
            </el-steps>

            <div class="zfy-install-content">
                <InstallCheckList v-if="step === 0" :checks="payload.checks" />
                <InstallDatabaseForm v-else-if="step === 1" v-model:db="form.db" />
                <InstallSiteForm v-else-if="step === 2" v-model:admin="form.admin" v-model:site="form.site" />

                <div v-else class="zfy-install-done">
                    <el-icon><CircleCheck /></el-icon>
                    <h2>安装完成</h2>
                    <p>系统已经写入安装锁，网页安装入口已关闭。后续如需重装，请通过命令行清除安装锁。</p>
                    <el-button type="primary" @click="goLogin">进入登录页</el-button>
                </div>
            </div>

            <el-alert
                v-if="errorMessage"
                class="zfy-install-alert"
                :closable="false"
                :title="errorMessage"
                type="error"
            />

            <div v-if="step < 3" class="zfy-install-actions">
                <el-button :disabled="step === 0 || installing" @click="previousStep">上一步</el-button>
                <el-button
                    v-if="step < 2"
                    :disabled="step === 0 && !checksOk"
                    type="primary"
                    @click="nextStep"
                >
                    下一步
                </el-button>
                <el-button
                    v-else
                    :disabled="installing"
                    :icon="Setting"
                    type="primary"
                    @click="submitInstall"
                >
                    {{ installing ? '正在安装' : '开始安装' }}
                </el-button>
            </div>
        </section>
    </main>
</template>
