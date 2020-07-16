<section id="closed_reports"> 

    <div class="accordion">

        @foreach ($data["content"] as $report)

            <div class="card-header " id="heading{{$report['report_id']}}">

                <div class="d-flex justify-content-between align-items-center">
                    @if ($report['type'] == 'post')
                        <a href="/posts/{{$report['item']['id']}}"><h5 class="text-primary"><span class="font-weight-bold">Post:</span> {{$report['item']['title']}} </h5> </a>
                    @endif

                    <button class="btn btn-link" data-toggle="collapse" data-target="#collapse{{$report['report_id']}}" aria-expanded="false" aria-controls="collapse{{$report['report_id']}}">
                        <i class="fas fa-chevron-down"></i>
                    </button>

                </div>

                <?php
                $reasons = "";
                
                for ($i=0; $i < count($report["reasons"]); $i++) { 

                $reasons = $reasons . $report["reasons"][$i]; 

                if( $i == count($report["reasons"]) - 1)
                break;
                $reasons = $reasons .  ", ";
                }

                ?>

                <a href="/users/{{$report['item']['author']['id']}}" class="text-warning pr-2 ">{{$report['item']['author']['name']}}</a> <span class="text-muted">|</span> <span class="px-2 text-warning">{{\Carbon\Carbon::parse($report['item']['publication_date'])->format('F j, Y')}}</span><span class="text-muted">|</span> <span class="text-warning"> Reported for: {{$reasons}}</span>

            </div>

            <div id="collapse{{$report['report_id']}}" class="collapse"  data-parent=".accordion">
                <div class="card-body ">

                    <div class="d-flex my-2 align-items-center justify-content-between">

                        <div id="report_inbox_report_tags" class="d-flex align-items-center">
                            <span class="font-italic pr-2 text-muted">Tags: </span>

                            @foreach ( $report['item']['tags'] as $tag )
                                <a style="background-color: {{$tag['color']}};"  class="mx-1 d-inline-block text-monospace text-decoration-none small py-1 px-3 text-light" href="/tags/{{$tag['id']}}">{{ucfirst($tag['name'])}}</a>
                            @endforeach

                        </div>

                        <div class="d-flex " id="report_inbox_report_rating">

                            <p class="mb-0 pr-1">{{$report['item']['rating']['likes']}}</p>
                            <label class="form-check-label mr-3"><i class="fas fa-angle-up"></i></label>

                            <p class="mb-0 pr-1">{{$report['item']['rating']['dislikes']}}</p>
                            <label class="form-check-label mr-3"><i class="fas fa-angle-down"></i></label>

                        </div>
                    </div>

                    <div class="my-2">

                        <span class="font-italic pr-2 text-muted">Reported By: </span> <a href="users/{{$report['reporter']['user_id']}}"> {{$report['reporter']['name']}}</a>

                    </div>

                    <span class="font-italic pr-2 text-muted">Comment: </span> <span>{{$report['explanation']}} </span>

                </div>
            </div>
        @endforeach

        @foreach ($data["user"] as $report)

            <div class="card-header " id="heading{{$report['report_id']}}">

            <div class="d-flex justify-content-between align-items-center">
                @if ($report['type'] == 'user')
                    <a href="/users/{{$report['item']['id']}}"><h5 class="text-primary"><span class="font-weight-bold">User:</span> {{$report['item']['name']}} </h5> </a>
                @endif

                <button class="btn btn-link" data-toggle="collapse" data-target="#collapse{{$report['report_id']}}" aria-expanded="false" aria-controls="collapse{{$report['report_id']}}">
                    <i class="fas fa-chevron-down"></i>
                </button>

            </div>

            <?php
            $reasons = "";
            
            for ($i=0; $i < count($report["reasons"]); $i++) { 

            $reasons = $reasons . $report["reasons"][$i]; 

            if( $i == count($report["reasons"]) - 1)
            break;
            $reasons = $reasons .  ", ";
            }

            ?>

            <span class="text-warning"> Reported for: {{$reasons}}</span>

            </div>

            <div id="collapse{{$report['report_id']}}" class="collapse"  data-parent=".accordion">
            <div class="card-body ">

                <div class="my-2">

                    <span class="font-italic pr-2 text-muted">Reported By: </span> <a href="/users/{{$report['reporter']['user_id']}}"> {{$report['reporter']['name']}}</a>

                </div>

                <span class="font-italic pr-2 text-muted">Comment: </span> <span>{{$report['explanation']}} </span>

            </div>
            </div>
        @endforeach

        @foreach ($data["tag"] as $report)

        <div class="card-header " id="heading{{$report['report_id']}}">

            <div class="d-flex justify-content-between align-items-center">
                @if ($report['type'] == 'tag')
                    <a href="/tags/{{$report['item']['id']}}"><h5 class="text-primary"><span class="font-weight-bold">Tag:</span> {{ucfirst($report['item']['name'])}} </h5> </a>
                @endif

                <button class="btn btn-link" data-toggle="collapse" data-target="#collapse{{$report['report_id']}}" aria-expanded="false" aria-controls="collapse{{$report['report_id']}}">
                    <i class="fas fa-chevron-down"></i>
                </button>

            </div>

            <?php
            $reasons = "";
            
            for ($i=0; $i < count($report["reasons"]); $i++) { 

            $reasons = $reasons . $report["reasons"][$i]; 

            if( $i == count($report["reasons"]) - 1)
            break;
            $reasons = $reasons .  ", ";
            }

            ?>

            <span class="text-warning"> Reported for: {{$reasons}}</span>

        </div>

        <div id="collapse{{$report['report_id']}}" class="collapse"  data-parent=".accordion">
            <div class="card-body ">

                <div class="my-2">

                    <span class="font-italic pr-2 text-muted">Reported By: </span> <a href="/users/{{$report['reporter']['user_id']}}"> {{$report['reporter']['name']}}</a>

                </div>

                <span class="font-italic pr-2 text-muted">Comment: </span> <span>{{$report['explanation']}} </span>

            </div>
        </div>
        @endforeach
    </div>


    <nav class="my-4 report_inbox_closed_nav" aria-label="Posts navigation .inbox_closed">
        @if (isset($paginator))
            {{$paginator->links('pagination.links')}}
        @endif
    </nav>
</section>