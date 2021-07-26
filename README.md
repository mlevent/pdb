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
require '/vendor/autoload.php';

$db = new \Mlevent\Pdb([
    'database' => 'ecommerce',
    'username' => 'root'
    'password' => 'test',
    'charset'  => 'utf8',
    'useRedis' => true,
    ...
]);
```

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

Son kaydedilen satırın id'sine ulaşmak için `lastInsertId()` metodunu, toplam etkilenen satır sayısı için `rowCount()` metodunu kullanabilirsiniz.

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

-  `$_POST` ile gelen veriyi temizler ve hatasız bir şekilde kayıt oluşturulur.

```php
$db->table('users')->filter(true)->insert([
    'name' => 'Walter Bishop',
    'age'  => 'walter@bishop.com'
]);
```

-  `email` sütunu `notnull` olarak tanımlandığı için kayıt eklenmez.

## Select

```php
$db->select('id, name, code, price')
```
-  `select('id, name')`, `select(['id', 'name', ...])`

> Metod kullanılmazsa varsayılan olarak `*` ile tüm sütunlar seçilir.

## Table

```php
$db->table('products')
```
-  `table('products')`, `table(['products as p', 'images as i'])`

## Join

```php
$db->leftJoin('images AS i', 'p.id', 'i.pid')
# OR #
$db->leftJoin('images AS i', 'p.id = i.pid')
# OR #
$db->leftJoin('images AS i ON p.id = i.pid')
```

-   $db->leftJoin(...)
-   $db->leftOuterJoin(...)
-   $db->rightJoin(...)
-   $db->rightOuterJoin(...)
-   $db->innerJoin(...)
-   $db->fullOuterJoin(...)

## Where

```php
$db->where('id', 32886)
# OR #
$db->where('stock >= ? AND active = ?', [2, 1])
# OR #
$db->where(['stock > ?', 'active > ?'], [2, 1])
# OR #
$db->where(['stock' => 2, 'active' => 1])
# OR #
$db->where('stock >= 2 AND active = 1 AND MONTH(updated) = MONTH(NOW())')
```

-   $db->where(...)
-   $db->orWhere(...)
-   $db->notWhere(...)
-   $db->orNotWhere(...)

## Between

```php
$db->between('price', 50, 250)
```

-   $db->between(...)
-   $db->orBetween(...)
-   $db->notBetween(...)
-   $db->orNotBetween(...)

## Is Null / Not Null

```php
$db->isNull('code')
# OR #
$db->isNull(['code', 'price'])
# OR #
$db->isNull(['code', 'price'], _OR)
```

-   $db->isNull(...)
-   $db->orIsNull(...)
-   $db->notNull(...)
-   $db->orNotNull(...)

## In / Not In

```php
$db->in('id', [33922, 31221, 45344, 35444])
```

-   $db->in(...)
-   $db->orIn(...)
-   $db->notIn(...)
-   $db->orNotIn(...)

## Find_In_Set

```php
$db->findInSet('categoryId', 139)
```

-   $db->findInSet(...)
-   $db->orFindInSet(...)

## Like

```php
$db->like('name', '%Apple%')
# OR #
$db->like(['name', 'code'], '%Apple%', _OR)
```

-   $db->like(...)
-   $db->orLike(...)
-   $db->notLike(...)
-   $db->orNotlike(...)

## Order

```php
$db->order('id')
# OR #
$db->order('id desc, name asc')
# OR #
$db->order('id', 'desc')
```

## Group

```php
$db->group('id')
# OR #
$db->group('id, name')
# OR #
$db->group(['id', 'name'])
```

## Having

```php
$db->having('stock', 5)
# OR #
$db->having('stock > 5')
# OR #
$db->having('stock > ?', 5)
```

## Limit / Offset / Pager

```php
$db->limit(100)
# OR #
$db->limit(100, 10)
# OR #
$db->limit(100)->offset(0)
# OR #
$db->pager(100, 2)
```

## History

```php
var_dump($db->queryHistory());
/* OUTPUT
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
*/

echo $db->lastQuery();
/* OUTPUT
SELECT id, name FROM products WHERE code = ? AND active = ? ORDER BY id desc
*/

echo $db->queryCount();
/*OUTPUT 1*/
```

## Structure

```php
$db->truncate('table');
$db->drop('table');
$db->optimize('table');
$db->analyze('table');
$db->check('table');
$db->checksum('table');
$db->repair('table');
```

## Contributors

-   [mlevent](https://github.com/mlevent) Mert Levent - creator, maintainer
