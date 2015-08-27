define ->
  angular.module('listr').registerController 'ListrItemEditController', ($scope, app, close)->
    console.log "ListrItemEditController inited2" , null  if window.console and console.log

    $scope.title="add Item"
    $scope.app=app
    console.log "app:" , app  if window.console and console.log

    $scope.close = (result)->
      console.log "closed my stuff" , null  if window.console and console.log
      close result, 500
