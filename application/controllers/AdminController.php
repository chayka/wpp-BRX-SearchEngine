<?php

/**
 * Description of AdminController
 *
 * @author borismossounov
 */
class wpp_BRX_SearchEngine_AdminController  extends Zend_Controller_Action{
    public function init(){
    }
    
    public function indexerAction(){
        
//        Util::print_r(get_taxonomies(array(
////            'object_type'=>array(
////                'review'
////                ),
////            'public'=>true,
////            '_builtin'=>false,
//            ), 'objects'));
        
//        $this->view->postTypeInfo = SearchHelper::getPostTypeInfo();
//        $lastOptimized = OptionHelper_wpp_BRX_SearchEngine::getOption('lastOptimized');
//        $this->view->lastOptimized = DateHelper::dbStrToDatetime($lastOptimized);
        wp_enqueue_style('se-control-panel');
        wp_enqueue_script('se-control-panel');
//        wp_enqueue_style('jquery-ui-smoothness');
//        wp_enqueue_style('jquery-ui');
    }

    public function setupSearchEngineAction(){
        wp_enqueue_style('admin-setupForm');
        wp_enqueue_style('se-setup-form');
        wp_enqueue_script('jquery-brx-setupForm');
        
        $this->view->items_per_iteration = $options['items_per_iteration'] = OptionHelper_wpp_BRX_SearchEngine::getOption('items_per_iteration', 10, true);
        $this->view->items_per_page = $options['items_per_page'] = OptionHelper_wpp_BRX_SearchEngine::getOption('items_per_page', 10, true);
        $this->view->vip_items_per_page = $options['vip_items_per_page'] = OptionHelper_wpp_BRX_SearchEngine::getOption('vip_items_per_page', 5, true);
        $this->view->highlight = $options['highlight'] = OptionHelper_wpp_BRX_SearchEngine::getOption('highlight', 0, true);
        $this->view->tamplate = $options['template'] = OptionHelper_wpp_BRX_SearchEngine::getOption('template', '', true);
        $this->view->samples = $options['samples'] = OptionHelper_wpp_BRX_SearchEngine::getOption('samples', '', true);
        $this->view->areas = $options['areas'] = OptionHelper_wpp_BRX_SearchEngine::getOption('areas', '', true);

        $this->view->options = $options;
    }

    public function updateSearchEngineAction(){
        Util::turnRendererOff();

        $itemsPerIteration = (int)InputHelper::getParam('items_per_iteration');
        $itemsPerPage = (int)InputHelper::getParam('items_per_page');
        $vipItemsPerPage = (int)InputHelper::getParam('vip_items_per_page');
        $highlight = InputHelper::getParam('highlight');
        $template = InputHelper::getParam('template');
        $samples = InputHelper::getParam('samples');
        $areas = InputHelper::getParam('areas');

        OptionHelper_wpp_BRX_SearchEngine::setOption('items_per_iteration', $itemsPerIteration);
        OptionHelper_wpp_BRX_SearchEngine::setOption('items_per_page', $itemsPerPage);
        OptionHelper_wpp_BRX_SearchEngine::setOption('vip_items_per_page', $vipItemsPerPage);
        OptionHelper_wpp_BRX_SearchEngine::setOption('highlight', $highlight);
        OptionHelper_wpp_BRX_SearchEngine::setOption('template', $template);
        OptionHelper_wpp_BRX_SearchEngine::setOption('samples', $samples);
        OptionHelper_wpp_BRX_SearchEngine::setOption('areas', $areas);
        
        JsonHelper::respond(array(
            'items_per_iteration' => OptionHelper_wpp_BRX_SearchEngine::getOption('items_per_iteration', 10, true),
            'items_per_page' => OptionHelper_wpp_BRX_SearchEngine::getOption('items_per_page', 10, true),
            'vip_items_per_page' => OptionHelper_wpp_BRX_SearchEngine::getOption('vip_items_per_page', 5, true),
            'highlight' => OptionHelper_wpp_BRX_SearchEngine::getOption('highlight', 0, true),
            'template' => OptionHelper_wpp_BRX_SearchEngine::getOption('template', '', true),
            'samples' => OptionHelper_wpp_BRX_SearchEngine::getOption('samples', '', true),
            'areas' => OptionHelper_wpp_BRX_SearchEngine::getOption('areas', '', true),
        ));
    }
    
    public function searchOptionsAction(){
        global $post;
        
        $zfPost = PostModel::unpackDbRecord($post);
        wp_enqueue_style('se-search-options');
//        wp_enqueue_script('less');
//        wp_enqueue_script('admin-editor');
        wp_nonce_field( plugin_basename( __FILE__ ), 'searchengine_options_box_content_nonce' );
        $meta = get_post_meta($post->ID, null, true);
//        Util::print_r($meta);
        $meta = array();
//        $this->view->itemType = $meta['item_type'] = get_post_meta($post->ID, 'item_type', true);
        $this->view->relatedQuery = $meta['related_query'] = get_post_meta($post->ID, 'related_query', true);
        $this->view->vipKeywords = $meta['vip_keywords'] = get_post_meta($post->ID, 'vip_keywords', true);
        $this->view->vipExcerpt = $meta['vip_excerpt'] = get_post_meta($post->ID, 'vip_excerpt', true);
        
        $this->view->meta = $meta;
    }
    
    public function updatePostAction(){
        
        Util::turnRendererOff();
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $postId = InputHelper::getParam('post_id');

        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        if (!wp_verify_nonce($_POST['searchengine_options_box_content_nonce'], plugin_basename(__FILE__))) {
            return;
        }

        $relatedQuery = InputHelper::getParam('search_related_query');
        $vipKeywords = InputHelper::getParam('search_vip_keywords');
        $vipExcerpt = InputHelper::getParam('search_vip_excerpt');

        update_post_meta($postId, 'related_query', $relatedQuery);
        update_post_meta($postId, 'vip_keywords', $vipKeywords);
        update_post_meta($postId, 'vip_excerpt', $vipExcerpt);
        Log::func();
        do_action('lucene_index_post');
        
        return;
    }
}
