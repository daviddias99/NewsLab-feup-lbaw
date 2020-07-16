<div class="modal fade" id="ban-modal" tabindex="-1" role="dialog" aria-labelledby="ban-modal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content p-sm-0 p-md-1">
            <div class="modal-header">
                <h5 class="modal-title">For how long do you wish to ban this user?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body mt-3 md-5">
                <div class="row">
                    <div class="input-group date col-md-8" id="banTime" data-target-input="nearest">
                        <input id="ban-calendar-input" type="text" class="form-control datetimepicker-input rounded-0" data-target="#banTime" pattern="(0[1-9]|1[0-2])/(0[1-9]|[1-2][0-9]|3[0-1])/[0-9]{4}" title="Invalid date format - mm/dd/yyy"/>
                        <div class="input-group-append"  data-target="#banTime" data-toggle="datetimepicker">
                            <div class="input-group-text rounded-0"><i class="fa fa-calendar"></i></div>
                        </div>
                    </div>

                    <div class="col-md-4 d-flex align-items-center">
                        <input type="checkbox" id="permaban" name="permaban" value="permaban">
                        <label for="permaban" class="ml-2 mb-0"> Permanently</label>
                    </div>
                </div>
                <p id="dateError" class="small text-primary mb-0"></p>
                <div class="modal-footer mt-3 d-flex ">
                    <button type="submit" id="ban_btn" data-href="/api/users/{{$user['id']}}/ban"
                        class="btn btn-primary">Ban</button>
                </div>
            </div>
        </div>
    </div>
</div>