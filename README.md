# PDB

PHP için PDO sınıfı

[![Latest Stable Version](https://poser.pugx.org/mlevent/pdb/v/stable.svg)](https://packagist.org/packages/mlevent/pdb)
[![Latest Unstable Version](https://poser.pugx.org/mlevent/pdb/v/unstable.svg)](https://packagist.org/packages/mlevent/pdb)
[![License](https://poser.pugx.org/mlevent/pdb/license.svg)](https://packagist.org/packages/mlevent/pdb)
[![Total Downloads](https://poser.pugx.org/mlevent/pdb/d/total.svg)](https://packagist.org/packages/mlevent/pdb)

## Kurulum

```
$ composer require mlevent/pdb
```

## Örnek Kullanım

```php
$db = new \Mlevent\Pdb([
    'database' => 'ecommerce',
    'username' => 'root'
    'password' => 'test',
    'charset'  => 'utf8'
]);
```

## Yapılandırma

Varsayılan yapılandırma ayarları.

-   `host` : `localhost`
-   `database` : `''`
-   `username` : `root`
-   `password` : `''`
-   `charset`: 'utf8'
-   `collation`: 'utf8_unicode_ci'
-   `debug`: false
-   `cacheTime`: 60
-   `cachePath`: `__DIR__ . '/Cache'`
-   `useRedis`: false
-   `redisHost`: '127.0.0.1'
-   `redisPort`: 6379
-   `redisUsername`: 'default'
-   `redisPassword`: ''
-   `redisDatabase`: 0

## Fetch

Sonuçlar `Array` formatında döner. `Object` olarak ulaşmak için `getObj()` metodunu kullanabilirsiniz.

```php
$results = $db->select('id, name, code, slug, price, stock')
              ->table('products')
              ->where('price > ?', 50)
              ->order('id')
              ->get();
```

Kullanılabilecek metotlar: `get()`, `getObj()`, `getRow()`, `getRowObj()`, `getCol()`, `getCols()`

## Raw Query

Salt sql sorgusu çalıştırmak için kullanılır.

### Raw Fecth

```php
$results = $db->raw('SELECT id FROM products WHERE active = ? AND MONTH(created) = MONTH(NOW())', 1)
              ->getCols();
```

### Raw Exec

```php
$update = $db->raw('UPDATE payments SET active = !active WHERE status = ?', ['paid'])
             ->exec();
```

## Cache

Sonuçları önbelleğe almak için kullanılır. Çok sık değişmesi gerekmeyen ve yoğun kullanımda performans sorunu oluşturabilecek sorgular için kullanılabilir.

### Disk Cache

`comments` tablosundaki verileri mysql'den okur ve diske kaydeder. Sonuçlar 30 saniye boyunca diskten okunur.

```php
$results = $db->cache(30)->get('comments');
```

`fromDisk()` metodu; son sorgu diskten okunuyorsa `true`, mysql'den okunuyorsa `false` döner.

### Redis Cache

`comments` tablosundaki verileri mysql'den okur ve redis veritabanına kayder. Sonuçlar 30 saniye boyunca Redis üzerinden okunur.

```php
$results = $db->redis(30)->get('comments');
```

`fromRedis()` metodu; son sorgu Redisten okunuyorsa `true`, mysql'den okunuyorsa `false` döner.

> Not: Redis ile önbellekleme işlemi yapabilmek için sunucunuzda Redis yüklü olması gerekir.

## Insert

Tabloya yeni bir satır eklemek için kullanılır. `insert()` metoduyla tek veya birden fazla kayıt eklenebilir.

### Tekli Kayıt

```php
$db->table('products')->insert([
    'name'  => 'Apple Iphone X 128 Gb',
    'code'  => 'APPLEX128',
    'price' => '999.9'
]);
```

### Çoklu Kayıt

```php
$db->table('products')->insert([
    ['name' => 'Apple Iphone X 128 Gb', 'code' => 'APPLEX128', 'price' => '999.9'],
    ['name' => 'Apple Iphone X 256 Gb', 'code' => 'APPLEX256', 'price' => '1149.9'],
    ['name' => 'Apple Iphone X 512 Gb', 'code' => 'APPLEX512', 'price' => '1349.9']
]);
```

Son kaydedilen satırın birincil anahtarına ulaşmak için `lastInsertId()` metodunu, toplam etkilenen satır sayısı için `rowCount()` metodunu kullanabilirsiniz.

### Insert Ignore

```php
$db->table('products')->insertIgnore([
    ['name' => 'Apple Iphone X 128 Gb', 'code' => 'APPLEX128', 'price' => '999.9'],
    ['name' => 'Apple Iphone X 256 Gb', 'code' => 'APPLEX256', 'price' => '1149.9']
]);
```

### Replace Into

```php
$db->table('products')->replaceInto([
    ['name' => 'Apple Iphone X 128 Gb', 'code' => 'APPLEX128', 'price' => '999.9'],
    ['name' => 'Apple Iphone X 256 Gb', 'code' => 'APPLEX256', 'price' => '1149.9']
]);
```

### On Duplicate

```php
$db->table('products')->onDuplicate([
    ['name' => 'Apple Iphone X 128 Gb', 'code' => 'APPLEX128', 'price' => '999.9'],
    ['name' => 'Apple Iphone X 256 Gb', 'code' => 'APPLEX256', 'price' => '1149.9']
]);
```

## Update

Bir veya birden fazla kaydı güncellemek için kullanılır.

```php
$update = $db->table('products')->where('active', 0)->update(['active' => 1]);
```

-   Etkilenen satır sayısı döner.

## Delete

Bir veya birden fazla kaydı silmek için kullanılır.

```php
$delete = $db->isNull('slug')->delete('products');
```

-   Etkilenen satır sayısı döner.

## Filter

Gönderilen veriyi tablodaki sütunlarla karşılaştırır ve yanlış/fazla veriyi otomatik olarak temizler. `insert()`, `insertIgnore()`, `replaceInto()`, `onDuplicate()`, `update()` metodlarıyla birlikte kullanılabilir.

| Primary | Not Null |     Not Null | Null |
| ------- | :------: | -----------: | ---- |
| id      |   name   |        email | age  |
| 1       | John Doe | john@doe.com | 32   |
| 2       | Jane Doe | jane@doe.com | 19   |

```php
$db->table('users')->filter()->insert([
    'name'  => 'Walter Bishop',
    'email' => 'walter@bishop.com',
    'age'   => 39,
    'price' => 3994
]);
```

-   Tabloda `price` sütunu olmamasına rağmen hatasız bir şekilde kayıt oluşturulur.

```php
$db->table('users')->filter()->insert($_POST);
```

-   `$_POST` ile gelen veriyi temizler ve hatasız bir şekilde kayıt oluşturulur.

```php
$db->table('users')->filter(true)->insert([
    'name' => 'Walter Bishop',
    'age'  => 'walter@bishop.com'
]);
```

-   `email` sütunu `notnull` olarak tanımlandığı için kayıt eklenmez.

## Transaction

Metodlar: `inTransaction()`, `beginTransaction()`, `commit()`, `rollBack()`

```php
try {

    $db->beginTransaction();

    $db->table('products')->insert([
        'name'  => 'Apple Iphone X 128 Gb',
        'code'  => 'APPLEX128',
        'price' => '999.9'
    ]);

    $db->table('images')->insert([
        'productId' => $db->lastInsertId(),
        'imageName' => 'foo.jpg'
    ]);

    $db->commit();

} catch(Exception $e) {

    $db->rollBack();
}
```

## Select

```php
$db->select('id, name, code, price')
```

-   `select('id, name')`
-   `select(['id', 'name', ...])`

> Metod kullanılmazsa varsayılan olarak `*` ile tüm sütunlar seçilir.

## Table

```php
$db->table('products')
```

-   `table('products')`
-   `table(['products as p', 'images as i'])`

## Join

Metodlar: `leftJoin()`, `rightJoin()`, `innerJoin()`, `leftOuterJoin()`, `rightOuterJoin()`, `fullOuterJoin()`

```php
$db->table('products as p')
   ->leftJoin('images as i', 'p.id', 'i.productId')
   ->get();
```

-   `leftJoin('images', 'products.id', 'images.productId')`
-   `leftJoin('images', 'products.id = images.productId')`
-   `leftJoin('images ON products.id = images.productId')`

## Where

Metodlar: `where()`, `orWhere()`, `notWhere()`, `orNotWhere()`

```php
$db->where('id', 32886)
```

-   `where('active', 1)`
-   `where('stock >= ? AND active = ?', [2, 1])`
-   `where(['stock > ?', 'active > ?'], [2, 1])`
-   `where(['stock' => 2, 'active' => 1])`
-   `where('stock >= 2 AND active = 1 AND MONTH(updated) = MONTH(NOW())')`

## Group Where

```php
$db->table('products')
   ->like('name', '%iphone%')
   ->grouped(function($q){
        $q->in('brandId', [1, 2, 3])->orIn('categoryId', [1, 2, 3]);
   })->get();
```

-   `SELECT * FROM products WHERE name LIKE ? AND (brandId IN(?,?,?) OR categoryId IN(?,?,?))`

## Between

Metodlar: `between()`, `orBetween()`, `notBetween()`, `orNotBetween()`

```php
$db->between('price', 50, 250)
```

## Is Null - Not Null

Metodlar: `isNull()`, `orIsNull()`, `notNull()`, `orNotNull()`

```php
$db->isNull('code')
```

-   `isNull('slug')`
-   `isNull(['slug', ...])`

## In - Not In

Metodlar: `in()`, `orIn()`, `notIn()`, `orNotIn()`

```php
$db->in('id', [33922, 31221, 45344, 35444])
```

## Find In Set

Metodlar: `findInSet()`, `orFindInSet()`

```php
$db->findInSet('categoryId', 139)
```

## Like - Not Like

Metodlar: `like()`, `orLike()`, `notLike()`, `orNotlike()`

```php
$db->like('name', '%Apple%')
```

## Order

Varsayılan olarak `desc` seçilir.

```php
$db->order('id')
```

-   `order('id')`
-   `order('id', 'asc')`
-   `order('id desc, name asc')`
-   `order('rand()')`

## Group

```php
$db->group('id')
```

-   `group('id')`
-   `group(['id', 'name'])`

## Having

```php
$db->having('stock', 5)
```

-   `having('stock', 5)`
-   `having('stock > 5')`
-   `having('stock > ?', 5)`

## Limit - Offset - Pager

Limit, Offset ve Sayfalama işlemleri için kullanılır.

```php
$db->limit(100)
$db->limit(100, 0)
$db->limit(100)->offset(0)
$db->pager(100, 1)
```

## History

### queryHistory()

Sorgu listesine ulaşmak için kullanılır.

```php
var_dump($db->queryHistory());
```

```
Array
(
    [0] => Array
        (
            [query] => SELECT id, name FROM products WHERE code = ? AND active = ? ORDER BY id desc
            [params] => Array
                (
                    [0] => 34066
                    [1] => 1
                )

            [from] => redis
        )
)
```

### lastQuery()

Son sorguyu görüntülemek için kullanılır.

```php
echo $db->lastQuery();
```

-   `SELECT id, name FROM products WHERE code = ? AND active = ? ORDER BY id desc`

### lastParams()

Son sorguyu ait parametreleri görmek için kullanılır.

```php
var_dump($db->lastParams());
```

```
Array
(
    [0] => 'APPLEX128',
    [1] => 1
)
```

### queryCount()

Toplam sorgu sayısına ulaşmak için kullanılır.

```php
echo $db->queryCount();
```

-   `1`

## Structure

Yapısal sorgular için kullanılır.

```php
$db->repair('sessions');
```

Metodlar: `truncate()`, `drop()`, `optimize()`, `analyze()`, `check()`, `checksum()`, `repair()`

## Contributors

-   [mlevent](https://github.com/mlevent) Mert Levent - creator, maintainer
