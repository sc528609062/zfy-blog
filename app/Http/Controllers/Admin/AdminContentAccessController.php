<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AdminContentAccessController extends Controller
{
    public function update(Request $request, Content $content)
    {
        $commerce = $request->user()?->can('manage commerce');
        abort_unless($commerce || $request->user()?->can('manage contents'), 403);
        $data = $request->validate([
            'price' => [$commerce ? 'required' : 'prohibited', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
            'vip_free' => [$commerce ? 'required' : 'prohibited', 'boolean'],
            'downloads_per_day' => ['sometimes', 'integer', 'between:0,10000'],
            'downloads_total' => ['sometimes', 'integer', 'between:0,100000'],
            'visibility' => ['sometimes', 'in:public,member,vip,comment,password'],
            'password' => ['nullable', 'string', 'min:6', 'max:128'],
            'seo_title' => ['sometimes', 'nullable', 'string', 'max:180'],
            'seo_description' => ['sometimes', 'nullable', 'string', 'max:300'],
        ]);
        $rules = $content->access_rules ?? [];
        if ($commerce) {
            $rules['vip_free'] = $data['vip_free'];
        }
        foreach (['downloads_per_day', 'downloads_total', 'visibility'] as $key) {
            if (array_key_exists($key, $data)) {
                $rules[$key] = $data[$key];
            }
        }
        if (filled($data['password'] ?? null)) {
            $rules['password_hash'] = Hash::make($data['password']);
        }
        if (($rules['visibility'] ?? '') === 'password' && ! filled($rules['password_hash'] ?? null)) {
            throw ValidationException::withMessages(['password' => '请设置内容密码。']);
        }
        $content->update([
            'pricing' => $commerce ? [...($content->pricing ?? []), 'price' => $data['price']] : $content->pricing,
            'access_rules' => $rules,
            'seo' => [...($content->seo ?? []), 'title' => $data['seo_title'] ?? data_get($content->seo, 'title'), 'description' => $data['seo_description'] ?? data_get($content->seo, 'description')],
        ]);

        return response()->json(['message' => '访问设置已保存']);
    }

    public function upload(Request $request, Content $content)
    {
        abort_unless($request->user()?->can('manage contents'), 403);
        $request->validate(['file' => ['required', 'file', 'max:102400', 'mimes:zip,rar,7z,pdf,txt,doc,docx,xls,xlsx,ppt,pptx,mp3,mp4,jpg,jpeg,png,webp']]);
        $file = $request->file('file');
        $disk = config('filesystems.downloads', 'local');
        abort_unless(in_array($disk, ['local', 's3', 'oss', 'cos'], true), 422, '私有存储配置无效。');
        zfy_validate('zfy_media_uploading', $file, $request->user());
        $path = $file->store('downloads', ['disk' => $disk, 'visibility' => 'private']);
        abort_unless($path, 422, '上传失败。');
        try {
            $attachment = DB::transaction(function () use ($request, $content, $file, $path, $disk) {
                $media = Media::create([
                    'user_id' => $request->user()->id, 'disk' => $disk, 'type' => 'file',
                    'name' => $file->getClientOriginalName(), 'path' => $path,
                    'mime' => $file->getMimeType(), 'size' => $file->getSize(),
                    'metadata' => ['private' => true],
                ]);

                zfy_after_commit('zfy_media_created', $media);

                return $content->attachments()->create(['media_id' => $media->id, 'role' => 'download']);
            });
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }

        return response()->json(['message' => '下载附件已上传', 'attachment' => ['id' => $attachment->id, 'name' => $file->getClientOriginalName()]], 201);
    }

    public function destroy(Request $request, Content $content, int $attachment)
    {
        abort_unless($request->user()?->can('manage contents'), 403);
        $content->attachments()->findOrFail($attachment)->delete();

        return response()->json(['message' => '附件关联已移除']);
    }
}
