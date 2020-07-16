                <!-- Banned Users list -->
                <div class="tab-pane fade" id="v-pills-banned_users" role="tabpanel" aria-labelledby="v-pills-banned_users-tab">

                    <h1>Banned Users</h1>
                    <hr class="my-4">
                    <div id="banned_users_list"> 
                        @include('partials.admin_banned_users_table', [
                            'data' => $data,
                            'emptyMessage' => 'There are no banned users.',
                            'paginator' => $paginator
                        ])
                    </div>
                </div>