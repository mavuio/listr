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


    }


    public function dispatch()
    {

      $action=Input::get('action');
      if ($action) {
        return $this->$action();
      }

    }


    public function get_items()
    {
      $ret=[];
      $ret['status']='ok';
      return Response::json($ret);
    }

}
