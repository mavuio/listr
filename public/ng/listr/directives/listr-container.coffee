
angular.module("listr").directive "listrContainer", ->
  restrict: "EA"
  replace: true
  transclude: true
  scope:
    src: '@'
    prefix: '@'

  link: (scope, element, attrs, ctrl, transclude) ->
    transclude scope, (clone, scope) ->
      element.append(clone)

  controller: ["$scope", "$element", "$attrs", "$timeout", "$filter", "$http", "$q","statemanager", ($scope, $element, $attrs, $timeout, $filter, $http, $q, statemanager) ->

    $scope.query = {}
    $scope.items = [{aaa:'test'}]
    listrApiUrl=$scope.src

    $scope.prefix='listr' unless $scope.prefix


    $scope.refreshListing = () ->
      console.log "➜  refreshListing"   if window.console and console.log

      page=$scope.query.page
      page=1 if page < 0

      $http.post(listrApiUrl ,
          action : 'getItems'
          prefix : $scope.prefix
          query  : $scope.query
          page   : page
      ).then (response) ->
          console.log "rr" , response  if window.console and console.log
          $scope.items = response.data.items
          # $scope.listmode = 'loaded'

    # watch state-changes, state changes on reload, back, url-modification
    $scope.$watch (->
      statemanager.get "query"
    ), ((query) ->
      console.log "listr-container: state-change detected" , null  if window.console and console.log
      # $scope.refreshListing()
    ), true

    $scope.refreshListing()


  ]



console.log "drectvie defined" , null  if window.console and console.log


