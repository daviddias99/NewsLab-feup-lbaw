@extends('layouts.app')

@section('scripts')

@if(Auth::check())
    <script  src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.1/css/tempusdominus-bootstrap-4.min.css" />				
    <script type="text/javascript" src="{{ asset('calendar/tempusdominus.js')}}"></script>
    <script src="{{ asset('js/tag/tag_logged.js')}}" defer></script>
@else
    <script src="{{ asset('js/tag/tag_block.js')}}" defer></script>
@endif

<link rel="stylesheet" href="{{ asset('range/nouislider.css')}}">
<script src="{{ asset('range/nouislider.min.js')}}"></script>

<script src="{{ asset('js/filter.js')}}" defer></script>
<script src="{{ asset('js/tag/tag_common.js')}}" defer></script>

@endsection

{{-- TODO: por nome da Tag no titulo --}}
@section('title', 'NewsLab - Tag: ' . ucwords($tag['name']))

@section('content')
{{-- TODO: ver aqui falta de parenteses se pode dar problemas de segurança --}}
@if(isset($tag['photo']))
<div class="img-header mb-4" title="Tag photo" style="background-image: url('{{URL::asset('storage/images/tags/'.$tag['photo'])}}')">
@else
<div class="img-header mb-4" title="Tag photo" style="background-image: url('{{URL::asset('storage/images/tags/default.jpg')}}')">
@endif
    <div class="overlay"></div>
    <div id="tag-title" data-color="{{$tag['color']}}" class="text-light bottomLeft m-5 p-3">
        <h1 class="text-capitalize">{{$tag['name']}}</h1>
        <p id="sub-count" class="text-right mr-3 my-0">{{$tag['num_subscribers']}} @if ($tag['num_subscribers'] == 1) Subscriber @else Subscribers @endif</p>
    </div>

    <div data-id={{$tag['id']}} class="m-5 menu bottomRight flex-row d-flex align-items-center">
            @if ($subscribed)
                <button id="subscribe_btn" data-subscribing_id={{Auth::check() ? Auth::user()->id : ""}} type="button" class="btn btn-outline-secondary ml-auto">Unsubscribe</button>
            @else
                <button id="subscribe_btn" data-subscribing_id={{Auth::check() ? Auth::user()->id : ""}} type="button" class="btn btn-primary ml-auto">Subscribe</button>
            @endif
            <div class="d-flex flex-row-reverse">
            <div class="dropdown">
                <button style="width:2.5rem" class="btn  btn-sm bg-white ml-2 py-2" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-h"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                    <a class="dropdown-item" href="{{$tag['id']}}" data-toggle="modal" data-target="#report-modal">Report</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="wrapper" data-id={{$tag['id']}} class="flex-grow-1 toggled">
    @include('partials.filter', ['full' => false])

    <!-- sidebar-wrapper  -->
    <main class="page-content container">
        <!-- news -->
        <section id="news">
            <h2 class="text-left mt-5">NEWS</h2>

            <div class="mt-5 tab-pane">
                @include('partials.post_preview_list', [
                    'posts' => $posts['news']['data'],
                    'editable' => false,
                    'showAuthor' => true,
                    'showDate' => false,
                    'emptyMessage' => 'No News found',
                    'paginator' =>  $posts['news']['paginator'],
                ])
            </div>
        </section>

        <!-- opinions -->
        <section id="opinions">
            <h2 class="text-left mt-5">OPINIONS</h2>

            <div class="mt-5 tab-pane">
                @include('partials.post_preview_list', [
                    'posts' => $posts['opinion']['data'],
                    'editable' => false,
                    'showAuthor' => true,
                    'showDate' => false,
                    'emptyMessage' => 'No Opinions found',
                    'paginator' =>  $posts['opinion']['paginator'],
                ])
            </div>
        </section>
    </main>

  <!-- page-content" -->
</div>

@if(Auth::check())
    @include('inc.report')
@endif

@endsection
