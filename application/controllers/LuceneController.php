<?php

require_once 'application/models/posts/AG_PostModel.php';
require_once 'application/models/users/AG_UserModel.php';
require_once 'ZendB/FileSystem.php';

/**
 * Description of LuceneController
 *
 * @author borismossounov
 */
class LuceneController extends Zend_Controller_Action{

    public function init(){
        Util::turnRendererOff();
    }
    
    public function indexAction(){
        try{
            printf("<pre>dir: %s\rnumdocs: %d\r</pre>", 
                    PathHelper::getLuceneDir(LuceneHelper::serverName()), 
                    LuceneHelper::getInstance()->numDocs());
        }catch(Exception $e){
            echo $e->getMessage();
        }
    }
    
    public function indexPostsAction(){
        set_time_limit(0);
        Util::turnRendererOff();
        $postId = InputHelper::getParam('postId', 0);
        $number = InputHelper::getParam('number', 10);
        $page = InputHelper::getParam('page', 1);
        $offset = InputHelper::getParam('offset', 0);
        $commit = InputHelper::getParam('commit', true);
        $postType = InputHelper::getParam('postType', 'question');
        echo "<pre>";
        $postIn = $postId?array($postId): array();
        $qry = array(
            'post_type' => $postType, 
            'posts_per_page'=>$number, 
            'post__in'=>$postIn, 
            'orderby'=>'ID',
            'order' => 'ASC',
            );
        if($offset){
            $qry['offset'] = $offset;
        }else{
            $qry['paged'] = $page;
            $offset = ($page - 1) * $number;
        }
        $posts = AG_PostModel::selectPosts($qry);
//        print_r($posts);
        printf("posts total: %d\rpost type: %s\rnumber: %d\roffset: %d\rpage: %d\rcommit: %s\r", 
                AG_PostModel::getWpQuery()->found_posts,
                $postType,
                $number,
                $offset,
                $page,
                $commit
                );
        
        try{
            $index = LuceneHelper::getInstance();
            echo "numDocs: ". $index->numDocs()."\n";
            foreach($posts as $post){
                LuceneHelper::indexDocument($post);
//                echo 'added: '.$post->getTitle()."\n";
                printf("added: [%d] %s\n",$post->getId(), $post->getTitle());
                if($commit){
                    $index->commit();
                    $index->optimize();
                }
            }
            if(!$commit){
                $index->commit();
                $index->optimize();
            }
            echo "numDocs: ". $index->numDocs()."\n";
        }catch(Exception $e){
            echo 'error: '. $e->getMessage();
        }
        echo "</pre>";
        die();
        
    }
    
    public function indexUsersAction(){
        set_time_limit(0);
        Util::turnRendererOff();
        $userId = InputHelper::getParam('userId', 0);
        $number = InputHelper::getParam('number', 10);
        $offset = InputHelper::getParam('offset', 0);
        $page = InputHelper::getParam('page', 1);
        if(!$offset){
            $offset = ($page-1) * $number;
        }
        $commit = InputHelper::getParam('commit', true);
        echo "<pre>";
        $userIn = $userId?array($userId): array();
        $users = AG_UserModel::selectUsers(array(
            'include' => $userIn,
            'exclude'=>1,
            'number' => $number,
            'offset' => $offset,
            'count_total' => true,
            ));
//        print_r(AG_UserModel::getWpUserQuery());
        printf("users total: %d\rnumber: %d\roffset: %d\rpage: %d\rcommit: %s\r", 
                AG_UserModel::getWpUserQuery()->total_users,
                $number,
                $offset,
                $page, 
                $commit
                );
//        print_r($users);
        try{
            $index = LuceneHelper::getInstance();
            echo "numDocs: ". $index->numDocs()."\n";
            foreach($users as $user){
                LuceneHelper::indexDocument($user);
                echo 'added: '.$user->getLogin()."\n";
                if($commit){
                    $index->commit();
                    $index->optimize();
                }
            }
            if(!$commit){
                $index->commit();
                $index->optimize();
            }
            echo "numDocs: ". $index->numDocs()."\n";
            
        }catch(Exception $e){
            echo 'error: '. $e->getMessage();
        }
        echo "</pre>";
        die();
        
    }
    
    public function recalculateReputationAction(){
        set_time_limit(0);
        Util::turnRendererOff();
        $userId = InputHelper::getParam('userId', 0);
        $number = InputHelper::getParam('number', 10);
        $offset = InputHelper::getParam('offset', 0);
        $page = InputHelper::getParam('page', 1);
        if(!$offset){
            $offset = ($page-1) * $number;
        }
        echo "<pre>";
        $userIn = $userId?array($userId): array();
        $users = AG_UserModel::selectUsers(array(
            'include' => $userIn,
            'number' => $number,
            'offset' => $offset,
            'count_total' => true,
            ));
        printf("users total: %d\rnumber: %d\roffset: %d\rpage: %d\r", 
                AG_UserModel::getWpUserQuery()->total_users,
                $number,
                $offset,
                $page
                );
//        print_r($users);
        try{
            $ids = array();
            foreach($users as $user){
                $ids[]=$user->getId();
            }
            
            ReputationHelper::recalculateReputation($ids);
            
        }catch(Exception $e){
            echo 'error: '. $e->getMessage();
        }
        echo "</pre>";
        die();
        
    }
    
    public function deleteAction(){
        $postId = InputHelper::getParam('postId', 0);
        $userId = InputHelper::getParam('userId', 0);
        if(!$postId && $userId){
            $user = AG_UserModel::selectById($userId);
            $profile = $user->getProfile();
            $postId = $profile->getId();
        }
        $deleted = 0;
        if($postId){
            $deleted = LuceneHelper::deleteById('pk_'.$postId);
        }
        
        printf('deleted %d entry(s)', $deleted);
    }
    
    public function flushAction(){
        try{
            FileSystem::delete(PathHelper::getLuceneDir(LuceneHelper::serverName()));
            printf('num docs: %d', LuceneHelper::getInstance()->numDocs());
        }catch(Exception $e){
            echo $e->getMessage();
        }
        
    }
    
    public function optimizeAction(){
        try{
            LuceneHelper::getInstance()->optimize();
            printf('<pre>num docs: %d\r</pre>', LuceneHelper::getInstance()->numDocs());
        }catch(Exception $e){
            echo $e->getMessage();
        }
        
    }
    
    public function testHighlightAction(){
        Util::turnRendererOff();
        $text = "
           Hello <b>world</b><br/>
           I'm nicely <b class='note'>highlighted</b> text
        ";
        
        echo "<pre> $text </pre>";
        
        $text = LuceneHelper::highlight($text, 'nicely');
        echo "<pre> $text </pre>";
    }
    
    public function findAction(){
        echo "<pre>";
        $index = LuceneHelper::getInstance();
        $term = InputHelper::getParam('term', 'кот');
        $page = InputHelper::getParam('page', 1);
        $itemsPerPage = InputHelper::getParam('number', 10);

        $lquery = LuceneHelper::parseQuery($term);
        LuceneHelper::setQuery($lquery);
        $hits = LuceneHelper::searchHits($lquery);
        $total = count($hits);

        printf("query: %s\rtotal found: %d\rnumdocs: %d\r\r",
                $term, $total,LuceneHelper::getInstance()->numDocs());
//        echo "term: ".$term;
//        echo "hits: ".count($hits);
//        echo "numdocs: ".  LuceneHelper::getInstance()->numDocs();
        
//        $this->view->results = $hits;
//        $this->view->query = $lquery;
        $ids = $total?array():array(0);
        foreach($hits as $hit){
            $ids[]=$hit->getDocument()->getFieldValue(LuceneHelper::getIdField());
        }
        
//        echo $scope." found: ".count($hits).' ';
        
        $total = count($ids);
        $ids = array_slice($ids, ($page - 1)*$itemsPerPage, $itemsPerPage);
        foreach($ids as $i=>$id){
            $ids[$i] = substr($id, 3);
        }
//        print_r($ids);
        $query = array(
            'post__in'=>$ids,
            'post_type' => 'any',
            'posts_per_page'=>$itemsPerPage,
        );

        $posts = AG_PostModel::selectPosts($query);
//        print_r($posts);
        foreach($posts as $post){
            printf("%10d\t%s\r", $post->getId(), LuceneHelper::highlight($post->getTitle()));
        }
        echo "</pre>";
    }
    
    public function cleanupAction(){
        global $wpdb;
        $table = AnotherGuru::dbTable('ag_postdata');
        $ids = $wpdb->get_col("
            SELECT pd.post_id FROM $table AS pd
            LEFT JOIN $wpdb->posts AS p ON (pd.post_id = p.ID)
            WHERE p.ID IS NULL
            ");
        print_r($ids);
        echo "<pre>";
        foreach($ids as $id){
            $deleted = LuceneHelper::deleteById("pk_".$id);
            echo "deleted $id: $deleted\n";
        }
        $idsStr = join(',', $ids);
        $wpdb->query("DELETE FROM $table WHERE post_id IN($idsStr)");
        echo "</pre>";
    }
    
    public function resetRolesAction(){
        set_time_limit(0);
        Util::turnRendererOff();
        $userId = InputHelper::getParam('userId', 0);
        $number = InputHelper::getParam('number', 10);
        $offset = InputHelper::getParam('offset', 0);
        $page = InputHelper::getParam('page', 1);
        if(!$offset){
            $offset = ($page-1) * $number;
        }
        echo "<pre>";
        $userIn = $userId?array($userId): array();
        $users = AG_UserModel::selectUsers(array(
            'include' => $userIn,
            'exclude'=>1,
            'number' => $number,
            'offset' => $offset,
            'count_total' => true,
//            'role' => 'Subscriber',
            ));
//        print_r(AG_UserModel::getWpUserQuery());
        printf("users total: %d\rnumber: %d\roffset: %d\rpage: %d\r", 
                AG_UserModel::getWpUserQuery()->total_users,
                $number,
                $offset,
                $page
                );
//        print_r($users);
        try{
            foreach($users as $user){
                $result = 'false';
//                $usr->loadMeta();
                printf( "ID: %d, login: %s, Role: %s, result: ", 
                        $user->getId(),
                        $user->getLogin(),
                        join(', ',$user->getWpUser()->roles));
                if(in_array('subscriber', $user->getWpUser()->roles)){
                    $user->getWpUser()->set_role("author");
                    $result = 'true';
                }else{
                    
                }
                echo "$result \r";
            }
        }catch(Exception $e){
            echo 'error: '. $e->getMessage();
        }
        echo "</pre>";
        die();
        
    }
}

