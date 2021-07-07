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

$results = $db->select('id, name, code, slug, price, stock, active, created')
              ->table('products')
              ->where(['categoryId = ?', 'price > ?'], [1237, 50])
              ->orderBy('id')
              ->get();

var_dump($results);
```

## Insert

```php
$insert = $db->table('products')->insert([
    'name' => 'Apple Iphone X 128 Gb',
    'code' => 'APPLEX128',
    'price' => '999.9'
]);

var_dump($insert)

$batchData = [
    ['name' => 'Apple Iphone X 128 Gb', 'code' => 'APPLEX128', 'price' => '999.9'],
    ['name' => 'Apple Iphone X 256 Gb', 'code' => 'APPLEX256', 'price' => '1149.9'],
    ['name' => 'Apple Iphone X 512 Gb', 'code' => 'APPLEX512', 'price' => '1349.9'],
];

$batchInsert = $db->filter(true)->insert($batchData, 'products');

var_dump($batchInsert)
```

## ON DUPLICATE

```php
$data = [
    ['name' => 'Apple Iphone X 128 Gb', 'code' => 'APPLEX128', 'price' => '999.9'],
    ['name' => 'Apple Iphone X 256 Gb', 'code' => 'APPLEX256', 'price' => '1149.9'],
    ['name' => 'Apple Iphone X 512 Gb', 'code' => 'APPLEX512', 'price' => '1349.9'],
];

$onDuplicate = $db->onDuplicate($data, 'products');

var_dump($onDuplicate)
```

## RAW

```php
$results = $db->raw('SELECT id, name FROM products WHERE active = ? AND MONTH(updated) = MONTH(NOW())', [1])
              ->getObj()

var_dump($results);
```

## Contributors

-   [mlevent](https://github.com/mlevent) Mert Levent - creator, maintainer
