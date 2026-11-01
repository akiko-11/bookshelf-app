@extends('errors::minimal')

@section('title', '操作できません')
@section('code', '409')

@section('message')
    {{ $exception->getMessage() ?: '操作できません。' }}
@endsection
