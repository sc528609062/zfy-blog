@extends('layouts.app')
@section('title', '作者主页')
@section('content')
@include('partials.placeholder', [
    'title'    => '作者主页',
    'subtitle' => '作者卡片 + 作品列表 + 关注按钮',
    'sprint'   => 'Sprint 5 / M11',
])
@endsection
