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
```

## Contributors

-   [mlevent](https://github.com/mlevent) Mert Levent - creator, maintainer
