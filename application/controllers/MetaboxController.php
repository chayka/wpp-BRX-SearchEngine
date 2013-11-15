<?php

class wpp_BRX_SearchEngine_MetaboxController  extends Zend_Controller_Action{
    public function init(){
    }

    public function searchOptionsAction(){
        global $post;
        
        $zfPost = PostModel::unpackDbRecord($post);
//        wp_enqueue_style('se-search-options');
        wp_nonce_field( 'search_options', 'search_options_nonce' );
        $meta = array();
        $this->view->relatedQuery = $meta['related_query'] = $zfPost->getMeta('related_query');
        $this->view->vipKeywords = $meta['vip_keywords'] = $zfPost->getMeta('vip_keywords');
        $this->view->vipExcerpt = $meta['vip_excerpt'] = $zfPost->getMeta('vip_excerpt');
        
        $this->view->meta = $meta;
    }
    
    
}