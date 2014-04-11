<?php

namespace Werkzeugh\Listr;

use EcoAsset;
use Input;
use Response;

class ListrControllerExtension
{


    // register parent controller, gets loaded soon after initializing, from the parent controller
    public function registerController($controller)
    {
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

    public function getHtml($prefix='listr')
    {

      $this->prefix=$prefix;
      $url=$this->getApiUrl();

      return <<<HTML
<div class='well' id="werkzeugh-listr" ng-controller="ListrController" >

<div listr-container src="$url">

  {$this->getTableHtml()}


</div>

</div>
<script>
 angular.bootstrap(document.getElementById('werkzeugh-listr'), ['listr']);
</script>
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

      $html='
<table class="listr-table table table-striped table-bordered table-condensed">
  <tbody>
    <tr>'.$thHtml.'
    </tr>
  </tbody>
  <tbody>
    <tr listr-item ng-repeat="rec in items">'.$tdHtml.'
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


    public function getCustomTemplateForColumn($columnName)
    {
      $conf=$this->getConfig();
      if ($conf['columnTemplates']) {
        if ($html=$conf['columnTemplates'][$columnName]) {
          return $html;
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
      $this->prefix=Input::get('prefix');
      if ($action) {
        $methodName='action'.ucfirst($action);
        return $this->$methodName();
      }

    }


    public function actionGetItems()
    {
      $ret['items']=$this->getItemList();
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

    public function getItemList()
    {
      if ($this->parentControllerHasMethod('query')){
        $query=$this->callMethodOnParentController('query');

        return $this->getItemsForQuery($query);
      }
    }

    public function executeQuery($query)
    {
      return $query->get()->take(2);
    }

    public function getItemsForQuery($query)
    {
      $items=[];
      foreach ($this->executeQuery($query) as $item) {

        array_push($items,$this->getListRecord($item));
      }
      return $items;
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
            }
      }

      return $record;

    }



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
