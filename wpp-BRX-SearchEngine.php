<?php

/*
  Plugin Name: wpp-BRX-SearchEngine
  Plugin URI: http://AnotherGuru.me/
  Description: Well-made search engine.
  Version: 1.0
  Author: Boris Mossounov
  Author URI: http://facebook.com/mossounov
  License: GPL2
 */


define( 'WPP_BRX_SEARCHENGINE_PATH', plugin_dir_path(__FILE__) );
define( 'WPP_BRX_SEARCHENGINE_URL', preg_replace('%^[\w\d]+\:\/\/[\w\d\.]+%', '',plugin_dir_url(__FILE__)) );

require_once 'application/helpers/SearchHelper.php';
require_once 'application/helpers/UrlHelper_wpp_BRX_SearchEngine.php';
require_once 'application/helpers/OptionHelper_wpp_BRX_SearchEngine.php';
require_once 'widgets-BRX-SearchEngine.php';

class wpp_BRX_SearchEngine extends WpPlugin{
    const NLS_DOMAIN = "wpp_BRX_SearchEngine";
    protected static $instance = null;
    
    public static function init() {
        
        self::$instance = $se = new wpp_BRX_SearchEngine(__FILE__, array('search', 'search-engine', 'indexer'));
        $se->addSupport_ConsolePages();
        $se->addSupport_Metaboxes();
        $se->addSupport_PostProcessing();
    }

    /**
     * 
     * @return wpp_BRX_SearchEngine
     */
    public static function getInstance() {
        return self::$instance;
    }

    public static function baseUrl() {
        echo self::getInstance()->getBaseUrl();
    }

    public function registerCustomPostTypes() {
        
    }

    public function registerTaxonomies(){

    }

    public function registerSidebars(){
        register_sidebar(array(
            'name'=>'Поиск: Результаты поиска',
            'id'=>'search-results'
        ));
        register_sidebar(array(
            'name'=>'Поиск: Ничего не найдено',
            'id'=>'search-not-found'
        ));
    }
    
    public static function blockStyles($block = true){
        self::$instance->needStyles = !$block;
    }

    public function registerResources($minimize = false){

        $this->registerStyle('se-search-options', 'bem-se_search_options.less');
        $this->registerStyle('se-control-panel', 'brx.SearchEngine.ControlPanel.view.less');
        $this->registerScript('se-control-panel', 'brx.SearchEngine.ControlPanel.view.js', array('backbone-brx', 'jquery-ui-progressbar'));
        $this->registerStyle('se-setup-form', 'bem-se_setup.less');
        $this->registerScript('se-search-form', 'jquery.se.searchForm.js', array('jquery-ui-templated', 'jquery-brx-placeholder'));
        if(self::$instance->needStyles){
            $this->registerStyle('se-search-page', 'bem-se_search_page.less');
        }
        $this->registerStyle('se-search-debug', 'bem-se_search_debug.less');

    }
    
    public function registerActions(){
        $this->addSupport_PostProcessing(100);
        $this->addAction('lucene_index_post', 'indexPost', 10, 2);
        $this->addAction('lucene_delete_post', 'deletePost', 10, 2);
//        $this->addAction('save_post', 'savePost', 90, 2);
        $this->addAction('save_post', 'indexPost', 100, 2);
//        $this->addAction('delete_post', 'deletePost', 100, 2);
//        $this->addAction('trashed_post', 'deletePost', 100, 2);
        
        $this->addAction('lucene_enable_indexer', 'enableIndexer', 10);
        $this->addAction('lucene_disable_indexer', 'disableIndexer', 10);
        $this->addAction('parse_request', 'parseRequest');
        
    }
    
    public function registerFilters(){

    }
    
    public function registerConsolePages() {
        
        $this->addConsolePage('Поисковая система', 'Поисковая система', 'update_core', 'search-engine-admin', 
                '/admin/indexer');
        
        $this->addConsoleSubPage('search-engine-admin', 
                'Настройка', 'Настройка', 'update_core', 'search-engine-setup', 
                '/admin/setup-search-engine');
    }


    public function registerMetaBoxes() {
        $this->addMetaBox(
                'searchengine_options_box', 
                __( 'Поиск', self::NLS_DOMAIN ), 
                '/admin/search-options', 
                'advanced',
                'high');
    }
    
    public function savePost($postId, $post){
        $this->processRequest('/admin/update-post/post_id/'.$postId);
    }
    
    public function indexPost($postId, $post){
        $this->processRequest('/indexer/index-post/'.$postId);
    }

    public function deletePost($postId, $post = null){
        $this->processRequest('/indexer/delete-post/'.$postId);
    }
    
    public function enableIndexer(){
        SearchHelper::enableIndexer();
    }
    
    public function disableIndexer(){
        SearchHelper::disableIndexer();
    }
    
    public function parseRequest(){
        if(strpos($_SERVER['REQUEST_URI'], 'index.php') && isset($_REQUEST['s'])){
            $s = Util::getItem($_REQUEST, 's');
            $url = '/search/?q='.  urldecode($s);
            header('Location: '.$url);
            die();
        }
    }

}




add_action('init', array('wpp_BRX_SearchEngine', 'init'));
