
listrapp = angular.module "listr", ['werkzeugh-statemanager','angularModalService']


listrapp.config ['$controllerProvider',($controllerProvider)->
  listrapp.registerController=$controllerProvider.register
]



angular.module("listr").controller 'ListrBaseController', ['$scope', '$location', '$http', '$filter', '$q', '$timeout','ModalService',
($scope, $location, $http, $filter, $q, $timeout, ModalService) ->
  $scope.app = {}
  console.log "listr base init" , null  if window.console and console.log

  $scope.app.loadController=(name,path)->
    defered=$q.defer()
    require [path], ->
      defered.resolve()

    return defered.promise


  $scope.app.callInParentFrame=(functionName, data)->
    parentWindow=window.parent
    console.log "pw" , parentWindow  if window.console and console.log
    promise=parentWindow.callAngularFunction(functionName,data)


  $scope.app.showModal=(args)->

    controllerName='ListrItemEditController';

    $scope.app.loadController(controllerName,args.controller).then ->
        ModalService.showModal(
          templateUrl: args.template
          controller: controllerName
          inputs: 
            app: $scope.app
        ).then (modal) ->
          console.log "show modal running" , null  if window.console and console.log
          #it's a bootstrap element, use 'modal' to show it
          modal.element.modal()
          modal.close.then (result) ->
            console.log "modal closed with", result
            return
          return
  console.log "scope.app" , $scope.app  if window.console and console.log

]

angular.module("listr").config [ '$locationProvider', ($locationProvider) ->

   # Note: Setting html5Mode to true seems to cause problems in browsers that doesn't support it, even though it's supposed to just ignore it and use the default mode. So it might be a good idea to check for support before turning it on, for example by checking Modernizr.history.

          $locationProvider.html5Mode false
    ]


angular.module("listr").filter "htmlToPlaintext", ->
  (text) ->
    if text
      return String(text).replace /<[^>]+>/g, ""

    return ""
