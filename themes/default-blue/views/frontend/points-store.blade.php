@extends('layouts.app')
@section('title', '积分商城')
@section('content')
@include('partials.placeholder', [
    'title'    => '积分商城',
    'subtitle' => '使用积分兑换 VIP 时长 / 内容 / 钱包余额 / 实物',
    'sprint'   => 'Sprint 5 / M10',
    'features' => ['签到/评论/发文积分','商品兑换','发货管理','积分流水'],
])
@endsection
