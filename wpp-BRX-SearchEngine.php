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


define( 'WPP_BRX_SEARCH_ENGINE_PATH', plugin_dir_path(__FILE__) );
define( 'WPP_BRX_SEARCH_ENGINE_URL', preg_replace('%^[\w\d]+\:\/\/[\w\d\.]+%', '',plugin_dir_url(__FILE__)) );

require_once 'application/helpers/SearchHelper.php';
require_once 'application/helpers/UrlHelper_wpp_BRX_SearchEngine.php';
require_once 'application/helpers/OptionHelper_wpp_BRX_SearchEngine.php';
require_once 'widgets-BRX-SearchEngine.php';

ZF_Query::registerApplication('WPP_BRX_SEARCH_ENGINE', WPP_BRX_SEARCH_ENGINE_PATH.'application', 
        array('search', 'search-engine', 'indexer'), array('search'));
//require_once 'application/helpers/UrlHelper_wpp_BRX_SearchEngine.php';

class wpp_BRX_SearchEngine {
    const NLS_DOMAIN = "wpp_BRX_SearchEngine";
    protected static $needStyles = true;

    public static function baseUrl(){
        echo WPP_BRX_SEARCH_ENGINE_URL;
    }
    
    public static function dbUpdate() {
        WpDbHelper::dbUpdate('1.0', 'search_engine_db_version', WPP_BRX_SEARCH_ENGINE_PATH.'res/sql');
    }
    
    public static function installPlugin() {
        self::registerResources();
        self::registerCustomPostTypes();
        self::registerTaxonomies();
        self::registerActions();
        self::registerFilters();
        self::registerSidebars();
        
    }

    public static function registerCustomPostTypes() {

    }

    public static function registerTaxonomies(){

    }

    public static function excerptLength(){
        return 20;
    }
    
    public static function registerSidebars(){
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
        self::$needStyles = !$block;
    }

    public static function addJQueryWidgets(){
        ZF_Core::addJQueryWidgets();
    }
    
    public static function registerResources($minimize = false){
        wp_register_style('se-search-options', WPP_BRX_SEARCH_ENGINE_URL.'res/css/bem-se_search_options.less');
        wp_register_style('se-control-panel', WPP_BRX_SEARCH_ENGINE_URL.'res/css/brx.SearchEngine.ControlPanel.view.less');
        wp_register_script('se-control-panel', WPP_BRX_SEARCH_ENGINE_URL.'res/js/brx.SearchEngine.ControlPanel.view.js', array('backbone-brx', 'jquery-ui-progressbar'));
//        wp_register_script('se-control-panel', WPP_BRX_SEARCH_ENGINE_URL.'res/js/jquery.se.controlPanel.js', array('jquery-brx-form', 'jquery-ui-progressbar'));
        wp_register_style('se-setup-form', WPP_BRX_SEARCH_ENGINE_URL.'res/css/bem-se_setup.less');
        wp_register_script('se-search-form', WPP_BRX_SEARCH_ENGINE_URL.'res/js/jquery.se.searchForm.js', array('jquery-ui-templated', 'jquery-brx-placeholder'));
        if(self::$needStyles){
//            die('#');
            wp_register_style('se-search-page', WPP_BRX_SEARCH_ENGINE_URL.'res/css/bem-se_search_page.less');
        }
        wp_register_style('se-search-debug', WPP_BRX_SEARCH_ENGINE_URL.'res/css/bem-se_search_debug.less');
//        wp_register_script('se-setup', WPP_BRX_SEARCH_ENGINE_URL.'res/js/jquery.se.setup.js', array('jquery-brx-form'));

//        wp_register_style('cr-body', WPP_BRX_SEARCH_ENGINE_URL.'res/css/bem-body.less');
//        wp_register_style('timeline-post-editor', WPP_BRX_SEARCH_ENGINE_URL.'res/css/bem-post_editor.less');
//        wp_register_script('timeline-post-editor', WPP_BRX_SEARCH_ENGINE_URL.'res/js/jquery.castanedaReview.postEditor.js', array('jquery-brx-form'));
//        wp_register_style('timeline', WPP_BRX_SEARCH_ENGINE_URL.'res/css/bem-timeline.less', array('timeline-post-editor'));
//        wp_register_script('timeline', WPP_BRX_SEARCH_ENGINE_URL.'res/js/jquery.castanedaReview.timeline.js', array('jquery-ui-datepicker', 'jquery-ui-datepicker-ru', 'jquery-ui-templated','timeline-post', 'timeline-post-editor'));
//
//        wp_register_style( 'jquery-ui', WPP_BRX_SEARCH_ENGINE_URL.'res/css/jquery-ui-1.9.2.smoothness.css');

    }
    
    public static function registerActions(){
        add_action('admin_menu', array('wpp_BRX_SearchEngine', 'registerConsolePages'));
        add_action('add_meta_boxes', array('wpp_BRX_SearchEngine', 'addMetaBoxSearchOptions') );
        add_action('lucene_index_post', array('wpp_BRX_SearchEngine', 'indexPost'), 10, 2);
        add_action('lucene_delete_post', array('wpp_BRX_SearchEngine', 'deletePost'), 10, 2);
        add_action('save_post', array('wpp_BRX_SearchEngine', 'savePost'), 90, 2);
        add_action('save_post', array('wpp_BRX_SearchEngine', 'indexPost'), 100, 2);
        add_action('delete_post', array('wpp_BRX_SearchEngine', 'deletePost'), 100, 2);
        add_action('trashed_post', array('wpp_BRX_SearchEngine', 'deletePost'), 100, 2);
        
        add_action('lucene_enable_indexer', array('wpp_BRX_SearchEngine', 'enableIndexer'), 10);
        add_action('lucene_disable_indexer', array('wpp_BRX_SearchEngine', 'disableIndexer'), 10);
        add_action('parse_request', array('wpp_BRX_SearchEngine', 'parseRequest'));
//        add_action('wp_footer', array('wpp_BRX_SearchEngine', 'addJQueryWidgets'));
        
    }
    
    public static function registerFilters(){
//        add_filter('post_type_link', array('wpp_BRX_SearchEngine', 'postPermalink'), 1, 3);
//        add_filter('term_link', array('wpp_BRX_SearchEngine', 'termLink'), 1, 3);
//        add_filter( 'the_search_query', array('wpp_BRX_SearchEngine', 'enableSearch' ));
//        add_filter('get_sample_permalink_html', array('wpp_BRX_SearchEngine', 'getSamplePermalinkHtml'), 1, 4);
//        add_filter('manage_question_posts_columns', array('wpp_BRX_SearchEngine', 'manageQuestionColumns'));
//        add_filter('manage_edit_question_sortable_columns', array('wpp_BRX_SearchEngine', 'questionSortableColumns'));
//        add_action('delete_post', array('wpp_BRX_SearchEngine', 'deletePost'), 10, 1);
//        add_filter('excerpt_more', array('wpp_BRX_SearchEngine', 'excerptMore'));
//        add_filter('wp_insert_post_data', array('wpp_BRX_SearchEngine', 'autoSlug'), 10, 1 );
//        add_filter('post_link', array('wpp_BRX_SearchEngine', 'postPermalink'), 1, 3);
//        add_filter('get_comment_link', array('wpp_BRX_SearchEngine', 'commentPermalink'), 1, 2);
//        add_filter('wp_nav_menu_objects', array('wpp_BRX_SearchEngine', 'wp_nav_menu_objects'), 1, 2);
//        add_filter('media_upload_tabs', array('wpp_BRX_SearchEngine', 'mediaUploadTabs'), 1, 1);
//        add_filter('excerpt_length', array('wpp_BRX_SearchEngine', 'excerpt_length'), 1, 1);
//        add_filter('wp_nav_menu_items', array('wpp_BRX_SearchEngine', 'wp_nav_menu_items'), 1, 2);
//        add_filter('wp_nav_menu', array('wpp_BRX_SearchEngine', 'wp_nav_menu'), 1, 2);
        
    }
    public static function registerConsolePages() {
//        add_submenu_page('edit.php?post_type='.wpp_BRX_SearchEngine::POST_TYPE_CATALOG_ITEM, 
//                'Импорт каталога', 'Импорт', 'update_core', 'ilat-catalogue-import-items', 
//                array('wpp_BRX_SearchEngine', 'renderConsolePageImportItems'), '', null); 
//        add_submenu_page('edit.php?post_type='.wpp_BRX_SearchEngine::POST_TYPE_CATALOG_ITEM, 
//                'Настройка полей', 'Настройка полей', 'update_core', 'ilat-catalogue-setup-fields', 
//                array('wpp_BRX_SearchEngine', 'renderConsolePageSetupFields'), '', null); 
//        add_submenu_page('edit.php?post_type='.wpp_BRX_SearchEngine::POST_TYPE_CATALOG_ITEM, 
//                'Настройка каталога', 'Настройка каталога', 'update_core', 'ilat-catalogue-setup-catalog', 
//                array('wpp_BRX_SearchEngine', 'renderConsolePageSetupCatalog'), '', null); 
//        add_submenu_page('edit.php?post_type='.wpp_BRX_SearchEngine::POST_TYPE_SERVICE_ITEM, 
//                'Импорт услуг', 'Импорт', 'update_core', 'ilat-catalogue-import-services', 
//                array('wpp_BRX_SearchEngine', 'renderConsolePageImportServices'), '', null); 
        add_menu_page('Поисковая система', 'Поисковая система', 'update_core', 'search-engine-admin', 
                array('wpp_BRX_SearchEngine', 'renderConsolePageIndexer'), '', null); 
//        add_submenu_page('search-engine-admin', 
//                'Индексация информации', 'Индексация информации', 'update_core', 'search-engine-indexer', 
//                array('wpp_BRX_SearchEngine', 'renderConsolePageIndexer'), '', null); 
        add_submenu_page('search-engine-admin', 
                'Настройка', 'Настройка', 'update_core', 'search-engine-setup', 
                array('wpp_BRX_SearchEngine', 'renderConsolePageSetup'), '', null); 
    }


    public static function renderConsolePageIndexer(){
       echo ZF_Query::processRequest('/admin/indexer', 'WPP_BRX_SEARCH_ENGINE');	
    }

    public static function renderConsolePageSetup(){
       echo ZF_Query::processRequest('/admin/setup-search-engine', 'WPP_BRX_SEARCH_ENGINE');	
    }

//    public static function renderConsolePageImportItems(){
//       echo ZF_Query::processRequest('/admin/import-items', 'WPP_BRX_SEARCH_ENGINE');	
//    }
//
//    public static function renderConsolePageSetupFields(){
//       echo ZF_Query::processRequest('/admin/setup-fields', 'WPP_BRX_SEARCH_ENGINE');	
//    }
//
//    public static function renderConsolePageSetupCatalog(){
//       echo ZF_Query::processRequest('/admin/setup-catalog', 'WPP_BRX_SEARCH_ENGINE');	
//    }
//
//    public static function renderConsolePageImportServices(){
//       echo ZF_Query::processRequest('/admin/import-services', 'WPP_BRX_SEARCH_ENGINE');	
//    }
    
    
    public static function addMetaBoxSearchOptions() {
        add_meta_box( 
            'searchengine_options_box',
            __( 'Поиск', self::NLS_DOMAIN ),
            array('wpp_BRX_SearchEngine', 'renderMetaBoxSearchOptions'),
            null,
            'advanced',
            'high'
        );
    }
    
    public static function renderMetaBoxSearchOptions(){
        echo ZF_Query::processRequest('/admin/search-options', 'WPP_BRX_SEARCH_ENGINE');
    }
    
    public static function savePost($postId, $post){
        ZF_Query::processRequest('/admin/update-post/post_id/'.$postId, 'WPP_BRX_SEARCH_ENGINE');
    }
    
    public static function indexPost($postId, $post){
        ZF_Query::processRequest('/indexer/index-post/'.$postId, 'WPP_BRX_SEARCH_ENGINE');
//        if(!SearchHelper::isIndexerEnabled()){
//            return;
//        }
//        if(!$postId){
//            return;
//        }
//        $post = get_post($postId);
//        if(!$post || is_wp_error($post)){
//            return;
//        }
//        if(wp_is_post_autosave($post) || wp_is_post_revision($post)){
//            return;
//        }
//        
//        if($post->post_status == 'publish' 
//                && SearchHelper::isSearchEnabled($post->post_type)){
//            SearchHelper::indexPost($post);
//        }else{
//            SearchHelper::deletePost($postId);
//        }
    }

    public static function deletePost($postId, $post = null){
        ZF_Query::processRequest('/indexer/delete-post/'.$postId, 'WPP_BRX_SEARCH_ENGINE');
//        Log::func();
//        if(!SearchHelper::isIndexerEnabled()){
//            return;
//        }
//        Log::func('indexer enabled');
//        if(!$postId){
//            return;
//        }
//        Log::func('postId set');
//        $post = get_post($postId);
//        if(wp_is_post_autosave($post) || wp_is_post_revision($post)){
//            Log::info('wp_is_post_autosave($post) || wp_is_post_revision($post)');
//            return;
//        }
//        Log::func('direct call');
//        
//        SearchHelper::deletePost($postId);
    }
    
    public static function enableIndexer(){
        SearchHelper::enableIndexer();
    }
    
    public static function disableIndexer(){
        SearchHelper::disableIndexer();
    }
    
    public static function parseRequest(){
//        Util::print_r($_SERVER);
        if(strpos($_SERVER['REQUEST_URI'], 'index.php') && isset($_REQUEST['s'])){
            $s = Util::getItem($_REQUEST, 's');
            $url = '/search/?q='.  urldecode($s);
            header('Location: '.$url);
            die();
        }
    }
    
    
}




add_action('init', array('wpp_BRX_SearchEngine', 'installPlugin'));
register_uninstall_hook(__FILE__, array('wpp_BRX_SearchEngine', 'uninstallPlugin'));
