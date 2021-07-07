<?php error_reporting(E_ALL); 

    require __DIR__.'/vendor/autoload.php';

    $db = new \Mlevent\Pdb(['database' => 'ecommerce', 'username' => 'root']);

    /** SELECT */
    //$db->select('id, name, price, tax')
    //$db->select(['id', 'name', 'price', 'tax'])

    /** TABLE *//*
    $db->table('products')
    $db->select('p.id, p.name, GROUP_CONCAT(v.attr) AS attributes')
       ->table(['products as p', 'variants as v'])
       ->where('p.id = v.pid')
       ->groupBy('v.pid')
    */

    /** RAW */
    //$db->raw('SELECT id, name FROM table WHERE id > ?', 34000)->getObj()
    //$db->raw('SELECT id, name FROM table WHERE id > ? AND active = ?', [34000, 1])->getObj()

    /** Join *//*
    $db->leftJoin('images as i', 'p.id', 'i.pid')
    $db->leftJoin('images as i', 'p.id = i.pid')
    $db->leftJoin('images as i ON p.id = i.pid')
    */

    /** Order *//*
    $db->orderBy('id')
    $db->orderBy('id desc, name asc')
    $db->orderBy('id', 'desc')
    */

    /** Group *//*
    $db->grpupBy('id')
    $db->grpupBy(['id', 'name'])
    */

    /** Limit // Ofset // Paging *//*
    $db->limit(100)
    $db->limit(100, 10)
    $db->offset(100)
    $db->paging(100, 2)
    */

    /** Having *//*
    $db->having('stock', 5')
    $db->having('stock > 5')
    $db->having('stock > ?', 5)
    $db->having('id IN(?,?)', [3,5])
    */

    /** Where *//*
    $db->where('id', 32886)
    $db->where('stock >= 2 AND active = 0')
    $db->where('stock >= ? AND active = ?', [3, 1])
    $db->where(['stock > ?', 'active > ?'], [2, 1])
    $db->where(['stock' => 2, 'active' => 1])
    */

    /** isNull *//*
    $db->isNull('min_age')
    $db->isNull(['min_age', 'max_age'], _OR)
    */

    /** in *//*
    $db->in('id', [23514, 23515])
    */

    /** findInSet *//*
    $db->findInSet('id', 23514)
    */

    /** findInSet *//*
    $db->between('price', 50, 100)
    */

    //pre($db->analyze('pip'));

    /*
    $data = $db->select('p.id, p.product_name, p.stock, p.price')
               //->max('p.stock')
               ->table('products as p')
               ->where('id > ?', 1)
               //->like('product_name', '%classic%')
               //->where(['stock = ?', 'active = ?'], [2, 1])
               ->between('price', 0, 20)
               ->getObj();

    //pre($db->getReadQuery());
    pre($db->queryHistory());
    pre($data);

    exit;
    */
     //$first = $db->raw('SELECT * FROM products WHERE id > ?', 2)->get();

            //pre(App::db()->showTable('urunler'));
    
            $data = $db->select('id, product_name')
                        //->select('(select count(p.id) from products as p) as total')
                            //->count('id', 'ss')
                            //->sum('price', 'sp')
                            ->table('products as u')
                            /*->group(function($q){
                                $q->where('p.id IN(?)', '13')->orWhere('p.name = ?', 'hasan');
                            })*/
                            //->leftJoin('products as p', 'p.id', '3')
                            //->where(['price', 'product_code'], [3, 3])
                            //->where(['price' => 3, 'product_code' => 4], _OR)
                            //->orWhere(['price > ?', 'product_code > ?'], [2,3])
                            //->where(['u.id > 3', 'u.price > 3'])
                            //->orWhere('product_code > ?', 14)
                            //->orNotLike('%lego%', 'product_name')
                            //->orNotLike('%HASO%', 'product_name')
                            ->where('id > ?', 34066)
                            //->notNull('product_name')
                            //->where('MONTH(updated)', 'MONTH(NOW())')
                            //->rawWhere(['id > 34060', 'MONTH(updated) = MONTH(NOW())'], _AND)
                            //->where('(id > ? OR price > ?)', [3, 100])
                            //->orIsNull(['created', 'url_slug'])
                            //->where(['u.productId > 3', 'u.price > 3'])
                            //->where(['u.productId > ?', 'u.price > ?'], [3,2])
                            ->orderBy('u.id')
                            //->cache(10)
                            //->in('u.productId', [1,2,3])
                            //->having('count(u.name)', 0)
                            //->groupBy('u.name')
                            //->limit(10)
                            ->getObj();
            
            #pre($db->fromCache());
            #pre($data);

            $insertDataBatch = [
                            array('product_name' => 'Deneme 1', 'url_slug' => 'deneme', 'product_code' => 'limon1', 'category_id' => 1, 'price' => '125.50', 'barcode' => 161242353453245, 'stock' => 1, 'min_age' => 0, 'max_age' => 3, 'gender' => 'Kız', 'active' => 1),
                            array('product_name' => 'Deneme 2', 'url_slug' => 'deneme', 'product_code' => 'limon2', 'category_id' => 1, 'price' => '125.50', 'barcode' => 161242353453245, 'stock' => 1, 'min_age' => 0, 'max_age' => 3, 'gender' => 'Kız', 'active' => 1),
                            array('product_name' => 'Deneme 3', 'url_slug' => 'deneme', 'product_code' => 'limon3', 'category_id' => 1, 'price' => '125.50', 'barcode' => 161242353453245, 'stock' => 1, 'min_age' => 0, 'max_age' => 3, 'gender' => 'Kız', 'active' => 1)
                        ];

            $insertData = array('product_name' => 'Deneme 1', 'url_slug' => 'deneme', 'product_code' => 'limon4', 'category_id' => 1, 'price' => '125.50', 'barcode' => 161242353453245, 'stock' => 1, 'min_age' => 0, 'max_age' => 3, 'gender' => 'Kız', 'active' => 1);
            
            #$insertData = array('product_name' => 'Haystack', 'url_slug' => 'deneme', 'product_code' => 'lanben2', 'price' => '125.50', 'price_old' => '322', 'tax' => 16, 'discount' => 0, 'stock' => 0, 'stock_status' => 0, 'cargo_free' => 0, 'cargo_paid' => 0, 'cargo_price' => 0, 'featured' => 0, 'marketplace' => 0, 'ad_services' => 'Normal', 'active' => 1, 'status' => 'Ban', 'on_sql' => 0, 'no_update' => 0, 'use_timer' => 0);

            $query = $db->filter(true)->onDuplicate($insertDataBatch, 'products');
            pre($query);
            #$query = $db->table('products')->filter(true)->onDuplicate($insertData);

            #$query = $db->table('products')->like('product_name', '%Deneme%')->update(['url_slug' => rand()]);
            #$query = $db->like('product_code', '%limon%')->delete('products');

            #echo $db->table('products')->where('category_id', '164')->update(['url_slug' => rand()]);
            #echo $db->table('products')->where('id > ?', 34089)->delete();

            //pre($db->table('products')->where('id > ?', 1)->touch('active'));
            
            pre($db->lastInsertId());
            pre($db->rowCount());
            pre($db->queryCount());
            pre($db->lastQuery());
            pre($db->queryHistory());

            function pre($var){
                echo '<pre>';
                print_r($var);
                echo '</pre>';
            }