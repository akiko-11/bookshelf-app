@extends('errors::minimal')

@section('title', 'アクセス権限がありません')
@section('code', '403')

@section('message')
    {{ $exception->getMessage() ?: '操作する権限がありません。' }}
@endsection
