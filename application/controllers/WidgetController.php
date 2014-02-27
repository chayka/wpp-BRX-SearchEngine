<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'application/models/posts/AG_PostModel.php';
require_once 'application/models/users/AG_UserModel.php';
require_once 'Zend/Search/Lucene.php';
require_once 'application/helpers/StatsHelper.php';
/**
 * Description of WidgetController
 *
 * @author borismossounov
 */
class WidgetController extends Zend_Controller_Action{
    public $settings = array();
    public function init(){
        Util::turnRendererOff();
//        $trunk = WpHelper::getTrunk();
        $widgets = Util::getItem($trunk, 'widgets', array());
//        print_r($widgets);
        $action = InputHelper::getParam('action');
        $this->settings = Util::getItem($widgets, $action);
//        print_r($settings);
        $title = Util::getItem($this->settings, "title", WP_Widget_ZF::getTitle());
        $this->view->title = $title;
        $this->view->hide = Util::getItem($this->settings, "hide", false);
//        continue;
//        echo $action;
//        if()
//        print_r(WP_Widget_ZF::getArgs());
    }
    
    public function indexAction(){
        global $wp_the_query;
        echo "Hi, I'm ZF Widget! ";
        
    }
    
//    public function queryAction(){
//        Util::turnRendererOn();
//    }
//    
//    public function articlesAction(){
//        if($this->view->hide){
//            Util::turnRendererOff();
//            return;
//        }
//        Util::turnRendererOn();
//        $posts = Util::getItem($this->settings, 'posts');
//        $total = Util::getItem($this->settings, 'total', 0);
//        $link = Util::getItem($this->settings, 'link');
//        $count = InputHelper::getParam('count', 5);
//        if(empty($posts) && !is_array($posts)){
//            $args = Util::getItem($this->settings, 'query', WpHelper::getQuery());
//            $args['post_type'] = 'post';
//            $args['posts_per_page'] = $count;
//            $args['offset'] = 0;
//            $args['paged'] = 1;
//            $posts = AG_PostModel::selectPosts($args);
//            $total = AG_PostModel::getWpQuery()->found_posts;
//            $link = '/articles';
//        }
//        
////        $this->view->title = WP_Widget_ZF::getTitle();
//        $this->view->articles = $posts;
//        $this->view->total = $total;
//        $this->view->link = $link;
//    }
//    
//    public function questionsAction(){
//        if($this->view->hide){
//            Util::turnRendererOff();
//            return;
//        }
//        Util::turnRendererOn();
//        $posts = Util::getItem($this->settings, 'posts');
//        $total = Util::getItem($this->settings, 'total', 0);
//        $link = Util::getItem($this->settings, 'link');
//        $count = InputHelper::getParam('count', 5);
//        if(empty($posts) && !is_array($posts)){
//            $args = Util::getItem($this->settings, 'query', WpHelper::getQuery());
//            $args['post_type'] = 'question';
//            $args['posts_per_page'] = $count;
//            $args['offset'] = 0;
//            $args['paged'] = 1;
//
//            $posts = AG_PostModel::selectPosts($args);
//            $total = AG_PostModel::getWpQuery()->found_posts;
//            $link = '/questions';
//        }
//        $this->view->questions = $posts;
//        $this->view->total = $total;
//        $this->view->link = $link;
//    }
//    
//    public function profilesAction(){
//        if($this->view->hide){
//            Util::turnRendererOff();
//            return;
//        }
//        Util::turnRendererOn();
//        $posts = Util::getItem($this->settings, 'posts');
//        $total = Util::getItem($this->settings, 'total', 0);
//        $link = Util::getItem($this->settings, 'link');
//        $count = InputHelper::getParam('count', 5);
//        if(empty($posts) && !is_array($posts)){
//            $args = Util::getItem($this->settings, 'query', WpHelper::getQuery());
//            $args['post_type'] = 'profile';
//            $args['posts_per_page'] = $count;
//            $args['offset'] = 0;
//            $args['paged'] = 1;
//            $posts = AG_PostModel::selectPosts($args);
//            $total = AG_PostModel::getWpQuery()->found_posts;
//            $link = '/users';
//        }
//        $this->view->profiles = $posts;
//        $this->view->total = $total;
//        $this->view->link = $link;
//    }
    
//    public function tagsAction(){
//        if($this->view->hide){
//            Util::turnRendererOff();
//            return;
//        }
//        Util::turnRendererOn();
//        $terms = Util::getItem($this->settings, 'terms');
//        if(!$terms){
//            $taxonomy = InputHelper::getParam('taxonomy');
//            if(!$taxonomy){
//                $taxonomy = array(
//                    'post_tag',
//                    'profession',
//                    'work',
//                    'equipment',
//                    'material',
//                );
//            }
//
//            $count = InputHelper::getParam('count', 10);
//            $terms = get_terms($taxonomy, array(
//                'number' => $count,
//                'orderby' => 'count',
//                'order' => 'DESC',
//                'hide_empty' => true,
//            ));
//        }
//        $this->view->terms = $terms;
//
//    }
    
////    public function bannersAction(){
////        if($this->view->hide){
////            Util::turnRendererOff();
////            return;
////        }
////        Util::turnRendererOn();
////        global $wpdb;
////        
////        $ids = InputHelper::getParam('ids');
////        $count = min(count($ids), InputHelper::getParam('count', 1));
////        $ids = preg_split('%,\s*%', $ids);
//////        print_r($ids);
////        $keys = array_rand($ids, $count);
////        if(!is_array($keys)){
////            $keys = array($keys);
////        }
////        $reducedIds = array();
//////        print_r($keys);
////        for($i = 0; $i<$count; $i++){
////            $key = $keys[$i];
////            $reducedIds[]=$ids[$key];
////        }
//////        print_r($reducedIds);
////        $reducedIds = join(', ', $reducedIds);
////        $query = $wpdb->prepare("
////                SELECT * FROM $wpdb->posts 
////                WHERE `post_type` = 'attachment'
////                AND ID IN ($reducedIds)
////                "
////                );
////        
////        $results = $wpdb->get_results($query);
////        
////        $banners = array();
////        foreach($results as $banner){
////            $banners[] = array(
////                'src' => $banner->guid,
////                'link' => $banner->post_content,
////                'alt' => $banner->post_excerpt
////            );
////        }
////        $this->view->banners = $banners;
////    }
//    
//    public function postAction(){
//        if($this->view->hide){
//            Util::turnRendererOff();
//            return;
//        }
//        Util::turnRendererOn();
//        $id = InputHelper::getParam('id');
//        $slug = InputHelper::getParam('slug');
//        $htmlClasses = InputHelper::getParam('classes', '');
//        $page = null;
//        if($id){
//            $page = get_page($id);
//        }elseif($slug){
//            $page = get_page_by_path($slug);
//        }
//        
////        print_r($page);
//        $post = $page?AG_PostModel::unpackDbRecord($page):null;
////        die('(!)');
//        $this->view->title = WP_Widget_ZF::getTitle()?
//                WP_Widget_ZF::getTitle():$post->getTitle();
//        $this->view->post = $post;
//        
//        $this->view->htmlClasses = $htmlClasses;
////        print_r($this->view->post);
//    }
//    
//    public function profileMenuAction(){
//        if($this->view->hide){
//            Util::turnRendererOff();
//            return;
//        }
//        Util::turnRendererOn();
//        $userId = WpHelper::getRequest()->getParam('id');
//        
//        $user = ($userId == get_current_user_id() || AclHelper::isAdmin())?
//                AG_UserModel::selectById($userId):null;
////        print_r($user);
//        $this->view->title = WP_Widget_ZF::getTitle();
//        $this->view->user = $user;
//    }
    
    public function searchAdvancedAction(){
        Util::turnRendererOff();
        $index = LuceneHelper::getInstance();
        echo "numdocs: ".  LuceneHelper::getInstance()->numDocs();
        $term = InputHelper::getParam('term', 'кот');
        echo "term: ".$term;
        $q = LuceneHelper::parseQuery($term);
        $hits = LuceneHelper::searchHits($q);
        echo "hits: ".count($hits);
        echo "numdocs: ".  LuceneHelper::getInstance()->numDocs();
        print_r(LuceneHelper::getInstance());
        
        print_r($hits);
    }
    
//    public function testSearchAction(){
//        set_time_limit(0);
//        Util::turnRendererOff();
//        $postId = InputHelper::getParam('postId', 0);
//        $number = InputHelper::getParam('number', 10);
//        $page = InputHelper::getParam('page', 1);
//        $postType = InputHelper::getParam('postType', 'question');
//        echo "<pre>";
//        $postIn = $postId?array($postId): array();
//        $posts = AG_PostModel::selectPosts(array(
//            'post_type' => $postType, 
//            'posts_per_page'=>$number, 
//            'post__in'=>$postIn, 
//            'paged'=>$page
//            ));
//        print_r($posts);
////        $users = AG_UserModel::selectUsers(array('exclude'=>1));
////        print_r($users);
//        try{
//            $index = LuceneHelper::getInstance();
//            echo "numDocs: ". $index->numDocs()."\n";
//            foreach($posts as $post){
//                LuceneHelper::indexDocument($post);
//                echo 'added: '.$post->getTitle()."\n";
//                $index->commit();
//                $index->optimize();
//            }
//            echo "numDocs: ". $index->numDocs()."\n";
//////            foreach($users as $user){
//////                LuceneHelper::indexDocument($user);
//////                echo 'added: '.$user->getLogin()."\n";
//////                $index->commit();
//////                $index->optimize();
//////            }
//////            echo "numDocs: ". $index->numDocs()."\n";
////            
//        }catch(Exception $e){
//            echo 'error: '. $e->getMessage();
//        }
//        echo "</pre>";
//        die();
//        
//    }
    
    public function searchAction(){
        Util::turnRendererOff();
        
        $term = InputHelper::getParam('term', 'кот');
//        $query = LuceneHelper::parseQuery('post_type: question AND '.$term);
        $query = LuceneHelper::parseQuery($term);
        $ids = LuceneHelper::searchIds($query);
//        foreach($ids as $hit){
//            $x[]=$hit->getDocument()->getFieldValue(LuceneHelper::getIdField());
//        }
//        print_r($x);
        
//        $ids = LuceneHelper::searchHits($query);
        print_r($ids);
        LuceneHelper::getInstance()->commit();
        LuceneHelper::getInstance()->optimize();
        $query = LuceneHelper::parseQuery($term);
        $ids = LuceneHelper::searchHits($query);
//        print_r($ids);
//        $index = Zend_Search_Lucene::open(PathHelper::getLuceneDir($_SERVER['SERVER_NAME']));
//        $index = LuceneHelper::getInstance();
////        print_r($index);
//        $docs = $index->find('кот');
//        echo " docs found :".count($docs);
//        $docs = $index->find('спать');
//        echo " docs found :".count($docs);
        
    }
    
    public function searchHistoryAction(){
        Util::turnRendererOff();
//        return;
        Util::turnRendererOn();
        $history = Util::getItem($_SESSION, 'search_history', array());
        $count = InputHelper::getParam('count', 5);
        $this->view->history = $history;
        $this->view->count = $count;
        wp_enqueue_script('jquery-brx-searchHistory');
    }
    
//    public function wpdbAction(){
//        Util::turnRendererOff();
//        try{
//            print_r(WpDbHelper::getAdapter());
////        $posts = WpDbHelper::getAdapter()
////                ->select()
////                ->from('wp_posts')
////                ->where('post_type = ?', 'question');
//        }catch(Exception $e){
//            echo $e->getMessage();
//        }
//    }
//    
//    public function emailAction(){
//        Util::turnRendererOff();
//        require_once 'application/helpers/EmailHelper.php';
////        EmailHelper::sendTemplate('Hello user', 'hello-user.phtml', array(
////            'user' => AG_UserModel::selectByLogin('admin')
////        ), 'mossounov@me.com');
//        
//        $login = InputHelper::getParam('login', '');
//        $userId = InputHelper::getParam('userId', 1);
//        $user = null;
//        if($login){
//            $user = AG_UserModel::selectByLogin($login);
//        }
//        if(!$user && $userId){
//            $user = AG_UserModel::selectById($userId);
//        }
//        $slug = InputHelper::getParam('slug');
//        $postId = InputHelper::getParam('postId');
//        $post = null;
//        if($slug){
//            $post = AG_PostModel::selectBySlug($slug);
//        }
//        if(!$post && $postId){
//            $post = AG_PostModel::selectById($postId);
//        }
//        $commentId = InputHelper::getParam('commentId');
//        $comment = $commentId?CommentModel::selectById($commentId):null;
//        
//        $template = InputHelper::getParam('template');
//        
//        switch($template){
//            case 'user-registered':
//                $password = InputHelper::getParam('password', '12345678');
//                EmailHelper::userRegistered($user, $password);
//                echo 'sent';
//                break;
//            case 'forgot-password':
//                $key = InputHelper::getParam('key', 'a1b2c3d4e5f6a7b8');
//                EmailHelper::forgotPassword($user, $key);
//                echo 'sent';
//                break;
//            case 'user-asked-question':
//                EmailHelper::userAskedQuestion($user, $post);
//                echo 'sent';
//                break;
//            case 'user-answered-question':
////                $question = AG_PostModel::selectById($post->getParentId());
//                EmailHelper::userAnsweredQuestion($user, $post);
//                echo 'sent';
//                break;
//            case 'user-commented-post':
//                EmailHelper::userCommentedPost($user, $comment);
//                echo 'sent';
//                break;
//            case 'user-marked-best-answer':
//                EmailHelper::userMarkedBestAnswer($user, $post);
//                echo 'sent';
//                break;
//        }
//        die(' :) ');
//    }
    
//    public function numbersAction(){
////        $ago = new Zend_Date();
//        
////        $ago->subYear(1);
////        $ago->subMonth(2);
////        $ago->subDay(3);
////        $ago->subHour(4);
////        $ago->subMinute(5);
////        $ago->subSecond(6);
////        print_r(FormatHelper::timeAgo($ago));
//        if($this->view->hide){
//            Util::turnRendererOff();
//            return;
//        }
//        Util::turnRendererOn();
//        $query = Util::getItem($this->settings, 'query');
//        $numbers = Util::getItem($this->settings, 'numbers', array());
//
//        $rootArticles = false;
//        if(in_array('articles', $query)){
//            $numbers['articles'] = array(
//                'text' => 'Статей всего',
//                'link' => '/articles',
//                'count' => StatsHelper::countArticles(),
//            );
//            $rootArticles = true;
//        }
//
//        if(in_array('articles-lastweek', $query)){
//            $number = array(
//                'text' => $rootArticles?'За неделю':'Статей за неделю',
//                'link' => '/articles/new',
//                'count' => StatsHelper::countArticlesLastWeek(),
//            );
//            if($rootArticles){
//                $numbers['articles']['subitems']['articles-lastweek'] = $number;
//            }else{
//                $numbers['articles-lastweek'] = $number;
//            }
//        }
//        
//        if(in_array('articles-lastmonth', $query)){
//            $number = array(
//                'text' => $rootArticles?'За месяц':'Статей за месяц',
//                'link' => '/articles/new',
//                'count' => StatsHelper::countArticlesLastMonth(),
//            );
//            if($rootArticles){
//                $numbers['articles']['subitems']['articles-lastmonth'] = $number;
//            }else{
//                $numbers['articles-lastmonth'] = $number;
//            }
//        }
//        
//        $rootQuestions = false;
//        if(in_array('questions', $query)){
//            $numbers['questions'] = array(
//                'text' => 'Вопросов всего',
//                'link' => '/questions',
//                'count' => StatsHelper::countQuestions(),
//            );
//            $rootQuestions = true;
//        }
//
//        if(in_array('questions-noanswer', $query)){
//            $number = array(
//                'text' => $rootQuestions?'Без ответа':'Вопросов без ответа',
//                'link' => '/unanswered',
//                'count' => StatsHelper::countQuestions(null, 1),
//            );
//            if($rootQuestions){
//                $numbers['questions']['subitems']['questions-noanswer'] = $number;
//            }else{
//                $numbers['questions-noanswer'] = $number;
//            }
//        }
//        
//        if(in_array('questions-lastweek', $query)){
//            $number = array(
//                'text' => $rootQuestions?'За неделю':'Вопросов за неделю',
//                'link' => '/questions/new',
//                'count' => StatsHelper::countQuestionsLastWeek(),
//            );
//            if($rootQuestions){
//                $numbers['questions']['subitems']['questions-lastweek'] = $number;
//            }else{
//                $numbers['questions-lastweek'] = $number;
//            }
//        }
//        
//        if(in_array('questions-lastmonth', $query)){
//            $number = array(
//                'text' => $rootQuestions?'За месяц':'Статей за месяц',
//                'link' => '/questions/new',
//                'count' => StatsHelper::countQuestionsLastMonth(),
//            );
//            if($rootQuestions){
//                $numbers['questions']['subitems']['questions-lastmonth'] = $number;
//            }else{
//                $numbers['questions-lastmonth'] = $number;
//            }
//        }
//        
//        $rootUnanswered = false;
//        if(in_array('unanswered', $query)){
//            $numbers['unanswered'] = array(
//                'text' => 'Вопросов без ответа',
//                'link' => '/unanswered',
//                'count' => StatsHelper::countQuestions(null, 1),
//            );
//            $rootUnanswered = true;
//        }
//
//        if(in_array('unanswered-lastweek', $query)){
//            $number = array(
//                'text' => $rootUnanswered?'За неделю':'Без ответа за неделю',
//                'link' => '/unanswered/new',
//                'count' => StatsHelper::countQuestionsLastWeek(1),
//            );
//            if($rootUnanswered){
//                $numbers['unanswered']['subitems']['unanswered-lastweek'] = $number;
//            }else{
//                $numbers['unanswered-lastweek'] = $number;
//            }
//        }
//        
//        if(in_array('unanswered-lastmonth', $query)){
//            $number = array(
//                'text' => $rootUnanswered?'За месяц':'Без ответа за месяц',
//                'link' => '/unanswered/new',
//                'count' => StatsHelper::countQuestionsLastMonth(1),
//            );
//            if($rootUnanswered){
//                $numbers['unanswered']['subitems']['unanswered-lastmonth'] = $number;
//            }else{
//                $numbers['unanswered-lastmonth'] = $number;
//            }
//        }
//        
//        $rootUsers = false;
//        if(in_array('users', $query)){
//            $numbers['users'] = array(
//                'text' => 'Пользователей всего',
//                'link' => '/users',
//                'count' => StatsHelper::countUsers(),
//            );
//            $rootUsers = true;
//        }
//
//        if(in_array('users-lastweek', $query)){
//            $number = array(
//                'text' => $rootUsers?'Новых за неделю':'Новых пользователей за неделю',
//                'link' => '/users/new',
//                'count' => StatsHelper::countUsersLastWeek(),
//            );
//            if($rootUsers){
//                $numbers['users']['subitems']['users-lastweek'] = $number;
//            }else{
//                $numbers['users-lastweek'] = $number;
//            }
//        }
//        
//        if(in_array('users-lastmonth', $query)){
//            $number = array(
//                'text' => $rootUsers?'Новых за месяц':'Новых пользователей за месяц',
//                'link' => '/users/new',
//                'count' => StatsHelper::countUsersLastMonth(),
//            );
//            if($rootUsers){
//                $numbers['users']['subitems']['users-lastmonth'] = $number;
//            }else{
//                $numbers['users-lastmonth'] = $number;
//            }
//        }
//
//        if(in_array('users-contractors', $query)){
//            $number = array(
//                'text' => $rootUsers?'Подрядчиков':'Подрядчиков',
//                'link' => '/users/contractors',
//                'count' => StatsHelper::countUsers(null, AG_UserModel::ACCOUNT_TYPE_CONTRACTOR),
//            );
//            if($rootUsers){
//                $numbers['users']['subitems']['users-contractors'] = $number;
//            }else{
//                $numbers['users-contractors'] = $number;
//            }
//        }
//        
//        if(in_array('users-consultants', $query)){
//            $number = array(
//                'text' => $rootUsers?'Экспертов':'Экспертов',
//                'link' => '/users/consultants',
//                'count' => StatsHelper::countUsers(null, AG_UserModel::ACCOUNT_TYPE_CONSULTANT),
//            );
//            if($rootUsers){
//                $numbers['users']['subitems']['users-consultants'] = $number;
//            }else{
//                $numbers['users-consultants'] = $number;
//            }
//        }
//        $this->view->numbers = $numbers;
//        
//        
////        $this->view->numbers = array(
////            array(
////                'text' => 'Статей',
////                'link' => '/articles',
////                'count' => 123,
////            ),
////            array(
////                'text' => 'Вопросов',
////                'link' => '/question',
////                'count' => 1234,
////                'subitems' => array(
////                    array(
////                        'text' => 'Без ответа',
////                        'link' => '/articles',
////                        'count' => 12345,
////                    ),
////                    array(
////                        'text' => 'За неделю',
////                        'link' => '/question',
////                        'count' => 123456,
////                    ),
////                    array(
////                        'text' => 'За месяц',
////                        'link' => '/question',
////                        'count' => 12345678,
////                    ),
////                )
////            ),
////            
////        );
//    }
//    
//    public function phpinfoAction(){
//        Util::turnRendererOff();
//        phpinfo();
//        die();
//    }
}
