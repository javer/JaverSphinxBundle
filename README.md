JaverSphinxBundle
=================

This bundle provides integration of [Sphinx](http://sphinxsearch.com) search engine with Symfony.

Features:
- SphinxQL Query Builder
- Integration with [doctrine/orm](https://packagist.org/packages/doctrine/orm) 
- Integration with [knplabs/knp-paginator-bundle](https://packagist.org/packages/knplabs/knp-paginator-bundle)
- Symfony Profiler toolbar section with number of executed queries and profiler page with detailed information about executed queries 

Requirements
------------

- PHP 7.0+
- pdo_mysql php extension

Installation
------------

Install the bundle using composer:
```sh
composer require javer/sphinx-bundle
```

Configuration
-------------

Add to your ```app/config/config.yml``` the following options:
```yml
javer_sphinx:
    host: 127.0.0.1
    port: 9306
```

Usage
-----

Synthetic example of SELECT query which returns an array:
```php
$results = $this->container->get('sphinx')
    ->select('id', 'column1', 'column2', 'WEIGHT() as weight')
    ->from('index1', 'index2')
    ->where('column3', 'value1')
    ->where('column4', '>', 4)
    ->where('column5', [5, '6'])
    ->where('column6', 'NOT IN', [7, '8'])
    ->where('column7', 'BETWEEN', [9, 10])
    ->match('column8', 'value2')
    ->match(['column9', 'column10'], 'value3')
    ->groupBy('column11')
    ->groupBy('column12')
    ->withinGroupOrderBy('column13', 'desc')
    ->withinGroupOrderBy('column14')
    ->having('weight', '>', 2)
    ->orderBy('column15', 'desc')
    ->orderBy('column16')
    ->offset(5)
    ->limit(10)
    ->option('agent_query_timeout', 10000)
    ->option('max_matches', 1000)
    ->option('field_weights', '(column9=10, column10=3)')
    ->getResults();
```

Paginate a list of entities fetched from the database using Doctrine ORM QueryBuilder by searching phrase in them using Sphinx:
```php
$queryBuilder = $this->container->get('doctrine.orm.default_entity_manager')
    ->createQueryBuilder()
    ->select('p', 'i')
    ->from('AppBundle:Product', 'p')
    ->join('AppBundle:Image', 'i')
    ->where('p.owner = :owner')
    ->setParameter('owner', $this->getUser());

$query = $this->container->get('sphinx')
    ->createQuery()
    ->select('*')
    ->from('product')
    ->match(['name', 'description'], $searchQuery)
    ->where('owner_id', $this->getUser()->getId())
    ->orderBy('created_timestamp', 'desc')
    ->useQueryBuilder($queryBuilder, 'p');

$paginator = $this->container->get('knp_paginator')
    ->paginate($query, $request->query->get('page', 1), 20);
```

Sample shpinx.conf for the given example above:
```
source product
{
        type                    = mysql
        sql_host                = localhost
        sql_user                = user
        sql_pass                = password
        sql_db                  = database_name
        sql_query               = \
                SELECT p.id, p.owner_id, p.name, p.description, UNIX_TIMESTAMP(p.created) as created_timestamp, \
                FROM product p \
                WHERE p.deletedAt IS NULL
        sql_attr_uint           = owner_id
        sql_attr_timestamp      = created_timestamp
}
                
index product
{
        source                  = product
        path                    = /usr/local/var/data/product
        morphology              = stem_en
        min_stemming_len        = 3
}
```
