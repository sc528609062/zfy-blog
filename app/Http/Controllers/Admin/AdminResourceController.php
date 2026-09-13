<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DeliverSiteNotification;
use App\Models\AuthorSettlementRule;
use App\Models\CardCode;
use App\Models\Comment;
use App\Models\Content;
use App\Models\Coupon;
use App\Models\Link;
use App\Models\LinkSubmission;
use App\Models\Menu;
use App\Models\PrivateMessage;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\ShippingTemplate;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserRequest;
use App\Models\VipLevel;
use App\Models\Widget;
use App\Services\AdminResourceRegistry;
use App\Services\PrivateMessageService;
use App\Services\ShipmentService;
use App\Services\SiteSettings;
use App\Support\AdminPagination;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AdminResourceController extends Controller
{
    public function __construct(private readonly AdminResourceRegistry $registry) {}

    public function index(Request $request, string $resource)
    {
        $definition = $this->authorizeResource($request, $resource);
        $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $query = $definition['model']::query();
        if (isset($definition['types'])) {
            $query->whereIn('type', $definition['types']);
        }
        if ($request->filled('q')) {
            $query->where($definition['search'], 'like', '%'.mb_substr($request->string('q')->toString(), 0, 120).'%');
        }
        $rows = AdminPagination::paginate($query->latest('id'), $request)->through(fn ($row) => $this->serialize($row, $definition));

        return response()->json(['data' => $rows, 'schema' => $this->registry->schema($resource)]);
    }

    public function store(Request $request, string $resource)
    {
        $definition = $this->authorizeResource($request, $resource);
        abort_unless($definition['create'] ?? true, 405);
        $model = new $definition['model'];
        $this->persist($request, $resource, $definition, $model);

        return response()->json(['data' => $this->serialize($model->refresh(), $definition), 'message' => '已创建'], 201);
    }

    public function update(Request $request, string $resource, int $id)
    {
        $definition = $this->authorizeResource($request, $resource);
        $model = $definition['model']::findOrFail($id);
        abort_if(isset($definition['types']) && ! in_array($model->type, $definition['types'], true), 404);
        $this->persist($request, $resource, $definition, $model);

        return response()->json(['data' => $this->serialize($model->refresh(), $definition), 'message' => '已保存']);
    }

    public function destroy(Request $request, string $resource, int $id)
    {
        $definition = $this->authorizeResource($request, $resource);
        abort_unless($definition['delete'] ?? true, 405);
        DB::transaction(function () use ($definition, $id) {
            $model = $definition['model']::lockForUpdate()->findOrFail($id);
            if ($model instanceof VipLevel && DB::table('user_vips')->where('vip_level_id', $id)->exists()) {
                throw ValidationException::withMessages(['resource' => '该等级仍有会员，不能删除。']);
            }
            if ($model instanceof CardCode && $model->status !== 'available') {
                throw ValidationException::withMessages(['resource' => '已交付卡密不能删除。']);
            }
            $model->delete();
            if ($model instanceof Comment) {
                $this->recountComments($model->content_id);
            }
        });

        return response()->json(['message' => '已删除']);
    }

    public function saveSettings(Request $request, string $group, SiteSettings $settings)
    {
        abort_unless($request->user()?->can($group === 'links' ? 'manage links' : 'manage system'), 403);
        $data = $request->validate(['values' => ['required', 'array']]);
        $settings->save($group, $data['values']);

        return response()->json(['message' => '设置已保存']);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:2000'],
            'password' => ['nullable', 'confirmed', 'string', 'min:12', 'max:128'],
            'current_password' => [Rule::requiredIf($request->filled('password') || $request->input('email') !== $user->email), 'nullable', $request->is('api/*') ? 'current_password:sanctum' : 'current_password'],
        ]);
        unset($data['current_password']);
        if (! filled($data['password'] ?? null)) {
            unset($data['password']);
        }
        if ($data['email'] !== $user->email) {
            $user->email_verified_at = null;
        }
        $user->fill($data)->save();

        if (! $request->expectsJson()) {
            return back()->with('status', '个人资料已保存');
        }

        return response()->json(['code' => 0, 'message' => '个人资料已保存', 'data' => null]);
    }

    private function authorizeResource(Request $request, string $resource): array
    {
        $definition = $this->registry->get($resource);
        abort_unless($request->user()?->can($definition['permission']), 403);

        return $definition;
    }

    private function persist(Request $request, string $resource, array $definition, Model $model): void
    {
        $rules = [];
        $defaults = [];
        foreach ($definition['fields'] as $field) {
            if (array_key_exists('default', $field)) {
                $defaults[$field['key']] = $field['default'];
            }
            $fieldRules = $field['rules'] ?? match ($field['type'] ?? 'text') {
                'select' => ['required', Rule::in(array_keys($field['options'] ?? []))],
                'boolean' => ['required', 'boolean'],
                default => ['nullable', 'string', 'max:255'],
            };
            if (isset($field['source'])) {
                $sourceModel = $this->registry->get($field['source'])['model'];
                $fieldRules[] = Rule::exists((new $sourceModel)->getTable(), 'id');
            }
            if ($field['unique'] ?? false) {
                $fieldRules[] = Rule::unique($model->getTable(), $field['key'])->ignore($model->id);
            }
            $rules[$field['key']] = $fieldRules;
        }
        $request->mergeIfMissing($defaults);
        if ($model instanceof Topic) {
            $rules['content_ids.*'] = ['integer', 'distinct', 'exists:contents,id'];
        }
        if ($model instanceof Role) {
            abort_unless($request->user()->hasRole('SUPER_ADMIN'), 403);
            abort_if($model->name === 'SUPER_ADMIN', 422, '超级管理员权限由核心维护。');
            $rules['permissions.*'] = ['string', 'distinct', Rule::exists('permissions', 'name')->where('guard_name', 'web')];
        }
        if ($model instanceof Product) {
            $rules += ['variants.*.id' => ['nullable', 'integer'], 'variants.*.sku' => ['required', 'string', 'max:100', 'distinct'], 'variants.*.title' => ['required', 'string', 'max:180'], 'variants.*.price' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'], 'variants.*.stock' => ['required', 'integer', 'min:0', 'max:100000000'], 'variants.*.status' => ['required', Rule::in(['active', 'inactive'])]];
        }
        if ($model instanceof User && ! $model->exists) {
            $rules['password'][0] = 'required';
        }
        if ($model instanceof Menu) {
            $rules += [
                'items.*.depth' => ['sometimes', 'integer', 'between:0,3'],
                'items.*.visibility' => ['sometimes', Rule::in(['public', 'guest', 'member', 'USER', 'EDITOR', 'ADMIN', 'SUPER_ADMIN'])],
                'items.*.target' => ['sometimes', Rule::in(['_self', '_blank'])],
                'items.*.title' => ['required', 'string', 'max:120'],
                'items.*.url' => ['required', 'string', 'max:2048', function ($attribute, $value, $fail) {
                    if (! preg_match('~^(?:/(?![/\\\\])[^\x00-\x20\\\\]*|https?://[^\x00-\x20\\\\]+)$~i', $value)) {
                        $fail('菜单地址必须为站内路径或 HTTP/HTTPS 网址。');
                    }
                }],
            ];
        }
        if ($model instanceof ShippingTemplate) {
            $rules += ['regions.*.prefix' => ['required', 'string', 'max:200'], 'regions.*.excluded' => ['sometimes', 'boolean'], 'regions.*.base_fee' => ['sometimes', 'numeric', 'between:0,99999.99', 'decimal:0,2'], 'regions.*.base_quantity' => ['sometimes', 'integer', 'between:1,10000'], 'regions.*.additional_fee' => ['sometimes', 'numeric', 'between:0,99999.99', 'decimal:0,2'], 'regions.*.free_threshold' => ['sometimes', 'nullable', 'numeric', 'between:0,999999.99', 'decimal:0,2']];
        }
        $data = $request->validate($rules);
        if ($model instanceof Menu) {
            $previousDepth = -1;
            foreach ($data['items'] as $item) {
                $depth = $item['depth'] ?? 0;
                if ($depth > $previousDepth + 1) {
                    throw ValidationException::withMessages(['items' => '菜单层级不能跳级，首项必须为顶层。']);
                }
                $previousDepth = $depth;
            }
        }
        if ($model instanceof AuthorSettlementRule && $data['scope'] !== 'default') {
            $table = ['content' => 'contents', 'author' => 'users', 'category' => 'categories'][$data['scope']];
            $request->validate(['target_id' => ['required', 'integer', Rule::exists($table, 'id')]]);
        }

        DB::transaction(function () use ($request, $model, $data, $resource) {
            if ($model instanceof Role) {
                $model->syncPermissions($data['permissions']);

                return;
            }
            if ($model instanceof PrivateMessage) {
                app(PrivateMessageService::class)->moderate($model, $data['status']);

                return;
            }
            if (in_array($resource, ['categories', 'link-categories'], true)) {
                // Lock the hierarchy before checking ancestors so concurrent edits cannot create a cycle.
                $parents = $model->newQuery()->lockForUpdate()->pluck('parent_id', 'id')->all();
                $ancestor = $data['parent_id'] ?? null;
                $seen = [];
                while ($ancestor) {
                    if ((string) $ancestor === (string) $model->id || isset($seen[$ancestor])) {
                        throw ValidationException::withMessages(['parent_id' => '上级分类不能指向自身或下级分类。']);
                    }
                    $seen[$ancestor] = true;
                    $ancestor = $parents[$ancestor] ?? null;
                }
            }
            if ($model instanceof User) {
                $isSuper = $request->user()->hasRole('SUPER_ADMIN');
                abort_if(! $isSuper && ($data['role'] === 'SUPER_ADMIN' || ($model->exists && $model->hasRole('SUPER_ADMIN'))), 403);
                if ($model->id === $request->user()->id && ! $model->hasRole($data['role'])) {
                    throw ValidationException::withMessages(['role' => '不能在此修改自己的角色。']);
                }
                if (($data['is_banned'] ?? false) && ($model->id === $request->user()->id || $model->hasRole('SUPER_ADMIN'))) {
                    throw ValidationException::withMessages(['is_banned' => '不能停用自己或超级管理员。']);
                }
                if (! filled($data['password'] ?? null)) {
                    unset($data['password']);
                }
                $model->fill(Arr::except($data, ['role']))->save();
                $model->syncRoles([$data['role']]);
                $model->wallet()->firstOrCreate([], ['balance' => 0]);
                $model->pointsAccount()->firstOrCreate([], ['points' => 0]);
            } elseif ($model instanceof Topic) {
                $model->fill(Arr::except($data, ['content_ids']))->save();
                $model->contents()->sync($data['content_ids']);
            } elseif ($model instanceof UserRequest) {
                $model = UserRequest::whereKey($model->id)->lockForUpdate()->firstOrFail();
                if ($model->status === $data['status'] && $model->reply === ($data['reply'] ?? null)) {
                    return;
                }
                if ($model->status !== 'pending') {
                    throw ValidationException::withMessages(['status' => '申请已处理，不能重复修改处理结果。']);
                }
                $model->fill($data)->forceFill(['handled_at' => $data['status'] === 'pending' ? null : now()])->save();
                $user = User::findOrFail($model->user_id);
                if ($model->type === 'author') {
                    $user->update(['is_author' => $data['status'] === 'approved', 'author_status' => $data['status']]);
                } elseif ($model->type === 'verification') {
                    $user->update(['meta' => [...($user->meta ?? []), 'verified' => $data['status'] === 'approved']]);
                } elseif ($model->type === 'appeal' && $data['status'] === 'approved') {
                    $user->update(['is_banned' => false, 'ban_reason' => null]);
                }
                if ($data['status'] !== 'pending') {
                    DeliverSiteNotification::dispatch('request.handled:'.$model->id, $user->id, 'request', '申请处理结果', $data['reply'] ?? $data['status']);
                }
            } elseif ($model instanceof CardCode) {
                if ($model->exists && $model->status !== 'available') {
                    throw ValidationException::withMessages(['code' => '已交付卡密不能修改。']);
                }
                if (! $model->exists && ! filled($data['code'] ?? null)) {
                    throw ValidationException::withMessages(['code' => '请填写卡密。']);
                }
                if (filled($data['code'] ?? null)) {
                    $hash = hash('sha256', $data['code']);
                    if (CardCode::where('code_hash', $hash)->whereKeyNot($model->id ?? 0)->exists()) {
                        throw ValidationException::withMessages(['code' => '卡密已存在。']);
                    }
                    $model->code_hash = $hash;
                    $model->code_payload = $data['code'];
                }
                $model->fill(Arr::except($data, ['code']))->save();
            } elseif ($model instanceof Shipment) {
                app(ShipmentService::class)->update($model, $data);
            } elseif ($model instanceof Product) {
                if ($model->exists) {
                    $model->newQuery()->whereKey($model->id)->lockForUpdate()->firstOrFail();
                }
                $stock = DB::table('stock_items')->where('product_id', $model->id)->whereNull('product_variant_id')->lockForUpdate()->first();
                if ($data['stock'] < ($stock?->reserved ?? 0)) {
                    throw ValidationException::withMessages(['stock' => '库存不能低于已预留数量。']);
                }
                $model->fill(Arr::except($data, ['stock', 'shipping_fee', 'shipping_template_id', 'variants']));
                $model->metadata = [...($model->metadata ?? []), 'shipping_fee' => (string) $data['shipping_fee'], 'shipping_template_id' => $data['shipping_template_id'] ?? null];
                $model->save();
                DB::table('stock_items')->updateOrInsert(['product_id' => $model->id, 'product_variant_id' => null], ['quantity' => $data['stock'], 'type' => 'inventory', 'updated_at' => now(), 'created_at' => now()]);
                if (array_key_exists('variants', $data)) {
                    $kept = [];
                    foreach ($data['variants'] as $variantData) {
                        $variant = ! empty($variantData['id']) ? $model->variants()->whereKey($variantData['id'])->lockForUpdate()->firstOrFail() : new ProductVariant(['product_id' => $model->id]);
                        if ($variantData['stock'] < ($variant->reserved ?? 0)) {
                            throw ValidationException::withMessages(['variants' => '规格库存不能低于预留数量。']);
                        }
                        if (ProductVariant::where('sku', $variantData['sku'])->whereKeyNot($variant->id ?? 0)->exists()) {
                            throw ValidationException::withMessages(['variants' => 'SKU 已被使用。']);
                        }
                        $variant->fill(Arr::except($variantData, ['id']))->save();
                        $kept[] = $variant->id;
                    }
                    $model->variants()->whereNotIn('id', $kept)->update(['status' => 'inactive']);
                }
            } elseif ($model instanceof Coupon) {
                if ($data['type'] === 'percent' && $data['amount'] > 100) {
                    throw ValidationException::withMessages(['amount' => '减免比例不能大于 100%。']);
                }
                $model->fill($data)->save();
            } elseif ($model instanceof LinkSubmission) {
                $model = LinkSubmission::whereKey($model->id)->lockForUpdate()->firstOrFail();
                $wasApproved = $model->status === 'approved';
                $model->fill($data)->forceFill(['reviewed_at' => now()])->save();
                if ($model->status === 'approved' && ! $wasApproved) {
                    Link::firstOrCreate(['url' => $model->url], ['name' => $model->name, 'status' => 'active', 'link_category_id' => $model->link_category_id, 'submitted_by' => $model->user_id]);
                }
            } elseif ($model instanceof Menu) {
                $model->fill(Arr::except($data, ['items']))->save();
                $model->items()->delete();
                $parents = [];
                foreach ($data['items'] as $index => $item) {
                    $depth = $item['depth'] ?? 0;
                    $created = $model->items()->create(['title' => $item['title'], 'url' => $item['url'], 'sort_order' => $index, 'parent_id' => $depth ? $parents[$depth - 1] : null, 'meta' => ['visibility' => $item['visibility'] ?? 'public', 'target' => $item['target'] ?? '_self']]);
                    $parents[$depth] = $created->id;
                }
            } elseif ($model instanceof Widget) {
                $data['config'] = ['text' => $data['text'] ?? ''];
                $model->fill(Arr::except($data, ['text']))->save();
            } elseif ($model instanceof VipLevel) {
                $data['benefits'] = array_values(array_filter(array_map('trim', preg_split('/\R/', $data['benefits_text'] ?? ''))));
                $model->fill(Arr::except($data, ['benefits_text']))->save();
            } else {
                $model->fill($data)->save();
            }
            if ($model instanceof Comment) {
                $this->recountComments($model->content_id);
            }
        });
    }

    private function serialize(Model $model, array $definition): array
    {
        $data = $model->only(array_merge(['id', 'created_at'], array_column($definition['fields'], 'key')));
        unset($data['password']);
        if ($model instanceof User) {
            $data['role'] = $model->getRoleNames()->first() ?? 'USER';
        } elseif ($model instanceof Role) {
            $data['permissions'] = $model->permissions->pluck('name')->all();
        } elseif ($model instanceof PrivateMessage) {
            $data += $model->only(['sender_id', 'recipient_id', 'body']);
        } elseif ($model instanceof Topic) {
            $data['content_ids'] = $model->contents()->pluck('contents.id')->all();
        } elseif ($model instanceof UserRequest) {
            $data += $model->only(['user_id', 'content_id', 'type', 'body']);
        } elseif ($model instanceof Menu) {
            $depths = [];
            $data['items'] = $model->items->map(function ($item) use (&$depths) {
                $depth = $item->parent_id ? ($depths[$item->parent_id] ?? -1) + 1 : 0;
                $depths[$item->id] = $depth;

                return ['title' => $item->title, 'url' => $item->url, 'depth' => $depth, 'visibility' => data_get($item->meta, 'visibility', 'public'), 'target' => data_get($item->meta, 'target', '_self')];
            })->all();
        } elseif ($model instanceof Widget) {
            $data['text'] = data_get($model->config, 'text', '');
        } elseif ($model instanceof VipLevel) {
            $data['benefits_text'] = implode("\n", $model->benefits ?? []);
        } elseif ($model instanceof Product) {
            $data['stock'] = (int) DB::table('stock_items')->where('product_id', $model->id)->whereNull('product_variant_id')->value('quantity');
            $data['shipping_fee'] = data_get($model->metadata, 'shipping_fee', 0);
            $data['shipping_template_id'] = data_get($model->metadata, 'shipping_template_id');
            $data['variants'] = $model->variants()->get()->map->only(['id', 'sku', 'title', 'price', 'stock', 'reserved', 'status'])->all();
        } elseif ($model instanceof CardCode) {
            $data['status'] = $model->status;
        } elseif ($model instanceof Shipment) {
            $data['order_id'] = $model->order_id;
            $data['address'] = data_get($model->address_snapshot, 'address');
        }

        return $data;
    }

    private function recountComments(?int $contentId): void
    {
        Content::whereKey($contentId)->update(['comment_count' => Comment::where('content_id', $contentId)->where('status', 'approved')->count()]);
    }
}
