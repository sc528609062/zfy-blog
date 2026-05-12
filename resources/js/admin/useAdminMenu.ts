export interface AdminMenuItem {
    key: string;
    label: string;
    description: string;
    kind?: string;
    status?: string;
}

export interface AdminMenuGroup {
    key: string;
    label: string;
    icon: string;
    items: AdminMenuItem[];
}

export interface AdminPageDefinition extends AdminMenuItem {
    group?: string;
    permission?: string | null;
}

export function findAdminMenuItem(menus: AdminMenuGroup[], section: string): AdminMenuItem | undefined {
    return menus.flatMap((group) => group.items).find((item) => item.key === section);
}
