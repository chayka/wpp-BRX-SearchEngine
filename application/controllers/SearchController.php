<?php


/**
 * Description of SearchController
 *
 * @author borismossounov
 */
class wpp_BRX_SearchEngine_SearchController extends Zend_Controller_Action{
    //put your code here
    
    public function init(){
        Util::turnRendererOff();
//        print_r($_SESSION);
//        session_write_close();
    }

    public function deleteRequestAction(){
        $term = InputHelper::getParam('request');
        $history = Util::getItem($_SESSION, 'search_history', array());
        $index = array_search($term, $history);
        if($index!==false){
            unset($history[$index]);
            $history = array_values($history);
        }
        $_SESSION['search_history'] = $history;
        session_write_close();
        JsonHelper::respond($history);
    }
    
    public function deleteHistoryAction(){
        $_SESSION['search_history'] = array();
        session_write_close();
        JsonHelper::respond($_SESSION['search_history']);
    }
    
    public function searchAction(){
        Util::turnRendererOn();
        $this->saveHistory();
        $term = InputHelper::getParam('q');
//        $mode = InputHelper::getParam('mode', 'votes');
        $scope = InputHelper::getParam('scope', 'all');
        $page = InputHelper::getParam('page', 1);
        $debug = InputHelper::getParam('debug', 0);
        $posts = array();
        $terms = array();
        if($term){
            
            $itemsPerPage = OptionHelper_wpp_BRX_SearchEngine::getOption('items_per_page', 10);
            $_SESSION['search_scope'] = $scope;
            $title = 'Результаты поиска';
            if('all' != $scope){
                $scopes = SearchHelper::getScopes();
                $scopeData = Util::getItem($scopes, $scope);
                $scopeLabel = Util::getItem($scopeData, 'label');
                $title = sprintf('&quot;%s&quot; %s', $term, $scopeLabel);
                
            }
            WpHelper::setPostTitle($title);
            
            $vipsPerPage = OptionHelper_wpp_BRX_SearchEngine::getOption('vip_items_per_page', 3);
            
            $vipPosts = $vipsPerPage?
                SearchHelper::searchPosts($term, $scope, $page, $vipsPerPage, 'vip_keywords', true):
                array();

            $posts = SearchHelper::searchPosts($term, $scope, $page, $itemsPerPage, null);

            $this->setupNavigation($term, $scope, $page, $itemsPerPage, SearchHelper::getTotalFound(), $debug);
            foreach ($posts as $post) {
                $post->loadTerms();
            }
            $words = preg_split('%[\s]+%u', $term);
            $pieces = array();
            foreach ($words as $word) {
                $pieces[] = "name LIKE '$word%'";
            }
            $where = join(' OR ', $pieces);
            global $wpdb;
            $wpdbquery = "
                SELECT *
                FROM $wpdb->terms AS tr
                JOIN $wpdb->term_taxonomy AS tx USING (term_id)
                WHERE $where
                ";
            $terms = $wpdb->get_results($wpdbquery);
        }
        $this->view->posts = $posts;
        $this->view->vipPosts = $vipPosts;
        $this->view->scope = $scope;
        $this->view->term = $term;
        $this->view->terms = $terms;
        $this->view->debug = $debug;
        
        wp_enqueue_style('se-search-page');
        wp_enqueue_style('pagination');
        wp_enqueue_script('se-search-form');
        if($debug){
            wp_enqueue_style('se-search-debug');
        }
        $template = OptionHelper_wpp_BRX_SearchEngine::getOption('template');
        if($template){
            WpHelper::setPageTemplate($template);
        }
        
    }
    
    protected function saveHistory(){
        $query = InputHelper::getParam('q');
        @session_start();
        $history = Util::getItem($_SESSION, 'search_history', array());
        if(array_search($query, $history)===false){
            $history = array_merge(array($query), $history);
        }
        $_SESSION['search_history'] = $history;
    }
   
    
    protected function setupNavigation($term, $scope, $page, $itemsPerPage, $total, $debug = false){
        $pagination = new PaginationModel();
        $pagination->setCurrentPage($page);
        $pagination->setPackSize(10);
        $pagination->setTotalPages(ceil($total / $itemsPerPage));
        $pagination->setItemsPerPage($itemsPerPage);
//        $router = Util::getFront()->getRouter();
        $pageLinkPattern = UrlHelper_wpp_BRX_SearchEngine::search($term, $scope, '.page.', $debug);//$router->assemble(array('mode'=>$mode, 'page'=>'.page.', 'taxonomy'=>$taxonomy, 'scope'=>$scope), 'tag');
        $pagination->setPageLinkPattern($pageLinkPattern);
        $this->view->pagination = $pagination;
    }
}