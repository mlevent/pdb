# PDB

PHP için PDO sınıfı

[![Latest Stable Version](https://poser.pugx.org/mlevent/pdb/v/stable.svg)](https://packagist.org/packages/mlevent/pdb)
[![Latest Unstable Version](https://poser.pugx.org/mlevent/pdb/v/unstable.svg)](https://packagist.org/packages/mlevent/pdb)
[![License](https://poser.pugx.org/mlevent/pdb/license.svg)](https://packagist.org/packages/mlevent/pdb)

## Kurulum ve Kullanım

```
$ composer require mlevent/pdb
```

### Örnek Kullanım

```php
$db = new \Mlevent\Pdb([
    'database' => 'ecommerce',
    'username' => 'root'
    'password' => 'test',
    'charset'  => 'utf8'
]);
```

Sqlite kullanımı için:

```php
$db = new \Mlevent\Pdb([
    'driver'   => 'sqlite',
    'database' => 'ecommerce.sqlite'
]);
```

### Composer Kullanmadan

Yeni bir dizin oluşturarak `src` klasörü altındaki tüm dosyaları içine kopyalayın ve `autoload.php` dosyasını require ile sayfaya dahil ederek sınıfı başlatın.

```php
require '{pdb_dosyalarinin_bulundugu_dizin}/autoload.php';

$db = new \Mlevent\Pdb([
    'database' => 'ecommerce',
    'username' => 'root',
    'password' => 'test',
    'charset'  => 'utf8'
]);
```

### Yapılandırma

Varsayılan yapılandırma ayarları:

```php
[
    'host'      => 'localhost',
    'driver'    => 'mysql',
    'database'  => '',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'debug'     => false,
    'cacheTime' => 60,
    'cachePath' => __DIR__ . '/Cache'
]
```

Şu anda kullanılabilir durumda olan driver listesi:

-   Mysql
-   Sqlite (test aşamasında)

---

## Fetch

### Get

Sonuç varsayılan olarak `Object` formatında döner. `Array` olarak ulaşmak isterseniz `toArray()` metoduna göz atın.

```php
$products = $db->get('products');

foreach ($products as $product) {
    echo $product->name;
}
```

-   `get()`
-   `get('products')`

---

Kullanılabilecek metodlar: `get()`, `first()`, `value()`, `pluck()`, `find()`

```php
$products = $db->select('id, name, code, slug, price, stock')
               ->table('products')
               ->where('stock > ?', 5)
               ->where('MONTH(created) = MONTH(NOW())')
               ->order('id')
               ->get();
```

Yukarıdaki zincirin sorgu çıktısı şu şekilde olacaktır:

```sql
SELECT
  id, name, code, slug, price, stock
FROM
  products
WHERE
  stock > ?
  AND MONTH(created) = MONTH(NOW())
ORDER BY
  id DESC
```

### toArray()

Sonuçlara `Array` formatında ulaşmak için kullanılır.

```php
$products = $db->table('products')
               ->toArray()
               ->get();

foreach ($products as $product) {
    echo $product['name'];
}
```

### toJson()

Sonuçlara `Json` formatında ulaşmak için kullanılır.

```php
$products = $db->table('products')
               ->toJson()
               ->get();
```

### First

Bir tablodan sadece tek bir satır almanız gerekiyorsa, `first()` yöntemini kullanabilirsiniz. Bu yöntem, varsayılan olarak tek bir stdClass nesnesi döndürür.

```php
$user = $db->table('users')
           ->first();

echo $user->email;
```

### Value

Bir satırın tamamına ihtiyacınız yoksa, value yöntemini kullanarak bir kayıttan tek bir değer çıkarabilirsiniz.

```php
$email = $db->table('users')
            ->where('name', 'Walter')
            ->value('email');

echo $email;
```

### Pluck

Tek bir sütunun değerlerini içeren bir dizi istiyorsanız `pluck()` yöntemini kullanabilirsiniz.

```php
$pluck = $db->table('products')
            ->pluck('name');
```

```
Array
(
    [0] => Apple Iphone X 128 GB
    [1] => Apple Iphone X 256 GB
    [2] => Apple Iphone X 512 GB
)
```

`pluck()` metoduna ikinci bir parametre göndererek, elde edilen dizinin anahtarları olarak kullanılmasını istediğiniz sütunu belirtebilirsiniz:

```php
$pluck = $db->table('products')
            ->pluck('name', 'code');
```

```
Array
(
    [APPLEX128] => Apple Iphone X 128 GB
    [APPLEX256] => Apple Iphone X 256 GB
    [APPLEX512] => Apple Iphone X 512 GB
)
```

### Find

Birincil anahtarla eşleşen kaydı döndürür.

```php
$user = $db->table('users')
           ->find(15);
```

```sql
SELECT * FROM users WHERE id=?
```

-   `find(15)`
-   `find(15, 'products')`

### Total

Toplam satır sayısına ulaşmak için kullanılır.

```php
$total = $db->table('users')
            ->where('userGroup', 'Admin')
            ->total();
```

-   `total()`
-   `total('users')`

### rowCount()

Etkilenen satır sayısı veya okunan satır sayısına ulaşmak için kullanılır.

```php
echo $db->rowCount();
```

### lastInsertId()

Insert işlemlerinde kaydedilen son satırın birincil anahtarını döndürür.

```php
echo $db->lastInsertId();
```

---

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

---

## Pager

Parametre olarak sayfa başına listelenecek kayıt sayısı gönderilmelidir. `pager()` metodu salt sorgularda çalışmaz.

```php
$posts = $db->table('posts')
            ->pager(25)
            ->get();
```

`pager()` fonksiyonu 2 parametre alır. İlk parametre sayfa başına listelenecek kayıt sayısı, İkinci parametre sayfa bilgisinin aktarılacağı `$_GET` parametresidir. Örneğin link yapısı `?sayfa=3` şeklinde kurgulanacaksa, örnek kullanım şu şekilde olmalıdır;

```php
$db->pager(25, 'sayfa');
```

### pagerLinks()

Linklerin çıktısını almak için kullanılır.

```php
echo $db->pagerLinks();
```

-   `«` `‹` `1` `2` `3` `4` `5` `6` `...` `›` `»`

### pagerData()

Toplam sonuç, sayfa sayısı, limit, ofset ve aktif sayfa gibi bilgilere ulaşmak için kullanılır.

```php
var_dump($db->pagerData());
```

```
Array
(
    [count] => 255
    [limit] => 10
    [offset] => 0
    [total] => 26
    [current] => 1
)
```

### setPagerTemplate()

Link çıktısına ait HTML şablonu düzenlemek için kullanılır.

```php
$db->setPagerTemplate('<li>
        <a class="{active}" href="{url}">
            {text}
        </a>
    </li>');
```

---

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

```php
$redisConnect = (function(){
    $redis = new \Redis();
    $redis->connect('127.0.0.1', 6379, 1, NULL, 0, 0, ['auth' => ['default', '']]);
    return $redis;
});

$db->setRedis($redisConnect());
```

`setRedis()` metodu ile Redis sınıfı dışarıdan dahil edilebilir.

> Not: Redis ile önbellekleme işlemi yapabilmek için sunucunuzda Redis yüklü olması gerekir.

---

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

### Upsert

```php
$db->table('products')->upsert([
    'name'  => 'Apple Iphone X 128 Gb',
    'code'  => 'APPLEX128',
    'price' => '999.9'
]);
```

Benzersiz anahtarlara eşleşen veri bulunursa var olan kayıt güncellenir, yoksa yeni kayıt eklenir.

-   Henüz Sqlite desteği yok.

### Insert Ignore

```php
$db->table('products')->insertIgnore([
    'name'  => 'Apple Iphone X 128 Gb',
    'code'  => 'APPLEX128',
    'price' => '999.9'
]);
```

Benzersiz anahtarlara eşleşen veri bulunursa kayıt eklenmez, yoksa yeni kayıt eklenir.

### Insert Replace

```php
$db->table('products')->insertReplace([
    'name'  => 'Apple Iphone X 128 Gb',
    'code'  => 'APPLEX128',
    'price' => '999.9'
]);
```

Benzersiz anahtarlara eşleşen veri bulunursa var olan kayıt silinir ve yeni kayıt eklenir, yoksa yeni kayıt eklenir. Her replace işleminde `auto_increment` olarak tanımlanan birincil anahtara (Genellikle ID) ait değer değişir. Değerin korunmasını istiyorsanız `upsert()` metodunu kullanmanız önerilir.

---

## Update

Bir veya birden fazla kaydı güncellemek için kullanılır.

```php
$update = $db->table('products')
             ->where('id', 11255)
             ->update(['active' => 1]);
```

-   Etkilenen satır sayısı döner.

### Touch

`active` sütunu `1` ise `0`, `0` ise `1` değerini alır.

```php
$touch = $db->table('products')
            ->touch('active');
```

-   `touch('active', 'products')`

### Increment

`hit` sütunu `1` veya gönderilen değer kadar artar.

```php
$increment = $db->table('posts')
                ->where('slug', 'whats-new-in-laravel-8')
                ->increment('hit');
```

-   `increment('hit')`
-   `increment('hit', 5)`

### Decrement

`hit` sütunu `1` veya gönderilen değer kadar azalır.

```php
$increment = $db->table('posts')
                ->where('slug', 'whats-new-in-laravel-8')
                ->decrement('hit');
```

-   `decrement('hit')`
-   `decrement('hit', 5)`

---

## Delete

Bir veya birden fazla kaydı silmek için kullanılır.

```php
$delete = $db->in('id', [321, 412, 324, 142])
             ->delete('products');
```

-   Etkilenen satır sayısı döner.

---

## Filter

Gönderilen veriyi tablodaki sütunlarla karşılaştırır ve yanlış/fazla veriyi otomatik olarak temizler. `insert()`, `insertIgnore()`, `insertReplace()`, `upsert()`, `update()` metodlarıyla birlikte kullanılabilir.

| Primary | Not Null | Not Null | Not Null | enum('Male', 'Female') |
| ------- | :------: | -------: | -------- | ---------------------- |
| id      |   name   |    email | password | gender                 |

`users` adında bir tablomuz olduğunu ve yukarıdaki sütunlardan oluştuğunu varsayalım.

```php
$db->table('users')->filter()->insert([
    'username' => 'walterbishop',
    'email'    => 'walter@bishop.com',
    'password' => 'U7!hsjlIus',
    'gender'   => 'Male',
    'fullname' => 'Walter Bishop'
]);
```

-   `filter()` metodu users tablosunda `fullname` sütununu bulamadığı için bu veriyi otomatik temizleyip hatasız bir şekilde kayıt oluşturulmasını sağlar.

```php
$db->table('users')->filter()->insert($_POST);
```

-   `$_POST` ile gönderilen formlar için örnek bir kullanım şekli.

## Validate

Bu metot şu an için yalnızca; gönderilen veriyi filtreler, boş gönderilen alanları varsayılan değerleriyle doldurur, not null ve enum kontrolleri yapar.

```php
try{
    $db->table('users')->validate()->insert([
        'username' => 'walterbishop',
        'email'    => 'walter@bishop.com',
        'password' => 'U7!hsjlIus',
        'gender'   => 'Elephant'
    ]);
} catch(Exception $e){
    echo $e->getMessage();
}
```

-   `gender` sütununda tanımlı enum değerleri arasında `Elephant` olmadığı için hata döner ve kayıt eklenmez.

---

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

---

## Select

```php
$db->select('id, name, code, price')...
```

-   `select('id, name')`
-   `select(['id', 'name', ...])`

> Metod kullanılmazsa varsayılan olarak `*` ile tüm sütunlar seçilir.

### Select Functions

Metodlar: `count()`, `sum()`, `avg()`, `min()`, `max()`

```php
$db->sum('amount')...
```

-   `sum('amount')`
-   `sum('amount', 'totalAmount')`

## Table

`table()` ve `from()` metodu aynı işlevi görür.

```php
$db->table('products')...
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
$db->where('id', 32886)...
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
$db->between('price', 50, 250)...
```

## Is Null - Not Null

Metodlar: `isNull()`, `orIsNull()`, `notNull()`, `orNotNull()`

```php
$db->isNull('code')...
```

-   `isNull('slug')`
-   `isNull(['slug', ...])`

## In - Not In

Metodlar: `in()`, `orIn()`, `notIn()`, `orNotIn()`

```php
$db->in('id', [33922, 31221, 45344, 35444])...
```

## Find In Set

Metodlar: `findInSet()`, `orFindInSet()`, `notFindInSet()`, `orNotFindInSet()`

```php
$db->findInSet('categoryId', 139)...
```

## Like - Not Like

Metodlar: `like()`, `orLike()`, `notLike()`, `orNotlike()`

```php
$db->like('name', '%Apple%')...
```

## Order

Varsayılan olarak `desc` seçilir.

```php
$db->order('id')...
```

-   `order('id')`
-   `order('id', 'asc')`
-   `order('id desc, name asc')`
-   `order('rand()')`

## Group

```php
$db->group('id')...
```

-   `group('id')`
-   `group(['id', 'name'])`

## Having

```php
$db->having('stock', 5)...
```

-   `having('stock', 5)`
-   `having('stock > 5')`
-   `having('stock > ?', 5)`

## Limit - Offset

Limit, Offset ve Sayfalama işlemleri için kullanılır.

```php
$db->limit(100)...
$db->limit(100, 0)...
$db->limit(100)->offset(0)...
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
    [0] => 34066,
    [1] => 1
)
```

### queryCount()

Toplam sorgu sayısına ulaşmak için kullanılır.

```php
echo $db->queryCount();
```

-   `1`

---

## Structure

Yapısal sorgular için kullanılır.

```php
$db->repair('sessions');
```

Metodlar: `truncate()`, `drop()`, `optimize()`, `analyze()`, `check()`, `checksum()`, `repair()`

## Contributors

-   [mlevent](https://github.com/mlevent) Mert Levent
