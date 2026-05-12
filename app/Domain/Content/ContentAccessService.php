<?php

namespace App\Domain\Content;

use App\Models\Content;
use App\Models\User;

/**
 * 内容访问授权服务。
 *
 * 集中处理 7 种可见性 (public/logged_in/vip/paid/commented/password/points) + 后台角色 + 作者本人。
 *
 * 返回 Decision 值对象：
 *   ->allowed   是否可见
 *   ->reason    被拒绝原因
 *   ->lock      锁定类型，前台用于决定弹什么解锁按钮（login/vip/buy/password/comment/points）
 *   ->preview   是否仅显示摘要 (true 时正文应裁剪)
 */
class ContentAccessService
{
    public function check(Content $content, ?User $user, ?string $providedPassword = null): AccessDecision
    {
        // 后台角色或作者本人：直接通过
        if ($user) {
            if ($user->isStaff()) {
                return AccessDecision::allow();
            }
            if ($user->id === $content->author_id) {
                return AccessDecision::allow();
            }
        }

        // 草稿/待审/拒绝/回收 不向公众展示
        if (! in_array($content->status, [Content::STATUS_PUBLISHED, Content::STATUS_PRIVATE, Content::STATUS_SCHEDULED], true)) {
            return AccessDecision::deny('not_published');
        }

        // 私密只允许作者和管理员（已在上方放行）
        if ($content->status === Content::STATUS_PRIVATE) {
            return AccessDecision::deny('private');
        }

        // 定时发布
        if ($content->status === Content::STATUS_SCHEDULED
            && $content->published_at
            && $content->published_at->isFuture()) {
            return AccessDecision::deny('scheduled');
        }

        return match ($content->visibility) {
            Content::VISIBILITY_PUBLIC    => AccessDecision::allow(),
            Content::VISIBILITY_LOGGED_IN => $this->checkLoggedIn($user),
            Content::VISIBILITY_VIP       => $this->checkVip($content, $user),
            Content::VISIBILITY_PAID      => $this->checkPaid($content, $user),
            Content::VISIBILITY_COMMENTED => $this->checkCommented($content, $user),
            Content::VISIBILITY_PASSWORD  => $this->checkPassword($content, $providedPassword),
            Content::VISIBILITY_POINTS    => $this->checkPaid($content, $user),
            default                       => AccessDecision::allow(),
        };
    }

    protected function checkLoggedIn(?User $user): AccessDecision
    {
        return $user
            ? AccessDecision::allow()
            : AccessDecision::deny('login_required', lock: 'login', preview: true);
    }

    protected function checkVip(Content $content, ?User $user): AccessDecision
    {
        if (! $user) {
            return AccessDecision::deny('login_required', lock: 'login', preview: true);
        }
        if (! $user->isVip()) {
            return AccessDecision::deny('vip_required', lock: 'vip', preview: true);
        }
        // TODO Sprint 3: 校验 required_vip_level_id
        return AccessDecision::allow();
    }

    protected function checkPaid(Content $content, ?User $user): AccessDecision
    {
        if (! $user) {
            return AccessDecision::deny('login_required', lock: 'login', preview: true);
        }

        $purchased = $content->purchases()->where('user_id', $user->id)->exists();
        if ($purchased) {
            return AccessDecision::allow();
        }

        // VIP 可绕过付费（Sprint 3 完整接入：可配置全免/折扣）
        if ($user->isVip()) {
            return AccessDecision::allow();
        }

        return AccessDecision::deny('purchase_required', lock: 'buy', preview: true);
    }

    protected function checkCommented(Content $content, ?User $user): AccessDecision
    {
        if (! $user) {
            return AccessDecision::deny('login_required', lock: 'login', preview: true);
        }
        $commented = $content->comments()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->exists();
        return $commented
            ? AccessDecision::allow()
            : AccessDecision::deny('comment_required', lock: 'comment', preview: true);
    }

    protected function checkPassword(Content $content, ?string $providedPassword): AccessDecision
    {
        if ($providedPassword && $providedPassword === $content->access_password) {
            return AccessDecision::allow();
        }
        return AccessDecision::deny('password_required', lock: 'password', preview: true);
    }
}
