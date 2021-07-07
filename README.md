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

```php
$data = [
    ['name' => 'Apple Iphone X 128 Gb', 'code' => 'APPLEX128', price => '999.9'],
    ['name' => 'Apple Iphone X 256 Gb', 'code' => 'APPLEX256', price => '1149.9'],
    ['name' => 'Apple Iphone X 512 Gb', 'code' => 'APPLEX512', price => '1349.9'],
];

$batchInsert = $db->insert($data, 'products');

var_dump($batchInsert)
```

## Contributors

-   [mlevent](https://github.com/mlevent) Mert Levent - creator, maintainer
