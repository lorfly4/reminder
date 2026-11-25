@extends('layouts.auth')
@section('title', 'Lupa password - Remindly')
@section('content')
    <h1>Pulihkan akun</h1>
    @if(session('status'))<div class="success-line">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="form-error">{{ $errors->first() }}</div>@endif
    <form class="auth-form" method="POST" action="{{ route('password.email') }}">
        @csrf
        <label>Email<input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
        <button class="primary-button" type="submit">Kirim tautan reset</button>
    </form>
    <div class="auth-links"><a href="{{ route('login') }}">Kembali ke login</a></div>
@endsection
