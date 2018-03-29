#Streams#

[![Build Status](https://travis-ci.org/icewind1991/Streams.svg?branch=master)](https://travis-ci.org/icewind1991/Streams)
[![Coverage Status](https://img.shields.io/coveralls/icewind1991/Streams.svg)](https://coveralls.io/r/icewind1991/Streams?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/icewind1991/Streams/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/icewind1991/Streams/?branch=master)

Generic stream wrappers for php.

##CallBackWrapper##

A `CallBackWrapper` can be used to register callbacks on read, write and closing of the stream,
it wraps an existing stream and can thus be used for any stream in php

The callbacks are passed in the stream context along with the source stream
and can be any valid [php callable](http://php.net/manual/en/language.types.callable.php)

###Example###
```php
<?php

use \Icewind\Streams\CallBackWrapper;

require('vendor/autoload.php');

// get an existing stream to wrap
$source = fopen('php://temp', 'r+');

// register the callbacks
$stream = CallbackWrapper::wrap($source,
	// read callback
	function ($count) {
		echo "read " . $count . "bytes\n";
	},
	// write callback
	function ($data) {
		echo "wrote '" . $data . "'\n";
	},
	// close callback
	function () {
		echo "stream closed\n";
	});

fwrite($stream, 'some dummy data');

rewind($stream);
fread($stream, 5);

fclose($stream);
```

Note: due to php's internal stream buffering the `$count` passed to the read callback
will be equal to php's internal buffer size (8192 on default) an not the number of bytes
requested by `fopen()`
