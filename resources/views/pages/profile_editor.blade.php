@extends('layouts.app')

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>
    <script src="{{ asset('calendar/tempusdominus.js')}}"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.1/css/tempusdominus-bootstrap-4.min.css" />

    <script  src="{{ asset('js/profile/edit_profile.js') }}" defer></script>
@endsection

@section('title', 'NewsLab - Edit Profile')

@section('content')

<main  class="container flex-grow-1">
    <div class="d-flex flex-column mt-5">
        <h1 class="m-0">Manage Profile</h1>
        <div class="flex-row d-flex align-items-center">
        </div>
    </div>

    <hr class="mt-1">
    <form method="POST" action="/api/users/{{ $user['id'] }}">

        {{ csrf_field() }}
        <div class="row">

            <div class="col-md-4 p-4">
                @if (!is_null($user['photo']))
                    <div id="profileImage" title="Author photo" class="p-rel square rounded-circle" style="background-image: url('{{URL::asset('storage/images/users/' . $user['photo'] )}}')">
                @else
                    <div id="profileImage" title="Author photo"  class="p-rel square rounded-circle" style="background-image: url('{{URL::asset('storage/images/users/default.png')}}')">
                @endif
                    <div class="row image-options h-100 w-100 rounded-circle ml-0">
                        <button id="deleteImageButton" class="btn w-50 rounded-0 text-right btn-secondary o-80">
                            <i class="fas fa-2x fa-times"></i>
                        </button>
                        <div class="form-group w-50 mb-0">
                            <label for="profilePic" class="text-white w-100 h-100 bg-success btn text-left rounded-0 o-80">
                                <i class="fas fa-2x fa-pencil-alt"></i>
                            </label>
                            <input type="file" class="form-control-file d-none" accept="image/*" id="profilePic" data-hasnewfile="oldImage" data-defaultimgpath={{URL::asset('storage/images/users/default.png')}}>
                        </div>
                    </div>
                </div>
                <p id="photoError" class="text-center mb-0 small text-primary"></p>
            </div>
            <div class="col-md-4 my-auto mx-0">
                <div class="form-group">
                    <label for="nameInput"><strong>Name: *</strong></label>
                    <input type="text" class="form-control rounded-0 text-dark" id="nameInput" placeholder="Name" required value="{{$user['name']}}" pattern="[a-zA-Z ]{3,25}" title="Name should contain between 3 and 25 letters and spaces">
                    <p id="nameError" class="small text-primary mb-0"></p>
                </div>
            </div>
        </div>
        <div class="my-3">
            <div class="form-group">
                <label for="birthdayInput" class="col-12 col-form-label"><strong>Email: *</strong></label>
                <div class="col-md-8">
                    <input type="email" class="form-control rounded-0 text-dark" id="emailInput" required value="{{$user['email']}}" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" title="Invalid email">
                </div>
                <p id="emailError" class="small col-12 text-primary mb-0"></p>
            </div>
            <div class="form-group">
                <label for="birthdayInput" class="col-12 col-form-label"><strong>Birthday: *</strong></label>
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <div class="input-group date" id="birthdayInput" data-target-input="nearest">
                            <input type="text" class="form-control text-dark datetimepicker-input" data-target="#birthdayInput" required value="{{$user['birthday']}}" pattern="(0[1-9]|1[0-2])/(0[1-9]|[1-2][0-9]|3[0-1])/[0-9]{4}" title="Invalid date format - mm/dd/yyy"/>
                            <div class="input-group-append" data-target="#birthdayInput" data-toggle="datetimepicker">
                                <div class="input-group-text rounded-0"><i class="far fa-calendar"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <p id="birthdayError" class="small col-12 text-primary mb-0"></p>
            </div>
            <div class="form-group">
                <label for="countryInput" class="col-12 col-form-label"><strong>Country:</strong></label>
                <div class="col-lg-3 col-md-4">
                    <select class="custom-select" id="countryInput">
                        <option value="-1">None</option>
                        @foreach ($countries as $country)
                            <option value="{{$country['id']}}" @if($user['local'] != null && strcmp($user['local']['country'], $country['name'])==0) selected @endif>{{$country['name']}}</option>
                        @endforeach
                    </select>
                </div>
                <p id="countryError" class="small col-12 text-primary mb-0"></p>
            </div>
            <div @if($user['local'] == null) 
                    {{-- class="d-none form-group"  --}}
                    class="form-group"  

                @else 
                    class="form-group"  
                @endif>
                <label for="cityInput" class="col-12 col-form-label"><strong>City:</strong></label>
                <div class="col-lg-3 col-md-4">
                    <select class="custom-select" id="cityInput">
                        <option value="-1">Choose...</option>
                        @foreach ($cities as $city)
                            <option data-country="{{$city['country_id']}}" value="{{$city['id']}}" @if($user['local'] != null && strcmp($user['local']['city'], $city['name']) == 0) selected @endif>{{$city['name']}}</option>
                        @endforeach
                    </select>
                </div>
                <p id="cityError" class="small col-12 text-primary mb-0"></p>
            </div>
            <div class="form-group">
                <label for="bioInput" class="col-12 col-form-label"><strong>Biography:</strong></label>
                <div class="col-md-8">
                    <textarea class="form-control rounded-0 text-dark" rows="5" id="bioInput" placeholder="Tell us about yourself...">@if(!is_null($user['bio'])){{$user['bio']}}@endif</textarea>
                </div>
            </div>
            <p id="bioError" class="small col-12 text-primary mb-0"></p>
            <div class="form-group">
                <label for="passInput" class="col-12 col-form-label"><strong>Your Password: *</strong></label>
                <div class="col-lg-3 col-md-4">
                    <input type="password" class="form-control rounded-0 text-dark" id="passInput" placeholder="Enter your password..." required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters">
                </div>
                <p id="passError" class="small col-12 text-primary mb-0"></p>
            </div>
            <div class="form-group">
                <label for="newPassInput" class="col-12 col-form-label"><strong>New Password:</strong></label>
                <div class="col-lg-3 col-md-4">
                    <input type="password" class="form-control rounded-0 text-dark" id="newPassInput" placeholder="Enter your new password..." pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters">
                </div>
                <p id="newPassError" class="small col-12 text-primary mb-0"></p>
            </div>
            <div class="form-group">
                <label for="confirmPassInput" class="col-12 col-form-label"><strong>Confirm Password:</strong></label>
                <div class="col-lg-3 col-md-4">
                    <input type="password" class="form-control rounded-0 text-dark" id="confirmPassInput" placeholder="Confirm your new password..." pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must contain at least one  number and one uppercase and lowercase letter, and at least 8 or more characters">
                </div>
                <p id="confirmPassError" class="small col-12 text-primary mb-0"></p>
            </div>
            <p class="small text-muted pl-3">* Required Input</p>

            <div class="d-flex flex-row justify-content-between col-md-10 px-3">
                <button id="submitBtn" type="submit" class="btn btn-primary">Submit</button>
                <button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#confirmDeletion">Delete Account</button>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="confirmDeletion" tabindex="-1" role="dialog" aria-labelledby="deleteConfirmation" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Are you sure you want to delete your account?</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        Once you delete this account all your posts will and comments will remain on the site but without your name attatched.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
                        <button id="confBtn" type="button" class="btn btn-primary">Confirm</button>
                    </div>
                </div>
            </div>
        </div>

    </form>
</main>

@endsection