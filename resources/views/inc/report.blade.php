<div class="modal fade" id="report-modal" tabindex="-1" role="dialog" aria-labelledby="report-modal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content p-sm-0 p-md-3">
            <div class="modal-header">
                <h5 class="modal-title">Why are you reporting this user?</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form  method="get">
                <input id="reported-id" class="d-none" type="number">
                <fieldset>
                    <legend class="col-form-label"><strong>Reason *</strong></legend>
                    <div class="row">
                        <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="abusiveLang">
                        <label class="form-check-label" for="abusiveLang">Abusive Language</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="2" id="fakeNews">
                        <label class="form-check-label" for="fakeNews">Fake News</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="5" id="clickbait">
                        <label class="form-check-label" for="clickbait">Clickbait</label>
                    </div>
                    </div>
                    <div class="col-md-6">

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="3" id="hateSpeech">
                        <label class="form-check-label" for="hateSpeech">Hate Speech</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="4" id="ads">
                        <label class="form-check-label" for="ads">Advertisement</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="6" id="other">
                        <label class="form-check-label" for="other">Other</label>
                    </div>
                    </div>
                    </div>
                    <div class="form-group mt-2 mb-0">
                        <label for="rep-reason"><strong>Comment *</strong></label>
                        <textarea class="form-control" id="rep-reason" rows="2" placeholder="Please detail your reasoning..." required></textarea>
                    </div>
                </fieldset>
                <p class="small text-muted mb-0">* Required Input</p>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Send</button>
            </div>
        </div>
    </div>
</div>