<div class="my-3">
    <div class="card border-0 text-center mx-auto" style="width: 10rem @if (! $hasBadge) ; opacity: 40% @endif">
        <div class="card-header bg-white">
            <i class='{{$badge->icon . ' fa-3x'}}'></i>
        </div>
        <div class="card-body px-0">
            <h5 class="card-title font-weight-bold">{{$badge->name}}</h5>
            <p class="card-text">{{$badge->description}}</p>
        </div>
    </div>
</div>