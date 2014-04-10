
angular.module("listr").directive "listrContainer", ->
  restrict: "EA"
  replace: true
  template: '<div></div>'
  scope:
    src: '@'
    prefix: '@'

  link: (scope, element, attrs) ->
    console.log "listr container init" , null  if window.console and console.log

  controller: ["$scope", "$element", "$attrs", "$timeout", "$filter", "$http", "$q","statemanager", ($scope, $element, $attrs, $timeout, $filter, $http, $q, statemanager) ->

    $scope.query = {}
    $scope.items = {}
    listrApiUrl=$scope.src+'/listr'

    $scope.prefix='listr' unless $scope.prefix


    $scope.refreshListing = () ->
      console.log "➜  refreshListing"   if window.console and console.log

      page=$scope.query.page
      page=1 if page < 0

      $http.post listrApiUrl,
          action : 'get_items'
          prefix : $scope.prefix
          query  : $scope.query
          page   : page
        , (response) ->
          $scope.items = response.items.data
          $scope.allItems = response.items
          $scope.listmode = 'loaded'
        , (error) ->
          console.log "error:" , error  if window.console and console.log
          $scope.items = []


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


