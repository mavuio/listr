angular.module("listr").directive("listrContainer", function() {
  return {
    restrict: "EA",
    replace: true,
    transclude: true,
    scope: {
      src: '@',
      prefix: '@',
      query: '=',
      app: '='
    },
    link: function(scope, element, attrs, ctrl, transclude) {
      return transclude(scope, function(clone, scope) {
        return element.append(clone);
      });
    },
    controller: [
      "$scope", "$element", "$attrs", "$timeout", "$filter", "$http", "$q", "statemanager", function($scope, $element, $attrs, $timeout, $filter, $http, $q, statemanager) {
        var listrApiUrl;
        if (!$scope.query) {
          $scope.query = {};
        }
        if (!$scope.sortBy) {
          $scope.sortBy = {};
        }
        $scope.items = [];
        $scope.listStatus = 'empty';
        $scope.itemsPagination = {};
        listrApiUrl = $scope.src;
        $scope.listrArguments = $scope.$eval($attrs.listrArguments);
        if (!$scope.prefix) {
          $scope.prefix = 'listr';
        }
        $scope.switchPage = function(page) {
          return $scope.listrSubmitQuery(page);
        };
        $scope.toggleSort = function(fieldname) {
          var curval, newval;
          curval = $scope.sortBy[fieldname];
          if (curval === 'asc') {
            newval = 'desc';
          } else {
            newval = 'asc';
          }
          $scope.sortBy = {};
          $scope.sortBy[fieldname] = newval;
          return $scope.listrSubmitQuery();
        };
        $scope.getSortStatus = function(fieldname) {
          if ($scope.sortBy) {
            return $scope.sortBy[fieldname];
          }
        };
        $scope.listrSubmitQuery = function(page) {
          var newstate, queryStrParts;
          if (page == null) {
            page = 0;
          }
          if ($scope.listStatus === 'loading') {
            if (window.console && console.log) {
              console.log("list is already loading, please wait", null);
            }
            return;
          }
          if (window.console && console.log) {
            console.log("❖ listrSubmitQuery", page);
          }
          if (page > 0) {
            $scope.query.page = page;
          }
          queryStrParts = [];
          angular.forEach($scope.sortBy, function(direction, fieldname) {
            return queryStrParts.push("" + fieldname + ":" + direction);
          });
          $scope.query.sortby = queryStrParts.join(',');
          newstate = {
            query: $scope.query
          };
          return statemanager.setState(newstate);
        };
        $scope.refreshListing = function() {
          var page;
          if ($scope.listStatus === 'loading') {
            if (window.console && console.log) {
              console.log("list is already loading, please wait", null);
            }
            return;
          }
          $scope.listStatus = 'loading';
          if (!$scope.prefix) {
            $scope.prefix = 'listr';
          }
          if (window.console && console.log) {
            console.log("➜  refreshListing", $scope.prefix);
          }
          page = $scope.query.page;
          if (page < 0) {
            page = 1;
          }
          return $http.post(listrApiUrl, {
            action: 'getItems',
            listrArguments: $scope.listrArguments,
            query: $scope.query,
            page: page
          }).then(function(response) {
            $scope.items = response.data.items.data;
            $scope.itemsPagination = response.data.items.pagination;
            if ($scope.items.length === 0) {
              return $scope.listStatus = 'empty';
            } else {
              return $scope.listStatus = 'loaded';
            }
          });
        };
        return $scope.$watch((function() {
          return statemanager.get("query");
        }), (function(query) {
          if (window.console && console.log) {
            console.log("❖ listr-container: state-change detected", null);
          }
          $scope.query = angular.copy(query);
          $scope.sortBy = {};
          if ($scope.query.sortby) {
            angular.forEach($scope.query.sortby.split(' '), function(sortpart, num) {
              var sortpartSplitted;
              sortpartSplitted = sortpart.split(":");
              if (sortpartSplitted.length === 2) {
                return $scope.sortBy[sortpartSplitted[0]] = sortpartSplitted[1];
              }
            });
          }
          return $scope.refreshListing();
        }), true);
      }
    ]
  };
});

angular.module("listr").directive("paginate", function() {
  return {
    scope: {
      allItems: "=paginate",
      reloadItems: "&paginateReload"
    },
    template: "<div class=\"pagination-wrapper\"><ul class=\"pagination\" ng-show=\"totalPages > 1\">" + "  <li><a ng-click=\"prevPage()\">&lsaquo;</a></li>" + "  <li ng-repeat=\"page in pages\" ng-class=\"{'active':(page.nr==current_page)}\" >" + "<a ng-bind=\"page.nr\" ng-click=\"setPage(page.nr)\">1</a>" + "  </li>" + "  <li><a ng-click=\"nextPage()\">&rsaquo;</a></li>" + "</ul></div>",
    link: function(scope) {
      var addAfter, addBefore, pageChange, paginate;
      scope.nextPage = function() {
        if (scope.current_page < scope.totalPages) {
          scope.current_page++;
        }
      };
      scope.prevPage = function() {
        if (scope.current_page > 1) {
          scope.current_page--;
        }
      };
      scope.firstPage = function() {
        scope.current_page = 1;
      };
      scope.last_page = function() {
        scope.current_page = scope.totalPages;
      };
      scope.setPage = function(page) {
        scope.current_page = page;
      };
      addBefore = function() {
        var minPage, newval;
        if (scope.pagingBox.before.length > 1 && scope.pagingBox.before[1] === '..') {
          if (scope.pagingBox.before.length > 2) {
            minPage = scope.pagingBox.before[2];
          } else {
            minPage = scope.pagingBox.current;
          }
        } else {
          minPage = 1;
          scope.pagingBox.beforeIsFull = true;
        }
        if (minPage > 2) {
          newval = minPage - 1;
          scope.pagingBox.before.splice(2, 0, newval);
        }
        if (scope.pagingBox.before[1] === '..' && (scope.pagingBox.before[2] === 2 || scope.pagingBox.current === 2)) {
          return scope.pagingBox.before.splice(1, 1);
        }
      };
      addAfter = function() {
        var maxPage, newval;
        if (scope.pagingBox.after.length > 1 && scope.pagingBox.after[scope.pagingBox.after.length - 2] === '..') {
          if (scope.pagingBox.after.length > 2) {
            maxPage = scope.pagingBox.after[scope.pagingBox.after.length - 3];
          } else {
            maxPage = scope.pagingBox.current;
          }
        } else {
          maxPage = scope.totalPages;
          scope.pagingBox.afterIsFull = true;
        }
        if (maxPage < scope.totalPages - 1) {
          newval = maxPage + 1;
          scope.pagingBox.after.splice(scope.pagingBox.after.length - 2, 0, newval);
        }
        if (scope.pagingBox.after[scope.pagingBox.after.length - 2] === '..' && (scope.pagingBox.after[scope.pagingBox.after.length - 3] === scope.totalPages - 1 || scope.pagingBox.current === scope.totalPages - 1)) {
          return scope.pagingBox.after.splice(scope.pagingBox.after.length - 2, 1);
        }
      };
      paginate = function(results, oldResults) {
        var concattedPageArray, safeCounter;
        if (oldResults === results) {
          return;
        }
        scope.current_page = results.current_page;
        scope.total = results.total;
        scope.totalPages = results.last_page;
        scope.pages = [];
        scope.pagingBox = {
          before: [1, ".."],
          current: results.current_page,
          after: ["..", scope.totalPages],
          limit: 12
        };
        if (scope.pagingBox.current === 1) {
          scope.pagingBox.before = [];
        }
        if (scope.pagingBox.current === scope.totalPages) {
          scope.pagingBox.after = [];
        }
        safeCounter = 0;
        while (scope.pagingBox.before.length + 1 + scope.pagingBox.after.length < scope.pagingBox.limit) {
          safeCounter++;
          if (safeCounter % 2) {
            addBefore();
          } else {
            addAfter();
          }
          if (safeCounter > scope.pagingBox.limit * 2) {
            break;
          }
        }
        if (scope.pagingBox.before[1] === '..' && (scope.pagingBox.before[2] === 3)) {
          scope.pagingBox.before.splice(1, 1, 2);
        }
        if (scope.pagingBox.after[scope.pagingBox.after.length - 2] === '..' && (scope.pagingBox.after[scope.pagingBox.after.length - 3] === scope.totalPages - 2)) {
          scope.pagingBox.after.splice(scope.pagingBox.after.length - 2, 1, scope.totalPages - 1);
        }
        concattedPageArray = scope.pagingBox.before.concat(scope.pagingBox.current, scope.pagingBox.after);
        angular.forEach(concattedPageArray, function(value, key) {
          return scope.pages.push({
            nr: value
          });
        });
      };
      pageChange = function(newPage, last_page) {
        if (last_page == null) {
          return;
        }
        return scope.reloadItems({
          page: newPage
        });
      };
      scope.$watch("allItems", paginate);
      return scope.$watch("current_page", pageChange);
    }
  };
});

if (window.console && console.log) {
  console.log("drectvie defined", null);
}
