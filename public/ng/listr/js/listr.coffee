
listrapp = angular.module "listr", ['werkzeugh-statemanager']



angular.module("listr").controller 'ListrController', ['$scope', '$location', '$http', '$filter', '$sce', '$timeout',
($scope, $location, $http, $filter, $sce, $timeout) ->
  $scope.app = {}

]

angular.module("listr").config [ '$locationProvider', ($locationProvider) ->

   # Note: Setting html5Mode to true seems to cause problems in browsers that doesn't support it, even though it's supposed to just ignore it and use the default mode. So it might be a good idea to check for support before turning it on, for example by checking Modernizr.history.

          $locationProvider.html5Mode false
    ]
