<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of OptionHelper_SearchEngine
 *
 * @author borismossounov
 */
class OptionHelper_SearchEngine {

    public static function getOption($option, $default='', $reload = false){
        $key = 'SearchEngine.'.$option;
        return get_site_option($key, $default, !$reload);
    }
    
    public static function setOption($option, $value){
        $key = 'SearchEngine.'.$option;
        return update_site_option($key, $value);
    }
    
}
