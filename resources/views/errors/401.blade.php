@extends('layouts.app')

@section('title', "401")

@section('content')
    <main id="not-found-main" class="mt-5 container d-flex flex-column justify-content-center align-items-center">
        <img id="nfimage" src={{URL::asset('storage/images/other/error.jpg')}} alt="401 image">
        <h1>401</h1>
        <h2 class="col-8 text-center"> Oops! You are not logged in. Please consider doing it before proceeding...</h2>
    </main>
@endsection