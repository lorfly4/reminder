@extends('layouts.auth')
@section('title', 'Reset password - Remindly')
@section('content')
    <h1>Atur ulang password</h1>
    @if($errors->any())<div class="form-error">{{ $errors->first() }}</div>@endif
    <form class="auth-form" method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label>Email<input type="email" name="email" value="{{ $email ?? old('email') }}" required></label>
        <label>Password baru<input type="password" name="password" minlength="8" required></label>
        <label>Konfirmasi password<input type="password" name="password_confirmation" minlength="8" required></label>
        <button class="primary-button" type="submit">Simpan password</button>
    </form>
@endsection
