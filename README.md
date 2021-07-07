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

$db->insert($batchData, 'products');
```

## On Duplicate

```php
$db->table('products')->onDuplicate([
    ['name' => 'Apple Iphone X 128 Gb', 'code' => 'APPLEX128', 'price' => '999.9'],
    ['name' => 'Apple Iphone X 256 Gb', 'code' => 'APPLEX256', 'price' => '1149.9'],
    ['name' => 'Apple Iphone X 512 Gb', 'code' => 'APPLEX512', 'price' => '1349.9'],
]);
```

## Replace Into

```php
$db->table('products')->replaceInto([
    ['name' => 'Apple Iphone X 128 Gb', 'code' => 'APPLEX128', 'price' => '999.9'],
    ['name' => 'Apple Iphone X 256 Gb', 'code' => 'APPLEX256', 'price' => '1149.9'],
    ['name' => 'Apple Iphone X 512 Gb', 'code' => 'APPLEX512', 'price' => '1349.9'],
]);
```

# Update

```php
$db->isNull('slug')->update(['slug' => rand(), 'update' => now()]);
```

# Delete

```php
$db->where('active', 0)->delete('products');
```

# Filter

## Insert / On Duplicate/ Replace Into / Update

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
$db->table('products')->cache(30)->get();
var_dump($db->fromCache());
# bool(true)
```

## Contributors

-   [mlevent](https://github.com/mlevent) Mert Levent - creator, maintainer
