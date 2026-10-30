@extends('layouts.app')

@section('title', 'Data Pengurus')
@section('subtitle', 'Kelola akun, NPP, NPM, dan bidang')

@section('content')
    <div class="card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-cream-200 px-4 py-3">
            <div class="min-w-40 flex-1">
                <label for="search" class="label">Cari</label>
                <input id="search" name="search" value="{{ request('search') }}" class="input" placeholder="Nama, NPP, atau NPM">
            </div>
            <div>
                <label for="field" class="label">Bidang</label>
                <select id="field" name="field" class="input">
                    <option value="">Semua bidang</option>
                    @foreach ($fields as $field)
                        <option value="{{ $field->id }}" @selected((string) request('field') === (string) $field->id)>{{ $field->code }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="role" class="label">Role</label>
                <select id="role" name="role" class="input">
                    <option value="">Semua role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary">Terapkan</button>
            <a href="{{ route('admin.users.create') }}" class="btn btn-secondary ml-auto">+ Pengurus</a>
        </form>

        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>NPP</th>
                        <th>NPM</th>
                        <th>Bidang</th>
                        <th>Jabatan</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Login terakhir</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="font-medium text-army-900">{{ $user->name }}</td>
                            <td class="text-xs">{{ $user->npp }}</td>
                            <td class="text-xs">{{ $user->npm }}</td>
                            <td>{{ $user->organizationalField?->code ?? '-' }}</td>
                            <td>{{ $user->structural_position?->label() ?? '-' }}</td>
                            <td>{{ $user->role->label() }}</td>
                            <td>
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $user->is_active ? 'bg-army-100 text-army-800' : 'bg-red-50 text-red-700' }}">
                                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-xs">{{ $user->last_login_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td class="text-right text-xs">
                                <a href="{{ route('admin.users.edit', $user) }}" class="font-medium text-army-700 hover:text-army-900">Edit</a>
                                @unless ($user->is(auth()->user()))
                                    <form method="POST" action="{{ route('admin.users.status', $user) }}" class="ml-2 inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="font-medium text-army-500 hover:text-army-800">{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-sm text-army-500">Belum ada data pengurus.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3">{{ $users->links() }}</div>
    </div>
@endsection
