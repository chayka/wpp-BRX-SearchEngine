<?php

class WP_Widget_SearchResults extends WP_Widget {
    
    protected static $args;

    public static function getArgs(){
        return self::$args;
    }
    
    public static function getTitle(){
        return self::$args['title'];
    }
    
    function __construct($id = 'widget-search-results', $name = 'Поиск похожих материалов', 
        $widget_ops = array(
            'classname' => 'WP_Widget_SearchResults',
            'description' => "Виджет показывает результаты поиска по запросу, взятому из указанного поля отображаемого на странице поста"
        )) {
        parent::__construct($id, $name, $widget_ops);
        $this->alt_option_name = $id;

    }

    function widget($args, $instance) {
        global $post;
//        echo "postId: ".$post->ID;
        if(!(is_single() || is_page()) || !$post->ID){
            return;
        }
        self::$args = $instance;
        extract($args);

        $showForPostTypes = Util::getItem($instance, 'showForPostTypes');
        
        $showForPostTypes = $showForPostTypes ? 
            preg_split('%\s*,\s*%', $showForPostTypes):
            null;
        if($showForPostTypes && !in_array($post->post_type, $showForPostTypes)){
            return;
        }
        
        $sources = preg_split('%\s*,\s*%', Util::getItem($instance, 'sources'));
        $search = '';
        
        if(empty($sources)){
            $sources = array(
                'title',
                'post_tag',
                'category',
            );
        }
        
        foreach($sources as $source){
            switch($source){
                case 'title':
                    $search = get_the_title();
//                    echo 'title detected';
                    break;
                default: 
                    $terms = wp_get_post_terms($post->ID, $source, array("fields" => "names"));
                    if(!is_wp_error($terms) && count($terms)){
//                        Util:: print_r($terms);
                        $search = join(' ', $terms);
//                        echo $source .' tax detected';
                        break;
                    }
                    $meta = get_post_meta($post->ID, $source, true);
                    if($meta){
//                        echo $source .' meta detected: '.$meta;
                        $search = $meta;
                    }
                    break;
            }
            if($search){
                break;
            }
//            echo " searching by $search ";
        }
        
        if(!$search){
            return;
        }
        
        $scope = Util::getItem($instance, 'scope', 'all');
        
        $number = Util::getItem($instance, 'number', 5);

        $limit = Util::getItem($instance, 'limit', 100);
        
        SearchHelper::setLimit($limit);
        
        $posts = SearchHelper::searchPosts($search, $scope, 1, $number+1);
        
        if(empty($posts)){
            return;
        }

        foreach ($posts as $i=>$p) {
            if($p->getId() == $post->ID){
                unset($posts[$i]);
            }
        }
        
        if(empty($posts)){
            return;
        }
        
        $title = apply_filters( 'widget_title', Util::getItem($instance, 'title', 'Похожие материалы') );

        echo $before_widget;
        if ( ! empty( $title ) )
                echo $before_title . $title . $after_title;
        echo '<ul>';
        foreach($posts as $p){
            if($number-- > 0){
                printf('<li><a href="%s">%s</a></li>', get_permalink($p->getId()), $p->getTitle());
            }else{
                break;
            }
        }
        echo '</ul>';
        echo $after_widget;
    }

    function flush() {
        wp_cache_delete($this->id_base, 'widget');
    }
    
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags(Util::getItem($new_instance, 'title'));
        $instance['showForPostTypes'] = strip_tags(Util::getItem($new_instance, 'showForPostTypes'));
        $instance['scope'] = strip_tags(Util::getItem($new_instance, 'scope'));
        $instance['sources'] = strip_tags(Util::getItem($new_instance, 'sources'));
        $instance['number'] = (int)strip_tags(Util::getItem($new_instance, 'number'));
        $instance['limit'] = (int)strip_tags(Util::getItem($new_instance, 'limit'));
//        $this->flush();

        $alloptions = wp_cache_get('alloptions', 'options');
        if (isset($alloptions[$this->id_base])){
            delete_option($this->id_base);
        }
        
        return $instance;
    }

    function form($instance) {
        $title = esc_attr(Util::getItem($instance, 'title', 'Похожие материалы'));
        $sources = esc_attr(Util::getItem($instance, 'sources', 'related_query, post_tag, title, category'));
        $showForPostTypes = esc_attr(Util::getItem($instance, 'showForPostTypes', ''));
        $scope = esc_attr(Util::getItem($instance, 'scope', 'all'));
        $number = esc_attr(Util::getItem($instance, 'number', 5));
        $limit = esc_attr(Util::getItem($instance, 'limit', 100));
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

            <p><label for="<?php echo $this->get_field_id('sources'); ?>"><?php _e('Источник запроса:'); ?></label>
            <input id="<?php echo $this->get_field_id('sources'); ?>" name="<?php echo $this->get_field_name('sources'); ?>" type="text" value="<?php echo $sources; ?>" /></p>

            <p><label for="<?php echo $this->get_field_id('scope'); ?>"><?php _e('Область поиска:'); ?></label>
            <input id="<?php echo $this->get_field_id('scope'); ?>" name="<?php echo $this->get_field_name('scope'); ?>" type="text" value="<?php echo $scope; ?>" /></p>

            <p><label for="<?php echo $this->get_field_id('showForPostTypes'); ?>"><?php _e('Показывать для типов записей:'); ?></label>
            <input id="<?php echo $this->get_field_id('showForPostTypes'); ?>" name="<?php echo $this->get_field_name('showForPostTypes'); ?>" type="text" value="<?php echo $showForPostTypes; ?>" /></p>

            <p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Количество результатов:'); ?></label>
            <input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" /></p>

            <p><label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('Точность поиска:'); ?></label>
            <input id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="text" value="<?php echo $limit; ?>" /></p>

        <?php
    }
    
    public static function registerWidget(){
        register_widget( "WP_Widget_SearchResults" );
    }

}

add_action( 'widgets_init', array('WP_Widget_SearchResults', 'registerWidget'));
