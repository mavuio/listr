<?php

namespace Werkzeugh\Listr;

use EcoAsset;
use Input;
use Response;

class ListrControllerExtension
{


    // register parent controller, gets loaded soon after initializing, from the parent controller
    public function registerController($controller, $prefix='listr')
    {
        $this->prefix=$prefix;
        $this->parentController=$controller;
        $this->setupAssets();
    }

    public function setupAssets()
    {

       // assume jquery and angular are loaded

       //TODO remove mysite thirdparty-references
       // EcoAsset::add('tinymce'                   , "/packages/werkzeugh/listr/thirdparty/tinymce4/js/tinymce/tinymce.min.js");
       // EcoAsset::add('ui-tinymce'                , "/packages/werkzeugh/listr/thirdparty/ui-tinymce-0.0.4/src/tinymce.js");

       EcoAsset::add('werkzeugh-statemanager', "/packages/werkzeugh/angular-statemanager/js/werkzeugh-statemanager.js");

       EcoAsset::add('werkzeugh-listr'       , "/packages/werkzeugh/listr/ng/listr/js/listr.js");
       EcoAsset::add('werkzeugh-listr-container' , "/packages/werkzeugh/listr/ng/listr/directives/listr-container.js");
       EcoAsset::add('werkzeugh-listr-item' , "/packages/werkzeugh/listr/ng/listr/directives/listr-item.js");
       EcoAsset::add('werkzeugh-listr-css'   , "/packages/werkzeugh/listr/css/listr.css");
       \View::share('listr',$this);

    }

    public function getApiUrl()
    {
      return \URL::current().'/listr';
    }

    public function topHtml()
    {
      $url=$this->getApiUrl();
      return <<<HTML
<div class='well' id="werkzeugh-listr" ng-controller="ListrController" >
  <div listr-container src="$url" query="query">
HTML;
    }

    public function middleHtml()
    {
      return $this->getTableHtml();
    }

    public function bottomHtml()
    {
      return <<<HTML
  </div>
</div>
<script>
 angular.bootstrap(document.getElementById('werkzeugh-listr'), ['listr']);
</script>
HTML;
    }

    public function html()
    {

      return $this->topHtml()
      .$this->middleHtml()
      .$this->bottomHtml();


      return <<<HTML



HTML;

    }


    public function getTableHtml()
    {

      foreach ($this->getDisplayColumns() as $columnName) {
        $columnHtml=$this->getHtmlForColumn($columnName);
        $tdHtml.="\n      <td>$columnHtml</td>";
        $columnHtml=$this->getHtmlForHeaderColumn($columnName);
        $thHtml.="\n      <th>$columnHtml</th>";
      }

      $columnCount=substr_count($thHtml , '<th' );

      $html='
<div paginate="itemsPagination" class="listr-pagination" paginate-reload="switchPage(page)"></div>

<table class="listr-table table table-striped table-bordered table-condensed">
  <tbody>
    <tr>'.$thHtml.'
    </tr>
  </tbody>
  <tbody>

    <tr ng-if="listStatus==\'loading\'"><td colspan='.$columnCount.' align="center">
    <i class="fa fa-refresh fa-spin"></i>
    </td>
    </tr>

    <tr ng-if="listStatus==\'empty\'"><td colspan='.$columnCount.' align="center">
      no items found
    </td>
    </tr>

    <tr listr-item ng-repeat="rec in items" ng-if="listStatus==\'loaded\'">'.$tdHtml.'
    </tr>

  </tbody>
</table>';

      return $html;
    }


    public function getDisplayColumns()
    {
      $conf=$this->getConfig();
      if (is_array($conf['displayColumns']))
      {
         return $conf['displayColumns'];
      }
      return [];

    }

    public function getDisplayColumnSetting($columnName,$key=null)
    {
      $conf=$this->getConfig();
      $settings=[];
      if (is_array($conf['displayColumnSettings']) && $conf['displayColumnSettings'][$columnName])
      {
         $settings = $conf['displayColumnSettings'][$columnName];
      }

      if ($key) {
        if ($settings && isset($settings[$key])) {
          return $settings[$key];
        }
        return null;
      }

      return $settings;
    }

    public function getAdditonalDataColumns()
    {
      $conf=$this->getConfig();
      if ($conf['additionalDataColumns'])
      {
        return array_keys($conf['additionalDataColumns']);
      }
      return [];

    }

    public function getColumnType($columnName)
    {

      $type=$this->getDisplayColumnSetting($columnName,'type');
      if (!$type)
      {
        $type=$this->guessTypeForColumnName($columnName);
      }

      return $type;
    }

    public function guessTypeForColumnName($columnName)
    {
      if(preg_match('#^.*_at$#',$columnName))
      {
        return "datetime";
      }

      if(preg_match('#date$#',$columnName))
      {
        return "date";
      }

      if(preg_match('#price$#',$columnName))
      {
        return "money";
      }

    }

    public function getCustomTemplateForColumn($columnName)
    {
      $conf=$this->getConfig();
      if ($conf['columnTemplates']) {
        if ($html=$conf['columnTemplates'][$columnName]) {
          return $html;
        }
      }
      if ($type=$this->getColumnType($columnName)) {
        switch ($type) {
          case 'date':
            return "{{rec.{$columnName} | date:'yyyy-MM-dd' }}";
            break;
          case 'datetime':
            return "{{rec.{$columnName} | date:'yyyy-MM-dd HH:mm' }}";
            break;

          default:
            # code...
            break;
        }

      }
      return null;

    }


    public function getCustomTemplateForHeaderColumn($columnName)
    {
      $conf=$this->getConfig();
      if ($conf['headerColumnTemplates']) {
        if ($html=$conf['headerColumnTemplates'][$columnName]) {
          return $html;
        }
      }
      return null;

    }


    public function getHtmlForColumn($columnName)
    {
      if ($tpl=$this->getCustomTemplateForColumn($columnName)) {
        return $tpl;
      }

      return '{{rec.'.$columnName.'}}';
    }

    public function getHtmlForHeaderColumn($columnName)
    {
      if ($tpl=$this->getCustomTemplateForHeaderColumn($columnName)) {
        return $tpl;
      }

      if ($label=$this->getDisplayColumnSetting($columnName, 'label')) {
        return $label;
      }
      return $columnName;
    }

    public function dispatch()
    {

      $action=Input::get('action');
      if ($action) {
        $methodName='action'.ucfirst($action);
        return $this->$methodName();
      }

    }


    public function actionGetItems()
    {

      \Paginator::setCurrentPage(Input::get('page'));
      $ret['items']=$this->getItemList(Input::get('query'));

      $ret['status']='ok';
      return Response::json($ret);
    }

    public function getConfig()
    {
      static $config;

      if ($config) {
        return $config;
      }

      if ($this->parentControllerHasMethod('config')){
          $config=$this->callMethodOnParentController('config');

      return $config;
    }
  }
/*==========  data handlers  ==========*/

    public function getItemList($filtersViaRequest)
    {
      if ($this->parentControllerHasMethod('query')){
        $query=$this->callMethodOnParentController('query');

        return $this->getItemsForQuery($query, $filtersViaRequest);
      }
    }

    public function getPageSize()
    {
      $conf=$this->getConfig();

      if ($conf['pagesize']>0) {
        return $conf['pagesize'];
      }

      return 10;
    }

    public function getDefaultSortString()
    {
      $conf=$this->getConfig();

      if (is_array($conf['orderBy'])) {
        foreach ($conf['orderBy'] as $sortTerm) {
          $searchStringParts[]=$sortTerm[0]."-".$sortTerm[1];
        }
        return implode(',',$searchStringParts);
      }
      if ($conf['orderBy']) {
        return $conf['orderBy'];
      }

      return '';
    }


    public function applySorts($query, $filtersViaRequest)
    {

      $sortString=trim($filtersViaRequest['sortby']);

      if (!$sortString) {
        $sortString=$this->getDefaultSortString();
      }


      foreach (explode(',', $sortString) as $fieldName) {

        $direction='asc';
        if (preg_match('#^(.+)-(desc|asc)$#', $fieldName, $matches)) {
          $fieldName=$matches[1];
          $direction=$matches[2];
        }
        $fieldName=trim($fieldName);
        if ($fieldName && $direction) {
          $query->orderBy($fieldName, $direction);
        }
      }


    }


    public function applyFilters($query, $filtersViaRequest)
    {

      foreach ($filtersViaRequest as $field=>$value) {

        $this->applySingleFilter($field,$value,$query);
      }

    }

    public function applySingleFilter($field, $value, $query)
    {

        if($field=='page')
          return;

        if ($field=='sortby')
          return;

        $sconf=$this->getConfigForSearchField($field);

        if ($sconf['fields']) {

          $query->where(function($query) use ($sconf, $value)
          {
            foreach ($sconf['fields'] as $fieldName) {
              $query->orWhere($fieldName,'like','%'.$value.'%');
            }
          });

        }
        elseif (substr($field,0,1)!='_') {
          #try to match plain fieldname
          if (trim($value)) {
            $query->where($field,'like','%'.$value.'%');
          }
        }
    }

    public function getConfigForSearchField($fieldName)
    {
      $conf=$this->getConfig();
      if ($conf['searchFields'] && $conf['searchFields'][$fieldName] ) {
        return $conf['searchFields'][$fieldName] ;
      }

      return [];
    }

    public function executeQuery($query, $filtersViaRequest)
    {
      $this->applyFilters($query, $filtersViaRequest);
      $this->applySorts($query, $filtersViaRequest);
      // $GLOBALS['debugsql']=1;

      return $query->paginate($this->getPageSize());
    }

    public function getItemsForQuery($query, $filtersViaRequest)
    {
      $items=[];
      $paginatedItems=$this->executeQuery($query, $filtersViaRequest);
      // if($_GET[d] || 1 ) { $x=$paginatedItems; $x=htmlspecialchars(print_r($x,1));echo "\n<li>mwuits: <pre>$x</pre>"; }
      foreach ( $paginatedItems as $item) {
        array_push($items,$this->getListRecord($item));
      }

      return ['data'=>$items,
      'pagination'=>[
      'current_page'=>$paginatedItems->getCurrentPage(),
      'from'=>$paginatedItems->getFrom(),
      'to'=>$paginatedItems->getTo(),
      'last_page'=>$paginatedItems->getLastPage(),
      'per_page'=>$paginatedItems->getPerPage(),
      'total'=>$paginatedItems->getTotal(),
      ]
      ];
    }

    public function getListRecord($item)
    {
      $conf=$this->getConfig();
      $record=[];

      foreach ($this->getAdditonalDataColumns() as $columnName) {
            if (is_callable($conf['additionalDataColumns'][$columnName])) {
              $record[$columnName]=$conf['additionalDataColumns'][$columnName]($item);
            }
      }

      foreach ($this->getDisplayColumns() as $columnName) {

            if (!isset($record[$columnName])) {
              $record[$columnName]=$item->getAttribute($columnName);

              if (is_object($record[$columnName]) && is_a($record[$columnName], '\Carbon\Carbon')) {
                $record[$columnName]=$record[$columnName]->toISO8601String();
              }
              // echo $item->email;
              // if($_GET[d] || 1 ) { $x=$item; $x=htmlspecialchars(print_r($x,1));echo "\n<li>mwuits: <pre>$x</pre>"; }
             // echo "<li>$columnName = ".$record[$columnName];
            }
      }
      // if($_GET[d] || 1 ) { $x=$record; $x=htmlspecialchars(print_r($x,1));echo "\n<li>mwuits: <pre>$x</pre>"; }
      // die();
      return $record;


    }

/*==========  data handlers  end ==========*/



    public function parentControllerHasMethod($methodName)
    {
        $methodName=$this->prefix.ucfirst($methodName);
        return (method_exists($this->parentController, $methodName)
            && is_callable(array($this->parentController, $methodName)));
    }


    public function callMethodOnParentController($methodName)
    {
        $methodName=$this->prefix.ucfirst($methodName);
        return $this->parentController->$methodName();
    }




}
