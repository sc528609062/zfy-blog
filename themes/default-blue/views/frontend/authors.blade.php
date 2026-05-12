@extends('layouts.app')
@section('title', '作者列表')
@section('content')
@include('partials.placeholder', [
    'title'    => '作者列表',
    'subtitle' => '浏览全站签约作者与认证创作者',
    'sprint'   => 'Sprint 5 / M11',
    'features' => ['作者卡片网格','按方向/分类筛选','作者主页与作品集','关注 / 取消关注'],
])
@endsection
