@extends('layouts.auth')
@section('title', 'Masuk - Remindly')
@section('content')
    <h1>Masuk ke Remindly</h1>
    @if(session('status'))<div class="success-line">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="form-error" role="alert">{{ $errors->first() }}</div>@endif
    <form class="auth-form" method="POST" action="{{ route('login.store') }}">
        @csrf
        <label>Email<input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
        <label>Password<input type="password" name="password" required></label>
        <label class="checkbox-label"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
        <button class="primary-button" type="submit">Masuk</button>
    </form>
    <div class="auth-links">
        <a href="{{ route('password.request') }}">Lupa password?</a>
        <span>Belum punya akun? <a href="{{ route('register') }}">Daftar</a></span>
    </div>
@endsection
