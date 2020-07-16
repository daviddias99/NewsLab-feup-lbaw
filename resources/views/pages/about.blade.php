@extends('layouts.app')

@section('title', 'NewsLab - About')

@section('content')
<main id="about-main"  class="container">
    <h1 class="text-left mt-5">About</h1>

    <hr>

    <section class="container mt-5">
        <div class="col-lg row">
            <div class="col-lg-7 d-flex flex-column align-items-start pl-0">
              <h3 class="mb-3">About NewsLab</h3>
              <div class="">
                NewsLab consists of a collaborative news web application where anyone has the freedom to write and develop news about various topics and read about occurrences all around the world, serving as a platform not only to increase each one's awareness and knowledge about the world but also as a way to unite people and increase communication. It will be designed to create an all-encompassing place where people can write and discuss news in a collaborative manner, centralizing news and perspectives from all over the world allowing users to create a wholesome outlook on whatever subject they like.
              </div>
              <a href="guidelines.pdf" class="mt-4 mb-2 btn btn-primary" download="guidelines.pdf">
                Read guidelines
              </a>
            </div>
            <div class="col-lg-5">
              <img alt="Stock photo" class="img-size-std" src={{URL::asset('storage/images/other/about_image.jpeg')}}>
            </div>
        </div>
    </section>

    <section class="container my-5 px-0">
        <h3>Our team</h3>
        <section class="container">
            <div id="team-cards" class="col-lg row justify-content-between mt-4">
                <div class="col-sm-3 mt-3">
                  <div class="card border-0 text-center mx-auto" style="width: 12rem;">
                    <div class="card-header bg-white">
                        <div class="square rounded-circle" title="David Dias photo" style="background-image: url('{{URL::asset('storage/images/other/daviduvidas.jpg')}}')"></div>
                    </div>
                    <div class="card-body px-0">
                        <h5 class="card-title font-weight-bold">David Dias</h5>
                        <p class="text-muted">up201705373</p>
                        <a role="button" class="btn btn-primary" href="https://github.com/daviddias99">Go to Github</a>
                    </div>
                  </div>
                </div>
                <div class="col-sm-3 mt-3">
                  <div class="card border-0 text-center mx-auto" style="width: 12rem;">
                    <div class="card-header bg-white">
                        <div class="square rounded-circle" title="Edu Ribeiro photo"  style="background-image: url('{{URL::asset('storage/images/other/eddy_stream.jpg')}}')"></div>
                    </div>
                    <div class="card-body px-0">
                        <h5 class="card-title font-weight-bold">Eduardo Ribeiro</h5>
                        <p class="text-muted">up201705421</p>
                        <a role="button" class="btn btn-primary" href="https://github.com/EduRibeiro00">Go to Github</a>
                    </div>
                  </div>
                </div>
                <div class="col-sm-3 mt-3">
                  <div class="card border-0 text-center mx-auto" style="width: 12rem;">
                    <div class="card-header bg-white">
                        <div class="square rounded-circle" title="Luis Cunha photo"  style="background-image: url('{{URL::asset('storage/images/other/parry.jpg')}}')"></div>
                    </div>
                    <div class="card-body px-0">
                        <h5 class="card-title font-weight-bold">Luis Cunha</h5>
                        <p class="text-muted">up201706736</p>
                        <a role="button" class="btn btn-primary" href="https://github.com/luispcunha">Go to Github</a>
                    </div>
                  </div>
                </div>
                <div class="col-sm-3 mt-3">
                  <div class="card border-0 text-center mx-auto" style="width: 12rem;">
                    <div class="card-header bg-white">
                        <div class="square rounded-circle" title="Manuel Coutinho photo"  style="background-image: url('{{URL::asset('storage/images/other/mcgun.jpg')}}')"></div>
                    </div>
                    <div class="card-body px-0">
                        <h5 class="card-title font-weight-bold">Manuel Coutinho</h5>
                        <p class="text-muted">up201704211</p>
                        <a role="button" class="btn btn-primary" href="https://github.com/manelcoutinho">Go to Github</a>
                    </div>
                  </div>
                </div>

            </div>
        </section>
    </section>
</main>
@endsection
