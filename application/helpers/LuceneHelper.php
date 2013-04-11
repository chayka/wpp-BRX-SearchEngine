<?php

require_once 'Zend/Search/Lucene.php';
require_once WPP_SEARCH_ENGINE_PATH.'library/phpmorphy-0.3.7/src/common.php';
interface LuceneReadyInterface {

    public function packLuceneDoc();
}

class LuceneHelper {

    protected static $instance;
    protected static $idField = "PK";
    protected static $queries;
    protected static $query;

    public static function serverName() {
        return str_replace('www.', '', $_SERVER['SERVER_NAME']);
    }
    
    /**
     *
     * @return Zend_Search_Lucene
     */
    public static function getInstance() {
        if (empty(self::$instance)) {
/**
 * How to fix highlight in utf-8 text:
 * /Zend/Search/Lucene/Analysis/Analyzer/Common/Utf8.php
 * function 'reset'
 * fix the if close on encoding check or add 
 * if(!$this->_encoding){$this->encoding = 'UTF-8';}
 */
//            echo "getInstance()";
//            $indexFnDir = PathHelper::getLuceneDir($_SERVER['SERVER_NAME']);
            $indexFnDir = WPP_SEARCH_ENGINE_PATH . 'data/lucene/' . self::serverName();

//            die($indexFnDir);
            try {
                //изначально Zend Lucene не настроена на работу с UTF-8
                //поэтому надо изменить используемый по умолчанию анализатор
                //в данном случае используется анализатор для UTF-8 нечувствительный к регистру
                Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('utf-8');
                $analyzer = new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8_CaseInsensitive();
//                $analyzer = new MorphyAnalyzer();
                //инициализируем фильтр стоп-слов
//                $stopWordsFilter = new Zend_Search_Lucene_Analysis_TokenFilter_StopWords();
//                $stopWordsFilter->loadFromFile(WPP_SEARCH_ENGINE_PATH.'data/lucene/stop-words.txt');
//                $analyzer->addFilter($stopWordsFilter);
                //инициализируем морфологический фильтр
                $analyzer->addFilter(new MorphyFilter());
                Zend_Search_Lucene_Analysis_Analyzer::setDefault($analyzer);

                //устанавливаем ограничение на количество записей в результате поиска
//                Zend_Search_Lucene::setResultSetLimit(100);

                self::$instance = new Zend_Search_Lucene($indexFnDir, !is_dir($indexFnDir));
            } catch (Exception $e) {
                die('Exception: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }
    
    public static function flush(){
        return FileSystem::delete(WPP_SEARCH_ENGINE_PATH . 'data/lucene/' . self::serverName());
    }

    public static function setIdField($value) {
        self::$idField = $value;
    }

    public static function getIdField() {
        return self::$idField;
    }

    public static function setQuery($query) {
        if ($query) {
//            echo "query set: $query\n";
            if ($query instanceof Zend_Search_Lucene_Search_Query) {
                self::$query = $query;
            } elseif (is_string($query)) {
                self::$query = Util::getItem(self::$queries, $query);
                if (!self::$query) {
                    self::$query = self::parseQuery($query);
                }
            }
        }
    }

    public static function parseQuery($query) {
        if (empty(self::$queries[$query])) {
            self::$queries[$query] = Zend_Search_Lucene_Search_QueryParser::parse($query, 'utf-8');
        }

        return self::$queries[$query];
    }

    public static function getQueryFromHttpReferer($postId = 0) {
        $url = Util::getItem($_SERVER, 'HTTP_REFERER');
        if (strpos($url, "search")) {
            $urlQuery = parse_url($url, PHP_URL_QUERY);
            $params = array();
            parse_str($urlQuery, $params);
            //        print_r($params);
            $query = Util::getItem($params, 'q');
            if ($query) {
                $lcQuery = self::parseQuery($postId ?
                                        sprintf('%s: pk_%d AND (%s)', self::$idField, $postId, $query) :
                                        $query);
                self::setQuery($lcQuery);
                self::searchIds($lcQuery);
            }

            return $query;
        }

        return '';
    }

    public static function luceneDocFromArray($item) {
//        print_r($item);
        $doc = new Zend_Search_Lucene_Document();
        foreach ($item as $field => $opts) {
//            $encoding = $field == self::$idField?null:'UTF-8';
            $encoding = 'UTF-8';
            $type = 'unstored';
            $value = $opts;
            if (is_array($opts)) {
                switch (count($opts)) {
                    case 2:
                        list($type, $value) = $opts;
                        $boost = 1;
                        break;
                    case 3:
                        list($type, $value, $boost) = $opts;
                        break;
                }
            }
//                    echo $type."\n";
            if ('keyword' == $type) {
                $doc->addField(Zend_Search_Lucene_Field::keyword($field, $value, $encoding));
            } elseif ('unindexed' == $type) {
                $doc->addField(Zend_Search_Lucene_Field::unIndexed($field, $value, $encoding));
            } elseif ('binary' == $type) {
                $doc->addField(Zend_Search_Lucene_Field::binary($field, $value));
            } elseif ('text' == $type) {
                $doc->addField(Zend_Search_Lucene_Field::text($field, $value, $encoding));
            } elseif ('unstored' == $type) {
                $doc->addField(Zend_Search_Lucene_Field::unStored($field, $value, $encoding));
            }
            $doc->getField($field)->boost = $boost;
        }
//        print_r($doc);
        return $doc;
    }

    public static function deleteByKey($key, $value) {
//        echo "deleteById $docId ";
        $deleted = 0;
        if ($key && $value) {
            $index = self::getInstance();
            $term = new Zend_Search_Lucene_Index_Term($value, $key);
            $docIds = $index->termDocs($term);
            foreach ($docIds as $id) {
                $index->delete($id);
                $deleted++;
            }
        }
        return $deleted;
    }

    public static function deleteById($docId) {
        return self::deleteByKey(self::$idField, $docId);
    }

    public static function indexLuceneDoc($doc) {
        Log::dir($doc, 'indexing doc');
        $index = self::getInstance();
        $id = $doc->getFieldValue(self::$idField);
        try {
            self::deleteById($id);
            $index->addDocument($doc);
//            echo "numDocs(): ".$index->numDocs();
//            $index->commit();
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    public static function indexDocument(LuceneReadyInterface $document) {
        $item = $document->packLuceneDoc();
        $doc = self::luceneDocFromArray($item);
        self::indexLuceneDoc($doc);
    }

    public static function searchHits($query) {
//        echo "(!)";
//        if($query instanceof Zend_Search_Lucene_Search_Query){
//            self::setQuery($query);
//        }else{
//            $query = Zend_Search_Lucene_Search_QueryParser::parse($query);
//            self::setQuery($query);
//        }
        try {
            $index = self::getInstance();
//            print_r($query);
            $hits = $index->find($query);
//               echo "hits found: ".count($hits)."\r";
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        self::$instance = null;

        return $hits;
    }

    public static function searchLuceneDocs($query) {
        $hits = self::searchHits($query);
//        echo "HITS FOUND: ".count($hits);
        $docs = array();
        foreach ($hits as $hit) {
            $docs[] = $hit->getDocument();
        }
        return $docs;
    }

    public static function searchIds($query) {
//        $docs = self::searchLuceneDocs($query);
//        $ids = array();
//        foreach($docs as $doc){
//            $ids[]=$doc->getFieldValue(self::$idField);
//        }
//        $ids = array();
        $hits = self::searchHits($query);
        foreach ($hits as $hit) {
            $ids[] = $hit->getDocument()->getFieldValue(self::getIdField());
//            echo $hit->wpid.' ';
        }
        return $ids;
    }

    public static function highlight($html, $query = '') {
        self::getInstance();
        self::setQuery($query);

        if (self::$query) {
//            print_r(self::$query);
            $html = preg_replace('%(<\/?)b\b%imUs', '$1strong', $html);
        }
        Zend_Search_Lucene_Analysis_Analyzer::getDefault()->reset();
        return self::$query ? self::$query->htmlFragmentHighlightMatches($html, 'UTF-8') : $html;
    }

}

class MorphyAnalyzer extends Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8_CaseInsensitive {

    /**
     * Current char position in an UTF-8 stream
     *
     * @var integer
     */
    private $_position;

    /**
     * Current binary position in an UTF-8 stream
     *
     * @var integer
     */
    private $_bytePosition;

//    public function __construct() {
//        parent::__construct();
//
//        $this->addFilter(new MorphyFilter());
//    }

    /**
     * Reset token stream
     */
    public function reset() {
        $this->_position = 0;
        $this->_bytePosition = 0;

        // convert input into UTF-8
        if (strcasecmp($this->_encoding, 'utf8') != 0 &&
                strcasecmp($this->_encoding, 'utf-8') != 0) {
//            $this->_input = iconv($this->_encoding, 'UTF-8', $this->_input);
            $this->_encoding = 'UTF-8';
        }
    }

    /**
     * Tokenization stream API
     * Get next token
     * Returns null at the end of stream
     *
     * @return Zend_Search_Lucene_Analysis_Token|null
     */
//    public function nextToken() {
//        if ($this->_input === null) {
//            return null;
//        }
//
//        do {
//            if (!preg_match('/[А-Я]+/u', $this->_input, $match, PREG_OFFSET_CAPTURE, $this->_bytePosition)) {
//                // It covers both cases a) there are no matches (preg_match(...) === 0)
//                // b) error occured (preg_match(...) === FALSE)
//                return null;
//            }
//
//            // matched string
//            $matchedWord = $match[0][0];
//
//            // binary position of the matched word in the input stream
//            $binStartPos = $match[0][1];
//
//            // character position of the matched word in the input stream
//            $startPos = $this->_position +
//                    iconv_strlen(substr($this->_input, $this->_bytePosition, $binStartPos - $this->_bytePosition), 'UTF-8');
//            // character postion of the end of matched word in the input stream
//            $endPos = $startPos + iconv_strlen($matchedWord, 'UTF-8');
//
//            $this->_bytePosition = $binStartPos + strlen($matchedWord);
//            $this->_position = $endPos;
//
//            $token = $this->normalize(new Zend_Search_Lucene_Analysis_Token($matchedWord, $startPos, $endPos));
//        } while ($token === null); // try again if token is skipped
//
//        return $token;
//    }

}

class MorphyFilter extends Zend_Search_Lucene_Analysis_TokenFilter {

    private $morphy;

    public function __construct() {
        //инициализируем объект phpMorphy
        $dir = WPP_SEARCH_ENGINE_PATH . 'library/phpmorphy-0.3.7/dicts';
        $lang = 'ru_RU';

        $this->morphy = new phpMorphy($dir, $lang);
    }

    public function castVariants($word, $pos, $variants) {
        $forms = null;
        foreach ($variants as $v) {
            $forms = $this->morphy->castFormByGramInfo($word, $pos, $v, true);
            if (!empty($forms)) {
                $min = 1000;
                $index = 0;

                foreach ($forms as $i => $form) {
                    $dif = levenshtein($word, $form);
                    if ($dif < $min) {
                        $min = $dif;
                        $index = $i;
                    }
                    if (!$min) {
                        break;
                    }
                }

                return $forms[$index];
            }
        }

        return null;
    }

    public function normalize(Zend_Search_Lucene_Analysis_Token $srcToken) {
        $str = trim(mb_strtoupper($srcToken->getTermText(), "utf-8"));
//        echo "[$str]";
        if (!preg_match('%^[А-Я]+$%u', $str)) {
//            echo "несклоняемо: [$str]\r";
            $newToken = new Zend_Search_Lucene_Analysis_Token(
                            $str,
                            $srcToken->getStartOffset(),
                            $srcToken->getEndOffset()
            );
            Log::dir($newToken, "$str - несклоняемо");
            return $newToken;
        }
//        //извлекаем корень слова
//        $pseudo_root = $this->morphy->getPseudoRoot($str);
//        if ($pseudo_root === false){
//            $newStr = $str;
//        //если корень извлечь не удалось, тогда используем все слово целиком
//        }else{
//            $newStr = $pseudo_root[0];
//        }
        $omit = false;


        $gramInfo = $this->morphy->getGramInfoMergeForms($str);
//            print_r($gramInfo);
        $part = $this->morphy->getPartOfSpeech($str);
        $form = $str;
        $part = is_array($part) && count($part) ? $part[0] : '';
        switch ($part) {
            case 'С':
            case 'МС':
                $form = $this->castVariants($str, $part, array(
                    array('ИМ', 'ЕД'),
                    array('ИМ', 'МН')
                        ));
                break;
            case 'П':
            case 'МС-П':
            case 'ЧИСЛ-П':
                $form = $this->castVariants($str, $part, array(
                    array('ИМ', 'ЕД', 'МР'),
                        ));
                break;
            case 'ПРИЧАСТИЕ':
                $form = $this->castVariants($str, $part, array(
                    array('ИМ', 'ЕД', 'МР'),
                        ));
                break;
            case 'Г':
            case 'ДЕЕПРИЧАСТИЕ':
            case 'ВВОДН':
//                    $form = $this->morphy->castFormByGramInfo($str, $part, array('1Л', 'ЕД', 'НСТ'), true); 
                $form = $this->castVariants($str, 'ИНФИНИТИВ', array(
                    array('ДСТ', 'СВ', 'НП'),
                    array('ДСТ', 'СВ', 'ПЕ'),
                    array('ДСТ', 'НС', 'НП'),
                    array('ДСТ', 'НС', 'ПЕ'),
                    array('СТР', 'СВ', 'НП'),
                    array('СТР', 'СВ', 'ПЕ'),
                    array('СТР', 'НС', 'НП'),
                    array('СТР', 'НС', 'ПЕ'),
                        ));
                break;
            case 'КР_ПРИЛ':
            case 'КР_ПРИЧАСТИЕ':
                $form = $this->castVariants($str, $part, array(
                    array('ЕД', 'СР'),
                    array('МН'),
                        ));
                break;
            case 'СОЮЗ':
            case 'ПРЕДЛ':
            case 'МЕЖД':
            case 'ЧАСТ':
                $omit = true;
                $form = '---------';
                break;
            case 'ИНФИНИТИВ':
            case 'Н':
            case 'ПРЕДК':
            case 'МС-ПРЕДК':
            case 'ЧИСЛ':
            case 'ФРАЗ':
                break;
            default:
        }
//        printf("%s: %s [%s]\n", $srcToken->getTermText(), $form, $pseudo_root[0]);
//        $resolved = array(
//            'С', 'П', 'ПРИЧАСТИЕ', 'Г', 'Н', 'ПРЕДЛ', 
//            'СОЮЗ', 'ИНФИНИТИВ', 'ПРЕДК', 'КР_ПРИЛ', 'ДЕЕПРИЧАСТИЕ', 
//            'МЕЖД', 'КР_ПРИЧАСТИЕ', 'МС-П', 'ВВОДН', 'ЧАСТ'
//            
//            );
//        if(!in_array($part, $resolved)){
//            foreach($gramInfo as $i){
//                printf("    %s (%s): %d\n", $i['pos'], join(', ', $i['grammems']), $i['form_no']);
//            }
//            $fa = $this->morphy-> getAllFormsWithAncodes($str);            
//            foreach($fa as $x => $f1){
//                $forms = $f1['forms'];
//                $common = $f1['common'];
//                $ancodes = $f1['all'];
//                foreach($forms as $i=>$f){
//                    printf("    %d) %s (%s : %s)\n", $x, $f, $ancodes[$i], $common);
//
//                }
//            }
//        }
        //если лексема короче 3 символов, то не используем её      
        if (/* mb_strlen($newStr, "utf-8") < 3 */$omit) {
            Log::dir(array('form'=>$form, 'omit'=>$omit), "$str - omitting");
            return null;
        }

        $newToken = new Zend_Search_Lucene_Analysis_Token(
                        $form?$form:$str,
                        $srcToken->getStartOffset(),
                        $srcToken->getEndOffset()
        );

        $newToken->setPositionIncrement($srcToken->getPositionIncrement());
//echo "($form)";
        Log::dir($newToken, "$str - success");
        return $newToken;
    }

}