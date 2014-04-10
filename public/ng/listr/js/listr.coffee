
listrapp = angular.module "listr", ['werkzeugh-statemanager']



angular.module("listr").controller 'ListrController', ['$scope', '$location', '$http', '$filter', '$sce', '$timeout',
($scope, $location, $http, $filter, $sce, $timeout) ->
  $scope.app = {}

]
