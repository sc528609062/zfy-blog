<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * 后台 4 级角色 + 权限点。
     */
    public function run(): void
    {
        // 清缓存避免冲突
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // 内容
            'content.view', 'content.create', 'content.update', 'content.delete',
            'content.publish', 'content.review', 'content.trash',
            // 分类标签
            'taxonomy.manage',
            // 评论
            'comment.moderate', 'comment.delete',
            // 媒体
            'media.upload', 'media.delete', 'media.manage_folder',
            // 用户
            'user.view', 'user.update', 'user.ban', 'user.assign_role',
            // 角色
            'role.manage',
            // 商业化
            'order.view', 'order.refund',
            'vip.manage',
            'wallet.adjust',
            'points.adjust',
            'withdrawal.review',
            'gateway.configure',
            // 外观
            'theme.manage', 'menu.manage', 'widget.manage', 'page-builder.manage',
            // 扩展
            'plugin.manage',
            // 系统
            'setting.manage', 'audit.view', 'system.upgrade',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // 角色
        $superAdmin = Role::firstOrCreate(['name' => 'SUPER_ADMIN', 'guard_name' => 'web']);
        $admin      = Role::firstOrCreate(['name' => 'ADMIN', 'guard_name' => 'web']);
        $editor     = Role::firstOrCreate(['name' => 'EDITOR', 'guard_name' => 'web']);
        $user       = Role::firstOrCreate(['name' => 'USER', 'guard_name' => 'web']);

        // 超级管理员：全部
        $superAdmin->syncPermissions(Permission::all());

        // 管理员：除 system.upgrade、gateway.configure 外
        $admin->syncPermissions(
            Permission::whereNotIn('name', ['system.upgrade', 'gateway.configure'])->get()
        );

        // 编辑：内容/媒体/评论/分类
        $editor->syncPermissions(Permission::whereIn('name', [
            'content.view', 'content.create', 'content.update',
            'content.publish', 'content.review',
            'taxonomy.manage',
            'comment.moderate',
            'media.upload',
        ])->get());

        // 普通用户：无后台权限
        $user->syncPermissions([]);
    }
}
