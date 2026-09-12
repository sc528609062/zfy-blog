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
    resource?: string;
    group?: string;
    permission?: string | null;
}

export interface AdminBreadcrumbItem {
    label: string;
    section?: string;
    current?: boolean;
}

export function findAdminMenuItem(menus: AdminMenuGroup[], section: string): AdminMenuItem | undefined {
    return menus.flatMap((group) => group.items).find((item) => item.key === section);
}

export function buildAdminBreadcrumbs(
    menus: AdminMenuGroup[],
    section: string,
    currentPage: AdminPageDefinition,
): AdminBreadcrumbItem[] {
    const group = menus.find((menuGroup) =>
        menuGroup.key === currentPage.group || menuGroup.items.some((item) => item.key === section),
    );
    const item = group?.items.find((menuItem) => menuItem.key === section);

    return [
        ...(group ? [{ label: group.label }] : []),
        {
            label: item?.label || currentPage.label || '后台',
            current: true,
        },
    ];
}
