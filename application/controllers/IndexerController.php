<?php

class wpp_BRX_SearchEngine_IndexerController extends Zend_Controller_Action
{

    public function init()
    {
        Util::turnRendererOff();
    }

    public function indexPostAction(){
        if(!SearchHelper::isIndexerEnabled()){
            return;
        }
        $postId = InputHelper::getParam('postId');
        if(!$postId){
            return;
        }
        $post = get_post($postId);
        if(!$post || is_wp_error($post)){
            return;
        }
        if(wp_is_post_autosave($post) || wp_is_post_revision($post)){
            return;
        }
        Log::func();
        if($post->post_status == 'publish' 
                && SearchHelper::isSearchEnabled($post->post_type)){
            SearchHelper::indexPost($post);
        }else{
            SearchHelper::deletePost($postId);
        }
        
    }
    
    public function deletePostAction(){
        if(!SearchHelper::isIndexerEnabled()){
            return;
        }
        $postId = InputHelper::getParam('postId');
        if(!$postId){
            return;
        }
        $post = get_post($postId);
        if(wp_is_post_autosave($post) || wp_is_post_revision($post)){
            return;
        }
        
        SearchHelper::deletePost($postId);
        
    }
    
    public function indexPostsAction(){
        set_time_limit(0);
        global $wpdb;
        Util::turnRendererOff();
        Util::sessionStart();
        $postId = InputHelper::getParam('postId', 0);
        $number = InputHelper::getParam('number', 10);
        $postTypes = InputHelper::getParam('postType', '');
        $start = Util::getItem($_SESSION, 'wpp_BRX_SearchEngine.indexStarted');
        $update = InputHelper::getParam('update');
        $payload = array();
        if(!$start){
            $now = new Zend_Date();
            $start = $_SESSION['wpp_BRX_SearchEngine.indexStarted'] = DateHelper::datetimeToDbStr($now);
            $payload['start'] = DateHelper::datetimeToJsonStr($now); 
//            session_commit();
//            session_write_close();
        }
        if($postTypes){
            $postTypes = explode(',', $postTypes);
        }else{
            $postTypes = SearchHelper::getSearchEnabledPostTypes();
        }
        $postIn = $postId?array($postId): array();
        $sql = sprintf("
            SELECT SQL_CALC_FOUND_ROWS p.*, pm.meta_value AS last_indexed 
            FROM $wpdb->posts AS p
            LEFT JOIN $wpdb->postmeta AS pm
                ON (p.ID = pm.post_id AND pm.meta_key = '%s') 
            WHERE p.post_type IN ('%s') 
                AND (p.post_status = 'publish') 
                AND (pm.post_id IS NULL OR CAST( pm.meta_value AS DATETIME ) < %s) 
            GROUP BY p.ID ORDER BY p.ID ASC LIMIT 0, %d        
            ", SearchHelper::META_FIELD_INDEXED, join("','", $postTypes), $update? "post_modified" :"'$start'", $number);
        
        $qry = array(
            'post_type' => $postTypes, 
            'posts_per_page'=>$number, 
            'post__in'=>$postIn, 
            'orderby'=>'ID',
            'order' => 'ASC',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => SearchHelper::META_FIELD_INDEXED,
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => SearchHelper::META_FIELD_INDEXED,
                    'value' => $start,
                    'compare' => '<',
//                    'type' => 'DATETIME'
                ),
            )
        );
//        Util::print_r($qry);
//        $posts = PostModel::selectPosts($qry);
        $posts = PostModel::selectSql($sql);
        if(empty($posts)){
//            $payload['sql'] = $sql;
            $payload['error'] = mysql_error();
        }

//        Util::print_r($posts);
        $payload['posts_found'] = $payload['posts_left'] = PostModel::postsFound();
        $payload['posts_indexed_before'] = SearchHelper::postsInIndex();
        $payload['log'] = array();
        $payload['posts_indexed'] = array();
        try{
//            $index = LuceneHelper::getInstance();
//            echo "numDocs: ". $index->numDocs()."\n";
            foreach($posts as $post){
                SearchHelper::indexPost($post);
                $payload['log'][] = sprintf("[%d:%s] %s",$post->getId(), $post->getType(), $post->getTitle());
                $payload['posts_left']--;
            }
//            SearchHelper::commit();
//            SearchHelper::optimize();
            $payload['posts_indexed']['total'] = SearchHelper::postsInIndex();
            if(!$payload['posts_left']){
                unset($_SESSION['wpp_BRX_SearchEngine.indexStarted']);
                $payload['stop'] = DateHelper::datetimeToJsonStr(new Zend_Date());
            }
        }catch(Exception $e){
            JsonHelper::respond(null, $e->getCode(), $e->getMessage());
        }
        SearchHelper::commit();
//        SearchHelper::optimize();
        foreach($postTypes as $postType){
            $payload['posts_indexed'][$postType] = SearchHelper::postsInIndex($postType);
        }
//        Util::print_r($payload);
//        die();
        JsonHelper::respond($payload);
        
    }
    
    public function deletePostsAction(){
        set_time_limit(0);
        Util::turnRendererOff();
        global $wpdb;
        $postTypes = InputHelper::getParam('postType', '');
        if($postTypes){
            $postTypes = explode(',', $postTypes);
            $total = SearchHelper::postsInIndex();
            foreach ($postTypes as $postType) {
                $total-=SearchHelper::postsInIndex($postType);
            }
            if($total){
                foreach ($postTypes as $postType) {
                    SearchHelper::deletePostsByKey('post_type', $postType);
                    $sql = $wpdb->prepare("
                        DELETE pm 
                        FROM $wpdb->postmeta AS pm
                        LEFT JOIN $wpdb->posts AS p ON(pm.post_id = p.ID)
                        WHERE pm.meta_key = %s AND p.post_type = %s
                        ", SearchHelper::META_FIELD_INDEXED, $postType);
//                    Log:: dir($sql, 'deleting meta');
                    $wpdb->query($sql);
                }
            }else{
                SearchHelper::flush();
                $sql = $wpdb->prepare("
                    DELETE pm 
                    FROM $wpdb->postmeta AS pm
                    WHERE pm.meta_key = %s
                    ", SearchHelper::META_FIELD_INDEXED);
//                Log:: dir($sql, 'deleting meta');
                    $wpdb->query($sql);
            }
            JsonHelper::respond(SearchHelper::getPostTypeInfo($postTypes));
        }else{
            SearchHelper::flush();
            JsonHelper::respond(SearchHelper::getPostTypeInfo());
        }
        
    }
    
    public function flushAction(){
        SearchHelper::flush();
        echo SearchHelper::postsInIndex();
    }
    
    public function enableTypeAction(){
        $postType = InputHelper::getParam('postType');
        if($postType){
            SearchHelper::enableSearch($postType);
        }
        JsonHelper::respond(SearchHelper::getSearchEnabledPostTypes());
    }
    
    public function disableTypeAction(){
        $postType = InputHelper::getParam('postType');
        if($postType){
            SearchHelper::disableSearch($postType);
        }
        JsonHelper::respond(SearchHelper::getSearchEnabledPostTypes());
    }
    
    public function optimizeAction(){
        SearchHelper::optimize();
        $dbDate = OptionHelper_wpp_BRX_SearchEngine::getOption('lastOptimized');
        $date = DateHelper::dbStrToDatetime($dbDate);
        JsonHelper::respond(array('last_optimized'=>$date));
    }

}

