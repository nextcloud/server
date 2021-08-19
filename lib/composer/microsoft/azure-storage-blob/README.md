# Microsoft Azure Storage Blob PHP Client Library

This project provides a PHP client library that makes it easy to access Microsoft Azure Storage blob services. For documentation on how to host PHP applications on Microsoft Azure, please see the [Microsoft Azure PHP Developer Center](http://www.windowsazure.com/en-us/develop/php/).

[![Latest Stable Version](https://poser.pugx.org/microsoft/azure-storage-blob/v/stable)](https://packagist.org/packages/microsoft/azure-storage-blob)

> **Note**
>
> * This [repository](https://github.com/azure/azure-storage-blob-php) is currently used for releasing only, please go to [azure-storage-php](https://github.com/azure/azure-storage-php) for submitting issues or contribution.
> * If you are looking for the Service Bus, Service Runtime, Service Management or Media Services libraries, please visit https://github.com/Azure/azure-sdk-for-php.
> * If you need big file (larger than 2GB) or 64-bit integer support, please install PHP 7 64-bit version.

# Features

* Blobs
  * create, list, and delete containers, work with container metadata and permissions, list blobs in container
  * create block and page blobs (from a stream or a string), work with blob blocks and pages, delete blobs
  * work with blob properties, metadata, leases, snapshot a blob

Please check details on [API reference documents](http://azure.github.io/azure-storage-php).

# Getting Started
## Minimum Requirements

* PHP 5.6 or above
* See [composer.json](composer.json) for dependencies
* Required extension for PHP:
  * php_fileinfo.dll
  * php_mbstring.dll
  * php_openssl.dll
  * php_xsl.dll

* Recommended extension for PHP:
  * php_curl.dll

## Download Source Code

To get the source code from GitHub, type

```
git clone https://github.com/Azure/azure-storage-php.git
cd ./azure-storage-php
```

## Install via Composer

1. Create a file named **composer.json** in the root of your project and add the following code to it:
```json
{
  "require": {
    "microsoft/azure-storage-blob": "*"
  }
}
```
2. Download **[composer.phar](http://getcomposer.org/composer.phar)** in your project root.

3. Open a command prompt and execute this in your project root

```
php composer.phar install
```

## Usage

There are four basic steps that have to be performed before you can make a call to any Microsoft Azure Storage API when using the libraries.

* First, include the autoloader script:

```php
require_once "vendor/autoload.php";
```

* Include the namespaces you are going to use.

  To create any Microsoft Azure service client you need to use the rest proxy classes, such as **BlobRestProxy** class:

```php
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
```

  To process exceptions you need:

```php
use MicrosoftAzure\Storage\Common\ServiceException;
```

* To instantiate the service client you will also need a valid [connection string](https://azure.microsoft.com/en-us/documentation/articles/storage-configure-connection-string/). The format is:

```
DefaultEndpointsProtocol=[http|https];AccountName=[yourAccount];AccountKey=[yourKey]
```

  or:

```
BlobEndpoint=[myBlobEndpoint];SharedAccessSignature=[sasToken]
```

* Instantiate a client object - a wrapper around the available calls for the given service.

```php
$blobClient = BlobRestProxy::createBlobService($connectionString);
```
Or for token authentication:
```php
$blobClient = BlobRestProxy::createBlobServiceWithTokenCredential($token, $connectionString);
```
### Using Middlewares
To specify the middlewares, user have to create an array with middlewares
and put it in the `$requestOptions` with key 'middlewares'. The sequence of
the array will affect the sequence in which the middleware is invoked. The
`$requestOptions` can usually be set in the options of an API call, such as
`MicrosoftAzure\Storage\Blob\Models\ListBlobOptions`.

The user can push the middleware into the array with key 'middlewares' in
services' `$_options` instead when creating them if the middleware is to be
applied to each of the API call for a rest proxy. These middlewares will always
be invoked after the middlewares in the `$requestOptions`.
e.g.:
```php
$blobClient = BlobRestProxy::createBlobService(
    $connectionString,
    $optionsWithMiddlewares
);
```

Each of the middleware should be either an instance of a sub-class that
implements `MicrosoftAzure\Storage\Common\Internal\IMiddleware`, or a
`callable` that follows the Guzzle middleware implementation convention.

User can create self-defined middleware that inherits from `MicrosoftAzure\Storage\Common\Internal\Middlewares\MiddlewareBase`.

### Using proxies
To use proxies during HTTP requests, set system variable `HTTP_PROXY` and the proxy will be used.

## Troubleshooting
### Error: Unable to get local issuer certificate
cURL can't verify the validity of Microsoft certificate when trying to issue a request call to Azure Storage Services. You must configure cURL to use a certificate when issuing https requests by the following steps:

1. Download the cacert.pem file from [cURL site](http://curl.haxx.se/docs/caextract.html). 

2. Then either:
    * Open your php.ini file and add the following line:
        ```ini
        curl.cainfo = "<absolute path to cacert.pem>"
        ```
        OR
    * Point to the cacert in the options when creating the Proxy.
        ```php
        $options["http"] = ["verify" => "<absolute path to cacert.pem>"];
        BlobRestProxy::createBlobService($connectionString, $options);
        ```

## Code samples

You can find samples in the [sample folder](samples)


# Migrate from [Azure SDK for PHP](https://github.com/Azure/azure-sdk-for-php/)

If you are using [Azure SDK for PHP](https://github.com/Azure/azure-sdk-for-php/) to access Azure Storage Service, we highly recommend you to migrate to this SDK for faster issue resolution and quicker feature implementation. We are working on supporting the latest service features as well as improvement on existing APIs.

For now, Microsoft Azure Storage PHP client libraries share almost the same interface as the storage blobs, tables, queues and files APIs in Azure SDK for PHP. However, there are some minor breaking changes need to be addressed during your migration. You can find the details in [BreakingChanges.md](BreakingChanges.md).

# Need Help?

Be sure to check out the Microsoft Azure [Developer Forums on Stack Overflow](http://go.microsoft.com/fwlink/?LinkId=234489) and [github issues](https://github.com/Azure/azure-storage-php/issues) if you have trouble with the provided code.

# Contribute Code or Provide Feedback

If you would like to become an active contributor to this project please follow the instructions provided in [Azure Projects Contribution Guidelines](http://azure.github.io/guidelines/).
You can find more details for contributing in the [CONTRIBUTING.md](CONTRIBUTING.md).

If you encounter any bugs with the library please file an issue in the [Issues](https://github.com/Azure/azure-storage-php/issues) section of the project.
