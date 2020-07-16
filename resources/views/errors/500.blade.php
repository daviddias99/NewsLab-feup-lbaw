@extends('layouts.app')

@section('title', $exception->getStatusCode())

@section('content')
    <main id="not-found-main" class="mt-5 container d-flex flex-column justify-content-center align-items-center">
        <img id="nfimage" src={{URL::asset('storage/images/other/error.jpg')}} alt="{{$exception->getStatusCode()}} image">
        <h1>{{$exception->getStatusCode()}}</h1>
        <h2 class="col-8 text-center">Oops! That was unexpected...</h2>
        <h3 class="col-8 text-center">Our scientists are mixing the solution</h3>
    </main>
@endsection