<?php

namespace Werkzeugh\Listr;

use EcoAsset;
use View;
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

       EcoAsset::add('requirejs', "/bower_components/requirejs/require.js",null,['data-main'=>'/packages/werkzeugh/listr/ng/listr/js/main.js']);
       EcoAsset::add('werkzeugh-statemanager', "/packages/werkzeugh/angular-statemanager/js/werkzeugh-statemanager.js");
       EcoAsset::add('werkzeugh-listr'       , "/packages/werkzeugh/listr/ng/listr/js/listr.js");
       EcoAsset::add('bootstrap-modal'       , "/bower_components/bootstrap/js/modal.js");

       EcoAsset::add('werkzeugh-listr-container' , "/packages/werkzeugh/listr/ng/listr/directives/listr-container.js");
       EcoAsset::add('angular-modal-service', "/packages/werkzeugh/listr/ng/listr/js/angular-modal-service.js");
       EcoAsset::add('werkzeugh-listr-item' , "/packages/werkzeugh/listr/ng/listr/directives/listr-item.js");
       EcoAsset::add('werkzeugh-listr-css'   , "/packages/werkzeugh/listr/css/listr.css");
       \View::share('listr',$this);

    }

    public function getApiUrl()
    {
       if ($this->parentControllerHasMethod('ApiUrl')){
        $url=$this->callMethodOnParentController('ApiUrl');
       } else {
         $url= \URL::current().'/listr';
       }
       return $url;
    }

    var  $storedFormFields=null;

    public function initFormFields()
    {
      if(!$this->storedFormFields) {
       if ($this->parentControllerHasMethod('InitFormFields')){
         $formConfig=$this->callMethodOnParentController('InitFormFields');
       } else {
         $formConfig=[];
       }
     }
      if (!$formConfig) {
          $formConfig=[];
      }

       $this->storedFormFields=$formConfig;

       return  $this->storedFormFields;
    }


    public function topHtml($listrArguments=NULL)
    {
      $this->listrArguments=$listrArguments;
      $url=$this->getApiUrl();
      $listrArgumentsJson=json_encode($listrArguments);

      $conf=$this->getConfig();

      if ($conf['maxWidth']) {
          $styles['max-width']=$conf['maxWidth'].'px';
      }
      if ($styles) {
          foreach ($styles as $key => $value) {
            $styleStrParts[]="$key:$value";
          }
          $styleStr="style=\"".implode(';',$styleStrParts)."\"";
      }
      return <<<HTML

<div ng-non-bindable data-\$injector="">
  <div id="werkzeugh-listr" ng-controller="ListrBaseController" $styleStr >
    <div ng-controller="ListrController" >
      <div listr-container src="$url" query="query" app="app"
  listr-arguments='{$listrArgumentsJson}'>
HTML;
    }

    public function middleHtml($listrArguments=NULL)
    {
      $this->listrArguments=$listrArguments;

      return $this->getTableHtml();
    }

    public function bottomHtml($listrArguments=NULL)
    {
      $this->listrArguments=$listrArguments;

      return <<<HTML
      </div>
    </div>
  </div>
</div>
<script>

/* jshint ignore:start */
angular.module("listr").controller('ListrController', [
  '\$scope', '\$location', '\$http', '\$filter', '\$sce', '\$timeout', function(\$scope, \$location, \$http, \$filter, \$sce, \$timeout) { 
    
    {$this->getCustomControllerJavascript()}

  } 
]);
/* jshint ignore:end */


jQuery(document).ready(function($) {

    angular.bootstrap(document.getElementById('werkzeugh-listr'), ['listr']);
          
 });


</script>
HTML;
    }
    
    


    public function html($listrArguments=NULL)
    {

      return $this->topHtml($listrArguments)
      .$this->middleHtml($listrArguments)
      .$this->bottomHtml($listrArguments);

    }


    public function getTableHtml()
    {

      foreach ($this->getDisplayColumns("") as $columnName) {
        $rowTagAttributes=$this->getTagAttributesForRow();

        $columnHtml=$this->getHtmlForColumn($columnName);
        $tdHtml.="\n      <td class=\"col-$columnName\">$columnHtml</td>";
        $columnHtml=$this->getHtmlForHeaderColumn($columnName);
        $thHtml.="\n      <th class=\"col-$columnName\">$columnHtml</th>";
      }

      $columnCount=substr_count($thHtml , '<th' );

      $html='
<div paginate="itemsPagination" class="listr-pagination" paginate-reload="switchPage(page)"></div>

<table class="listr-table table table-striped table-bordered table-condensed">
  <tbody>
    <tr valign="bottom">'.$thHtml.'
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

    <tr listr-item ng-repeat="rec in items" ng-if="listStatus==\'loaded\'" ng-class="{\'selected\':rec.__selected,\'loading\':rec.__loading,\'deleted\':rec.__deleted}" '.$rowTagAttributes.'>'.$tdHtml.'
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

    public function getSettingsForAutoButtonColumn()
    {

        return [
            'label'=>'&nbsp;'
        ];  
        
    }


    public function getDisplayColumnSetting($columnName,$key=null)
    {
      $conf=$this->getConfig();
      $settings=[];
      if (is_array($conf['displayColumnSettings']) && $conf['displayColumnSettings'][$columnName]) {
         $settings = $conf['displayColumnSettings'][$columnName];
      }

      if ($columnName=='_buttons') {
          $settings=$this->getSettingsForAutoButtonColumn();
      }

      if ($key) {
        if ($settings && isset($settings[$key])) {
          return $settings[$key];
        }
        return null;
      }

      return $settings;
    }

    public function getAdditionalDataColumns()
    {
      static $ret;

      if (!isset($ret))
      {
          $conf=$this->getConfig();
          $ret=[];
          if ($conf['additionalDataColumns'])
          {
            $ret=array_keys($conf['additionalDataColumns']);
          }
      }

      return $ret;

    }

    public function getAdditionalDataColumnsFromColumnTemplates()
    {
      $conf=$this->getConfig();
      $ret=[];
      if (is_array($conf['columnTemplates'])) {
        foreach ($conf['columnTemplates'] as $fieldName => $template) {
          if(in_array($fieldName,$conf['displayColumns'])) {
            $fields=$this->getFieldNamesFromTemplate($template);
            if ($fields) {
              $ret[$fieldName]['fields']=$fields;
            }
          }        }
        }
      return $ret;

    }

    public function getFieldNamesFromTemplate($template)
    {

      $ret=[];
      preg_match_all('#rec\.([a-z0-9_]+)#mis',$template,$matches);

      foreach (array_unique($matches[1]) as $str) {
        array_push($ret,$str);
      }

      // if($_GET[d] || 1 ) { $x=$ret; $x=htmlspecialchars(print_r($x,1));echo "\n<li>getFieldNamesFromTemplate($template): <pre>$x</pre>"; }

      return $ret;
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

    public function getTemplateForAutoButtonColumn()
    {
       $btn['edit']='<button class="btn btn-xs btn-primary" type="button" ng-disabled="rec.__loading || app.currentDisplayRecord" ng-click="editRecord(rec)" title="edit"><i class="fa fa-pencil"></i></button>';    
       $btn['delete']='<button class="btn btn-xs btn-danger" type="button" ng-disabled="rec.__loading || app.currentDisplayRecord" ng-click="deleteRecord(rec)" title="delete"><i class="fa fa-trash-o"></i></button>';    
       foreach ($btn as $key => $value) {
         $html.=$value." ";
       }
       $html.="<i class=\"fa fa-refresh fa-spin\" ng-show=\"rec.__loading\"></i>";
       return $html;
    }


    public function getCustomTagAttributesForRow()
    {
        
      $conf=$this->getConfig();
      if ($html=$conf['customTagAttributesForRow']) {
        return $html;
      }
      return null;
    }

    public function getCustomTemplateForColumn($columnName)
    {
      $conf=$this->getConfig();
      if ($conf['columnTemplates']) {
        if ($html=$conf['columnTemplates'][$columnName]) {
          return $html;
        }
      }

      if ($columnName=='_buttons') {
        return $this->getTemplateForAutoButtonColumn();    
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

    public function getTagAttributesForRow()
    {
        if ($tpl=$this->getCustomTagAttributesForRow()) {
          return $tpl;
        }
    }

    public function getHtmlForHeaderColumn($columnName)
    {
      if ($tpl=$this->getCustomTemplateForHeaderColumn($columnName)) {
        return $tpl;
      }
      
      $fieldname=$columnName;
      if ($label=$this->getDisplayColumnSetting($columnName, 'label')) {
        $fieldname=$label;
      }
      
      $sortColumnName=$this->getDisplayColumnSetting($columnName, 'sortfield');
      if(!$sortColumnName)
          $sortColumnName="$columnName";
      
      $header=$fieldname;
      
      if(!preg_match('#^_(.*)$#',$sortColumnName)) {
          $header="<a href=\"javascript:void(0)\" 
                      ng-click=\"toggleSort('$sortColumnName')\">
          <i ng-show=\"getSortStatus('$sortColumnName')\" class=\"sorticon fa\" ng-class=\"{asc:'fa-arrow-down',desc:'fa-arrow-up'}[getSortStatus('$sortColumnName')]\"></i>
          $header
          </a>";
      }
      
      
      return $header;
    }

    public function dispatch()
    {


      $action=Input::get('action');
      if ($action) {
        $methodName='action'.ucfirst($action);
        return $this->$methodName();
      }

    }


    public function FormItems()
    {
          $ret=[];

          \NgForm::set('model','app.record');

          foreach ($this->initFormFields() as $formFieldConfig) {
            $ret[$formFieldConfig['fieldname']]=\NgForm::createField($formFieldConfig);
          }

          return $ret;
    }


    public function actionGetDefaultRecord()
    {

      $ret['status']='error';
      $ret['msg']='error on GetDefaultRecord';


      if ($this->parentControllerHasMethod('GetDefaultRecord')){
        $record=$this->callMethodOnParentController('GetDefaultRecord');
      }

      if (!$record) {
       $record=[];
     }

     $ret['status']='ok';
     unset($ret['msg']);
     $ret['payload']=$record;
     return Response::json($ret);

   }

    public function actionGetNgEditTemplate()
    {
      
        return  View::make('listr::ng-edit-template',['ctrl'=>$this]);


    }


    public function actionDeleteItem()
    {
        
      $ret['status']='error';
      $ret['msg']='error on delete';
      $data=Input::get('record');
      $id=$data['id'];
      if ($id) {
          $rec=$this->getModel()->find($id);
          if ($rec) {
              $rec->delete();
          }
        $ret['status']='ok';
        unset($ret['msg']);
      }

      return Response::json($ret);

    }

    public function actionSaveItem()
    {
        
      $ret['status']='error';
      $ret['msg']='error on save';
      $data=Input::get('data');
      if ($rec=$this->saveRecord($data)) {

        $ret['status']='ok';
        unset($ret['msg']);
        $ret['record']=$rec->toArray();
      }

      return Response::json($ret);

    }

    public function saveRecord($data)
    {
        $conf=$this->getConfig();

        //find existing
        $model=$this->getModel();
        if ($id=$data['id']) {
          $record=$model->find($id);
          if (!$record && !$conf['idColumnIsEditable']) {
            return null;
          }
        } 
        if(!$record) {
          $record=$model->create($data);
        }
        $record->unguard();
        $record->update($data);
        $record->reguard();

        return $record;
    }

    public function actionGetNgEditController()
    {
        return <<<'HTML'
define(function() {
  return angular.module('listr').registerController('ListrItemEditController', function($scope, app, close, $element,$timeout) {
    $scope.app=app;
    
    $timeout(function(){ $element.find('.form-control').first().focus() },500);
    
    return $scope.close = function(result) {
      if (window.console && console.log) {
        console.log("closed my stuff", null);
      }
      return close(result, 500);
    };
  });
});
HTML;

    }


    public function actionGetItem()
    {
      
      $ret['record']=$this->getModel()->find(Input::get('id'))->toArray();
      $ret['status']='ok';
      return Response::json($ret);
    }

    public function actionGetItems()
    {

      \Paginator::setCurrentPage(Input::get('page'));
      $this->listrArguments=Input::get('listrArguments');
      $ret['items']=$this->getItemList(Input::get('query'));

      $ret['status']='ok';
      return Response::json($ret);
    }


 


    public function getConfig()
    {
      static $config;

      if (!isset($config)) {

        if ($this->parentControllerHasMethod('Config')){
          $config=$this->callMethodOnParentController('Config');
        }

        $additionalDataColumns=$this->getAdditionalDataColumnsFromColumnTemplates();
        if ($additionalDataColumns) {
          foreach ($additionalDataColumns as $fieldName => $value) {
            if (!isset($config['additionalDataColumns'][$fieldName])) {
              $config['additionalDataColumns'][$fieldName]= $value;
            }
          }
          // if($_GET[d] || 1 ) { $x=$config; $x=htmlspecialchars(print_r($x,1));echo "\n<li>ret: <pre>$x</pre>"; }
        }

      }


      return $config;
    }
/*==========  data handlers  ==========*/

    public function getItemList($filtersViaRequest)
    {
      $query=$this->getQuery();
      if ($query) {
        return $this->getItemsForQuery($query, $filtersViaRequest);
      }
    }

    public function getQuery()
    {
         if ($this->parentControllerHasMethod('Query')){
            return $this->callMethodOnParentController('Query');
         }
    }

    public function getModel()
    {
       if ($this->parentControllerHasMethod('Model')){
            return $this->callMethodOnParentController('Model');
         }

         return $this->getQuery()->getModel();

    }

    public function getCustomControllerJavascript()
    {
        
      if ($this->parentControllerHasMethod('Javascript')){
        $js=$this->callMethodOnParentController('Javascript');
        return $js;
      }
      return "<!-- no custom javascript for listr-->";
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
        if (preg_match('#^(.+)[-:](desc|asc)$#', $fieldName, $matches)) {
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
          if($field!='sortby'){
              $this->applySingleFilter($field,$value,$query);
          }
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

      $realColumns=$this->getDisplayColumns();

      foreach ($this->getAdditionalDataColumns() as $columnName) {

            if (is_callable($conf['additionalDataColumns'][$columnName])) {
              $record[$columnName]=$conf['additionalDataColumns'][$columnName]($item);
            } elseif (is_array($conf['additionalDataColumns'][$columnName])) {
              if (is_array($conf['additionalDataColumns'][$columnName]['fields'])) {
                // if($_GET[d] || 1 ) { $x=$conf['additionalDataColumns'][$columnName]['fields']; $x=htmlspecialchars(print_r($x,1));echo "\n<li>mwuits: <pre>$x</pre>"; }
                $realColumns=array_merge($realColumns,$conf['additionalDataColumns'][$columnName]['fields']);
              }
            }
      }


      if (!in_array('id',$realColumns)) {
          $realColumns[]="id";
      }

      foreach ($realColumns as $columnName) {
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
        $methodName=$this->prefix.$methodName;
        return (method_exists($this->parentController, $methodName)
            && is_callable(array($this->parentController, $methodName)));
    }


    public function callMethodOnParentController($methodName)
    {
        $methodName=$this->prefix.$methodName;
        return $this->parentController->$methodName();
    }




}
