@extends('layouts.app')
@section('title', 'Pengaturan - Remindly')
@section('content')
    <section class="card panel page-panel">
        <div class="panel-header"><h2>Pengaturan</h2></div>
        @if($errors->any())<div class="form-error" role="alert">{{ $errors->first() }}</div>@endif
        <form class="settings-form" method="POST" action="{{ route('settings.update') }}">
            @csrf @method('PUT')
            <label>Timezone
                <select name="timezone">
                    @foreach(['Asia/Jakarta','Asia/Makassar','Asia/Jayapura','UTC'] as $timezone)<option value="{{ $timezone }}" @selected($user->timezone === $timezone)>{{ $timezone }}</option>@endforeach
                </select>
            </label>
            <label>Bahasa
                <select name="language"><option value="id" @selected($user->language === 'id')>Indonesia</option><option value="en" @selected($user->language === 'en')>English</option></select>
            </label>
            <label>Tema
                <select name="theme"><option value="system" @selected($user->theme === 'system')>Ikuti sistem</option><option value="light" @selected($user->theme === 'light')>Terang</option><option value="dark" @selected($user->theme === 'dark')>Gelap</option></select>
            </label>
            <button class="primary-button" type="submit">Simpan pengaturan</button>
        </form>
        <div class="category-section">
            <h3>Kategori</h3>
            <form class="task-create category-create" method="POST" action="{{ route('categories.store') }}">
                @csrf
                <label>Nama<input type="text" name="name" required></label>
                <label>Warna<input type="color" name="color" value="#6366F1" required></label>
                <button class="primary-button" type="submit">Tambah kategori</button>
            </form>
            <div class="task-table">
                @foreach($categories as $category)
                    <article class="task-row task-row-expanded">
                        <div class="task-main"><strong>{{ $category->name }}</strong><span>{{ $category->color }}</span></div>
                        <div class="task-actions">
                            <form method="POST" action="{{ route('categories.update', $category) }}">
                                @csrf @method('PUT')
                                <input type="text" name="name" value="{{ $category->name }}" required>
                                <input type="color" name="color" value="{{ $category->color }}" required>
                                <button class="secondary-button" type="submit">Simpan</button>
                            </form>
                            <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori ini?')">
                                @csrf @method('DELETE')
                                <button class="danger-button" type="submit">Hapus</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
        <div class="settings-actions">
            <a class="secondary-button" href="{{ route('settings.export') }}">Ekspor data JSON</a>
            <form method="POST" action="{{ route('settings.account.delete') }}" onsubmit="return confirm('Akun dan seluruh data akan dihapus permanen. Lanjutkan?')">
                @csrf @method('DELETE')
                <input type="password" name="password" placeholder="Password akun" required>
                <button class="danger-button" type="submit">Hapus akun</button>
            </form>
        </div>
    </section>
@endsection
