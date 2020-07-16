@extends('layouts.app')

@section('scripts')
<script  src="{{ asset('js/count_up.js')}}" defer></script>
<script  src="{{ asset('js/admin.js')}}" defer></script>
@endsection

@section('title', 'NewsLab - Admin Center')

@section('content')
<main  class="container">

    <div class="row mt-5">

        <!-- Tab interface -->
        <div class="col-md-3">
            <h3 class="mb-3 ">Admin Center</h3>

            <div class="nav flex-md-column flex-row nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                <a class="nav-link active" id="v-pills-general-tab" data-toggle="pill" href="#v-pills-general" role="tab" aria-controls="v-pills-general" aria-selected="true">General</a>
                <a class="nav-link" id="v-pills-admin_list-tab" data-toggle="pill" href="#v-pills-admin_list" role="tab" aria-controls="v-pills-admin_list" aria-selected="false">Admin List</a>
                <a class="nav-link" id="v-pills-report-tab" data-toggle="pill" href="#v-pills-report" role="tab" aria-controls="v-pills-report" aria-selected="false">Report Inbox</a> 
                <a class="nav-link" id="v-pills-banned_users-tab" data-toggle="pill" href="#v-pills-banned_users" role="tab" aria-controls="v-pills-banned_users" aria-selected="false">Banned Users</a>
            </div>
            <hr>
        </div>

        <!-- Display section -->
        <div class="col-md-9">
            <div class="tab-content pl-3" id="v-pills-tabContent">

                @include('partials.admin_general')
                @include('partials.admin_list',$admin_list)
                @include('partials.admin_report_inbox', $report_inbox)
                @include('partials.admin_banned_users', $banned_list)

            </div>
        </div>
    </div>
</main>


@endsection