@extends('errors::minimal')

@section('title', 'ページが見つかりません')
@section('code', '404')

@section('message',
    in_array($exception->getMessage(), [
        '読書計画が見つかりません。',
        '通知が見つかりません。',
    ], true)
        ? $exception->getMessage()
        : 'ページが見つかりません.'
)
