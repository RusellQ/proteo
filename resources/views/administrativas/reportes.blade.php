@extends('layouts.master')

@section('page-title', 'Reportes')
@section('title', 'Reportes')

@section('content')
@php
    $user = auth()->user();
    $userName = trim(($user->nombre ?? '') . ' ' . ($user->apellido_paterno ?? '') . ' ' . ($user->apellido_materno ?? ''));
@endphp
<div id="app">
    <reportes-vue user-name="{{ $userName }}"></reportes-vue>
</div>
@endsection
