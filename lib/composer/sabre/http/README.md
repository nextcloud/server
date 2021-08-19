sabre/http
==========

This library provides a toolkit to make working with the [HTTP protocol](https://tools.ietf.org/html/rfc2616) easier.

Most PHP scripts run within a HTTP request but accessing information about the
HTTP request is cumbersome at least.

There's bad practices, inconsistencies and confusion. This library is
effectively a wrapper around the following PHP constructs:

For Input:

* `$_GET`,
* `$_POST`,
* `$_SERVER`,
* `php://input` or `$HTTP_RAW_POST_DATA`.

For output:

* `php://output` or `echo`,
* `header()`.

What this library provides, is a `Request` object, and a `Response` object.

The objects are extendable and easily mockable.

Build status
------------

| branch | status |
| ------ | ------ |
| master | [![Build Status](https://travis-ci.org/sabre-io/http.svg?branch=master)](https://travis-ci.org/sabre-io/http) |
| 4.2    | [![Build Status](https://travis-ci.org/sabre-io/http.svg?branch=4.2)](https://travis-ci.org/sabre-io/http) |
| 3.0    | [![Build Status](https://travis-ci.org/sabre-io/http.svg?branch=3.0)](https://travis-ci.org/sabre-io/http) |

Installation
------------

Make sure you have [composer][1] installed. In your project directory, create,
or edit a `composer.json` file, and make sure it contains something like this:

```json
{
    "require" : {
        "sabre/http" : "~5.0.0"
    }
}
```

After that, just hit `composer install` and you should be rolling.

Quick history
-------------

This library came to existence in 2009, as a part of the [`sabre/dav`][2]
project, which uses it heavily.

It got split off into a separate library to make it easier to manage
releases and hopefully giving it use outside of the scope of just `sabre/dav`.

Although completely independently developed, this library has a LOT of
overlap with [Symfony's `HttpFoundation`][3].

Said library does a lot more stuff and is significantly more popular,
so if you are looking for something to fulfill this particular requirement,
I'd recommend also considering [`HttpFoundation`][3].


Getting started
---------------

First and foremost, this library wraps the superglobals. The easiest way to
instantiate a request object is as follows:

```php
use Sabre\HTTP;

include 'vendor/autoload.php';

$request = HTTP\Sapi::getRequest();
```

This line should only happen once in your entire application. Everywhere else
you should pass this request object around using dependency injection.

You should always typehint on it's interface:

```php
function handleRequest(HTTP\RequestInterface $request) {

    // Do something with this request :)

}
```

A response object you can just create as such:

```php
use Sabre\HTTP;

include 'vendor/autoload.php';

$response = new HTTP\Response();
$response->setStatus(201); // created !
$response->setHeader('X-Foo', 'bar');
$response->setBody(
    'success!'
);

```

After you fully constructed your response, you must call:

```php
HTTP\Sapi::sendResponse($response);
```

This line should generally also appear once in your application (at the very
end).

Decorators
----------

It may be useful to extend the `Request` and `Response` objects in your
application, if you for example would like them to carry a bit more
information about the current request.

For instance, you may want to add an `isLoggedIn` method to the Request
object.

Simply extending Request and Response may pose some problems:

1. You may want to extend the objects with new behaviors differently, in
   different subsystems of your application,
2. The `Sapi::getRequest` factory always returns a instance of
   `Request` so you would have to override the factory method as well,
3. By controlling the instantation and depend on specific `Request` and
   `Response` instances in your library or application, you make it harder to
   work with other applications which also use `sabre/http`.

In short: it would be bad design. Instead, it's recommended to use the
[decorator pattern][6] to add new behavior where you need it. `sabre/http`
provides helper classes to quickly do this.

Example:

```php
use Sabre\HTTP;

class MyRequest extends HTTP\RequestDecorator {

    function isLoggedIn() {

        return true;

    }

}
```

Our application assumes that the true `Request` object was instantiated
somewhere else, by some other subsystem. This could simply be a call like
`$request = Sapi::getRequest()` at the top of your application,
but could also be somewhere in a unittest.

All we know in the current subsystem, is that we received a `$request` and
that it implements `Sabre\HTTP\RequestInterface`. To decorate this object,
all we need to do is:

```php
$request = new MyRequest($request);
```

And that's it, we now have an `isLoggedIn` method, without having to mess
with the core instances.


Client
------

This package also contains a simple wrapper around [cURL][4], which will allow
you to write simple clients, using the `Request` and `Response` objects you're
already familiar with.

It's by no means a replacement for something like [Guzzle][7], but it provides
a simple and lightweight API for making the occasional API call.

### Usage

```php
use Sabre\HTTP;

$request = new HTTP\Request('GET', 'http://example.org/');
$request->setHeader('X-Foo', 'Bar');

$client = new HTTP\Client();
$response = $client->send($request);

echo $response->getBodyAsString();
```

The client emits 3 event using [`sabre/event`][5]. `beforeRequest`,
`afterRequest` and `error`.

```php
$client = new HTTP\Client();
$client->on('beforeRequest', function($request) {

    // You could use beforeRequest to for example inject a few extra headers.
    // into the Request object.

});

$client->on('afterRequest', function($request, $response) {

    // The afterRequest event could be a good time to do some logging, or
    // do some rewriting in the response.

});

$client->on('error', function($request, $response, &$retry, $retryCount) {

    // The error event is triggered for every response with a HTTP code higher
    // than 399.

});

$client->on('error:401', function($request, $response, &$retry, $retryCount) {

    // You can also listen for specific error codes. This example shows how
    // to inject HTTP authentication headers if a 401 was returned.

    if ($retryCount > 1) {
        // We're only going to retry exactly once.
    }

    $request->setHeader('Authorization', 'Basic xxxxxxxxxx');
    $retry = true;

});
```

### Asynchronous requests

The `Client` also supports doing asynchronous requests. This is especially handy
if you need to perform a number of requests, that are allowed to be executed
in parallel.

The underlying system for this is simply [cURL's multi request handler][8],
but this provides a much nicer API to handle this.

Sample usage:

```php

use Sabre\HTTP;

$request = new Request('GET', 'http://localhost/');
$client = new Client();

// Executing 1000 requests
for ($i = 0; $i < 1000; $i++) {
    $client->sendAsync(
        $request,
        function(ResponseInterface $response) {
            // Success handler
        },
        function($error) {
            // Error handler
        }
    ); 
}

// Wait for all requests to get a result.
$client->wait();

```

Check out `examples/asyncclient.php` for more information.

Writing a reverse proxy
-----------------------

With all these tools combined, it becomes very easy to write a simple reverse
http proxy.

```php
use
    Sabre\HTTP\Sapi,
    Sabre\HTTP\Client;

// The url we're proxying to.
$remoteUrl = 'http://example.org/';

// The url we're proxying from. Please note that this must be a relative url,
// and basically acts as the base url.
//
// If youre $remoteUrl doesn't end with a slash, this one probably shouldn't
// either.
$myBaseUrl = '/reverseproxy.php';
// $myBaseUrl = '/~evert/sabre/http/examples/reverseproxy.php/';

$request = Sapi::getRequest();
$request->setBaseUrl($myBaseUrl);

$subRequest = clone $request;

// Removing the Host header.
$subRequest->removeHeader('Host');

// Rewriting the url.
$subRequest->setUrl($remoteUrl . $request->getPath());

$client = new Client();

// Sends the HTTP request to the server
$response = $client->send($subRequest);

// Sends the response back to the client that connected to the proxy.
Sapi::sendResponse($response);
```

The Request and Response API's
------------------------------

### Request

```php

/**
 * Creates the request object
 *
 * @param string $method
 * @param string $url
 * @param array $headers
 * @param resource $body
 */
public function __construct($method = null, $url = null, array $headers = null, $body = null);

/**
 * Returns the current HTTP method
 *
 * @return string
 */
function getMethod();

/**
 * Sets the HTTP method
 *
 * @param string $method
 * @return void
 */
function setMethod($method);

/**
 * Returns the request url.
 *
 * @return string
 */
function getUrl();

/**
 * Sets the request url.
 *
 * @param string $url
 * @return void
 */
function setUrl($url);

/**
 * Returns the absolute url.
 *
 * @return string
 */
function getAbsoluteUrl();

/**
 * Sets the absolute url.
 *
 * @param string $url
 * @return void
 */
function setAbsoluteUrl($url);

/**
 * Returns the current base url.
 *
 * @return string
 */
function getBaseUrl();

/**
 * Sets a base url.
 *
 * This url is used for relative path calculations.
 *
 * The base url should default to /
 *
 * @param string $url
 * @return void
 */
function setBaseUrl($url);

/**
 * Returns the relative path.
 *
 * This is being calculated using the base url. This path will not start
 * with a slash, so it will always return something like
 * 'example/path.html'.
 *
 * If the full path is equal to the base url, this method will return an
 * empty string.
 *
 * This method will also urldecode the path, and if the url was incoded as
 * ISO-8859-1, it will convert it to UTF-8.
 *
 * If the path is outside of the base url, a LogicException will be thrown.
 *
 * @return string
 */
function getPath();

/**
 * Returns the list of query parameters.
 *
 * This is equivalent to PHP's $_GET superglobal.
 *
 * @return array
 */
function getQueryParameters();

/**
 * Returns the POST data.
 *
 * This is equivalent to PHP's $_POST superglobal.
 *
 * @return array
 */
function getPostData();

/**
 * Sets the post data.
 *
 * This is equivalent to PHP's $_POST superglobal.
 *
 * This would not have been needed, if POST data was accessible as
 * php://input, but unfortunately we need to special case it.
 *
 * @param array $postData
 * @return void
 */
function setPostData(array $postData);

/**
 * Returns an item from the _SERVER array.
 *
 * If the value does not exist in the array, null is returned.
 *
 * @param string $valueName
 * @return string|null
 */
function getRawServerValue($valueName);

/**
 * Sets the _SERVER array.
 *
 * @param array $data
 * @return void
 */
function setRawServerData(array $data);

/**
 * Returns the body as a readable stream resource.
 *
 * Note that the stream may not be rewindable, and therefore may only be
 * read once.
 *
 * @return resource
 */
function getBodyAsStream();

/**
 * Returns the body as a string.
 *
 * Note that because the underlying data may be based on a stream, this
 * method could only work correctly the first time.
 *
 * @return string
 */
function getBodyAsString();

/**
 * Returns the message body, as it's internal representation.
 *
 * This could be either a string or a stream.
 *
 * @return resource|string
 */
function getBody();

/**
 * Updates the body resource with a new stream.
 *
 * @param resource $body
 * @return void
 */
function setBody($body);

/**
 * Returns all the HTTP headers as an array.
 *
 * @return array
 */
function getHeaders();

/**
 * Returns a specific HTTP header, based on it's name.
 *
 * The name must be treated as case-insensitive.
 *
 * If the header does not exist, this method must return null.
 *
 * @param string $name
 * @return string|null
 */
function getHeader($name);

/**
 * Updates a HTTP header.
 *
 * The case-sensitity of the name value must be retained as-is.
 *
 * @param string $name
 * @param string $value
 * @return void
 */
function setHeader($name, $value);

/**
 * Resets HTTP headers
 *
 * This method overwrites all existing HTTP headers
 *
 * @param array $headers
 * @return void
 */
function setHeaders(array $headers);

/**
 * Adds a new set of HTTP headers.
 *
 * Any header specified in the array that already exists will be
 * overwritten, but any other existing headers will be retained.
 *
 * @param array $headers
 * @return void
 */
function addHeaders(array $headers);

/**
 * Removes a HTTP header.
 *
 * The specified header name must be treated as case-insenstive.
 * This method should return true if the header was successfully deleted,
 * and false if the header did not exist.
 *
 * @return bool
 */
function removeHeader($name);

/**
 * Sets the HTTP version.
 *
 * Should be 1.0, 1.1 or 2.0.
 *
 * @param string $version
 * @return void
 */
function setHttpVersion($version);

/**
 * Returns the HTTP version.
 *
 * @return string
 */
function getHttpVersion();
```

### Response

```php
/**
 * Returns the current HTTP status.
 *
 * This is the status-code as well as the human readable string.
 *
 * @return string
 */
function getStatus();

/**
 * Sets the HTTP status code.
 *
 * This can be either the full HTTP status code with human readable string,
 * for example: "403 I can't let you do that, Dave".
 *
 * Or just the code, in which case the appropriate default message will be
 * added.
 *
 * @param string|int $status
 * @throws \InvalidArgumentExeption
 * @return void
 */
function setStatus($status);

/**
 * Returns the body as a readable stream resource.
 *
 * Note that the stream may not be rewindable, and therefore may only be
 * read once.
 *
 * @return resource
 */
function getBodyAsStream();

/**
 * Returns the body as a string.
 *
 * Note that because the underlying data may be based on a stream, this
 * method could only work correctly the first time.
 *
 * @return string
 */
function getBodyAsString();

/**
 * Returns the message body, as it's internal representation.
 *
 * This could be either a string or a stream.
 *
 * @return resource|string
 */
function getBody();


/**
 * Updates the body resource with a new stream.
 *
 * @param resource $body
 * @return void
 */
function setBody($body);

/**
 * Returns all the HTTP headers as an array.
 *
 * @return array
 */
function getHeaders();

/**
 * Returns a specific HTTP header, based on it's name.
 *
 * The name must be treated as case-insensitive.
 *
 * If the header does not exist, this method must return null.
 *
 * @param string $name
 * @return string|null
 */
function getHeader($name);

/**
 * Updates a HTTP header.
 *
 * The case-sensitity of the name value must be retained as-is.
 *
 * @param string $name
 * @param string $value
 * @return void
 */
function setHeader($name, $value);

/**
 * Resets HTTP headers
 *
 * This method overwrites all existing HTTP headers
 *
 * @param array $headers
 * @return void
 */
function setHeaders(array $headers);

/**
 * Adds a new set of HTTP headers.
 *
 * Any header specified in the array that already exists will be
 * overwritten, but any other existing headers will be retained.
 *
 * @param array $headers
 * @return void
 */
function addHeaders(array $headers);

/**
 * Removes a HTTP header.
 *
 * The specified header name must be treated as case-insenstive.
 * This method should return true if the header was successfully deleted,
 * and false if the header did not exist.
 *
 * @return bool
 */
function removeHeader($name);

/**
 * Sets the HTTP version.
 *
 * Should be 1.0, 1.1 or 2.0.
 *
 * @param string $version
 * @return void
 */
function setHttpVersion($version);

/**
 * Returns the HTTP version.
 *
 * @return string
 */
function getHttpVersion();
```

Made at fruux
-------------

This library is being developed by [fruux](https://fruux.com/). Drop us a line for commercial services or enterprise support.

[1]: http://getcomposer.org/
[2]: http://sabre.io/
[3]: https://github.com/symfony/HttpFoundation
[4]: http://php.net/curl
[5]: https://github.com/fruux/sabre-event
[6]: http://en.wikipedia.org/wiki/Decorator_pattern
[7]: http://guzzlephp.org/
[8]: http://php.net/curl_multi_init
