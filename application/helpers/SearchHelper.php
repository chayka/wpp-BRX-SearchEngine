<?php

require_once 'LuceneHelper.php';

/**
 * Description of SearchHelper
 *
 * @author borismossounov
 */
class SearchHelper {
    
    const META_FIELD_INDEXED = 'last_indexed';
    const SITE_OPTION_SEARCH_ENABLED = 'wpp_BRX_SearchEngine.SearchEnabled';
    
    protected static $totalFound;
    protected static $results;
    protected static $searchEnabled;
    protected static $indexerEnabled = true;
    protected static $scopes;
    
    public static function getSearchEnabledPostTypes(){
        if(empty(self::$searchEnabled)){
            $value = get_site_option(self::SITE_OPTION_SEARCH_ENABLED, '');
            self::$searchEnabled = $value?explode(',', $value):array();
        }
        return self::$searchEnabled;
    }
    
    public static function setSearchEnabledPostTypes($postTypes){
        self::$searchEnabled = array_unique($postTypes);
        self::saveSearchEnabledPostTypes();
    }
    
    public static function saveSearchEnabledPostTypes(){
        $enabled = self::getSearchEnabledPostTypes();
        $value = join(',', $enabled);
        update_site_option(self::SITE_OPTION_SEARCH_ENABLED, $value);
        return $value;
    }
    
    public static function enableSearch($postType){
        if(!$postType){
            return false;
        }
        $enabled = self::getSearchEnabledPostTypes();
//                Util::print_r(self::$searchEnabled);
        if(is_array($postType)){
            self::$searchEnabled = array_unique(array_merge($enabled, $postType));
            self::saveSearchEnabledPostTypes();
        }else{
            if(!in_array($postType, $enabled)){
                self::$searchEnabled[]=$postType;
                self::saveSearchEnabledPostTypes();
            }
        }
        return true;
    }
    
    public static function disableSearch($postType){
        if(!$postType){
            return false;
        }
        $enabled = self::getSearchEnabledPostTypes();
        if(!is_array($postType)){
            $postType = array($postType);
        }
        foreach($enabled as $i=>$value){
            if(in_array($value, $postType)){
                unset(self::$searchEnabled[$i]);
            }
        }
        self::$searchEnabled = array_values(self::$searchEnabled);
        self::saveSearchEnabledPostTypes();
        return true;
    }
    
    public static function isSearchEnabled($postType){
        $enabled = self::getSearchEnabledPostTypes();
        return in_array($postType, $enabled);
    }
    
    public static function enableIndexer(){
        self::$indexerEnabled = true;
    }
    
    public static function disableIndexer(){
        self::$indexerEnabled = false;
    }
    
    public static function isIndexerEnabled(){
        return self::$indexerEnabled;
    }
    
    public static function getScopes(){
        if(!self::$scopes){
            $raw = OptionHelper_wpp_BRX_SearchEngine::getOption('areas');
            $rawStrings = preg_split('%\r?\n%', $raw);
            foreach ($rawStrings as $string) {
//                echo "($string) ";
                $raws = preg_split('%\s*;\s*%', $string);
                
                $scope = Util::getItem($raws, 0); 
                $label = Util::getItem($raws, 1); 
                $postTypes  = Util::getItem($raws, 2); 
                self::$scopes[$scope]=array('label'=>$label);
                if($postTypes){
                    $postTypes = preg_split('%\s*,\s*%', $postTypes);
//                    print_r($postTypes);
                    self::$scopes[$scope]['postTypes'] = array();
                    foreach($postTypes as $postType){
                        if(get_post_type_object($postType) && self::isSearchEnabled($postType)){
                            self::$scopes[$scope]['postTypes'][] = $postType;
                        }
                    }
                    if(!count(self::$scopes[$scope]['postTypes'])){
                        unset(self::$scopes[$scope]);
                    }
                }elseif($scope!='all' && (!get_post_type_object($scope) || !self::isSearchEnabled($scope))){
                    unset(self::$scopes[$scope]);
                }
            }
        }
        
        return self::$scopes;
    }
    
    public static function resolvePostTypes($scope){
        if(!$scope || 'all' == $scope){
            return null;
        }
        
        $scopes = self::getScopes();
        
        $area = Util::getItem($scopes, $scope);
        
        if(!$area){
            return null;
        }
        
        $postTypes = Util::getItem($area, 'postTypes', null);
        
        return $postTypes?$postTypes:$scope;
    }
    
    public static function setLimit($limit = 0){
        Zend_Search_Lucene::setResultSetLimit($limit);
    }
    
    public static function setDefaultSearchField($field = null){
        LuceneHelper::getInstance()->setDefaultSearchField($field);
    }

    public static function cmpTokens($a, $b){
        $diff = $a->numDocs - $b->numDocs;
        return $diff ? $diff : $a->position - $b->position;
    }
    
    /**
     * Reorders words in the query to search more unique words at first
     * 
     * @param type $query
     * @return string Description
     */
    public static function reorederWordsInQuery($query, $searchField = null){
        $tokens = LuceneHelper::tokenize($query);
        $morphy = new MorphyFilter();
        $lucene = LuceneHelper::getInstance();
        $fieldNames = $searchField?
            array($searchField):
            array_diff($lucene->getFieldNames(true), array(
                'PK',
                'post_type',
                'user_id',
                'vip_search_status'
            ));
        foreach($tokens as $token){
            $normalized = $morphy->normalizeWord($token->text);
            $numDocs = 0;
            foreach($fieldNames as $fieldName){
                $term = new Zend_Search_Lucene_Index_Term($normalized, $fieldName);
                $numDocs += $lucene->docFreq($term);
            }
            $token->numDocs = $numDocs;
        }
        
        usort($tokens, array('SearchHelper', 'cmpTokens'));
        $words = array();
        foreach($tokens as $token){
            $words[]=$token->text;
        }
        return implode(' ', $words);
    }
    
    public static function searchPosts($searchQuery, $scope, $page = 1, $itemsPerPage = 5, $searchField = null, $shuffle = false){
        $reorderedQuery = self::reorederWordsInQuery($searchQuery, $searchField);
        $postTypes = self::resolvePostTypes($scope);
        if($postTypes){
            if(!is_array($postTypes)){
                $postTypes = array($postTypes);
            }
            $postTypes = array_intersect(self::getSearchEnabledPostTypes(), $postTypes);
        }else{
            $postTypes = self::getSearchEnabledPostTypes();
        }
        if(!count($postTypes)){
            return array();
        }
        $ptQuery = array();
        foreach($postTypes as $postType){
            $ptQuery[] = sprintf('(post_type: %s)', $postType);
        }
        $strQuery = sprintf('(%s) AND (%s)', 
                join(' OR ', $ptQuery), $reorderedQuery
            );
        
//        $strQuery = '('.$reorderedQuery.')';
        
        if('vip_keywords' == $searchField){
            $strQuery .= ' AND (vip_search_status: VS_YES)';
        }
//(
//    (post_type: page) 
//    OR (post_type: service-item) 
//    OR (post_type: post) 
//    OR (post_type: catalog-item)
//) 
//AND (молодечно) 
//AND (vip_search_status: VS_YES)
//
//(
//    (post_type: page) 
//    OR (post_type: service-item) 
//    OR (post_type: post) 
//    OR (post_type: catalog-item)
//) 
//AND (молодечно)
//        echo $strQuery;
        self::setDefaultSearchField($searchField);
        $lquery = LuceneHelper::parseQuery($strQuery);

        LuceneHelper::setQuery(
            LuceneHelper::parseQuery($searchQuery)
        );
        
        $hits = LuceneHelper::searchHits($lquery);
//        Util::print_r($hits);
        self::setDefaultSearchField(null);
        if(empty($hits)){
            return array();
        }
        $posts = array();
        if(count($hits)){
            $ids = array();
            $scores = array();
            foreach($hits as $hit){
                $id = substr($hit->getDocument()->getFieldValue(LuceneHelper::getIdField()), 3);
                $ids[]=$id;
                $scores[$id]=$hit->score; 
            }

            self::$totalFound = count($ids);
//            printf('[Q: %s, S: %s, f: %d] ', $term, $scope, self::$totalFound);
            if($shuffle){
                shuffle($ids);
            }
            
            $ids = array_slice($ids, ($page - 1)*$itemsPerPage, $itemsPerPage);

            $posts = PostModel::query()
                    ->postType($postTypes)
                    ->postIdIn($ids)
                    ->postsPerPage($itemsPerPage)
                    ->postStatus_Any()
                    ->orderBy_None()
                    ->select();
            $tmp = array();
            foreach($posts as $post){
                $tmp[$post->getId()] = $post;
            }
            $posts = $tmp;
            $tmp = array();
            foreach($ids as $id){
                $tmp[$id] = $posts[$id];
                $tmp[$id]->getWpPost()->score = $scores[$id];
            }
            $posts = $tmp;
        }
        LuceneHelper::getInstance()->resetTermsStream();
//        print_r($posts);
        return $posts;
    }
    
    public static function getTotalFound(){
        return self::$totalFound;
    }
    
    public static function highlight($html, $query=''){
        return OptionHelper_wpp_BRX_SearchEngine::getOption('highlight')?
                LuceneHelper::highlight($html, $query):
                $html;
    }
    
    public static function postsInIndex($postType = ''){
        if($postType){
            $lquery = LuceneHelper::parseQuery(
                sprintf('post_type: %s', $postType)
            );
            $hits = LuceneHelper::searchHits($lquery);
            return count($hits);
        }
        
        return LuceneHelper::getInstance()->numDocs();
    }
    
    public static function luceneReadyPost($post){
        if(!$post){
            return null;
        }
        if(!($post instanceof PostModel)){
            $post = PostModel::unpackDbRecord($post);
        }
        $item[LuceneHelper::getIdField()] = array('keyword', 'pk_'.$post->getId());
        $item['post_type'] = array('keyword', $post->getType());
        $item['title'] = array('unstored', $post->getTitle(), 2);
        $item['content'] = array('unstored', wp_strip_all_tags($post->getContent()));
        $item['user_id'] = array('keyword', 'user_'.$post->getUserId());
        $taxonomies = get_taxonomies();
        foreach ($taxonomies as $taxonomy){
            $post->loadTerms($taxonomy);
        }
        $t = $post->getTerms();
        foreach($t as $taxonomy=>$terms){
            if(count($terms)){
                $item[$taxonomy] = array('unstored', join(', ', $terms));
            }
        }
        
        $item = apply_filters('lucene_ready', $item, $post->getWpPost());
        if($post->getType()!='post'){
            $item = apply_filters(sprintf('lucene_ready_%s', $post->getType()), $item, $post->getWpPost());
        }
        $vipKeywords = trim($post->getMeta('vip_keywords'));
        if($vipKeywords){
            $item['vip_keywords'] = array('unstored', $vipKeywords, 0.001);
            $item['vip_search_status'] = array('keyword', 'VS_YES');
        }else{
//            $item['vip_search_status'] = array('keyword', 'VS_NO');
        }
        return $item;
    }


    public static function indexPost($post){
        if(!$post){
            return null;
        }
        if(!($post instanceof PostModel)){
            $post = PostModel::unpackDbRecord($post);
        }
        
        $item = self::luceneReadyPost($post);
        
        $doc = LuceneHelper::luceneDocFromArray($item);
        LuceneHelper::indexLuceneDoc($doc);
//        update_post_meta($post->getId(), self::META_FIELD_INDEXED, DateHelper::datetimeToDbStr($post->getDtModified()));
        update_post_meta($post->getId(), self::META_FIELD_INDEXED, DateHelper::datetimeToDbStr(new Zend_Date()));
        
        return null;
    }
    
    public static function deletePost($postId){
        delete_post_meta($postId, self::META_FIELD_INDEXED);
        return LuceneHelper::deleteById('pk_'.$postId);
    }
    
    public static function deletePostsByKey($key, $value){
        return LuceneHelper::deleteByKey($key, $value);
    }

    public static function flush(){
        return LuceneHelper::flush();
    }
    
    public static function commit(){
        return LuceneHelper::getInstance()->commit();
    }

    public static function optimize(){
        $date = new Zend_Date();
        OptionHelper_wpp_BRX_SearchEngine::setOption('lastOptimized', DateHelper::datetimeToDbStr($date));
        return LuceneHelper::getInstance()->optimize();
    }
    
    public static function getPostTypeInfo($postTypes = array()){
        $allPostTypes = get_post_types(array(

        ), 'objects');
        $forbidden = array(
            'attachment',
            'revision',
            'nav_menu_item'
        );
        $postTypeInfo = array();
        foreach($allPostTypes as $name => $postType){
            if(in_array($name, $forbidden)||count($postTypes)&&!in_array($name, $postTypes)){
//                unset($postTypes[$name]);
            }else{
                $postType->total = wp_count_posts($name);
                $postType->indexed = SearchHelper::postsInIndex($name);
                $info = array(
                    'name' => $name,
                    'label' => $postType->label,
                    'total' => (int)$postType->total->publish,
                    'indexed' => (int)$postType->indexed,
                    'enabled' => SearchHelper::isSearchEnabled($name)
                );
                $postTypeInfo[$name]=$info;
            }
        }
        
        return $postTypeInfo;
    }
}
