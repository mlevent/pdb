<?php

    namespace Mlevent;

    use PDOException;
    use PDO;
    use Closure;

    if(!defined('_AND')) define('_AND', 'AND');
    if(!defined('_OR'))  define('_OR',  'OR');

    class Pdb
    {
        private $pdo;
        private $config;
        private $cache;
        
        private $fromCache = false;

        private $queryHistory  = [];
        private $rowCount      = 0;
        private $lastInsertId  = 0;

        private $select        = null;
        private $table         = null;
        private $join          = null;
        private $where         = null;
        private $order         = null;
        private $group         = null;
        private $having        = null;
        private $limit         = null;
        private $offset        = null;
        private $rawQuery      = null;

        private $isGrouped     = false;
        private $isGroupIn       = false;

        private $isFilter      = false;
        private $isFilterValid = false;

        private $joinParams    = [];
        private $havingParams  = [];
        private $whereParams   = [];
        private $rawParams     = []; 
        
        /**
         * __construct
         *
         * @param array $config
         */
        public function __construct($config = null)
        {
            $this->config = [
                'database'  => '',
                'username'  => '',
                'password'  => null,
                'host'      => 'localhost',
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'debug'     => false,
                'cacheTime' => 60,
                'cachePath' => __DIR__ . '/Cache'
            ];

            foreach($this->config as $k => $v) 
                $this->config[$k] = !isset($config[$k]) 
                    ? $this->config[$k] 
                    : $config[$k];

            $options = [
                PDO::ATTR_PERSISTENT         => true, 
                PDO::MYSQL_ATTR_FOUND_ROWS   => true,
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->config['charset']} COLLATE {$this->config['collation']}"
            ];

            try{
            
                $this->pdo = new PDO("mysql:dbname={$this->config['database']};host={$this->config['host']}", $this->config['username'], $this->config['password'], $options);

            } catch(PDOException $e){ die($e->getMessage()); }
        }
                     
        /**
         * init
         */
        protected function init(){
            
            $this->select        = null;
            $this->table         = null;
            $this->join          = null;
            $this->where         = null;
            $this->order         = null;
            $this->group         = null;
            $this->having        = null;
            $this->limit         = null;
            $this->offset        = null;
            $this->rawQuery      = null;
            $this->isGrouped     = false;
            $this->isGroupIn     = false;
            $this->isFilter      = false;
            $this->isFilterValid = false;
            $this->joinParams    = [];
            $this->havingParams  = [];
            $this->whereParams   = [];
            $this->rawParams     = [];
        }
                
        /**
         * cache
         *
         * @param int $timeout
         * @return $this
         */
        public function cache(int $timeout = null){
            $this->cache = new Cache($this->config['cachePath'], is_null($timeout) ? $this->config['cacheTime'] : $timeout);
            return $this;
        }
        
        /**
         * Verinin diskten okunup okunmadığını doğrular
         *
         * @return $this
         */
        public function fromCache(){
            return $this->fromCache;
        }
        
        /**
         * Closure and-or gruplama
         *
         * @param closure $object
         * @return $this
         */
        public function grouped(Closure $object){
            $this->isGrouped = true;
            call_user_func_array($object, [$this]);
            $this->where .= ')';
            return $this;
        } 

        /**
         * Sorgu içi and-or gruplama
         *
         * @param bool $andOr
         */
        protected function setGroup($andOr = false){
            $this->isGroupIn = $andOr;
        }
     
        /**
         * select
         *
         * @param string|array $fields
         * @return $this
         */
        public function select($fields){
            $select = is_array($fields) 
                ? implode(', ', $fields) 
                : $fields;
            $this->select = !is_null($this->select) 
                ? $this->select . ', '. $select 
                : $select;
            return $this;
        }                
        
        /**
         * selectBuild
         *
         * @return string
         */
        protected function selectBuild(){
            return $this->select ? $this->select : '*';
        }
        
        /**
         * selectFunctions
         *
         * @param string $field
         * @param string $alias
         * @param string $function
         */
        protected function selectFunctions($field, $alias = null, $function = null){
            return $this->select($alias ? $function.'('.$field.') AS '.$alias : $function.'('.$field.')');
        }        

        /**
         * count
         *
         * @param string $field
         * @param string $alias
         * @return $this
         */
        public function count($field, $alias = null){
            return $this->selectFunctions($field, $alias, 'COUNT');
        }       
         
        /**
         * sum
         *
         * @param string $field
         * @param string $alias
         * @return $this
         */
        public function sum($field, $alias = null){
            return $this->selectFunctions($field, $alias, 'SUM');
        }        
        
        /**
         * avg
         *
         * @param string $field
         * @param string $alias
         * @return $this
         */
        public function avg($field, $alias = null){
            return $this->selectFunctions($field, $alias, 'AVG');
        }        
        
        /**
         * min
         *
         * @param string $field
         * @param string $alias
         * @return $this
         */
        public function min($field, $alias = null){
            return $this->selectFunctions($field, $alias, 'MIN');
        }      

        /**
         * max
         *
         * @param string $field
         * @param string $alias
         * @return $this
         */
        public function max($field, $alias = null){
            return $this->selectFunctions($field, $alias, 'MAX');
        }
        
        /**
         * table
         *
         * @param string|array $table
         * @return $this
         */
        public function table($table){
            $table = is_array($table) 
                ? implode(', ', $table) 
                : $table;
            $this->table = !is_null($this->table) 
                ? $this->table . ', '. $table 
                : $table;
            return $this;
        }
        
        /**
         * tableBuild
         *
         * @return string
         */
        protected function tableBuild(){
            if(!$this->table)
                throw new Exception('Tablo seçilmeden devam edilemez.');
            return $this->table;
        }
        
        /**
         * join
         *
         * @param string $from
         * @param string $field
         * @param string $params
         * @param string $join
         * @return $this
         */
        protected function join($from, $field = null, $params = null, $join = 'INNER'){
            if(!is_null($field)){
                if(!is_null($params))
                    $field = $field . '=' . $params;
                $join = $join . ' JOIN ' . $from . ' ON ' . $field;
            } else {
                $join = $join . ' JOIN ' . $from;
            }
            $this->join = !is_null($this->join) ? $this->join . ' '. $join : $join;
            return $this;
        }

        /**
         * leftJoin
         *
         * @param string $from
         * @param string $on
         * @param string $params
         * @return $this
         */
        public function leftJoin($from, $on = null, $params = null){
            return $this->join($from, $on, $params, 'LEFT');
        }

        /**
         * leftOuterJoin
         *
         * @param string $from
         * @param string $on
         * @param string $params
         * @return $this
         */
        public function leftOuterJoin($from, $on = null, $params = null){
            return $this->join($from, $on, $params, 'LEFT OUTER');
        }

        /**
         * rightJoin
         *
         * @param string $from
         * @param string $on
         * @param string $params
         * @return $this
         */
        public function rightJoin($from, $on = null, $params = null){
            return $this->join($from, $on, $params, 'RIGHT');
        }

        /**
         * rightOuterJoin
         *
         * @param string $from
         * @param string $on
         * @param string $params
         * @return $this
         */
        public function rightOuterJoin($from, $on = null, $params = null){
            return $this->join($from, $on, $params, 'RIGHT OUTER');
        }

        /**
         * innerJoin
         *
         * @param string $from
         * @param string $on
         * @param string $params
         * @return $this
         */
        public function innerJoin($from, $on = null, $params = null){
            return $this->join($from, $on, $params, 'INNER');
        }

        /**
         * fullOuterJoin
         *
         * @param string $from
         * @param string $on
         * @param string $params
         * @return $this
         */
        public function fullOuterJoin($from, $on = null, $params = null){
            return $this->join($from, $on, $params, 'FULL OUTER');
        }

        /**
         * joinBuild
         *
         * @param string $from
         * @param string $on
         * @param string $params
         * @return $this
         */
        public function joinBuild(){
            return $this->join ? $this->join : null;
        }
        
        /**
         * order
         *
         * @param string|array $order
         * @param string $dir
         * @return $this
         */
        public function order($order, $dir = null){
            if(!is_null($dir)){
                $this->order = $order . ' ' . $dir;
            } else{
                $this->order = stristr($order, ' ') || $order == 'rand()'
                    ? $order
                    : $order . ' DESC';
            }
            return $this;
        }

        /**
         * orderBuild
         *
         * @return string
         */
        public function orderBuild(){
            return $this->order ? 'ORDER BY ' . $this->order : null;
        }
        
        /**
         * group
         *
         * @param string|array $group
         * @return $this
         */
        public function group($group){
            $this->group = is_array($group) ? implode(', ', $group) : $group;
            return $this;
        }
        public function groupBuild(){
            return $this->group ? 'GROUP BY ' . $this->group : null;
        }
        
        /**
         * limit
         *
         * @param int $limit
         * @param int $offset
         * @return $this
         */
        public function limit(int $limit, int $offset = null){
            $this->limit  = $limit;
            $this->offset = $offset;
            return $this;
        }
                
        /**
         * offset
         *
         * @param int $offset
         * @return $this
         */
        public function offset(int $offset){
            $this->offset = $offset;
            return $this;
        }
                
        /**
         * pager
         *
         * @param int $limit
         * @param int $page
         * @return $this
         */
        public function pager(int $limit, int $page = 1){
            if($limit < 1) $limit = 1;
            if($page  < 1) $page  = 1;
            $this->limit  = $limit;
            $this->offset = ($limit * $page) - $limit;
            return $this;
        }
                
        /**
         * limitOffsetBuild
         *
         * @return string
         */
        public function limitOffsetBuild(){
            return ($this->limit ? 'LIMIT ' . (int)$this->limit : null).($this->offset ? ' OFFSET ' . (int)$this->offset : null);
        }
        
        /**
         * having
         *
         * @param string|array $field
         * @param string $value
         * @return $this
         */
        public function having($field, $value = null){
            if($this->findMarker($field)){
                $this->having = $field;
            } else {
                $this->having = !is_null($value) ? $field . ' > ' .$value : $field;
            }
            $this->addHavingParams($value);
            return $this;
        }
        public function havingBuild(){
            return $this->having ? 'HAVING ' . $this->having : null;
        }
  
        /**
         * rawWhere
         */
        public function rawWhere($column, $group = null, $andOr = _AND){
            if(($group !== _AND || $group !== _OR) && !is_array($column)) 
                $group = null;
            return $this->whereFactory($column, $group, $andOr);
        }
        public function orRawWhere($column, $group = null, $andOr = _OR){
            return $this->rawWhere($column, $group, $andOr);
        }
    
        /**
         * where
         *
         * @param string|array $column
         * @param string|array $value
         * @param string $andOr
         * @return $this
         */
        public function where($column, $value = null, $andOr = _AND){
            return $this->whereFactory($column, $value, $andOr);
        }
                
        /**
         * orWhere
         *
         * @param string|array $column
         * @param string|array $value
         * @return $this
         */
        public function orWhere($column, $value = null){
            return $this->where($column, $value, _OR);
        }
                
        /**
         * notWhere
         *
         * @param string|array $column
         * @param string|array $value
         * @param string $andOr
         * @return $this
         */
        public function notWhere($column, $value = null, $andOr = _AND){
            return $this->whereFactory($column, $value, $andOr, "%s <> ?");
        }
                
        /**
         * orNotWhere
         *
         * @param string|array $column
         * @param string|array $value
         * @return $this
         */
        public function orNotWhere($column, $value = null){
            return $this->notWhere($column, $value, _OR);
        }
                
        /**
         * whereBuild
         *
         * @return string
         */
        protected function whereBuild(){
            return !is_null($this->where) ? 'WHERE ' . $this->where : null;
        }

        /**
        * null/orNull
		*/
        public function isNull($column, $group = null, $andOr = _AND){
            return $this->whereFactory($column, $group, $andOr, "%s IS NULL", true);
        }
        public function orIsNull($column, $group = null){
            return $this->isNull($column, $group, _OR);
        }
        public function notNull($column, $group = null, $andOr = _AND){
            return $this->whereFactory($column, $group, $andOr, "%s IS NOT NULL", true);
        }
        public function orNotNull($column, $group = null){
            return $this->notNull($column, $group, _OR);
        }

        /**
		* In/Not IN
		*/
        public function in($column, $value, $andOr = _AND){
            return $this->whereFactory($column, (array)$value, $andOr, "%s IN({$this->createMarker((array)$value)})");
        }
        public function orIn($column, $value){
            return $this->in($column, $value, _OR);
        }
        public function notIn($column, $value, $andOr = _AND){
            return $this->whereFactory($column, (array)$value, $andOr, "%s NOT IN({$this->createMarker((array)$value)})");
        }
        public function orNotIn($column, $value){
            return $this->in($column, $value, _OR);
        }

        /**
		* Between
		*/
        public function between($column, $begin, $end, $andOr = _AND){
            return $this->whereFactory($column, [$begin, $end], $andOr, "%s BETWEEN ? AND ?");
        }
        public function orBetween($column, $begin, $end){
            return $this->between($column, $begin, $end, _OR);
        }
        public function notBetween($column, $begin, $end, $andOr = _AND){
            return $this->whereFactory($column, [$begin, $end], $andOr, "%s NOT BETWEEN ? AND ?");
        }
        public function orNotBetween($column, $begin, $end){
            return $this->between($column, $begin, $end, _OR);
        }

        /**
		* FindInSet
		*/
        public function findInSet($column, $search, $andOr = _AND){
            return $this->whereFactory(null, $search, $andOr, "FIND_IN_SET(?, {$column})");
        }
        public function orFindInSet($column, $search){
            return $this->findInSet($column, $search, _OR);
        }

        /**
        * Like
		*/
        public function like($column, $search, $group = null, $andOr = _AND, $pattern = '%s LIKE ?'){
            $params = [];
            $column = (array)$column;
            foreach($column as $val) $params[sprintf($pattern, $val)] = $search;
            return $this->whereFactory($params, $group, $andOr);
        }
        public function orLike($column, $search, $group = null){
            return $this->like($column, $search, $group, _OR);
        }
        public function notLike($column, $search, $group = null){
            return $this->like($column, $search, $group, _AND, '%s NOT LIKE ?');
        }
        public function orNotlike($column, $search, $group = null){
            return $this->like($column, $search, $group, _OR, '%s NOT LIKE ?');
        }

        /**
		* Veri içerisinde '?' karakteri geçiyor mu?
		*/
        public function findMarker($string){
            return strpos($string, '?') !== FALSE;
        }

        /**
        * Sorgu için parametre sayısı kadar question mark oluşturur
		*/
        public function createMarker($params){
            if(!is_array(reset($params))):
                return rtrim(str_repeat('?,', sizeof($params)), ',');
            else:
                array_walk($params, function(&$val, $key){
                    $val = $this->createMarker($val);
                });
                return '('.implode('),(', $params).')';
            endif;
        }
        public function createMarkerWithKey($params, $pattern = '%key=?', $comma = ','){
            $params = is_array(reset($params)) ? $params[0] : $params;
            if(is_array($params)){
                array_walk($params, function(&$val, $key) use ($pattern){
                    $val = str_replace(['%val', '%key'], [$val, $key], $pattern);
                });
                return implode($comma, $params);
            } else{
                return str_replace(['%val', '%key'], [$params, $params], $pattern);
            }
        }

        /**
        * Sorgu için parametre aktarır
		*/
        protected function addParams($params, $type = 'whereParams'){
            if(is_array($params))
                foreach($params as $p) $this->$type[] = $p;
            else
                if(!is_null($params))
                    $this->$type[] = $params;
        }
        protected function delParams($key){
            if(isset($this->$key))
                $this->$key = [];
        }
        public function addWhereParams($params){
            $this->addParams($params);
        }
        public function addJoinParams($params){
            $this->addParams($params, 'joinParams');
        }
        public function addHavingParams($params){
            $this->delParams('havingParams');
            $this->addParams($params, 'havingParams');
        }
        public function addRawParams($params){
            $this->addParams($params, 'rawParams');
        }

        /**
		* Ham sorguyu ve varsa parametreleri kaydeder
		*/
        public function raw($query, $params = null){
            if(!is_null($params))
                $this->addRawParams($params);
            $this->rawQuery = $query;
            return $this;
        }

        /**
		* Ham sorguyu çalıştırır
		*/
        public function exec(){
            $runQuery = $this->pdo->prepare($this->rawQuery);
            $runQuery->execute($this->rawParams);
            $this->killQuery($this->rawQuery, $this->rawParams);
            return $runQuery->rowCount();
        }

        /**
        * Koşulları tanımlamak için kullanılır
		*/
        public function whereFactory($column, $value = null, $andOr = _AND, $pattern = "%s=?", $withoutParam = false){

            $where = [];
            $param = [];

            if(is_array($column)){
                foreach($column as $key => $val){

                    // Dizideki veriler key => val şeklinde geliyorsa
                    if(!is_numeric($key)){

                        // Key içerisinde marker kontrolü
                        if($this->findMarker($key)){
                            $where[] = $key;
                            $param[] = $val;

                        // Marker yoksa formatla
                        } else{
                            $param[] = $val; // key => val geliyorsa
                            $where[] = sprintf($pattern, $key);
                        }
                    
                    // Dizi formatında gelmiyorsa key koşul olarak kaydedilir
                    } else{

                        // Parametre gönderilmiyorsa değiştir; bkz: isNull
                        $where[] = $withoutParam 
                            ? sprintf($pattern, $val) 
                            : $val;
                    }
                }

                if(!is_null($value))
                    if($value === _AND || $value === _OR) 
                        $this->setGroup($value);
                    else
                        $this->addWhereParams($value);
                
                if($param)
                    $this->addWhereParams($param);
            
            } else{

                // Koşul için bir değer belirtildiyse
                if(!is_null($value)){
                    
                    $where[] = !$this->findMarker($column) 
                        ? sprintf($pattern, $column) 
                        : $column;

                    $this->addWhereParams($value);
                
                } else{

                    // Parametre gönderilmiyorsa değiştir; bkz: isNull
                    $where[] = $withoutParam 
                            ? sprintf($pattern, $column) 
                            : $column;
                }
            }
            
            if($this->isGroupIn)
                $where = '(' . implode(' ' . $this->isGroupIn . ' ', $where) . ')'; 
            else
                $where = implode(' ' . $andOr . ' ', $where);
            $this->setGroup();

            if($this->isGrouped)
                $where = '(' . $where; $this->isGrouped = false;
            
            $this->where = is_null($this->where)
                ? $where
                : $this->where . ' ' . $andOr . ' ' . $where;

            return $this;
        }

        /**
		* Okuma işlemleri için kullanılacak parametreleri döndürür
		* @return self
		*/
        public function getReadParams(){
            if($this->rawQuery)
                return $this->rawParams;
            else
                return array_merge($this->joinParams, $this->whereParams, $this->havingParams);
        }

        /**
		* Çalıştırılacak son sorguyu oluşturur
		*/
        public function getReadQuery(){
            
            if($this->rawQuery) return $this->rawQuery;

            $build = [
                'SELECT',
                $this->selectBuild(),
                'FROM',
                $this->tableBuild(),
                $this->joinBuild(),
                $this->whereBuild(),
                $this->groupBuild(),
                $this->havingBuild(),
                $this->orderBuild(),
                $this->limitOffsetBuild(),
            ];
            return implode(' ', array_filter($build));
        }

        /**
        * Sorgu çalışıyor
		*/
        public function readQuery($fetch = 'fetch', $cursor = PDO::FETCH_ASSOC){

            $query  = $this->getReadQuery();
            $params = $this->getReadParams();

            $this->init();
            $this->killQuery($query, $params);

            if($this->cache)
                $this->cache->hash($query, join((array)$params), $fetch, $cursor);

            if(!$this->cache || !$cached = $this->cache->get())
            {
                $runQuery = $this->pdo->prepare($query);
                if($runQuery->execute($params)){
                    
                    $this->rowCount = $runQuery->rowCount();

                    $results = call_user_func_array([$runQuery, $fetch], [$cursor]);

                    if($results)
                        if($this->cache)
                            $this->cache->set($results);
                        return $results;
                }
                
            } else{
                $this->fromCache = true;
                $this->rowCount = $cached['rows'];
                return $cached['data'];
            }
        }

        /**
		* Fetch
		*/
        public function get(){
            return $this->readQuery('fetchAll', PDO::FETCH_ASSOC);
        }
        public function getObj(){
            return $this->readQuery('fetchAll', PDO::FETCH_OBJ);
        }

        /**
		* Rows
		*/
        public function getRow(){
            return $this->readQuery('fetch', PDO::FETCH_ASSOC);
        }
        public function getRowObj(){
            return $this->readQuery('fetch', PDO::FETCH_OBJ);
        }

        /**
		* Columns
		*/
        public function getCol(){
            return $this->readQuery('fetchColumn', 0);
        }
        public function getCols(){
            return $this->readQuery('fetchAll', PDO::FETCH_COLUMN);
        }

        /**
		* Filter
		*/
        public function filter($forceValid = false){
            $this->isFilter = true;
            if($forceValid)
                $this->isFilterValid = true;
            return $this;
        }

        /**
		* Tablodaki verileri doğrular
		*/
        public function filterData($table, $insertData, $forceValid = false){

            $filtered = [];
            $isBatchData = is_array(reset($insertData));
            $tableStructure = $this->showTable($table);

            if(!$isBatchData)
                $insertData = [$insertData];

            foreach($insertData as $key => $data){
                if(!$forceValid):
                    $filtered[$key] = array_intersect_key($data, $tableStructure);
                else:
                    foreach($tableStructure as $structure){
                        
                        if(!is_null($structure['Default']) && (!isset($data[$structure['Field']]) || is_null($data[$structure['Field']])))
                            $data[$structure['Field']] = $structure['Default'];

                        if(!$structure['Extra'] && $structure['Null'] == 'NO' && (!isset($data[$structure['Field']]) || is_null($data[$structure['Field']]) || $data[$structure['Field']] == '')):
                            unset($insertData[$key]); unset($filtered[$key]); break;
                        endif;
                        
                        if(isset($data[$structure['Field']]))
                            $filtered[$key][$structure['Field']] = $data[$structure['Field']];
                    }
                endif;
            }
            return !$isBatchData ? reset($filtered) : array_values($filtered);
        }

        /**
		* Insert
		*/
        public function insert($insertData, $table = null, $type = 'INSERT'){

            $typeList = ['INSERT', 'REPLACE', 'DUPLICATE'];
            
            if(!is_null($table)) 
                $this->table($table);

            if(!in_array($type, $typeList) || !is_array($insertData) || !count($insertData))
                return false;

            if($this->isFilter)
                $insertData = $this->filterData($this->tableBuild(), $insertData, $this->isFilterValid);

            if($insertData){

                if(!is_array(reset($insertData)))
                    $insertData = [$insertData];

                $columnList = implode(',', array_keys($insertData[0]));
                $markerList = $this->createMarker($insertData);
                $valuesList = [];
                array_walk_recursive($insertData, function($val, $key) use (&$valuesList){
                    $valuesList[] = $val;
                });

                if($type == 'DUPLICATE'):
                    $query = "INSERT INTO {$this->tableBuild()} ({$columnList}) VALUES {$markerList} ON DUPLICATE KEY UPDATE {$this->createMarkerWithKey($insertData, '%key=VALUES(%key)')}";
                else:
                    $query = "{$type} INTO {$this->tableBuild()} ({$columnList}) VALUES {$markerList}";
                endif;

                $runQuery = $this->pdo->prepare($query);

                if($runQuery->execute($valuesList))
                    $this->killQuery($query, $insertData);
                    $this->rowCount     = $runQuery->rowCount();
                    $this->lastInsertId = $this->pdo->lastInsertId();
                    return $this->lastInsertId;
            }
            $this->init();
        }

        public function replaceInto($insertData, $table = null){
            return $this->insert($insertData, $table, 'REPLACE');
        }

        public function onDuplicate($insertData, $table = null){
            return $this->insert($insertData, $table, 'DUPLICATE');
        }
        
        /**
		* Update
		*/
        public function update($data, $table = null){
            
            if(!$data || !$this->whereParams)
                return false;

            if(!is_null($table)) 
                $this->table($table);

            if($this->isFilter)
                $data = $this->filterData($this->tableBuild(), $data, $this->isFilterValid);

            if($data){
                $query = "UPDATE {$this->tableBuild()} SET {$this->createMarkerWithKey($data)} {$this->whereBuild()}";
                $runQuery = $this->pdo->prepare($query);
                if($runQuery->execute(array_merge(array_values($data), $this->whereParams)))
                    $this->killQuery($query, $data);
                    $this->rowCount = $runQuery->rowCount();
                    return $this->rowCount;
            }
            return false;
        }

        /**
		* Touch
		*/
        public function touch($column, $table = null){
            if(!is_null($table)) 
                $this->table($table);
            return $this->raw("UPDATE {$this->tableBuild()} SET {$column} = !{$column} {$this->whereBuild()}", $this->whereParams)->exec();
        }

        /**
		* Delete
		*/
        public function delete($table = null){

            if(!$this->whereParams)
                return false;
            
            if(!is_null($table)) 
                $this->table($table);
            
            $query = "DELETE FROM {$this->tableBuild()} {$this->whereBuild()}";

            $runQuery = $this->pdo->prepare($query);

            if($runQuery->execute($this->whereParams))
                $this->killQuery($query, $this->whereParams);
                $this->rowCount = $runQuery->rowCount();
                return $this->rowCount;

            return false;
        }

        /**
		* Structure Tools
		*/
        public function truncate($table = null){
            return $this->runStructureTool('TRUNCATE', $table);
        }
        public function drop($table = null){
            return $this->runStructureTool('DROP', $table);
        }
        public function optimize($table = null){
            return $this->runStructureTool('OPTIMIZE', $table);
        }
        public function analyze($table = null){
            return $this->runStructureTool('ANALYZE', $table);
        }
        public function check($table = null){
            return $this->runStructureTool('CHECK', $table);
        }
        public function checksum($table = null){
            return $this->runStructureTool('CHECKSUM', $table);
        }
        public function repair($table = null){
            return $this->runStructureTool('REPAIR', $table);
        }
        protected function runStructureTool($type, $table = null){

            if(!is_null($table)) 
                $this->table($table);

            $query = "{$type} TABLE {$this->tableBuild()}";

            if($runQuery = $this->pdo->query($query)){
                $this->killQuery($query);
                return $query;
            }
            return false;
        }

        /**
		* Tablodaki sütunları listeler
		*/
        public function showTable($table){
            $query = $this->pdo->query("SHOW COLUMNS FROM {$table}");
            $table = $query->fetchAll(PDO::FETCH_ASSOC);
            $valid = [];
            foreach($table as $col) $valid[$col['Field']] = $col;
            return $valid;
        }

        /**
		* Tablodaki keyleri listeler
		*/
        public function showKeys($table){
            $query = $this->pdo->query("SHOW KEYS FROM {$table}");
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }

        /**
		* Transaction
		*/
        public function transactionStatus(){
            return $this->pdo->inTransaction();
        }
        public function transaction(){
            if(!$this->pdo->inTransaction())
                $this->pdo->beginTransaction();
        }
        public function commit(){
            if($this->pdo->inTransaction())
                $this->pdo->commit();
        }
        public function rollBack(){
            if($this->pdo->inTransaction())
                $this->pdo->rollBack();
        }

        /**
		* Son eklenen ID'yi döndürür
		*/
        public function lastInsertId(){
            return $this->lastInsertId;
        }

        /**
		* Etkilenen veya görüntülenen satır sayısını döndürür
		*/
        public function rowCount(){
            return $this->rowCount;
        }

        /**
		* Toplam sorgu sayısını getirir
		*/
        public function queryCount(){
            return sizeof($this->queryHistory);
        }

        /**
		* Geçmiş sorguları listeler
		*/
        public function queryHistory(){
            return $this->queryHistory;
        }

        /**
		* Son sorguyu getirir
		*/
        public function lastQuery($withParams = false){
            return $withParams ? end($this->queryHistory) : end($this->queryHistory)['query'];
        }

        /**
		* Sorguyu geçmişe kaydeder
		*/
        public function addQueryHistory($query, $params = null){
            return $this->queryHistory[] = [
                'query'  => $query,
                'params' => $params
            ];
        }

        /**
		* Sorguyu geçmişe kaydeder ve yeniler
		*/
        public function killQuery($query, $params = null){
            $this->addQueryHistory($query, $params);
            $this->init();
        }

        /**
		* Bağlantıyı sonlandırır
		*/
        public function close(){
            $this->pdo = null;
        }
    }