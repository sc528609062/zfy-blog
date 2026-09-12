import { defineStore } from 'pinia';

export const useAdminStore = defineStore('admin', {
    state: () => ({
        dark: localStorage.getItem('zfy-admin-dark') === 'true',
        tabs: [] as { path: string; title: string }[],
    }),
    actions: {
        visit(path: string, title: string) {
            const tab = this.tabs.find(item => item.path === path);
            if (tab) tab.title = title;
            else this.tabs.push({ path, title });
            if (this.tabs.length > 15) this.tabs.splice(0, this.tabs.length - 15);
        },
        close(path: string) { this.tabs = this.tabs.filter(tab => tab.path !== path); },
        toggleDark() {
            this.dark = !this.dark;
            localStorage.setItem('zfy-admin-dark', String(this.dark));
            document.documentElement.classList.toggle('dark', this.dark);
        },
    },
});
