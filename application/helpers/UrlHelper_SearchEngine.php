<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UrlHelper_SearchEngine
 *
 * @author borismossounov
 */
class UrlHelper_SearchEngine {
    
    public static function search($term, $postTypes=null, $page=1){
        if(!$postTypes){
            $postTypes = 'all';
        }
        if(is_array($postTypes)){
            $postTypes = join(',', $postTypes);
        }
            
        $router = Util::getFront()->getRouter();
        $router->clearParams();
        $url = $router->assemble(array('scope'=>$postTypes), 'search', true)
            .'/?q='.urlencode($term);
        if($page!=1){
            $url.='&page='.$page;
        }
        
        return $url;
    }
}

