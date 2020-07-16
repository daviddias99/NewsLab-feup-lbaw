@extends('layouts.app')

@section('title', "403")

@section('content')
    <main id="not-found-main" class="mt-5 container d-flex flex-column justify-content-center align-items-center">
        <img id="nfimage" src={{URL::asset('storage/images/other/error.jpg')}} alt="403 image">
        <h1>403</h1>
        <h2 class="col-8 text-center"> Oops! You are not allowed to enter the page you're looking for...</h2>
    </main>
@endsection