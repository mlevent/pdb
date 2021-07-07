# PDB

PHP için basit URL oluşturma ve manipülasyon aracı

## Kurulum

```
$ composer require mlevent/pdob
```

## URL Oluşturma

```php
echo $url->path('news')
         ->params(['q' => 'latest', 'tags' => ['sport', 'health'], 'sort' => 'desc'])
         ->build();
```

```
http(s)://site.com/news?q=latest&tags=sport,health&sort=desc
```

```php
echo $url->base('https://www.google.com')
         ->path('search')
         ->params(['q' => 'php'])
         ->build();
```

```
https://www.google.com/search?q=php
```

```php
echo $url->base(false)
         ->path('search')
         ->params(['q' => 'php'])
         ->build();
```

```
/search?q=php
```

## Manipülasyon

Example URL = https://site.com/products/?colors=blue&sort=price&page=2

```php
echo $url->params(['colors' => ['red', 'black'], 'page' => 1])
         ->deny('sort', 'page')
         ->push();
```

```
https://site.com/products/?colors=blue,red,black&page=1
```

Example URL = http://site.com/products/?gender=male&color=blue,red,black&page=1

```php
echo $url->allow('gender', 'page')->push();
```

```
http://site.com/products/?gender=male&page=1
```

```php
echo $url->getParams('sort'); // string
echo $url->getParams('sort', true); // array
echo $url->getPath(); // ex./category/electronics/telephone
echo $url->getPath(0); // category
echo $url->searchValue('blue'); // boolean
echo $url->isParam('color'); // booelan
echo $url->getCurrent(); // current url
```

## Contributors

-   [mlevent](https://github.com/mlevent) Mert Levent - creator, maintainer
