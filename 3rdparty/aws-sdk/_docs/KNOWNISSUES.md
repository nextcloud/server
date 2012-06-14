# Known Issues

## 2GB limit for 32-bit stacks; all Windows stacks.

Because PHP's integer type is signed and many platforms use 32-bit integers, the AWS SDK for PHP does not correctly
handle files larger than 2GB on a 32-bit stack (where "stack" includes CPU, OS, web server, and PHP binary). This is a
[well-known PHP issue]. In the case of Microsoft® Windows®, there are no official builds of PHP that support 64-bit
integers.

The recommended solution is to use a 64-bit Linux stack, such as the [64-bit Amazon Linux AMI] with the latest version of
PHP installed.

For more information, please see: [PHP filesize: Return values]. A workaround is suggested in
`AmazonS3::create_mpu_object()` [with files bigger than 2GB].

   [well-known PHP issue]: http://www.google.com/search?q=php+2gb+32-bit
   [64-bit Amazon Linux AMI]: http://aws.amazon.com/amazon-linux-ami/
   [PHP filesize: Return values]: http://docs.php.net/manual/en/function.filesize.php#refsect1-function.filesize-returnvalues
   [with files bigger than 2GB]: https://forums.aws.amazon.com/thread.jspa?messageID=215487#215487


## Amazon S3 Buckets containing periods

Amazon S3's SSL certificate covers domains that match `*.s3.amazonaws.com`. When buckets (e.g., `my-bucket`) are accessed
using DNS-style addressing (e.g., `my-bucket.s3.amazonaws.com`), those SSL/HTTPS connections are covered by the certificate.

However, when a bucket name contains one or more periods (e.g., `s3.my-domain.com`) and is accessed using DNS-style
addressing (e.g., `s3.my-domain.com.s3.amazonaws.com`), that SSL/HTTPS connection will fail because the certificate
doesn't match.

The most secure workaround is to change the bucket name to one that does not contain periods. Less secure workarounds
are to use `disable_ssl()` or `disable_ssl_verification()`. Because of the security implications, calling either of
these methods will throw a warning. You can avoid the warning by adjusting your `error_reporting()` settings.


## Expiring request signatures

When leveraging `AmazonS3::create_mpu_object()`, it's possible that later parts of the multipart upload will fail if
the upload takes more than 15 minutes.


## Too many open file connections

When leveraging `AmazonS3::create_mpu_object()`, it's possible that the SDK will attempt to open too many file resources
at once. Because the file connection limit is not available to the PHP environment, the SDK is unable to automatically
adjust the number of connections it attempts to open.

A workaround is to increase the part size so that fewer file connections are opened.


## Exceptionally large batch requests

When leveraging the batch request feature to execute multiple requests in parallel, it's possible that the SDK will
throw a fatal exception if a particular batch pool is exceptionally large and a service gets overloaded with requests.

This seems to be most common when attempting to send a large number of emails with the SES service.


## Long-running processes using SSL leak memory

When making requests with the SDK over SSL during long-running processes, there will be a gradual memory leak that can
eventually cause a crash. The leak occurs within the PHP bindings for cURL when attempting to verify the peer during an
SSL handshake. See <https://bugs.php.net/61030> for details about the bug.

A workaround is to disable SSL for requests executed in long-running processes.
