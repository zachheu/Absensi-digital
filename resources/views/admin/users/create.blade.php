@extends('layouts.app')

@section('title', 'Tambah Pengurus')
@section('subtitle', 'Akun dibuat oleh admin, tanpa pendaftaran publik')

@section('content')
    <div class="card max-w-3xl p-4 sm:p-6">
        @include('admin.users._form')
    </div>
@endsection
