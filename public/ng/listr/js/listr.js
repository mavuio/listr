var listrapp;

listrapp = angular.module("listr", ['werkzeugh-statemanager']);

angular.module("listr").controller('ListrController', [
  '$scope', '$location', '$http', '$filter', '$sce', '$timeout', function($scope, $location, $http, $filter, $sce, $timeout) {
    return $scope.app = {};
  }
]);

angular.module("listr").config([
  '$locationProvider', function($locationProvider) {
    return $locationProvider.html5Mode(false);
  }
]);

angular.module("listr").filter("htmlToPlaintext", function() {
  return function(text) {
    if (text) {
      return String(text).replace(/<[^>]+>/g, "");
    }
    return "";
  };
});
