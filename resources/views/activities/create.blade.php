@extends('layouts.app')

@section('title', 'Buat Kegiatan')
@section('subtitle', 'Atur jadwal, lokasi, dan toleransi keterlambatan')

@section('content')
    <div class="card max-w-3xl p-4 sm:p-6">
        @include('activities._form')
    </div>
@endsection
