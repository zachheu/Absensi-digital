<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses Ditolak · Absensi HIMATIF</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-cream-50 px-4">
    <div class="card max-w-md p-6 text-center">
        <p class="text-xs font-semibold uppercase tracking-widest text-army-500">403</p>
        <h1 class="mt-2 text-xl font-semibold text-army-900">Akses ditolak</h1>
        <p class="mt-2 text-sm text-army-600">
            {{ $exception->getMessage() ?: 'Kamu tidak memiliki izin untuk membuka halaman ini.' }}
        </p>
        <div class="mt-5 flex flex-wrap justify-center gap-2">
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Kembali ke dashboard</a>
            <a href="{{ route('activities.index') }}" class="btn btn-secondary">Lihat kegiatan</a>
        </div>
    </div>
</body>
</html>
