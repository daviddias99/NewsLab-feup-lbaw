@extends('layouts.app')

@section('title', "400")

@section('content')
    <main id="not-found-main" class="mt-5 container d-flex flex-column justify-content-center align-items-center">
        <img id="nfimage" src={{URL::asset('storage/images/other/error.jpg')}} alt="400 image">
        <h1>400</h1>
        <h2 class="col-8 text-center"> Oops! Invalid request. {{$exception->getMessage()}} </h2>
    </main>
@endsection