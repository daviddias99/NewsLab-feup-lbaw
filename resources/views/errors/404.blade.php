@extends('layouts.app')

@section('title', "404")

@section('content')
    <main id="not-found-main" class="mt-5 container d-flex flex-column justify-content-center align-items-center">
        <img id="nfimage" src={{URL::asset('storage/images/other/error.jpg')}} alt="404 image">
        <h1>404</h1>
        <h2 class="col-8 text-center"> Oops! The page you're looking for does not exist...</h2>
    </main>
@endsection