# PDB

PHP için PDO sınıfı

[![Total Downloads](https://poser.pugx.org/mlevent/pdb/d/total.svg)](https://packagist.org/packages/mlevent/pdb)
[![Latest Stable Version](https://poser.pugx.org/mlevent/pdb/v/stable.svg)](https://packagist.org/packages/mlevent/pdb)
[![Latest Unstable Version](https://poser.pugx.org/mlevent/pdb/v/unstable.svg)](https://packagist.org/packages/mlevent/pdb)
[![License](https://poser.pugx.org/mlevent/pdb/license.svg)](https://packagist.org/packages/mlevent/pdb)

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

```php
$results = $db->select('id, name, code, slug, price, stock, active, created')
              ->table('products')
              ->where(['categoryId = ?', 'price > ?'], [1237, 50])
              ->orderBy('id')
              ->get();

var_dump($results);
```

## Fetch Types

-   ->get();
-   ->getObj();
-   ->getRow();
-   ->getRowObj();
-   ->getCol();
-   ->getCols();

## Raw Fetch

```php
$db->raw('SELECT id FROM products WHERE active = ? AND MONTH(created) = MONTH(NOW())', 1)
   ->getCols()
```

## Raw Exec

```php
$db->raw('UPDATE payments SET active = !active WHERE status = ? AND id > ?', ['paid', 1])
   ->exec()
```

## Insert [Single or Batch]

```php
$db->table('products')->insert([
    'name'  => 'Apple Iphone X 128 Gb',
    'code'  => 'APPLEX128',
    'price' => '999.9'
]);

$batchData = [
    ['name' => 'Apple Iphone X 128 Gb', 'code' => 'APPLEX128', 'price' => '999.9'],
    ['name' => 'Apple Iphone X 256 Gb', 'code' => 'APPLEX256', 'price' => '1149.9'],
    ['name' => 'Apple Iphone X 512 Gb', 'code' => 'APPLEX512', 'price' => '1349.9'],
];

$db->insert($batchData, 'products');
```

## On Duplicate [Single or Batch]

```php
$db->table('products')->onDuplicate([
    ['name' => 'Apple Iphone X 128 Gb', 'code' => 'APPLEX128', 'price' => '999.9'],
    ['name' => 'Apple Iphone X 256 Gb', 'code' => 'APPLEX256', 'price' => '1149.9'],
    ['name' => 'Apple Iphone X 512 Gb', 'code' => 'APPLEX512', 'price' => '1349.9'],
]);
```

## Replace Into [Single or Batch]

```php
$db->table('products')->replaceInto([
    ['name' => 'Apple Iphone X 128 Gb', 'code' => 'APPLEX128', 'price' => '999.9'],
    ['name' => 'Apple Iphone X 256 Gb', 'code' => 'APPLEX256', 'price' => '1149.9'],
    ['name' => 'Apple Iphone X 512 Gb', 'code' => 'APPLEX512', 'price' => '1349.9'],
]);
```

## Insert Ignore [Single or Batch]

```php
$db->table('products')->insertIgnore([
    ['name' => 'Apple Iphone X 128 Gb', 'code' => 'APPLEX128', 'price' => '999.9'],
    ['name' => 'Apple Iphone X 256 Gb', 'code' => 'APPLEX256', 'price' => '1149.9'],
    ['name' => 'Apple Iphone X 512 Gb', 'code' => 'APPLEX512', 'price' => '1349.9'],
]);
```

## Update

```php
$db->isNull('slug')->update(['slug' => rand(), 'update' => now()]);
```

## Delete

```php
$db->where('active', 0)->delete('products');
```

## Filter

### Insert / On Duplicate/ Replace Into / Update

| Primary | Not Null |     Not Null | Null |
| ------- | :------: | -----------: | ---- |
| id      |   name   |        email | age  |
| 1       | John Doe | john@doe.com | 32   |
| 2       | Jane Doe | jane@doe.com | 19   |

```php
$db->filter()->table('users')->insert([
    'name'  => 'Walter Bishop',
    'email' => 'walter@bishop.com',
    'age'   => 39,
    'price' => 3994
]);
```

-   New record added

```php
$db->filter(true)->table('users')->insert([
    'name' => 'Walter Bishop',
    'age'  => 'walter@bishop.com'
]);
```

-   Column 'email' cannot be null

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

## Disk Cache

```php
$db->table('products')->cache(30)->get();
var_dump($db->fromDisk());
# bool(true)
```

## Redis Cache

```php
$db->table('products')->redis(30)->get();
var_dump($db->fromRedis());
# bool(true)
```

## Select

```php
$db->select('id, name, price, tax')
# OR #
$db->select(['id', 'name', 'price', 'tax'])
```

## Table

```php
$db->table('products')
# OR #
$db->table(['products as p', 'variants as v'])
```

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
