import { defineStore } from 'pinia';

export const adminColors = ['#5D87FF', '#1D84FF', '#13A88A', '#E99520', '#E75B78'];
function preference(key: string, fallback: string): string {
    try { return localStorage.getItem(key) || fallback; } catch { return fallback; }
}
function persist(key: string, value: string) {
    try { localStorage.setItem(key, value); } catch { /* Browser storage may be disabled. */ }
}

export const useAdminStore = defineStore('admin', {
    state: () => ({
        dark: preference('zfy-admin-dark', 'false') === 'true',
        collapsed: preference('zfy-admin-collapsed', 'false') === 'true',
        compact: preference('zfy-admin-compact', 'false') === 'true',
        primary: adminColors.includes(preference('zfy-admin-primary', '')) ? preference('zfy-admin-primary', '') : adminColors[0],
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
        closeOthers(path: string) { this.tabs = this.tabs.filter(tab => tab.path === path); },
        toggleSidebar() {
            this.collapsed = !this.collapsed;
            persist('zfy-admin-collapsed', String(this.collapsed));
        },
        setCompact(value: boolean) { this.compact = value; persist('zfy-admin-compact', String(value)); },
        setPrimary(value: string) {
            if (!adminColors.includes(value)) return;
            this.primary = value;
            persist('zfy-admin-primary', value);
            this.applyAppearance();
        },
        applyAppearance() {
            document.documentElement.classList.toggle('dark', this.dark);
            document.documentElement.style.setProperty('--zfy-admin-primary', this.primary);
        },
        toggleDark() {
            this.dark = !this.dark;
            persist('zfy-admin-dark', String(this.dark));
            this.applyAppearance();
        },
    },
});
