@php
    $isEdit = $user->exists;
    $action = $isEdit ? route('admin.users.update', $user) : route('admin.users.store');
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="npp" class="label">NPP</label>
            <input id="npp" name="npp" value="{{ old('npp', $user->npp) }}" required class="input" placeholder="Nomor Pokok Pengurus">
            <p class="help">Dipakai untuk login. Simpan sebagai teks, bukan angka.</p>
            @error('npp') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="npm" class="label">NPM</label>
            <input id="npm" name="npm" value="{{ old('npm', $user->npm) }}" required class="input" placeholder="Nomor Pokok Mahasiswa">
            <p class="help">Identitas mahasiswa, tidak dipakai untuk login.</p>
            @error('npm') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="name" class="label">Nama lengkap</label>
            <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="input">
            @error('name') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="label">Email (opsional)</label>
            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="input">
            @error('email') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="organizational_field_id" class="label">Bidang</label>
            <select id="organizational_field_id" name="organizational_field_id" required class="input">
                <option value="">Pilih bidang</option>
                @foreach ($fields as $field)
                    <option value="{{ $field->id }}" @selected((string) old('organizational_field_id', $user->organizational_field_id) === (string) $field->id)>{{ $field->code }}</option>
                @endforeach
            </select>
            @error('organizational_field_id') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="structural_position" class="label">Jabatan struktural (opsional)</label>
            <select id="structural_position" name="structural_position" class="input">
                <option value="">Tidak ada</option>
                @foreach ($structuralPositions as $position)
                    <option value="{{ $position->value }}" @selected(old('structural_position', $user->structural_position?->value) === $position->value)>{{ $position->label() }}</option>
                @endforeach
            </select>
            <p class="help">Khusus Staff Inti. Tidak menambah hak akses otomatis.</p>
            @error('structural_position') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="membership_type" class="label">Tipe pengurus (opsional)</label>
            <select id="membership_type" name="membership_type" class="input">
                <option value="">Tidak ada</option>
                @foreach ($membershipTypes as $type)
                    <option value="{{ $type->value }}" @selected(old('membership_type', $user->membership_type?->value) === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
            @error('membership_type') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="role" class="label">Role aplikasi</label>
            <select id="role" name="role" class="input" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected(old('role', $user->role?->value ?? 'pengurus') === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </select>
            <p class="help">Jabatan kepanitiaan tidak memengaruhi role aplikasi.</p>
            @error('role') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="label">Password {{ $isEdit ? '(kosongkan bila tidak diubah)' : '' }}</label>
            <input id="password" type="password" name="password" class="input" autocomplete="new-password" {{ $isEdit ? '' : 'required' }}>
            @error('password') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-end">
            <label class="flex items-center gap-2 text-sm text-army-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true)) class="h-4 w-4 rounded border-cream-400 text-army-700">
                Akun aktif
            </label>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <button class="btn btn-primary">{{ $isEdit ? 'Simpan perubahan' : 'Buat akun' }}</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
