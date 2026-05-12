@extends('admin.layouts.app')

@section('title', $title ?? '建设中')
@section('page_title', $title ?? '建设中')

@section('content')
    @include('admin.partials.placeholder', [
        'title'    => $title ?? '建设中',
        'subtitle' => $subtitle ?? '该模块将在后续 Sprint 完整实现，敬请期待',
        'sprint'   => $milestone ?? null,
        'features' => $features ?? [],
    ])
@endsection
