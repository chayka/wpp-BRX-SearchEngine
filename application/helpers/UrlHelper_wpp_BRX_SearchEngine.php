<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UrlHelper_wpp_BRX_SearchEngine
 *
 * @author borismossounov
 */
class UrlHelper_wpp_BRX_SearchEngine {
    
    public static function search($term, $postTypes=null, $page=1, $debug = false){
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
        if($debug){
            $url.='&debug=1';
        }
        
        return $url;
    }
}

