@extends('layouts.app')
@section('title', '排行榜')
@section('content')
@include('partials.placeholder', [
    'title'    => '排行榜',
    'subtitle' => '基于浏览/点赞/购买/收藏 的多维度排行',
    'sprint'   => 'Sprint 5 / M11',
    'features' => ['热门内容榜','作者贡献榜','付费/免费分榜','日/周/月/总榜'],
])
@endsection
