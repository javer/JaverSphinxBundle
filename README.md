JaverSphinxBundle
=================

This bundle provides integration of [Sphinx](http://sphinxsearch.com) search engine with Symfony.

Features:
- SphinxQL Query Builder
- Integration with [doctrine/orm](https://packagist.org/packages/doctrine/orm) 
- Integration with [knplabs/knp-paginator-bundle](https://packagist.org/packages/knplabs/knp-paginator-bundle)
- Symfony Profiler toolbar section with number of executed queries and profiler page with detailed information about executed queries
- Ability to test search using [Behat](https://packagist.org/packages/behat/behat) scenarios

Requirements
------------

- PHP 7.1+
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

Full configuration with default values:
```yml
javer_sphinx:
    host: 127.0.0.1
    port: 9306
    config_path: "%kernel.root_dir%/config/sphinx.conf"
    data_dir: "%kernel.cache_dir%/sphinx"
    searchd_path: searchd
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
    ->orderBy('created', 'desc')
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
                SELECT p.id, p.owner_id, p.name, p.description, UNIX_TIMESTAMP(p.created) as created, \
                FROM product p \
                WHERE p.deletedAt IS NULL
        sql_attr_uint           = owner_id
        sql_attr_timestamp      = created
}
                
index product
{
        source                  = product
        path                    = /usr/local/var/data/product
        morphology              = stem_en
        min_stemming_len        = 3
}
```

Behat tests
===========

To be able to test search in Behat scenarios there is built-in behat context SphinxContext.
 
To use it you should add this context in your behat.yml, for example:
```yml
selenium:
    extensions:
        Behat\Symfony2Extension: ~
    suites:
        frontend:
            contexts:
                - Javer\SphinxBundle\Behat\Context\SphinxContext
```

Please note that Symfony2Extension Behat extension is required to be able to use this feature.

Then you should add a new step to your scenario:
```
Given I create search index and run sphinx
```

This step:
* creates a new configuration for sphinx based on your configuration
* converts all MySQL indexes to real-time indexes
* starts daemon
* loads data from the database to indexes for converted MySQL -> real-time indexes
* stops daemon at the end of the scenario

Please note that you should explicitly declare all text fields in your indexes in the following form:
```
source product
{
    #!sql_field_string = name
}
```
It is not necessary when you declare fields for MySQL index in sphinx.conf, but it is needed to be able to convert indexes to real-time.

If you use sqlite as the database engine for running tests you should take into account that not all functions of the MySQL are presented in sqlite, so you should use portable analogs for these functions:
* `IF(condition, true, false)` -> `CASE WHEN condition THEN true ELSE false END`
* and so on
