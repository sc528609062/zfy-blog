import { createRouter, createWebHistory } from 'vue-router';

export const adminRouter = createRouter({
    history: createWebHistory(),
    routes: [{ path: '/admin/:pathMatch(.*)*', component: { render: () => null } }],
});
