<div class="modal fade" id="fade">
  <div class="modal-dialog">
    <div class="modal-content">
      <form  ng-submit="app.submitEditForm(close)">
        <div class="modal-header">
          <a href="javascript:void(0)" class="fa fa-times pull-right close" ng-click="close('cancel')"  aria-hidden="true"></a>
          <h4 class="modal-title"><i class='fa fa-lg @{{app.modalIcon}}'></i> @{{app.modalTitle}}</h4>
        </div>
        <div class="modal-body">


          <div class="formsection">
            @foreach ($ctrl->FormItems() as $fi)
            {{$fi->html()}}
            @endforeach
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" ng-disabled="app.editForm.loading" ng-click="close(false)" class="btn btn-default" ><i class="fa fa-times"></i> Cancel</button>
          <button ng-disabled="app.editForm.loading" type="submit" class="btn btn-primary" ><i class="fa fa-check"></i> OK</button>
        </div>
      </form>
    </div>
  </div>
</div>
