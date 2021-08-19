# SearchDAV

[![Build Status](https://travis-ci.org/icewind1991/SearchDAV.svg?branch=master)](https://travis-ci.org/icewind1991/SearchDAV)
[![codecov](https://codecov.io/gh/icewind1991/SearchDAV/branch/master/graph/badge.svg)](https://codecov.io/gh/icewind1991/SearchDAV)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/icewind1991/SearchDAV/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/icewind1991/SearchDAV/?branch=master)

A sabre/dav plugin to implement [rfc5323](https://tools.ietf.org/search/rfc5323) SEARCH

## Usage

The plugin implements the DAV specific parts of the rfc but leaves the actual search
implementation to the user of the plugin.

This is done by implementing the `\SearchDAV\Backend\ISearchBackend` interface and passing
it to the plugin during construction.

### Basic usage

```php
$server = new \Sabre\DAV\Server();
$server->addPlugin(new \SearchDAV\DAV\SearchPlugin(new MySearchBackend()));

$server->exec();
```

### Terms

The rfc uses the following terms to describe various part of search handling

- Search scope: the DAV resource that is being queries.
- Search arbiter: the end point to which the SEARCH request can be made.
 
  Note that a single search arbiter can support searching in multiple scopes
  
- Search grammar: The type of search query that is supported by a scope
 
  rfc5323 requires any implementation to at least implement "basicsearch" which is 
  also currently the only supported grammar in this plugin
  
- Search schema: Details on how to use a search grammar,
such as the supported properties that can be searched for


### ISearchBackend

The `ISearchBackend` defines the arbiter end point, which scopes are valid to query,
the search schema that is supported and implements the actual search.

For a full list of methods required and their description see [`ISearchBackend.php`](src/Backend/ISearchBackend.php)

### Query

The `Query` class defines the query that was made by the client and consists of four parts:

- select: the properties are requested.
- from: the scope(s) in which the search should be made.
- where: the filter parameters for the search.
- orderBy: how the search results should be ordered.

For further information about these elements see
 [`Query.php`](src/Query/Query.php), [`Scope.php`](src/Query/Scope.php),
  [`Operator.php`](src/Query/Operator.php) and [`Order.php`](src/Query/Order.php)
