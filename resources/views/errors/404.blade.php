@extends('errors::minimal')

@section('title', 'ページが見つかりません')
@section('code', '404')

@section('message',
    $exception->getMessage() === '読書計画が見つかりません。'
        ? '読書計画が見つかりません。'
        : 'ページが見つかりません。'
)