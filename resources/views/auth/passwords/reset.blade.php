@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header border-0">
                    <h2 class="card-title" id="pwRecoveryCard">{{ __('Reset Password') }}</h2>
                </div>
                <div class="card-body text-center col">
                    {{-- @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif --}}
                    <form class="form-pwrecovery col m-auto p-0" method="POST" action="{{ route('password.update') }}">
                        @csrf
    
                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="form-group">
                            <label for="email" class="col text-left px-0 pb-0 mb-0"><strong>{{ __('Email *') }}</strong></label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" title="Invalid email">
                            @error('email')
                                <p class="small text-primary text-left mb-0">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password" class="col text-left px-0 pb-0 mb-0"><strong>{{ __('Password *') }}</strong></label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters">
                            @error('password')
                                <p class="small text-primary text-left mb-0">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password-confirm" class="col text-left px-0 pb-0 mb-0"><strong>{{ __('Confirm Password *') }}</strong></label>
                            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters">
                            @error('password_confirmation')
                                <p class="small text-primary text-left mb-0">{{ $message }}</p>
                            @enderror
                        </div>
                        <p class="small text-muted text-left mb-0">* Required Input</p>

    
                        <button type="submit" class="btn btn-primary btn-block mt-3">{{ __('Reset Password') }}</button>
                    </form>    
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
