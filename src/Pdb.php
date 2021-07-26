<?php

    namespace Mlevent;

    use PDOException;
    use PDO;
    use Closure;
    use RedisException;

    if(!defined('_AND')) define('_AND', 'AND');
    if(!defined('_OR'))  define('_OR',  'OR');

    class Pdb
    {
        private $pdo;
        private $config;
        private $cache;
        private $redis;
        private $redisActive;
        
        private $fromDisk      = false;
        private $fromRedis     = false;

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
        private $isGroupIn     = false;

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
                'host'          => 'localhost',
                'database'      => '',
                'username'      => 'root',
                'password'      => '',
                'charset'       => 'utf8',
                'collation'     => 'utf8_unicode_ci',
                'debug'         => false,
                'cacheTime'     => 60,
                'cachePath'     => __DIR__ . '/Cache',
                'useRedis'      => false,
                'redisHost'     => '127.0.0.1',
                'redisPort'     => 6379,
                'redisUsername' => 'default',
                'redisPassword' => '',
                'redisDatabase' => 0,
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

            if($this->config['useRedis'])
            {
                try{
                    $this->redis = new \Redis(); 
                    $this->redis->connect($this->config['redisHost'], $this->config['redisPort']);
                    $this->redis->auth([$this->config['redisUsername'], $this->config['redisPassword']]);
                    $this->redis->select($this->config['redisDatabase']);

                } catch(RedisException $e){ die($e->getMessage()); }
            }
        }
                     
        /**
         * init
         */
        protected function init(){
            
            $this->rowCount      = 0;
            $this->cache         = null;
            $this->redisActive   = false;
            $this->fromDisk      = false;
            $this->fromRedis     = false;
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
         * redis
         *
         * @param int $timeout
         * @return $this
         */
        public function redis(int $timeout = null){
            $this->redisActive = $this->redis ? $timeout : false;
            return $this;
        }
        
        /**
         * Verinin diskten okunup okunmadığını doğrular
         *
         * @return $this
         */
        public function fromDisk(){
            return $this->fromDisk;
        }

        /**
         * Verinin redisten okunup okunmadığını doğrular
         *
         * @return $this
         */
        public function fromRedis(){
            return $this->fromRedis;
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
        
        /**
         * havingBuild
         *
         * @return void
         */
        public function havingBuild(){
            return $this->having ? 'HAVING ' . $this->having : null;
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
         * isNull
         *
         * @param string|array $column
         * @param string $group
         * @param string $andOr
         * @return $this
         */
        public function isNull($column, $group = null, $andOr = _AND){
            return $this->whereFactory($column, $group, $andOr, "%s IS NULL", true);
        }

        /**
         * orIsNull
         *
         * @param string|array $column
         * @param string $group
         * @param string $andOr
         * @return $this
         */
        public function orIsNull($column, $group = null){
            return $this->isNull($column, $group, _OR);
        }

        /**
         * notNull
         *
         * @param string|array $column
         * @param string $group
         * @param string $andOr
         * @return $this
         */
        public function notNull($column, $group = null, $andOr = _AND){
            return $this->whereFactory($column, $group, $andOr, "%s IS NOT NULL", true);
        }

        /**
         * orNotNull
         *
         * @param string|array $column
         * @param string $group
         * @param string $andOr
         * @return $this
         */
        public function orNotNull($column, $group = null){
            return $this->notNull($column, $group, _OR);
        }
        
        /**
         * in
         *
         * @param string $column
         * @param array  $value
         * @param string $andOr
         * @return $this
         */
        public function in($column, $value, $andOr = _AND){
            return $this->whereFactory($column, (array)$value, $andOr, "%s IN({$this->createMarker((array)$value)})");
        }

        /**
         * orIn
         *
         * @param string $column
         * @param array  $value
         * @return $this
         */
        public function orIn($column, $value){
            return $this->in($column, $value, _OR);
        }

        /**
         * notIn
         *
         * @param string $column
         * @param array  $value
         * @return $this
         */
        public function notIn($column, $value, $andOr = _AND){
            return $this->whereFactory($column, (array)$value, $andOr, "%s NOT IN({$this->createMarker((array)$value)})");
        }

        /**
         * orNotIn
         *
         * @param string $column
         * @param array  $value
         * @return $this
         */
        public function orNotIn($column, $value){
            return $this->in($column, $value, _OR);
        }
        
        /**
         * between
         *
         * @param string $column
         * @param int    $begin
         * @param int    $end
         * @param string $andOr
         * @return $this
         */
        public function between($column, int $begin, int $end, $andOr = _AND){
            return $this->whereFactory($column, [$begin, $end], $andOr, "%s BETWEEN ? AND ?");
        }

        /**
         * orBetween
         *
         * @param string $column
         * @param int    $begin
         * @param int    $end
         * @return $this
         */
        public function orBetween($column, int $begin, int $end){
            return $this->between($column, $begin, $end, _OR);
        }

        /**
         * notBetween
         *
         * @param string $column
         * @param int    $begin
         * @param int    $end
         * @param string $andOr
         * @return $this
         */
        public function notBetween($column, int $begin, int $end, $andOr = _AND){
            return $this->whereFactory($column, [$begin, $end], $andOr, "%s NOT BETWEEN ? AND ?");
        }

        /**
         * orNotBetween
         *
         * @param string $column
         * @param int    $begin
         * @param int    $end
         * @return $this
         */
        public function orNotBetween($column, int $begin, int $end){
            return $this->between($column, $begin, $end, _OR);
        }
        
        /**
         * findInSet
         *
         * @param string $column
         * @param string $search
         * @param string $andOr
         * @return $this
         */
        public function findInSet($column, $search, $andOr = _AND){
            return $this->whereFactory(null, $search, $andOr, "FIND_IN_SET(?, {$column})");
        }

        /**
         * orFindInSet
         *
         * @param string $column
         * @param string $search
         * @return $this
         */
        public function orFindInSet($column, $search){
            return $this->findInSet($column, $search, _OR);
        }

        /**
         * like
         *
         * @param string $column
         * @param string|array $search
         * @param string $group
         * @param string $andOr
         * @param string $pattern
         * @return $this
         */
        public function like($column, $search, $group = null, $andOr = _AND, $pattern = '%s LIKE ?'){
            $params = [];
            $column = (array)$column;
            foreach($column as $val) $params[sprintf($pattern, $val)] = $search;
            return $this->whereFactory($params, $group, $andOr);
        }
                
        /**
         * orLike
         *
         * @param string $column
         * @param string|array $search
         * @param string $group
         * @return $this
         */
        public function orLike($column, $search, $group = null){
            return $this->like($column, $search, $group, _OR);
        }

        /**
         * notLike
         *
         * @param string $column
         * @param string|array $search
         * @param string $group
         * @return $this
         */
        public function notLike($column, $search, $group = null){
            return $this->like($column, $search, $group, _AND, '%s NOT LIKE ?');
        }

        /**
         * orNotlike
         *
         * @param string $column
         * @param string|array $search
         * @param string $group
         * @return $this
         */
        public function orNotlike($column, $search, $group = null){
            return $this->like($column, $search, $group, _OR, '%s NOT LIKE ?');
        }
        
        /**
         * String içinde marker arar
         *
         * @param string $string
         * @return bool
         */
        public function findMarker($string){
            return strpos($string, '?') !== FALSE;
        }
        
        /**
         * Sorgu için parametre sayısı kadar marker oluşturur
         *
         * @param mixed $params
         * @return string
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
                
        /**
         * Sorgu için pattern oluşturur
         *
         * @param array  $params
         * @param string $pattern
         * @param mixed  $comma
         * @return string
         */
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
         * addParams
         *
         * @param array $params
         * @param string $type
         * @return void
         */
        protected function addParams($params, $type = 'whereParams'){
            if(is_array($params))
                foreach($params as $p) $this->$type[] = $p;
            else
                if(!is_null($params))
                    $this->$type[] = $params;
        }
                
        /**
         * delParams
         *
         * @param string $key
         * @return void
         */
        protected function delParams($key){
            if(isset($this->$key))
                $this->$key = [];
        }
                
        /**
         * addWhereParams
         *
         * @param array $params
         * @return void
         */
        public function addWhereParams($params){
            $this->addParams($params);
        }
                
        /**
         * addJoinParams
         *
         * @param array $params
         * @return void
         */
        public function addJoinParams($params){
            $this->addParams($params, 'joinParams');
        }    

        /**
         * addHavingParams
         *
         * @param array $params
         * @return void
         */
        public function addHavingParams($params){
            $this->delParams('havingParams');
            $this->addParams($params, 'havingParams');
        }        

        /**
         * addRawParams
         *
         * @param array $params
         * @return void
         */
        public function addRawParams($params){
            $this->addParams($params, 'rawParams');
        }
        
        /**
         * raw
         *
         * @param string $query
         * @param string|array $params
         * @return $this
         */
        public function raw($query, $params = null){
            if(!is_null($params))
                $this->addRawParams($params);
            $this->rawQuery = $query;
            return $this;
        }
    
        /**
         * exec
         *
         * @return int
         */
        public function exec(){
            $runQuery = $this->pdo->prepare($this->rawQuery);
            $runQuery->execute($this->rawParams);
            $this->killQuery($this->rawQuery, $this->rawParams);
            return $runQuery->rowCount();
        }
        
        /**
         * whereFactory
         *
         * @param string|array $column
         * @param string|array $value
         * @param string $andOr
         * @param string $pattern
         * @param bool $withoutParam
         * @return $this
         */
        public function whereFactory($column, $value = null, $andOr = _AND, $pattern = "%s=?", $withoutParam = false){

            $where = [];
            $param = [];

            if(is_array($column)){

                foreach($column as $key => $val){

                    // key => val
                    if(!is_numeric($key)){

                        // Key içinde marker var mı?
                        if($this->findMarker($key)){
                            $where[] = $key;
                            $param[] = $val;

                        } else{
                            $param[] = $val; // key => val
                            $where[] = sprintf($pattern, $key);
                        }

                    } else{

                        // Parametre gönderilmiyorsa(bkz; isNull)
                        $where[] = $withoutParam 
                            ? sprintf($pattern, $val) 
                            : $val;
                    }
                }

                // Value grup bilgisi gönderdiyse
                if(!is_null($value))
                    if($value === _AND || $value === _OR) 
                        $this->setGroup($value);
                    else
                        $this->addWhereParams($value);
                
                if($param)
                    $this->addWhereParams($param);
            
            } else{

                if(!is_null($value)){
                    
                    $where[] = !$this->findMarker($column) 
                        ? sprintf($pattern, $column) 
                        : $column;

                    $this->addWhereParams($value);
                
                } else{

                    // Parametre gönderilmiyorsa(bkz; isNull)
                    $where[] = $withoutParam 
                            ? sprintf($pattern, $column) 
                            : $column;
                }
            }
            
            // Sorgu içi grup isteniyorsa grupla
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
         * getReadParams
         *
         * @return array
         */
        public function getReadParams(){
            if($this->rawQuery)
                return $this->rawParams;
            else
                return array_merge($this->joinParams, $this->whereParams, $this->havingParams);
        }
        
        /**
         * getReadQuery
         *
         * @return string
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
         * getReadHash
         *
         * @return string
         */
        public function getReadHash(){
            return md5(implode(func_get_args()));
        }
    
        /**
         * readQuery
         *
         * @param string $fetch
         * @param int $cursor
         * @return mixed
         */
        public function readQuery($fetch = 'fetch', $cursor = PDO::FETCH_ASSOC){

            $query  = $this->getReadQuery();
            $params = $this->getReadParams();
            $hash   = $this->getReadHash($query, join((array)$params), $fetch, $cursor);

            // Redis Cache
            if($this->redisActive)
            {
                if($this->redis->exists($hash))
                {
                    $data = unserialize($this->redis->get($hash));
                    $this->killQuery($query, $params, 'redis');
                    $this->fromRedis = true;
                    $this->rowCount = sizeof((array)$data);
                    return $data;
                }
            }

            // Disk Cache
            if($this->cache)
                $this->cache->setFile($hash);

            if($this->cache && $cached = $this->cache->get())
            {
                $this->killQuery($query, $params, 'disk');
                $this->fromDisk = true;
                $this->rowCount = $cached['rows'];
                return $cached['data'];
            }

            // SQL Query
            $runQuery = $this->pdo->prepare($query);
            if($runQuery->execute($params))
            {
                $results = call_user_func_array([$runQuery, $fetch], [$cursor]);

                if($this->redisActive)
                    $this->redis->set($hash, serialize($results), $this->redisActive);
                    
                if($this->cache)
                    $this->cache->set($results);

                $this->killQuery($query, $params, 'mysql');
                $this->rowCount = $runQuery->rowCount();
                return $results;
            }
        }

        /**
		* Fetch
		*/
        public function get($table = null){
            if(!is_null($table)) 
                $this->table($table);
            return $this->readQuery('fetchAll', PDO::FETCH_ASSOC);
        }
        public function getObj($table = null){
            if(!is_null($table)) 
                $this->table($table);
            return $this->readQuery('fetchAll', PDO::FETCH_OBJ);
        }

        /**
		* Row
		*/
        public function getRow($table = null){
            if(!is_null($table)) 
                $this->table($table);
            return $this->readQuery('fetch', PDO::FETCH_ASSOC);
        }
        public function getRowObj($table = null){
            if(!is_null($table)) 
                $this->table($table);
            return $this->readQuery('fetch', PDO::FETCH_OBJ);
        }

        /**
		* Col
		*/
        public function getCol($table = null){
            if(!is_null($table)) 
                $this->table($table);
            return $this->readQuery('fetchColumn', 0);
        }
        public function getCols($table = null){
            if(!is_null($table)) 
                $this->table($table);
            return $this->readQuery('fetchAll', PDO::FETCH_COLUMN);
        }
    
        /**
         * filter
         *
         * @param bool $forceValid
         * @return $this
         */
        public function filter($forceValid = false){
            $this->isFilter = true;
            if($forceValid)
                $this->isFilterValid = true;
            return $this;
        }
     
        /**
         * filterData
         *
         * @param string $table
         * @param array $insertData
         * @param bool $forceValid
         * @return array
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
         * insert
         *
         * @param array $insertData
         * @param string $table
         * @param string $type
         * @return int|bool
         */
        public function insert($insertData, $table = null, $type = 'INSERT'){

            $typeList = ['INSERT', 'INSERT IGNORE', 'REPLACE', 'DUPLICATE'];
            
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

        /**
         * replaceInto
         *
         * @param array $insertData
         * @param string $table
         * @return int|bool
         */
        public function replaceInto($insertData, $table = null){
            return $this->insert($insertData, $table, 'REPLACE');
        }

        /**
         * insertIgnore
         *
         * @param array $insertData
         * @param string $table
         * @return int|bool
         */
        public function insertIgnore($insertData, $table = null){
            return $this->insert($insertData, $table, 'INSERT IGNORE');
        }
        
        /**
         * onDuplicate
         *
         * @param array $insertData
         * @param string $table
         * @return int|bool
         */
        public function onDuplicate($insertData, $table = null){
            return $this->insert($insertData, $table, 'DUPLICATE');
        }
           
        /**
         * update
         *
         * @param array $data
         * @param string $table
         * @return int|bool
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
         * touch
         *
         * @param string $column
         * @param string $table
         * @return int|bool
         */
        public function touch($column, $table = null){
            if(!is_null($table)) 
                $this->table($table);
            return $this->raw("UPDATE {$this->tableBuild()} SET {$column} = !{$column} {$this->whereBuild()}", $this->whereParams)->exec();
        }
        
        /**
         * delete
         *
         * @param string $table
         * @return int|bool
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
         * runStructureTool
         *
         * @param  string $type
         * @param  string $table
         * @return string|bool
         */
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
        
        /**
         * showTable
         *
         * @param string $table
         * @return array
         */
        public function showTable($table){
            $query = $this->pdo->query("SHOW COLUMNS FROM {$table}");
            $table = $query->fetchAll(PDO::FETCH_ASSOC);
            $valid = [];
            foreach($table as $col) $valid[$col['Field']] = $col;
            return $valid;
        }
        
        /**
         * showKeys
         *
         * @param string $table
         * @return array
         */
        public function showKeys($table){
            $query = $this->pdo->query("SHOW KEYS FROM {$table}");
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }

        /**
         * getPrimary
         *
         * @param string $table
         * @return void
         */
        public function getPrimary($table){
            $query = $this->pdo->query("SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = '{$table}' AND CONSTRAINT_NAME = 'PRIMARY'");
            return $query->fetchColumn();
        }
        
        /**
         * inTransaction
         *
         * @return void
         */
        public function inTransaction(){
            return $this->pdo->inTransaction();
        }   

        /**
         * beginTransaction
         *
         * @return void
         */
        public function beginTransaction(){
            if(!$this->pdo->inTransaction())
                $this->pdo->beginTransaction();
        } 

        /**
         * commit
         *
         * @return void
         */
        public function commit(){
            if($this->pdo->inTransaction())
                $this->pdo->commit();
        }
               
        /**
         * rollBack
         *
         * @return void
         */
        public function rollBack(){
            if($this->pdo->inTransaction())
                $this->pdo->rollBack();
        }
        
        /**
         * lastInsertId
         *
         * @return int
         */
        public function lastInsertId(){
            return $this->lastInsertId;
        }
        
        /**
         * rowCount
         *
         * @return int
         */
        public function rowCount(){
            return $this->rowCount;
        }
        
        /**
         * queryCount
         *
         * @return int
         */
        public function queryCount(){
            return sizeof($this->queryHistory);
        }
        
        /**
         * queryHistory
         *
         * @return array
         */
        public function queryHistory(){
            return $this->queryHistory;
        }
        
        /**
         * lastQuery
         *
         * @param bool $withParams
         * @return string|array
         */
        public function lastQuery($withParams = false){
            return $withParams ? end($this->queryHistory) : end($this->queryHistory)['query'];
        }

        /**
         * lastParams
         *
         * @return array
         */
        public function lastParams(){
            return $this->queryCount() ? end($this->queryHistory)['params'] : false;
        }
        
        /**
         * addQueryHistory
         *
         * @param string $query
         * @param string|array $params
         * @return array
         */
        public function addQueryHistory($query, $params = null, $from = false){
            return $this->queryHistory[] = [
                'query'  => $query,
                'params' => $params,
                'from'   => $from,
            ];
        }
        
        /**
         * killQuery
         *
         * @param string $query
         * @param string|array $params
         * @return void
         */
        public function killQuery($query, $params = null, $from = null){
            $this->addQueryHistory($query, $params, $from);
            $this->init();
        }
        
        /**
         * close
         *
         * @return void
         */
        public function close(){
            $this->pdo = null;
        }
    }