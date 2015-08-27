define(function() {
  return angular.module('listr').registerController('ListrItemEditController', function($scope, app, close) {
    if (window.console && console.log) {
      console.log("ListrItemEditController inited2", null);
    }
    $scope.title = "add Item";
    $scope.app = app;
    if (window.console && console.log) {
      console.log("app:", app);
    }
    return $scope.close = function(result) {
      if (window.console && console.log) {
        console.log("closed my stuff", null);
      }
      return close(result, 500);
    };
  });
});
