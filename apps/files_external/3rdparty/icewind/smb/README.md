SMB
===

[![Coverage Status](https://img.shields.io/coveralls/icewind1991/SMB.svg)](https://coveralls.io/r/icewind1991/SMB?branch=master)
[![Build Status](https://travis-ci.org/icewind1991/SMB.svg?branch=master)](https://travis-ci.org/icewind1991/SMB)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/icewind1991/SMB/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/icewind1991/SMB/?branch=master)

PHP wrapper for `smbclient` and [`libsmbclient-php`](https://github.com/eduardok/libsmbclient-php)

- Reuses a single `smbclient` instance for multiple requests
- Doesn't leak the password to the process list
- Simple 1-on-1 mapping of SMB commands
- A stream-based api to remove the need for temporary files
- Support for using libsmbclient directly trough [`libsmbclient-php`](https://github.com/eduardok/libsmbclient-php)

Examples
----

### Upload a file ###

```php
<?php
use Icewind\SMB\Server;

require('vendor/autoload.php');

$fileToUpload = __FILE__;

$server = new Server('localhost', 'test', 'test');
$share = $server->getShare('test');
$share->put($fileToUpload, 'example.txt');
```

### Download a file ###

```php
<?php
use Icewind\SMB\Server;

require('vendor/autoload.php');

$target = __DIR__ . '/target.txt';

$server = new Server('localhost', 'test', 'test');
$share = $server->getShare('test');
$share->get('example.txt', $target);
```

### List shares on the remote server ###

```php
<?php
use Icewind\SMB\Server;

require('vendor/autoload.php');

$server = new Server('localhost', 'test', 'test');
$shares = $server->listShares();

foreach ($shares as $share) {
	echo $share->getName() . "\n";
}
```

### List the content of a folder ###

```php
<?php
use Icewind\SMB\Server;

require('vendor/autoload.php');

$server = new Server('localhost', 'test', 'test');
$share = $server->getShare('test');
$content = $share->dir('test');

foreach ($content as $info) {
	echo $info->getName() . "\n";
	echo "\tsize :" . $info->getSize() . "\n";
}
```

### Using read streams

```php
<?php
use Icewind\SMB\Server;

require('vendor/autoload.php');

$server = new Server('localhost', 'test', 'test');
$share = $server->getShare('test');

$fh = $share->read('test.txt');
echo fread($fh, 4086);
fclose($fh);
```

### Using write streams

```php
<?php
use Icewind\SMB\Server;

require('vendor/autoload.php');

$server = new Server('localhost', 'test', 'test');
$share = $server->getShare('test');

$fh = $share->write('test.txt');
fwrite($fh, 'bar');
fclose($fh);
```

### Using libsmbclient-php ###

Install [libsmbclient-php](https://github.com/eduardok/libsmbclient-php)

```php
<?php
use Icewind\SMB\Server;
use Icewind\SMB\NativeServer;

require('vendor/autoload.php');

$fileToUpload = __FILE__;

if (Server::NativeAvailable()) {
    $server = new NativeServer('localhost', 'test', 'test');
} else {
    echo 'libsmbclient-php not available, falling back to wrapping smbclient';
    $server = new Server('localhost', 'test', 'test');
}
$share = $server->getShare('test');
$share->put($fileToUpload, 'example.txt');
```
