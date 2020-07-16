<a id="show-sidebar" class="btn btn-sm btn-primary" href="#">
    <svg height="1.25rem" viewBox="-4 0 393 393.99003" width="1.25rem">
        <g stroke="white">
            <path stroke-width="8" fill="white" d="m368.3125 0h-351.261719c-6.195312-.0117188-11.875 3.449219-14.707031 8.960938-2.871094 5.585937-2.3671875 12.3125 1.300781 17.414062l128.6875 181.28125c.042969.0625.089844.121094.132813.183594 4.675781 6.3125 7.203125 13.957031 7.21875 21.816406v147.796875c-.027344 4.378906 1.691406 8.582031 4.777344 11.6875 3.085937 3.105469 7.28125 4.847656 11.65625 4.847656 2.226562 0 4.425781-.445312 6.480468-1.296875l72.3125-27.574218c6.480469-1.976563 10.78125-8.089844 10.78125-15.453126v-120.007812c.011719-7.855469 2.542969-15.503906 7.214844-21.816406.042969-.0625.089844-.121094.132812-.183594l128.683594-181.289062c3.667969-5.097657 4.171875-11.820313 1.300782-17.40625-2.832032-5.511719-8.511719-8.9726568-14.710938-8.960938zm-131.53125 195.992188c-7.1875 9.753906-11.074219 21.546874-11.097656 33.664062v117.578125l-66 25.164063v-142.742188c-.023438-12.117188-3.910156-23.910156-11.101563-33.664062l-124.933593-175.992188h338.070312zm0 0" />
        </g>
    </svg>
</a>

<aside id="sidebar" class="sidebar-wrapper fixed-top h-100 border-right bg-white px-4 pt-3 pb-5">
    <button id="close-sidebar" type="button" class="close" data-toggle="collapse" data-target="#sidebar-wrapper" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    <h3 class="border-bottom pb-2">
        <svg height="1.25rem" viewBox="-4 0 393 393.99003" width="1.25rem">
            <g stroke="black">
                <path stroke-width="8" fill="black" d="m368.3125 0h-351.261719c-6.195312-.0117188-11.875 3.449219-14.707031 8.960938-2.871094 5.585937-2.3671875 12.3125 1.300781 17.414062l128.6875 181.28125c.042969.0625.089844.121094.132813.183594 4.675781 6.3125 7.203125 13.957031 7.21875 21.816406v147.796875c-.027344 4.378906 1.691406 8.582031 4.777344 11.6875 3.085937 3.105469 7.28125 4.847656 11.65625 4.847656 2.226562 0 4.425781-.445312 6.480468-1.296875l72.3125-27.574218c6.480469-1.976563 10.78125-8.089844 10.78125-15.453126v-120.007812c.011719-7.855469 2.542969-15.503906 7.214844-21.816406.042969-.0625.089844-.121094.132812-.183594l128.683594-181.289062c3.667969-5.097657 4.171875-11.820313 1.300782-17.40625-2.832032-5.511719-8.511719-8.9726568-14.710938-8.960938zm-131.53125 195.992188c-7.1875 9.753906-11.074219 21.546874-11.097656 33.664062v117.578125l-66 25.164063v-142.742188c-.023438-12.117188-3.910156-23.910156-11.101563-33.664062l-124.933593-175.992188h338.070312zm0 0" />
            </g>
        </svg>
        Filter
    </h3>
    <fieldset id="category" class="mb-3">
        <legend>Categories:</legend>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="news" id="newsCheckBox">
            <label class="form-check-label" for="newsCheckBox">News</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="opinions" id="opinionsCheckBox">
            <label class="form-check-label" for="opinionsCheckBox">Opinions</label>
        </div>
        @if($full)
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="authors" id="authorsCheckBox">
            <label class="form-check-label" for="authorsCheckBox">Authors</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="tags" id="tagsCheckBox">
            <label class="form-check-label" for="tagsCheckBox">Tags</label>
        </div>
        @endif
    </fieldset>

    <fieldset id="author" class="mb-3">
        <legend>Author:</legend>
        @if(Auth::check())
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="subscribed" id="subscribedCheckBox">
            <label class="form-check-label" for="subscribedCheckBox">Subscribed</label>
        </div>
        @endif
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="verified" id="verifiedCheckBox">
            <label class="form-check-label" for="verifiedCheckBox">Verified</label>
        </div>
    </fieldset>

    <fieldset class="mb-3">
        <legend>Date:</legend>
        <div class="form-group col-md-10">
            <label class="col-form-label" for="fromDate">From</label>
            <div id="fromFilter" data-target-input="nearest">
                <input type="text" class="form-control datetimepicker-input" id="fromDate" data-toggle="datetimepicker" data-target="#fromFilter"  placeholder="Start..." pattern="(0[1-9]|1[0-2])/(0[1-9]|[1-2][0-9]|3[0-1])/[0-9]{4}" title="Invalid date format - mm/dd/yyy"/>
            </div>
            <p id="fromError" class="small text-primary"></p>
        </div>
        <div class="form-group col-md-10">
            <label class="col-form-label" for="toDate">To</label>
            <div id="toFilter" data-target-input="nearest">
                <input type="text" class="form-control datetimepicker-input" id="toDate" data-toggle="datetimepicker" data-target="#toFilter"  placeholder="End..." pattern="(0[1-9]|1[0-2])/(0[1-9]|[1-2][0-9]|3[0-1])/[0-9]{4}" title="Invalid date format - mm/dd/yyy"/>
            </div>
            <p id="toError" class="small text-primary"></p>
        </div>
    </fieldset>

    <fieldset class="mb-4">
        <legend>Likes:</legend>
            <div class="row">
                <div class="form-group col-6">
                    <label class="col-form-label" for="minLikes">Minimum:</label>
                    <input type="number" class="custom-range" id="minLikes">
                </div>
                <div class="form-group col-6">
                    <label class="col-form-label" for="maxLikes">Maximum:</label>
                    <input type="number" class="custom-range" id="maxLikes">
                </div>
            </div>
            <div id="likes-slider"></div>
            <p id="likesError" class="small text-primary"></p>
    </fieldset>
    <hr>
    <fieldset id="sort" class="mb-3">
        <legend>Sort</legend>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="sort" id="sortAlpha" value="alpha">
            <label class="form-check-label" for="sortAlpha">Alphabetical</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="sort" id="sortNumer" value="numerical">
            <label class="form-check-label" for="sortAlpha">Most Liked/Subs</label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="sort" id="sortRecent" value="recent">
            <label class="form-check-label" for="sortRecent">Recent</label>
        </div>
    </fieldset>
</aside>