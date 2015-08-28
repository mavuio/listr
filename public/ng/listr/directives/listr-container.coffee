
angular.module("listr").directive "listrContainer", ->
  restrict: "EA"
  replace: true
  transclude: true
  scope:
    src: '@'
    prefix: '@'
    query: '='
    app: '='

  link: (scope, element, attrs, ctrl, transclude) ->
    transclude scope, (clone, scope) ->
      element.append(clone)

  controller: ["$scope", "$element", "$attrs", "$timeout", "$filter", "$http", "$q","statemanager", ($scope, $element, $attrs, $timeout, $filter, $http, $q, statemanager) ->

    if !$scope.query
      $scope.query = {}

    if !$scope.sortBy
      $scope.sortBy = {}
    
    if !$scope.app.callInParentFrame
      $scope.app.callInParentFrame=(functionName, data)->
        console.log "called callInParentFrame" , null  if window.console and console.log
        topWindow=window.parent
        promise=topWindow.callAngularFunction(functionName,data)


    $scope.items = []
    $scope.listStatus='empty'
    $scope.itemsPagination = {}
    listrApiUrl=$scope.src
    $scope.listrArguments=$scope.$eval $attrs.listrArguments

    $scope.prefix='listr' unless $scope.prefix

    $scope.switchPage = (page) ->
      $scope.listrSubmitQuery(page)

    $scope.toggleSort= (fieldname) ->
   
        
      curval=$scope.sortBy[fieldname]
      if curval=='asc' 
        newval='desc'
      else
        newval='asc'
      $scope.sortBy={}
      $scope.sortBy[fieldname]=newval
      $scope.listrSubmitQuery()
         
    
    $scope.getSortStatus= (fieldname) ->
      if $scope.sortBy
        $scope.sortBy[fieldname]
        
       

    $scope.listrSubmitQuery = (page=0) ->
      if $scope.listStatus is 'loading'
        console.log "list is already loading, please wait" , null  if window.console and console.log
        return

      console.log "❖ listrSubmitQuery" , page  if window.console and console.log

      if page > 0
        $scope.query.page=page

      #compact sortBy
      queryStrParts=[]
      angular.forEach $scope.sortBy,(direction, fieldname)->
        queryStrParts.push "#{fieldname}:#{direction}"
      
      $scope.query.sortby=queryStrParts.join(',')

      newstate=query: $scope.query
      # newstate.hash={} if $scope.app.currentExpandedItem
      statemanager.setState newstate

    $scope.refreshListing = () ->
      if $scope.listStatus is 'loading'
        console.log "list is already loading, please wait" , null  if window.console and console.log
        return
      $scope.listStatus='loading'
      $scope.prefix='listr' unless $scope.prefix

      console.log "➜  refreshListing" ,$scope.prefix  if window.console and console.log

      page=$scope.query.page
      page=1 if page < 0

      $http.post(listrApiUrl ,
          action : 'getItems'
          listrArguments : $scope.listrArguments
          query  : $scope.query
          page   : page
      ).then (response) ->
          $scope.items = response.data.items.data
          $scope.itemsPagination = response.data.items.pagination
          if $scope.items.length is 0
            $scope.listStatus = 'empty'
          else
            $scope.listStatus = 'loaded'

    # watch state-changes, state changes on reload, back, url-modification
    $scope.$watch (->
      statemanager.get "query"
    ), ((query) ->
      console.log "❖ listr-container: state-change detected" , null  if window.console and console.log
      $scope.query = angular.copy(query)
      
      $scope.sortBy={}
      if $scope.query.sortby
        angular.forEach $scope.query.sortby.split(' '),(sortpart, num)->
          sortpartSplitted=sortpart.split(":")
          if sortpartSplitted.length is 2
            $scope.sortBy[sortpartSplitted[0]]=sortpartSplitted[1]
        
      
      $scope.refreshListing()
    ), true


    # $scope.refreshListing()


  ]

angular.module("listr").directive "paginate", ->
      scope:
        allItems: "=paginate"
        reloadItems: "&paginateReload"

      template: "<div class=\"pagination-wrapper\"><ul class=\"pagination\" ng-show=\"totalPages > 1\">" +  "  <li><a ng-click=\"prevPage()\">&lsaquo;</a></li>" + "  <li ng-repeat=\"page in pages\" ng-class=\"{'active':(page.nr==current_page)}\" >" + "<a ng-bind=\"page.nr\" ng-click=\"setPage(page.nr)\">1</a>" + "  </li>" + "  <li><a ng-click=\"nextPage()\">&rsaquo;</a></li>" + "</ul></div>"
      #template: "pb:<pre>{{pagingBox |json}}</pre>"
      link: (scope) ->
        scope.nextPage = ->
          scope.current_page++  if scope.current_page < scope.totalPages
          return

        scope.prevPage = ->
          scope.current_page--  if scope.current_page > 1
          return

        scope.firstPage = ->
          scope.current_page = 1
          return

        scope.last_page = ->
          scope.current_page = scope.totalPages
          return

        scope.setPage = (page) ->
          scope.current_page = page
          return

        addBefore = ->

          if scope.pagingBox.before.length>1 and scope.pagingBox.before[1] is '..'
            # '..' is present:
            if scope.pagingBox.before.length>2
              minPage=scope.pagingBox.before[2] # get page after "..", if ".." is present
            else
              minPage=scope.pagingBox.current
          else
            minPage=1
            scope.pagingBox.beforeIsFull=true

          if minPage > 2
            newval=minPage-1
            scope.pagingBox.before.splice(2, 0, newval)
            # console.log "added #"+newval+" before" , null  if window.console and console.log

          if (scope.pagingBox.before[1] is '..' and (scope.pagingBox.before[2] is 2 or scope.pagingBox.current is 2))
            scope.pagingBox.before.splice(1, 1) #remove '..' if not needed anymore
            # console.log "remove .. before" , null  if window.console and console.log



        addAfter = ->

          if scope.pagingBox.after.length>1 and scope.pagingBox.after[scope.pagingBox.after.length-2] is '..'
            # '..' is present:
            if scope.pagingBox.after.length>2
              maxPage=scope.pagingBox.after[scope.pagingBox.after.length-3] # get page after "..", if ".." is present
            else
              maxPage=scope.pagingBox.current
          else
            maxPage=scope.totalPages
            scope.pagingBox.afterIsFull=true

          if maxPage < scope.totalPages - 1
            newval=maxPage+1
            scope.pagingBox.after.splice(scope.pagingBox.after.length-2, 0, newval)
            # console.log "added #"+newval+" after" , null  if window.console and console.log

          if scope.pagingBox.after[scope.pagingBox.after.length-2] is '..' and (scope.pagingBox.after[scope.pagingBox.after.length-3] is scope.totalPages - 1 or scope.pagingBox.current is scope.totalPages - 1)
            # console.log "remove .." , null  if window.console and console.log
            scope.pagingBox.after.splice(scope.pagingBox.after.length-2, 1) #remove '..' if not needed anymore


        paginate = (results, oldResults) ->

          return  if oldResults is results

          scope.current_page = results.current_page
          scope.total = results.total
          scope.totalPages = results.last_page
          scope.pages = []

          scope.pagingBox=
            before:[1,".."]
            current:results.current_page
            after:["..",scope.totalPages]
            limit:12

          if scope.pagingBox.current is 1
            scope.pagingBox.before=[]

          if scope.pagingBox.current is scope.totalPages
            scope.pagingBox.after=[]

          # build paginglist
          safeCounter=0

          while scope.pagingBox.before.length+1+scope.pagingBox.after.length < scope.pagingBox.limit
            safeCounter++
            # console.log safeCounter+": "+(scope.pagingBox.before.length+1+scope.pagingBox.after.length) , null  if window.console and console.log
            if safeCounter % 2
              addBefore()
            else
              addAfter()
            break if safeCounter>scope.pagingBox.limit*2


          # fix 1 .. 3 4 5
          if scope.pagingBox.before[1] is '..' and (scope.pagingBox.before[2] is 3)
            scope.pagingBox.before.splice(1, 1, 2) #remove '..' if not needed anymore, and add "2" instead
            # console.log "remove .., added 2 before" , null  if window.console and console.log


          # fix 98 .. 100
          if scope.pagingBox.after[scope.pagingBox.after.length-2] is '..' and (scope.pagingBox.after[scope.pagingBox.after.length-3] is scope.totalPages - 2)
            scope.pagingBox.after.splice(scope.pagingBox.after.length-2, 1, scope.totalPages - 1) #remove '..' if not needed anymore, and add "2" instead
            # console.log "remove .., added totalpage -1 before" , null  if window.console and console.log

          #recombine

          concattedPageArray=scope.pagingBox.before.concat(scope.pagingBox.current, scope.pagingBox.after)
          angular.forEach concattedPageArray, (value, key) ->
            # console.log "concatme" , null  if window.console and console.log
            scope.pages.push {nr:value}
          return

        pageChange = (newPage, last_page) ->
          return  unless last_page?
          # console.log "pageChange" , newPage, last_page  if window.console and console.log
          scope.reloadItems {page:newPage}

        scope.$watch "allItems", paginate
        scope.$watch "current_page", pageChange



console.log "drectvie defined" , null  if window.console and console.log


