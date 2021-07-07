# PDB

PHP için PDO sınıfı

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
    'charset'  => 'utf8'
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

```php
->get();
->getObj();
->getRow();
->getRowObj();
->getCol();
->getCols();
```

## Raw

```php
$results = $db->raw('SELECT id FROM products WHERE active = ? AND MONTH(created) = MONTH(NOW())', 1)
              ->getCols()
```

## Insert

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

$db->filter(true)->insert($batchData, 'products');
```

## On Duplicate

```php
$data = [
    ['name' => 'Apple Iphone X 128 Gb', 'code' => 'APPLEX128', 'price' => '999.9'],
    ['name' => 'Apple Iphone X 256 Gb', 'code' => 'APPLEX256', 'price' => '1149.9'],
    ['name' => 'Apple Iphone X 512 Gb', 'code' => 'APPLEX512', 'price' => '1349.9'],
];

$db->onDuplicate($data, 'products');
```

# Update

```php
$db->isNull('slug')->update(['slug' => rand(), 'update' => now()]);
```

# Delete

```php
$db->where('active', 0)->delete('products');
```

# Join

```php
$db->leftJoin('images AS i', 'p.id', 'i.pid')
$db->leftOuterJoin(...)
$db->rightJoin(...)
$db->rightOuterJoin(...)
$db->innerJoin(...)
$db->fullOuterJoin(...)
```

# Cache

```php
$db->table('products')->cache(30)->
```

## Contributors

-   [mlevent](https://github.com/mlevent) Mert Levent - creator, maintainer
