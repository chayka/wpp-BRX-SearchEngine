<?php

class wpp_BRX_SearchEngine_Bootstrap extends WpPluginBootstrap// Zend_Application_Bootstrap_Bootstrap
{
    const MODULE = 'wpp_BRX_SearchEngine';
    
    public function run(){
        parent::run();
    }
    
    public function getModuleName() {
        return self::MODULE;
    }
    
    
    public function setupRouting(){
        $router = parent::setupRouting();
//        $front = Util::getFront();
//        $router = $front->getRouter();
//        $cd = $front->getControllerDirectory();
//        $front->addControllerDirectory($cd['default'], self::MODULE);        
//
//        $router->addRoute(self::MODULE, new Zend_Controller_Router_Route(':controller/:action/*', array('controller' => 'index', 'action'=>'index', 'module'=>self::MODULE)));
        $router->addRoute('search', new Zend_Controller_Router_Route('search/:scope/*', array('controller' => 'search', 'action'=>'search', 'module'=>self::MODULE, 'scope'=>'all')));
        $router->addRoute('update-options', new Zend_Controller_Router_Route('search-engine/update-options/*', array('controller' => 'admin', 'action'=>'update-search-engine', 'module'=>self::MODULE)));
        $router->addRoute('index-post', new Zend_Controller_Router_Route('indexer/index-post/:postId/*', array('controller' => 'indexer', 'action'=>'index-post', 'module'=>self::MODULE, 'postId'=>0), array('postId'=>'\d+')));
        $router->addRoute('delete-post', new Zend_Controller_Router_Route('indexer/delete-post/:postId/*', array('controller' => 'indexer', 'action'=>'delete-post', 'module'=>self::MODULE, 'postId'=>0), array('postId'=>'\d+')));
//        $router->addRoute('catalog', new Zend_Controller_Router_Route('catalog/:itemType/:service/*', array('controller' => 'catalog', 'action'=>'index', 'module'=>self::MODULE, 'page'=>1, 'service'=>'all', 'itemType'=>'all')));
//        $router->addRoute('catalog-edit-item', new Zend_Controller_Router_Route('catalog/edit/:postId', array('controller' => 'catalog', 'action'=>'edit-item', 'module'=>self::MODULE)));
//        $router->addRoute('catalog-update-item', new Zend_Controller_Router_Route('catalog/update/*', array('controller' => 'catalog', 'action'=>'update-item', 'module'=>self::MODULE)));
    }

}

