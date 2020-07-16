<div class="modal fade" id="searchAdmin" tabindex="-1" role="dialog" aria-labelledby="searchAdminLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="container">
                    <div class="row">
                        <h5 class="modal-title" id="searchAdminLabel">Add new admin</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="row my-3">
                        <div class="input-group input-group-md">

                            <input type="text" class="form-control input-sm" id="search-users" placeholder="User name..." pattern="[a-zA-Z ]{3,25}" title="Name should contain between 3 and 25 letters and spaces">
                            <span class="input-group-btn px-1">
                                <button class="btn btn-primary" id="admin_search_button" type="submit">Search</button>
                            </span>
                        </div><!-- /input-group -->
                        <p id="nameError" class="small text-primary"></p>
                    </div>

                </div>

            </div>
            <div class="modal-body">
                <div class="row with-margin">
                    <div id="new_admin_list_placeholder" class="col-sm-12">

                        @include('partials.admin_add_new_modal_body',['$users' => $users])

                    </div><!-- /.col-lg-6 -->
                </div><!-- /.row -->
            </div>
        </div>
    </div>
</div>