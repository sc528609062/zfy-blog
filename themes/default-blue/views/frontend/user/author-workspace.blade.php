@extends('layouts.app')
@section('title', '作者中心')
@section('content')
@include('partials.placeholder', [
    'title'    => '作者中心',
    'subtitle' => '前台投稿入口 / 我的作品 / 收益 / 提现',
    'sprint'   => 'Sprint 5 / M11',
    'features' => ['投稿与编辑器','作品管理（草稿/审核中/已发布）','收益与分成','提现申请'],
])
@endsection
