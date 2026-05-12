@extends('layouts.app')
@section('title', '我的 VIP')
@section('content')
@include('partials.placeholder', ['title' => '我的 VIP', 'subtitle' => '当前等级 / 到期时间 / 续费', 'sprint' => 'Sprint 4 / M9'])
@endsection
