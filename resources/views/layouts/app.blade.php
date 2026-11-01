@php
    $user = auth()->user();
    $isAdmin = $user?->isAdmin() ?? false;

    $links = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'pattern' => 'dashboard'],
        ['label' => 'Kegiatan', 'route' => 'activities.index', 'pattern' => 'activities.*'],
        ['label' => 'Pindai QR', 'route' => 'scan.index', 'pattern' => 'scan.*'],
        ['label' => 'Absensi Saya', 'route' => 'attendances.mine', 'pattern' => 'attendances.mine'],
        ['label' => 'Izin & Sakit', 'route' => 'permission-requests.index', 'pattern' => 'permission-requests.*'],
    ];

    $adminLinks = [
        ['label' => 'Data Pengurus', 'route' => 'admin.users.index', 'pattern' => 'admin.users.*'],
        ['label' => 'Bidang', 'route' => 'admin.fields.index', 'pattern' => 'admin.fields.*'],
        ['label' => 'Jabatan Panitia', 'route' => 'admin.positions.index', 'pattern' => 'admin.positions.*'],
        ['label' => 'Persetujuan Izin', 'route' => 'admin.permission-requests.index', 'pattern' => 'admin.permission-requests.*'],
        ['label' => 'Rekap & Laporan', 'route' => 'reports.index', 'pattern' => 'reports.*'],
        ['label' => 'Audit Log', 'route' => 'admin.audits.index', 'pattern' => 'admin.audits.*'],
    ];
@endphp

<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · Absensi HIMATIF</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full">
    <div class="min-h-screen lg:flex">
        <div data-nav-backdrop class="fixed inset-0 z-30 hidden bg-army-950/40 lg:hidden"></div>

        <aside data-sidebar class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-army-900 transition-transform duration-200 lg:static lg:translate-x-0">
            <div class="flex items-center gap-3 px-5 py-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-cream-100 font-bold text-army-800">AH</div>
                <div>
                    <p class="text-sm font-semibold text-cream-50">Absensi HIMATIF</p>
                    <p class="text-xs text-cream-300/70">Sistem Kehadiran Pengurus</p>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 pb-4">
                @foreach ($links as $link)
                    <a href="{{ route($link['route']) }}"
                       class="nav-link {{ request()->routeIs($link['pattern']) ? 'nav-link-active' : '' }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach

                @if ($isAdmin)
                    <p class="px-3 pb-1 pt-5 text-[11px] font-semibold uppercase tracking-widest text-cream-300">Administrasi</p>
                    @foreach ($adminLinks as $link)
                        <a href="{{ route($link['route']) }}"
                           class="nav-link {{ request()->routeIs($link['pattern']) ? 'nav-link-active' : '' }}">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                @endif
            </nav>

            <div class="border-t border-white/10 p-4">
                <p class="text-sm font-medium text-cream-50">{{ $user?->name }}</p>
                <p class="text-xs text-cream-300/70">{{ $user?->npp }} · {{ $isAdmin ? 'Admin' : 'Pengurus' }}</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="btn w-full border border-white/15 text-cream-100 hover:bg-white/10">
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-cream-300 bg-cream-50/90 px-4 py-3 backdrop-blur lg:px-8">
                <button type="button" data-nav-toggle class="rounded-lg border border-cream-300 bg-white p-2 text-army-800 lg:hidden" aria-label="Buka menu">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>
                <div class="min-w-0">
                    <h1 class="truncate text-base font-semibold text-army-900 lg:text-lg">@yield('title', 'Dashboard')</h1>
                    @hasSection('subtitle')
                        <p class="truncate text-xs text-army-600">@yield('subtitle')</p>
                    @endif
                </div>
                <div class="ml-auto hidden items-center gap-2 text-xs text-army-700 sm:flex">
                    <span class="rounded-full bg-army-100 px-3 py-1 font-medium">{{ $user?->organizationalField?->code ?? 'Tanpa Bidang' }}</span>
                    <span class="rounded-full border border-cream-300 px-3 py-1">{{ $isAdmin ? 'Admin' : 'Pengurus' }}</span>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 lg:px-8">
                @include('components.flash')
                @yield('content')
            </main>

            <footer class="border-t border-cream-300 px-4 py-4 text-xs text-army-600 lg:px-8">
                Absensi Digital HIMATIF · Waktu server {{ now()->format('d M Y H:i') }} WIB
            </footer>
        </div>
    </div>
</body>
</html>
