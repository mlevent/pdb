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
$db->table('products')->insert([
    'name' => 'Apple Iphone X 128 Gb',
    'code' => 'APPLEX128',
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
$db->isNull('slug')->update(['slug' => rand()]);
```

# Delete

```php
$db->where('active', 0)->delete('products');
```

## Raw

```php
$results = $db->raw('SELECT id, name FROM products WHERE active = ? AND MONTH(created) = MONTH(NOW())', 1)
              ->getObj()
```

## Contributors

-   [mlevent](https://github.com/mlevent) Mert Levent - creator, maintainer
