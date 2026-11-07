<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · Absensi HIMATIF</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-army-900">
    <main class="mx-auto flex min-h-screen max-w-6xl flex-col justify-center px-4 py-10 lg:flex-row lg:items-center lg:gap-16">
        <div class="mb-8 lg:mb-0 lg:flex-1">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo-himatif.png') }}" alt="Logo HIMATIF" class="h-12 w-12 object-contain">
                <div>
                    <p class="text-lg font-semibold text-cream-50">Absensi HIMATIF</p>
                    <p class="text-sm text-cream-300/70">Sistem kehadiran pengurus</p>
                </div>
            </div>
            <h1 class="mt-8 max-w-md text-3xl font-semibold leading-tight text-cream-50 lg:text-4xl">
                Himpunan Mahasiswa Teknik Informatika
            </h1>
            <p class="mt-3 max-w-md text-xl font-medium leading-relaxed text-cream-300/80">
                Connect Your Mind
            </p>
        </div>

        <div class="w-full lg:max-w-md">
            <div class="rounded-2xl bg-cream-50 p-6 shadow-xl sm:p-8">
                <h2 class="text-xl font-semibold text-army-900">Masuk ke aplikasi</h2>
                <p class="mt-1 text-sm text-army-600">Gunakan NPP dan password akun pengurus.</p>

                @if (session('status'))
                    <div class="mt-4 rounded-lg border border-army-200 bg-army-50 px-4 py-3 text-sm text-army-900">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <label for="npp" class="label">NPP</label>
                        <input id="npp" name="npp" type="text" inputmode="numeric" autocomplete="username"
                               value="{{ old('npp') }}" required autofocus class="input" placeholder="Nomor Pokok Pengurus">
                        @error('npp')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="label">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password"
                               required class="input" placeholder="Password akun">
                        @error('password')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-army-700">
                        <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-cream-400 text-army-700 focus:ring-army-300">
                        Ingat saya di perangkat ini
                    </label>

                    <button type="submit" class="btn btn-primary w-full">Masuk</button>
                </form>

                <p class="mt-6 text-center text-xs text-army-600">
                    Lupa password? Hubungi admin HIMATIF untuk pengaturan ulang.
                </p>
            </div>
        </div>
    </main>
</body>
</html>
