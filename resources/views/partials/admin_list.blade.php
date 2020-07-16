                <!-- Admin List Tab -->
                <div class="tab-pane fade" id="v-pills-admin_list" role="tabpanel" aria-labelledby="v-pills-admin_list-tab">

                    <h1>Administrator List</h1>
                    <hr class="my-4">
                    <div id="admin_list">
                        @include('partials.admin_list_table', [
                            'data' => $data,
                            'emptyMessage' => 'There are no admins',
                            'paginator' => $paginator
                        ])
        
                    </div>
                    
                    @include('partials.admin_add_new_modal')
                </div>
