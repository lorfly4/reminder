@extends('layouts.auth')
@section('title', 'Daftar - Remindly')
@section('content')
    <h1>Buat akun baru</h1>
    @if($errors->any())<div class="form-error" role="alert">{{ $errors->first() }}</div>@endif
    <form class="auth-form" method="POST" action="{{ route('register.store') }}">
        @csrf
        <label>Nama<input type="text" name="name" value="{{ old('name') }}" required autofocus></label>
        <label>Email<input type="email" name="email" value="{{ old('email') }}" required></label>
        <label>Password<input type="password" name="password" minlength="8" required></label>
        <label>Konfirmasi Password<input type="password" name="password_confirmation" minlength="8" required></label>
        <button class="primary-button" type="submit">Buat akun</button>
    </form>
    <div class="auth-links"><span>Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></span></div>
@endsection
