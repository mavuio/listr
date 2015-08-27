var listrapp;

listrapp = angular.module("listr", ['werkzeugh-statemanager', 'angularModalService']);

listrapp.config([
  '$controllerProvider', function($controllerProvider) {
    return listrapp.registerController = $controllerProvider.register;
  }
]);

angular.module("listr").controller('ListrBaseController', [
  '$scope', '$location', '$http', '$filter', '$q', '$timeout', 'ModalService', function($scope, $location, $http, $filter, $q, $timeout, ModalService) {
    $scope.app = {};
    if (window.console && console.log) {
      console.log("listr base init", null);
    }
    $scope.app.loadController = function(name, path) {
      var defered;
      defered = $q.defer();
      require([path], function() {
        return defered.resolve();
      });
      return defered.promise;
    };
    $scope.app.callInParentFrame = function(functionName, data) {
      var parentWindow, promise;
      parentWindow = window.parent;
      if (window.console && console.log) {
        console.log("pw", parentWindow);
      }
      return promise = parentWindow.callAngularFunction(functionName, data);
    };
    $scope.app.showModal = function(args) {
      var controllerName;
      controllerName = 'ListrItemEditController';
      return $scope.app.loadController(controllerName, args.controller).then(function() {
        return ModalService.showModal({
          templateUrl: args.template,
          controller: controllerName,
          inputs: {
            app: $scope.app
          }
        }).then(function(modal) {
          if (window.console && console.log) {
            console.log("show modal running", null);
          }
          modal.element.modal();
          modal.close.then(function(result) {
            console.log("modal closed with", result);
          });
        });
      });
    };
    if (window.console && console.log) {
      return console.log("scope.app", $scope.app);
    }
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
