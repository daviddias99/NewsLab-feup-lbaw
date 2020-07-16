<!-- Report inbox tab -->
<div class="tab-pane fade" id="v-pills-report" role="tabpanel" aria-labelledby="v-pills-report-tab">
    <h1>Report Inbox</h1>
    <hr class="my-4">

    <nav>
        <ul class="nav nav-tabs" id="nav-tab" role="tablist">
            <li class="nav-item">
                <a class="nav-item nav-link active" id="nav-rp-open-tab" data-toggle="tab" href="#nav-rp-open" role="tab" aria-controls="nav-rp-open" aria-selected="true">Open</a>
            </li>
            <li class="nav-item">
                <a class="nav-item nav-link" id="nav-rp-closed-tab" data-toggle="tab" href="#nav-rp-closed" role="tab" aria-controls="nav-rp-closed" aria-selected="false">Closed</a>
            </li>
        </ul>
    </nav>
    <div class="tab-content" id="nav-tabContent">
        <div class="tab-pane fade show active" id="nav-rp-open" role="tabpanel" aria-labelledby="nav-rp-open-tab">
            
            @include('partials.admin_report_inbox_open', $open)

        </div>
        <div class="tab-pane fade" id="nav-rp-closed" role="tabpanel" aria-labelledby="nav-rp-closed-tab">
            @include('partials.admin_report_inbox_closed', $closed)
        </div>
    </div>

</div>