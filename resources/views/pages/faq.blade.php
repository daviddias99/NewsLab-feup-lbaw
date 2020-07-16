@extends('layouts.app')

@section('title', 'NewsLab - FAQ')

@section('content')
<main  class="container flex-grow-1">
  <h1 class="mt-5">FAQ</h1>
  <hr>
  <div class="accordion" id="accordionExample">
      <div class="card">
          <div class="card-header" id="headingOne">
              <h2 class="mb-0">
                  <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                      Why is this site named NewsLab?
                  </button>
              </h2>
          </div>

          <div id="collapseOne" class="collapse hide" aria-labelledby="headingOne">
              <div class="card-body ml-3">
                 The name of the site came to be because the development team uses GitLab and the project was about a news site. News + Lab, NewsLab. :)
              </div>
          </div>
      </div>
      <div class="card">
          <div class="card-header" id="headingTwo">
              <h2 class="mb-0">
                  <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                      Who made this website?
                  </button>
              </h2>
          </div>
          <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo">
          <div class="card-body ml-3">
                  We made this website as a project for LBAW class. You can find more about our team in the <a href="/about">about page</a>.
              </div>
          </div>
      </div>
      <div class="card">
          <div class="card-header" id="headingThree">
              <h2 class="mb-0">
                  <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                      What if someone has abusive behaviour?
                  </button>
              </h2>
          </div>
          <div id="collapseThree" class="collapse" aria-labelledby="headingThree">
          <div class="card-body ml-3">
                  Our site is constantly monitored by admins from all over the world. They analyse reports made by users and issue the correct responses. If you violate our terms of
                  conduct, you may be banned for a determined ammount of time (maybe forever).
              </div>
          </div>


      </div>
      <div class="card">
          <div class="card-header" id="headingFour">
              <h2 class="mb-0">
                  <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                      What happens if I get banned?
                  </button>
              </h2>
          </div>
          <div id="collapseFour" class="collapse" aria-labelledby="headingFour">
              <div class="card-body ml-3">
                  If you get banned you loose your active participation in the community. You can't post, like or comment and you loose all your badges.
              </div>
          </div>


      </div>
      <div class="card">
          <div class="card-header" id="headingFive">
              <h2 class="mb-0">
                  <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                      What is that green tick?
                  </button>
              </h2>
          </div>
          <div id="collapseFive" class="collapse" aria-labelledby="headingFive">
          <div class="card-body ml-3">
                  There are certain achievements that you can get by completing certain tasks (<a href="./profile.php">See badges section of your profile.</a>). When you collect all achievements
                  you get accepted into the green tick square, and you get several benefits such as increased respect by the community and increased weight of your ratings.
              </div>
          </div>


      </div>
  </div>
</main>

@endsection
