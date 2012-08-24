# Changelog: 1.5.6.2 "Gershwin"
Code name for Apple's never-released successor to the never-released Copeland. <http://en.wikipedia.org/wiki/Gershwin_operating_system>

Launched Tuesday, May 29th, 2012.

## Services
### AmazonDynamoDB
- **Fixed:** STS credentials were not always being cached correctly.

----

# Changelog: 1.5.6.1 "Gershwin"
Code name for Apple's never-released successor to the never-released Copeland. <http://en.wikipedia.org/wiki/Gershwin_operating_system>

Launched Tuesday, May 24th, 2012.

## Services
### AmazonDynamoDB
- **Fixed:** STS credentials were not always being cached correctly.

----

# Changelog: 1.5.6 "Gershwin"
Code name for Apple's never-released successor to the never-released Copeland. <http://en.wikipedia.org/wiki/Gershwin_operating_system>

Launched Tuesday, May 15th, 2012.

## Services
### AmazonSES
- **New:** Support for domain verification has been added to the SDK, which enables customers to verify an entire email domain.
- **New:** Requests to this service are now signed with Signature V4.

----

# Changelog: 1.5.5 "Fishhead"
Code name for the Apple II File Mangement Utility. <http://applemuseum.bott.org/sections/codenames.html>

Launched Wednesday, May 9, 2012.

## Services
### AmazonCloudFormation
* **New:** Requests to this service are now signed with Signature V4.

### AmazonCloudFront
* **New:** Updated the supported API version to `2012-03-15`.

### AmazonDynamoDB
* **New:** Support for the US West (Northern California), US West (Oregon), Asia Pacific "Southeast" (Signapore) endpoints have been added.

### AmazonElasticBeanstalk
* **New:** Support for the new Asia Pacific "Northeast" (Japan) endpoint has been added.

### AmazonStorageGateway
* **New:** Support for the AWS Storage Gateway service has been added to the SDK.

---

# Changelog: 1.5.4 "Enterprise"
Code name for Mac OS X Server 1.0 (Rhapsody CR1). <http://en.wikipedia.org/wiki/Rhapsody_(operating_system)>

Launched Thursday, April 19, 2012.

## Bug fixes and enhancements
* [PHP SDK Bug - Memory leak](https://forums.aws.amazon.com/thread.jspa?threadID=72310)
* [Does update_object work in 1.5.3?](https://forums.aws.amazon.com/thread.jspa?threadID=89297)
* [The value of CURLOPT_SSL_VERIFYHOST](https://forums.aws.amazon.com/thread.jspa?threadID=86186)
* [PHP SDK BUG: s3.class.php Line 2396 on 1.5.2](https://forums.aws.amazon.com/thread.jspa?threadID=86779)
* [first create_bucket(), then get_bucket_list()](https://forums.aws.amazon.com/thread.jspa?messageID=318885)
* [Issue with AmazonS3::get_object_list() max-keys](https://forums.aws.amazon.com/thread.jspa?threadID=85878)
* [Correct the "Bottom line" minimum requirements check](https://github.com/amazonwebservices/aws-sdk-for-php/pull/23)
* [S3 PHP SDK: copy_object() fails to update the header](http://stackoverflow.com/questions/7677837/s3-php-sdk-copy-object-fails-to-update-the-header)
* [Adds the following utility methods to simplexml.class.php](https://github.com/amazonwebservices/aws-sdk-for-php/pull/22)
* [Adding the ability to name a 'rule' for Object Expiration (suggested tweak)](https://forums.aws.amazon.com/thread.jspa?messageID=328023)

## Runtime
* **New:** Support for Signature Version 4 has been added to the SDK. Signature Version 4 is now the default authentication method for AWS Identity and Access Management, AWS Security Token Service and Amazon CloudSearch.

## Services
### AmazonCloudFront
* **New:** Support for a Minimum TTL of zero has been added to the SDK.

### AmazonCloudSearch
* **New:** Support for Amazon CloudSearch has been added to the SDK. This includes only the Configuration API.

### AmazonDynamoDB
* **New:** Support for BatchWriteItem API has been added to the SDK.
* **New:** Support for the European (Ireland) endpoint has been added.
* **New:** Support for the Asia Pacific "Northeast" (Tokyo) endpoint has been added.
* **New:** Amazon DynamoDB Session Handler has been added to the SDK.
* **New:** A simplified interface for adding attributes has been added to the SDK.

### AmazonEC2
* **New:** The new "m1.medium" instance type is now supported.
* **New:** Amazon EBS support for Volume Status and Volume Attributes have been added to the SDK.
* **New:** Amazon EBS support for Conversion Tasks has been added to the SDK.
* **New:** Amazon EC2 support for the Report Instance Status feature has been added to the SDK.
* **New:** Amazon VPC support for Network Interfaces has been added to the SDK.
* **Fixed:** Various parameter fixes have been applied.

### AmazonIAM
* **New:** Support for Password Policies and the ability to change passwords has been added to the SDK.

### AmazonS3
* **New:** Support for pre-signed URLs using temporary credentials has been added to the SDK.
* **New:** Support for setting a custom name to Lifecycle (i.e., Object Expiration) rules has been added to the SDK.
* **New:** Support for pre-signed URLs with https has been added to the SDK.
* **Fixed:** Resolved an issue where setting a custom XML parsing class was not being respected.
* **Fixed:** Resolved an issue where the `get_object_list()` method would return an incorrect number of entries.
* **Fixed:** Resolved an issue where `update_object()` was attempting to COPY instead of REPLACE.
* **Fixed:** Resolved an issue stemming from using path-style URLs, `create_bucket()` + `list_bucket()` and the EU-West region.
* **Fixed:** Resolved an issue where XML responses were not being parsed consistently.
* **Fixed:** Resolved an issue where Private Streaming URLs contained a double-encoded signature.
* **Fixed:** The `Expect: 100-continue` HTTP header is now only sent during `create_object()` and `upload_part()` requests.

## Utilities
### CFRuntime
* **Fixed:** Resolved an issue where `CURLOPT_SSL_VERIFYHOST` was not set strictly enough.
* **Fixed:** The `Expect: 100-continue` HTTP header is no longer set on every request.

### CFSimpleXML
* **New:** Support for `matches()`, `starts_with()` and `ends_with()` methods have been added to the SDK. (Thanks [Wil Moore III](https://github.com/wilmoore)!)

## Compatibility Test
* **New:** SDK Compatibility Test pages are marked up as to not be indexed by search engines. (Thanks [Eric Caron](http://www.ericcaron.com)!)
* **Fixed:** Duplicate code between the CLI and web versions of the SDK has been refactored. (Thanks [Jimmy Berry](https://github.com/boombatower)!)

---

# Changelog: 1.5.3 "Darwin"
UNIX foundation upon which Mac OS X, Apple TV, and iOS are based. <http://en.wikipedia.org/wiki/Darwin_operating_system>

Launched Wednesday, Tuesday, February 21, 2012.

## Bug fixes and enhancements
* [Fixing Issue with set_distribution_config](https://github.com/amazonwebservices/aws-sdk-for-php/pull/20)

## Services
### AmazonCloudFront
* **Fixed:** Resolved an issue where the `set_distribution_config()` method could fail to satisfy an API constraint when using a custom origin server. (Thanks [zoxa](https://github.com/zoxa)!)

### AmazonSWF
* **New:** Support for the new Amazon Simple Workflow Service has been added to the SDK.

----

# Changelog: 1.5.2 "Copland"
Code name for Apple's never-released successor to System 7. <http://en.wikipedia.org/wiki/Copland_(operating_system)>

Launched Wednesday, Febraury 1, 2012.

## Bug fixes and enhancements
* [SSL Cert on PHP SDK 1.5.0.1 ](https://forums.aws.amazon.com/thread.jspa?threadID=84947)
* [Stream Wrapper need a buffer !](https://forums.aws.amazon.com/thread.jspa?threadID=85436)
* [Fixing Issue with set_distribution_config](https://github.com/amazonwebservices/aws-sdk-for-php/pull/20)
* [[Bug] SDK Autoloader Interferes with PHPExcel Autoloader](https://forums.aws.amazon.com/thread.jspa?threadID=85239)
* [get_object query does not always return the same content type](https://forums.aws.amazon.com/thread.jspa?threadID=84148)
* [AWSSDKforPHP/authentication/swift_transport_esmtp_signature_handler.class.p ](https://forums.aws.amazon.com/thread.jspa?threadID=85087)

## Runtime
* **New:** Updated the CA Root Certificates file to version 1.81.
* **Fixed:** Resolved an issue in the autoloader where the matching logic was too aggressive in certain cases, causing subsequent autoloaders to never trigger.

## Services
### AmazonAS
* **New:** Support for Auto Scaling Resource Tagging has been added to the SDK.

### AmazonS3
* **Fixed:** Resolved an issue where `delete_all_objects()` and `delete_all_object_versions()` was being limited to 1000 items.
* **Fixed:** Resolved an issue where `delete_bucket()` would fail to delete a bucket with the "force" option enabled if the bucket contained more than 1000 items.
* **Fixed:** Resolved an issue where JSON documents stored in Amazon S3 would be parsed into a native PHP object when retrieved.

## Utilities
### S3StreamWrapper
* **New:** Support for multiple stream wrappers (e.g., one per region) has been added to the SDK.
* **Fixed:** Writes to Amazon S3 are now buffered, resolving issues with pushing more than 8k of data at a time.

### CFJSON
* **Fixed:** The JSON-to-XML conversion code is now substantially more robust and better handles encoded characters.

### CacheCore
* **Changed:** Formerly, attempting to cache to a file system location that didn't exist or was not writable by the PHP process would fail silently. This behavior has been changed to throw a `CacheFile_Exception`.

----

# Changelog: 1.5.1 "Blue"
Code name for Macintosh System 7. <http://en.wikipedia.org/wiki/System_7>

Launched Wednesday, January 18, 2012.

## Bug fixes and enhancements
* [Documentation patch](https://github.com/amazonwebservices/aws-sdk-for-php/pull/13)
* [Removed duplicate comment line.](https://github.com/amazonwebservices/aws-sdk-for-php/pull/17)
* [CFRuntime credentials handling issue](https://forums.aws.amazon.com/thread.jspa?messageID=310388)
* [PHP 5.2 bug in AWS SDK for PHP 1.5.x](https://forums.aws.amazon.com/thread.jspa?messageID=311543)
* [[Bug] Custom Curl Opts Lost During Retry](https://forums.aws.amazon.com/thread.jspa?threadID=84835)
* [json_last_error doesn't exist before php v 5.3.0](https://github.com/amazonwebservices/aws-sdk-for-php/pull/12)
* [XML still being parsed when use_cache_flow is false](https://github.com/amazonwebservices/aws-sdk-for-php/pull/15)
* [Bug ssl_verification option not respected for AmazonS3 ](https://forums.aws.amazon.com/thread.jspa?threadID=83710)
* [[Bug] Compatibility test for Garbage Collector enabled should use ini_get](https://forums.aws.amazon.com/thread.jspa?threadID=84156)

## Runtime
* **Fixed:** Corrected an issue where calling `AmazonS3->get_object()` would continue to parse the content if caching was being leveraged. (Thanks [Eric Caron](http://www.ericcaron.com)!)
* **Fixed:** The autoloader now returns `false` for any class it doesn't match, allowing subsequent autoloaders to catch the class name. (Thanks [Eric Caron](http://www.ericcaron.com)!)
* **Fixed:** An issue that caused CloudWatch to fail to decompress gzipped data correctly has been resolved.
* **Fixed:** Resolved an issue with passing explicit credentials without requiring a config file or a `CFCredentials` declaration.
* **Fixed:** Resolved an issue which causes custom cURL options to be unset from the payload when retrying.

## Services
### AmazonAS
* **New:** Support for Amazon SNS notifications and Tagging have been added to the SDK.

### AmazonCloudFront
* **Fixed:** Resolved an issue with disabling SSL verification.
* **Fixed:** Resolved an issue where `AmazonCloudFront` were throwing warnings in `E_STRICT` mode.

### AmazonCloudWatch
* **Fixed:** Resolved an issue with decompressing gzipped data.

### AmazonDynamoDB
* **New:** Support for Amazon DynamoDB has been added to the SDK.
* **New:** Amazon DynamoDB requires a default cache configuration to be set in the credential set, otherwise it will not function properly.

### AmazonS3
* **Fixed:** Resolved an issue with disabling SSL verification.
* **Fixed:** Resolved multiple documentation issues. (Thanks [Aizat Faiz](http://aizatto.com) and [Jason Ardell](http://ardell.posterous.com/)!)
* **Fixed:** Resolved an issue where `AmazonS3` were throwing warnings in `E_STRICT` mode.

### AmazonSNS
* **New:** Support for Short Messaging Service (SMS) endpoints has been added to the SDK.
* **New:** Support for Subscription Attributes has been added to the SDK.

## Utilities
### CFJSON
* **Fixed:** Support for the handling of JSON nulls in PHP 5.2 has been improved. (Thanks [David Chan](http://www.chandeeland.org)!)

## Compatibility Test
* **Fixed:** The SDK compatibility test now uses `ini_get()` instead of `get_cfg_var()` and `get_cfg_ini()` for more accurate test results.


----

# Changelog: 1.5 "Allegro"
Code name for Mac OS 8.5. <http://en.wikipedia.org/wiki/Mac_OS_8#Mac_OS_8.5>

Launched Wednesday, December 14, 2011

## Credentials
* !! BACKWARDS-INCOMPATIBLE CHANGE !! - The function signature of all service constructors has changed. Instead of passing a key and secret as the first and second parameters, the constructor now accepts a hash (associative array) containing `key` and `secret` keys. Please see the API reference documentation

## Runtime
* !! BACKWARDS-INCOMPATIBLE CHANGE !! - The function signature of all service constructors has changed. Instead of passing a key and secret as the first and second parameters, the constructor now accepts a hash (associative array) containing `key` and `secret` keys. If you are explicitly passing a key and secret to the constructor, you will need to change your code. If you are simply inheriting your default credentials from a config file, you don't need to make any changes beyond upgrading your config file to the new 1.5 format. Please see the API reference documentation for more information.
* !! BACKWARDS-INCOMPATIBLE CHANGE !! - The method by which the `config.inc.php` file maintains its list of credentials has been re-factored and updated to support managing multiple sets of credentials in a single location (e.g., development, staging, production).
* !! BACKWARDS-INCOMPATIBLE CHANGE !! - The `init()` method has been renamed to `factory()` to better reflect what it actually does.
* !! BACKWARDS-INCOMPATIBLE CHANGE !! - The `adjust_offset()` method has been removed. Instead, please ensure that the machine's time is set correctly using an [NTP server](https://secure.wikimedia.org/wikipedia/en/wiki/Network_Time_Protocol).
* !! BACKWARDS-INCOMPATIBLE CHANGE !! - In version 1.4 we enabled a mode where -- for services that supported it -- a set of temporary credentials were fetched and cached before the first request. This functionality has been reverted. The use of short-term credentials must be explicitly enabled by instantiating the `AmazonSTS` class and passing those credentials into the service constructor.
* **New:** Improved the user directory lookup for the config file.
* **Changed:** Made `set_region()` an alias of `set_hostname()`.

## Services
### AmazonAS
* **New:** Support for the South American (São Paulo) region has been added to the SDK.
* **New:** Plain english aliases have been added to the SDK: `REGION_VIRGINA`, `REGION_CALIFORNIA`, `REGION_OREGON`, `REGION_IRELAND`, `REGION_SINGAPORE`, `REGION_TOKYO`, `REGION_SAO_PAULO`

### AmazonCloudFormation
* **New:** Support for the South American (São Paulo) region has been added to the SDK.
* **New:** Plain english aliases have been added to the SDK: `REGION_VIRGINA`, `REGION_CALIFORNIA`, `REGION_OREGON`, `REGION_IRELAND`, `REGION_SINGAPORE`, `REGION_TOKYO`, `REGION_SAO_PAULO`
* **New:** Support for cost estimation of CloudFormation templates has been added to the SDK.

### AmazonCloudWatch
* **New:** Support for the South American (São Paulo) region has been added to the SDK.
* **New:** Plain english aliases have been added to the SDK: `REGION_VIRGINA`, `REGION_CALIFORNIA`, `REGION_OREGON`, `REGION_IRELAND`, `REGION_SINGAPORE`, `REGION_TOKYO`, `REGION_SAO_PAULO`

### AmazonEC2
* **New:** Support for the South American (São Paulo) region has been added to the SDK.
* **New:** Plain english aliases have been added to the SDK: `REGION_VIRGINA`, `REGION_CALIFORNIA`, `REGION_OREGON`, `REGION_IRELAND`, `REGION_SINGAPORE`, `REGION_TOKYO`, `REGION_SAO_PAULO`
* **New:** Support for 24x7 Reserved Instances has been added to the SDK. For more information, please see [New Amazon EC2 Reserved Instance Options Now Available](https://aws.amazon.com/about-aws/whats-new/2011/12/01/New-Amazon-EC2-Reserved-Instances-Options-Now-Available/).
* **New:** Support for VPC Spot Instances has been added to the SDK. For more information, please see [Announcing Amazon EC2 Spot Integration with Amazon VPC](https://aws.amazon.com/about-aws/whats-new/2011/10/11/announcing-amazon-ec2-spot-integration-with-amazon-vpc/).
* **New:** Support for VPC Everywhere has been added to the SDK. For more information, please see [Amazon VPC Generally Available in Multiple AZs in All Regions](https://aws.amazon.com/about-aws/whats-new/2011/08/03/Announcing-VPC-GA/).
* **New:** Instance Type-related constants have been added to the SDK: `INSTANCE_MICRO`, `INSTANCE_SMALL`, `INSTANCE_LARGE`, `INSTANCE_XLARGE`, `INSTANCE_HIGH_MEM_XLARGE`, `INSTANCE_HIGH_MEM_2XLARGE`, `INSTANCE_HIGH_MEM_4XLARGE`, `INSTANCE_HIGH_CPU_MEDIUM`, `INSTANCE_HIGH_CPU_XLARGE`, `INSTANCE_CLUSTER_4XLARGE`, `INSTANCE_CLUSTER_8XLARGE`, `INSTANCE_CLUSTER_GPU_XLARGE`.

### AmazonElastiCache
* **New:** Support for US-West 1 (California), EU-West (Ireland), Asia Pacific Southeast (Singapore), and Asia Pacific Northeast (Tokyo) regions has been added to the SDK. For more information, please see [Amazon ElastiCache is now available in four additional AWS Regions and as a CloudFormation template](https://aws.amazon.com/about-aws/whats-new/2011/12/05/amazon-elasticache-new-regions/).
* **New:** Plain english aliases have been added to the SDK: `REGION_VIRGINA`, `REGION_CALIFORNIA`, `REGION_IRELAND`, `REGION_SINGAPORE`, `REGION_TOKYO`

### AmazonElasticBeanstalk
* **New:** Plain english aliases have been added to the SDK: `REGION_VIRGINA`

### AmazonELB
* **New:** Support for the South American (São Paulo) region has been added to the SDK.
* **New:** Plain english aliases have been added to the SDK: `REGION_VIRGINA`, `REGION_CALIFORNIA`, `REGION_OREGON`, `REGION_IRELAND`, `REGION_SINGAPORE`, `REGION_TOKYO`, `REGION_SAO_PAULO`
* **New:** Support for ELBs running in VPC has been added to the SDK. For more information, please see [Announcing Elastic Load Balancing in Amazon VPC](https://aws.amazon.com/about-aws/whats-new/2011/11/21/announcing-elastic-load-balancing-in-amazon-vpc/).

### AmazonEMR
* **New:** Support for the South American (São Paulo) region has been added to the SDK.
* **New:** Plain english aliases have been added to the SDK: `REGION_VIRGINA`, `REGION_CALIFORNIA`, `REGION_OREGON`, `REGION_IRELAND`, `REGION_SINGAPORE`, `REGION_TOKYO`, `REGION_SAO_PAULO`
* **New:** Support for EMR AMI Versioning, new Hadoop and Pig versions, and EMR running in VPC has been added to the SDK. For more information, please see [Amazon Elastic MapReduce Announces Support for New Hadoop and Pig Versions, AMI Versioning, and Amazon VPC](https://aws.amazon.com/about-aws/whats-new/2011/12/11/amazon-elastic-mapreduce-ami-versioning-vpc/).

### AmazonIAM
* **New:** Plain english aliases have been added to the SDK: `REGION_VIRGINA`

### AmazonImportExport
* **New:** Plain english aliases have been added to the SDK: `REGION_VIRGINA`

### AmazonRDS
* **New:** Support for the South American (São Paulo) region has been added to the SDK.
* **New:** Plain english aliases have been added to the SDK: `REGION_VIRGINA`, `REGION_CALIFORNIA`, `REGION_OREGON`, `REGION_IRELAND`, `REGION_SINGAPORE`, `REGION_TOKYO`, `REGION_SAO_PAULO`

### AmazonS3
* **New:** Support for the South American (São Paulo) region has been added to the SDK.
* **New:** Plain english aliases have been added to the SDK: `REGION_VIRGINA`, `REGION_CALIFORNIA`, `REGION_OREGON`, `REGION_IRELAND`, `REGION_SINGAPORE`, `REGION_TOKYO`, `REGION_SAO_PAULO`
* **New:** Support for an S3 Stream Wrapper has been added to the SDK. This enables users to read/write to Amazon S3 as though it were the local file system.
**Fixed:** The `get_object()` method no longer attempts to parse XML/JSON content.
**Fixed:** Simplified S3 region logic. Now uses fully-qualified domain names across the board.

### AmazonSES
* **New:** Plain english aliases have been added to the SDK: `REGION_VIRGINA`

### AmazonSDB
* **New:** Support for the South American (São Paulo) region has been added to the SDK.
* **New:** Plain english aliases have been added to the SDK: `REGION_VIRGINA`, `REGION_CALIFORNIA`, `REGION_OREGON`, `REGION_IRELAND`, `REGION_SINGAPORE`, `REGION_TOKYO`, `REGION_SAO_PAULO`

### AmazonSNS
* **New:** Support for the South American (São Paulo) region has been added to the SDK.
* **New:** Plain english aliases have been added to the SDK: `REGION_VIRGINA`, `REGION_CALIFORNIA`, `REGION_OREGON`, `REGION_IRELAND`, `REGION_SINGAPORE`, `REGION_TOKYO`, `REGION_SAO_PAULO`

### AmazonSQS
* **New:** Support for the South American (São Paulo) region has been added to the SDK.
* **New:** Plain english aliases have been added to the SDK: `REGION_VIRGINA`, `REGION_CALIFORNIA`, `REGION_OREGON`, `REGION_IRELAND`, `REGION_SINGAPORE`, `REGION_TOKYO`, `REGION_SAO_PAULO`

### AmazonSTS
* **New:** Plain english aliases have been added to the SDK: `REGION_VIRGINA`


----

# Changelog: 1.4.8 "Zanarkand"
<http://finalfantasy.wikia.com/wiki/Zanarkand>

Launched Wednesday, December 7, 2011

## Services
### AmazonCloudFront
* **Fixed:** Merged in a pull request contributed by Ben Lumley: <https://github.com/amazonwebservices/aws-sdk-for-php/pull/11>

### AmazonEC2
* **Fixed:** Resolved an issue where `set_region()` was not setting the correct endpoint for the region.

### AmazonS3
* **New:** Support for S3-side multi-object delete has been added to the SDK as the `delete_objects()` method. The implementations of `delete_all_objects()` and `delete_all_object_versions()` have been updated to use this new functionality.
* **Changed:** XML and JSON responses from `get_object()` are no longer parsed. The raw XML and JSON string content is now returned.


----

# Changelog: 1.4.7 "Yuna"
<http://finalfantasy.wikia.com/wiki/Yuna>

Launched Wednesday, November 9, 2011

## Service Classes
### AmazonAS
* **New:** Support for the US-West 2 (Oregon) region has been added to the SDK.

### AmazonCloudFormation
* **New:** Support for the US-West 2 (Oregon) region has been added to the SDK.

### AmazonCloudWatch
* **New:** Support for the US-West 2 (Oregon) region has been added to the SDK.
* **New:** Support for the US GovCloud region has been added to the SDK.

### AmazonEC2
* **New:** Support for the US-West 2 (Oregon) region has been added to the SDK.
* **New:** Support for the US GovCloud region has been added to the SDK.

### AmazonELB
* **New:** Support for the US-West 2 (Oregon) region has been added to the SDK.

### AmazonEMR
* **New:** Support for the US-West 2 (Oregon) region has been added to the SDK.

### AmazonIAM
* **New:** Support for the US GovCloud region has been added to the SDK.

### AmazonRDS
* **New:** Support for the US-West 2 (Oregon) region has been added to the SDK.

### AmazonS3
* **New:** Support for the US-West 2 (Oregon) region has been added to the SDK.
* **Fixed:** Resolved an issue where certain bits of metadata were not maintained during a copy operation. <https://forums.aws.amazon.com/thread.jspa?threadID=77630>
* **Fixed:** Resolved an issue where an unsuccessful lookup of an existing content-type would throw a warning. <https://forums.aws.amazon.com/thread.jspa?threadID=78121>
* **Fixed:** Resolved an issue where an exception would be thrown when a filesize lookup was attempted on an object that didn't exist. <https://forums.aws.amazon.com/thread.jspa?threadID=78197>

### AmazonSDB
* **New:** Support for the US-West 2 (Oregon) region has been added to the SDK.

### AmazonSNS
* **New:** Support for the US-West 2 (Oregon) region has been added to the SDK.

### AmazonSQS
* **New:** Support for the US-West 2 (Oregon) region has been added to the SDK.


----

# Changelog: 1.4.6 "Xezat"
<http://finalfantasy.wikia.com/wiki/Xezat>

Launched Thursday, November 3, 2011

## Service Classes
### AmazonIAM
* **New:** Support for a virtual MFA device. A virtual MFA device uses a software application that can generate six-digit authentication codes that are Open AuTHentication Time-based One-Time Password (OATHTOTP)-compatible. The software application can run on any mobile hardware device, including a smartphone.


----

# Changelog: 1.4.5 "Weiss"
<http://finalfantasy.wikia.com/wiki/Weiss>

Launched Friday, October 21, 2011

## Service Classes
### AmazonSQS
* **New:** Support for delayed queues and batch operations has been added to the SDK.


----

# Changelog: 1.4.4 "Vaan"
<http://finalfantasy.wikia.com/wiki/Vaan>

Launched Tuesday, October 12, 2011

## Runtime
* **Fixed:** Resolved an issue where a segmentation fault is triggererd when there are multiple autoloaders in the stack and one of them doesn't return a value.

## Service Classes
### AmazonS3
* **New:** Support for server-side encryption has been added to the SDK.


----

# Changelog: 1.4.3 "Ultros"
<http://finalfantasy.wikia.com/wiki/Ultros>

Launched Friday, September 30, 2011

## Service Classes
### AmazonCloudFormation
* **New:** Support for new features in CloudFormation have been added to the SDK.

### AmazonS3
* **Fixed:** Setting the default cache configuration no longer causes authentication errors in `AmazonS3`.


----

# Changelog: 1.4.2.1 "Tiamat, Part II"
<http://finalfantasy.wikia.com/wiki/Tiamat>

Launched Wednesday, September 7, 2011

## Utility Classes
### RequestCore
* **Fixed:** RequestCore has updated the `cacert.pem` file from Mozilla. This update revokes trust from the DigiNotar and Staat der Nederlanden root certificates.


----

# Changelog: 1.4.2 "Tiamat"
<http://finalfantasy.wikia.com/wiki/Tiamat>

Launched Thursday, September 1, 2011

## Service Classes
### AmazonEC2
* **Fixed:** Requests made to Amazon EC2 now use the correct API version (2011-07-15).

### AmazonELB
* **New:** A pre-defined set of ciphers may now be used for SSL termination at the Elastic Load Balancer.
* **New:** Application servers can now accept secure communication from the corresponding Elastic Load Balancer.
* **New:** In cases where HTTPS is required for all traffic entering the back-end server, Elastic Load Balancing can now perform health checks using HTTPS.
* **New:** White list of public keys can now be associated with back-end servers. Elastic Load Balancing authenticates back-end servers with the public keys in the white list and communicates only with back-end servers that pass this authentication check.

## Utility Classes
### RequestCore
* **Fixed:** RequestCore has updated the `cacert.pem` file from Mozilla. This update revokes trust from the DigiNotar root certificate.


----

# Changelog: 1.4.1 "Sephiroth"
<http://finalfantasy.wikia.com/wiki/Sephiroth>

Launched Tuesday, August 23, 2011

## Service Classes
### AmazonElastiCache
* **New:** Support for Amazon ElastiCache has been added to the SDK.

### AmazonEMR
* **New:** Support for Hadoop Bootstrap Actions has been added to the SDK.
* **New:** Support for Amazon Elastic MapReduce on Spot Instances has been added to the SDK.
* **New:** Support for Termination Protection has been added to the SDK.
* **Changed:** For the <code>add_instance_groups()</code> method, the <code>$instance_groups</code> and <code>$job_flow_id</code> parameters have been reversed.

## Utility Classes
### CFHadoopBootstrap
* **New:** The `CFHadoopBootstrap` class has been added to the SDK. Simplifies the process of working with Hadoop system and daemon configurations in Amazon EMR.
* **New:** This class extends from the `CFHadoopBase` class.


----

# Changelog: 1.4 "Rikku"
<http://finalfantasy.wikia.com/wiki/Rikku>

Launched Wednesday, August 3, 2011

## Bug fixes and enhancements

## Service Classes
### AmazonEC2
* **New:** Support for Session-Based Authentication (SBA) leveraging Amazon Secure Token Service (STS) has been added to the SDK.

### AmazonS3
* **New:** Support for Session-Based Authentication (SBA) leveraging Amazon Secure Token Service (STS) has been added to the SDK.

### AmazonSNS
* **New:** Support for Session-Based Authentication (SBA) leveraging Amazon Secure Token Service (STS) has been added to the SDK.

### AmazonSQS
* **New:** Support for Session-Based Authentication (SBA) leveraging Amazon Secure Token Service (STS) has been added to the SDK.

### AmazonSTS
* **New:** Support for the Amazon Secure Token Service (STS) has been added to the SDK.

## Utility Classes
### CFRuntime
* **New:** The following anonymous datapoints are now collected in aggregate so that we can make more informed decisions about future SDK features: `memory_limit`, `date.timezone`, `open_basedir`, `safe_mode`, `zend.enable_gc`.

## Compatibility Test
* **New:** Support for verifying the installed SSL certificate has been added to the compatibility test.
* **New:** Support for verifying the status of `open_basedir` and `safe_mode` has been added to the compatibility test.
* **New:** Support for verifying the status of the PHP 5.3 garbage collector has been added to the compatibility test.
* **New:** The compatibility test now recommends optimal values for the `AWS_CERTIFICATE_AUTHORITY` and `AWS_DEFAULT_CACHE_CONFIG` configuration options based on the system's configuration.


----

# Changelog: 1.3.7 "Quistis"
<http://finalfantasy.wikia.com/wiki/Quistis_Trepe>

Launched Monday, July 25, 2011

## Bug fixes and enhancements
* Addressed minor bug fixes reported via the feedback form in the API Reference.

## Service Classes
### AmazonAS
* **Changed:** Introduced backwards-incompatible changes to the <code>put_scheduled_update_group_action()</code> method.


----

# Changelog: 1.3.6 "Penelo"
<http://finalfantasy.wikia.com/wiki/Penelo>

Launched Tuesday, July 12, 2011

## Bug fixes and enhancements
* [[Bug Report] rawurlencode error when using SES and curlopts](https://forums.aws.amazon.com/thread.jspa?threadID=68484)

## Service Classes
### AmazonCloudFormation
* **New:** Support for the `list_stacks()` method has been added to the SDK.

### AmazonElasticBeanstalk
* **New:** Support for the `swap_environment_cnames()` method has been added to the SDK.

### AmazonS3
* **Fixed:** Additional information about maximum open connections has been added to the `create_mpu_object()` method.

## Compatibility Test
* **New:** Now tests whether the system is 64- or 32-bit.


----

# Changelog: 1.3.5 "Occuria"
<http://finalfantasy.wikia.com/wiki/Occuria>

Launched Tuesday, June 21, 2011

## Service Classes
### AmazonS3
* **New:** Support for S3 copy part has been added to the SDK.


----

# Changelog: 1.3.4 "Nero"
<http://finalfantasy.wikia.com/wiki/Nero>

Launched Tuesday, June 7, 2011

## Bug fixes and enhancements
* [Bug in PHP SDK](https://forums.aws.amazon.com/thread.jspa?threadID=67502)
* [cURL error: SSL certificate problem (60) with aws-sdk-for-php 1.3.3](https://forums.aws.amazon.com/thread.jspa?threadID=68349)


## Service Classes
### AmazonEC2
* **New:** Support for Local Availability Zone Pricing has been added to the SDK.

### AmazonELB
* **New:** Elastic Load Balancing provides a special Amazon EC2 security group that you can use to ensure that a back-end Amazon EC2 instance receives traffic only from its load balancer.

### AmazonRDS
* **New:** Support for Oracle databases has been added to the SDK.


## Utility Classes
### CFArray
* **New:** Added the init() method which simplifies the process of instantiating and chaining a class.
* **New:** Added support for associative arrays to `each()`, `map()` and `filter()`.

### CFRequest
* **New:** Now supports the `AWS_CERTIFICATE_AUTHORITY` configuration option.


----

# Changelog: 1.3.3 "Moogle"
<http://finalfantasy.wikia.com/wiki/Moogle>

Launched Tuesday, May 10, 2011

## Bug fixes and enhancements
* [Bug in AmazonCloudFront::get_private_object_url](https://forums.aws.amazon.com/thread.jspa?threadID=64004)
* [SDK 1.3.2 - Call to undefined function json_last_error()](https://forums.aws.amazon.com/thread.jspa?threadID=64767)
* [CURLOPT_FOLLOWLOCATION cannot be activated when in safe_mode or an open_basedir](https://forums.aws.amazon.com/thread.jspa?threadID=61333)


## Service Classes
### AmazonCloudFront
* **Fixed:** Resolved an issue where the expires value for `get_private_object_url()` only accepted a string instead of a string or integer.

### AmazonCloudWatch
* **New:** Support for CloudWatch custom user metrics has been added to the SDK.


## Extensions
### S3BrowserUpload
* **New:** Added the `S3BrowserUpload` class to the SDK. This class assists in generating the correct HTML/XHTML markup for uploading files to S3 via an HTML <form> element.


## Utility Classes
### CFArray
* **New:** Added the `init()` method which simplifies the process of instantiating and chaining a class.

### CFHadoopBase
* **New:** The `CFHadoopBase` class has been extracted out of `CFHadoopStep` as a shared library.

### CFHadoopStep
* **New:** The `CFHadoopBase` class has been extracted out of `CFHadoopStep` as a shared library.
* **New:** This class now extends from the `CFHadoopBase` class.

### CFJSON
* **Fixed:** Resolved an issue where a PHP 5.3-specific function was being used.

### CFPolicy
* **New:** Added the init() method which simplifies the process of instantiating and chaining a class.

### CFSimpleXML
* **New:** Added the init() method which simplifies the process of instantiating and chaining a class.

### RequestCore
* **Fixed:** Improvements to running in PHP environments with open_basedir enabled.
* **Fixed:** RequestCore now uses an up-to-date `cacert.pem` file from Mozilla instead of the Certificate Authority that libcurl or libopenssl was compiled with, which should resolve certain issues with making SSL connections to AWS services.


----

# Changelog: 1.3.2 "Luna"
<http://finalfantasy.wikia.com/wiki/Luna_Wolf>

Launched Tuesday, April 5, 2011

## New Features & Highlights (Summary)
* Support for Dedicated Instances within a Virtual Private Cloud on single-tenant hardware has been added to the SDK.
* Bug fixes and enhancements:
	* [AmazonCloudWatch get_metric_statistics returns gzipped body](https://forums.aws.amazon.com/thread.jspa?threadID=62625)


## Service Classes
### AmazonCloudWatch
* **Fixed:** Worked around an issue where when CloudWatch sends back `Content-Encoding: gzip`, it really means `deflate`. When CloudWatch sends back `Content-Encoding: deflate`, it really means the data isn't encoded at all.

### AmazonEC2
* **New:** Support for Dedicated Instances within a Virtual Private Cloud on single-tenant hardware has been added to the SDK.


----

# Changelog: 1.3.1 "Kraken"
<http://finalfantasy.wikia.com/wiki/Kraken>

Launched Friday, March 25, 2011

## New Features & Highlights (Summary)
* Fixed issues with Signature v3 authentication (SES).
* Added gzip decoding.
* Added support for converting data to more alternate formats.
* Bug fixes and enhancements:
	* [Cannot send email](https://forums.aws.amazon.com/thread.jspa?threadID=62833)
	* [AmazonCloudWatch get_metric_statistics returns gzipped body](https://forums.aws.amazon.com/thread.jspa?threadID=62625)


## Utility Classes
### CFArray
* **New:** The `to_json()` and `to_yaml()` methoda have been added to the class.

### CFGzipDecode
* **New:** Handles a variety of primary and edge cases around gzip/deflate decoding in PHP.

### CFRuntime
* **New:** Gzip decoding has been added to the SDK.
* **Fixed:** The previous release contained a regression in the Signature v3 support that affected AmazonSES. This has been resolved.
* **Fixed:** Completed support for Signature v3 over HTTP connections.

### CFSimpleXML
* **New:** The `to_stdClass()` and `to_yaml()` methoda have been added to the class.


----

# Changelog: 1.3 "Jecht"
<http://finalfantasy.wikia.com/wiki/Jecht>

Launched Tuesday, March 15, 2011

## New Features & Highlights (Summary)
* Support for VPC Internet Access has been added to the SDK.
* Bug fixes and enhancements:
	* [AmazonEC2::register_image issue](https://forums.aws.amazon.com/thread.jspa?threadID=52499)
	* [Automatic Parseing of XML objects](https://forums.aws.amazon.com/thread.jspa?threadID=61882)

## Service Classes
### AmazonEC2
* **New:** Support for VPC Internet Access has been added to the SDK.
* **Fixed:** The `$image_location` parameter in the `register_image()` method is no longer required. This is a backwards-incompatible change.

### AmazonS3
* **Fixed:** Resolved an issue in `get_object()` where using the `lastmodified` and `etag` parameters required both to be set before taking effect. They can now be set independently from each other.


## Utility classes
### CFArray
* **Changed:** The `reduce()` method has been renamed to `filter()`. `reduce()` is now simply an alias for `filter()`.

### CFJSON
* **New:** Simplifies the task of normalizing XML and JSON responses as `CFSimpleXML` objects.

### CFRuntime
* **New:** Preliminary support for Signature v3 over HTTP has been added to the SDK. This is useful for debugging Signature v3 issues over non-HTTPS connections.
* **Changed:** Classes that use the shared authentication method (i.e., NOT `AmazonS3` or `AmazonCloudFront`) will automatically convert JSON service responses into a `CFSimpleXML` object.
* **Changed:** Formerly, the SDK would attempt to sniff the content to determine the type. Now, the SDK will check the HTTP response headers for `text/xml`, `application/xml` or `application/json` to determine whether or not to parse the content. If the HTTP response headers are not available, the SDK will still attempt content sniffing.

### CFSimpleXML
* **New:** The `to_json()` method has been added to the class.

### CFUtilities
* **New:** The `is_json()` method has been added to the class.


----

# Changelog: 1.2.6 "Ifrit"
<http://finalfantasy.wikia.com/wiki/Ifrit>

Launched Wednesday, March 2, 2011

## New Features & Highlights (Summary)
* **New:** Support for the new Asia Pacific "Northeast" (Japan) endpoint has been added for all relevant services.
* **New:** Support for registering callback functions for read/write streams has been added to the SDK. Includes a runnable sample.
* **Fixed:** Improvements to avoid triggering warnings when PHP is in Safe Mode.


## Service Classes
### AmazonAS
* **New:** Added a new _class_ constant: `REGION_APAC_NE1`.

### AmazonCloudFormation
* **New:** Added a new _class_ constant: `REGION_APAC_NE1`.

### AmazonCloudWatch
* **New:** Added a new _class_ constant: `REGION_APAC_NE1`.

### AmazonEC2
* **New:** Added a new _class_ constant: `REGION_APAC_NE1`.

### AmazonELB
* **New:** Added a new _class_ constant: `REGION_APAC_NE1`.

### AmazonRDS
* **New:** Added a new _class_ constant: `REGION_APAC_NE1`.

### AmazonS3
* **New:** Added a new _class_ constant: `REGION_APAC_NE1`.
* **New:** Added support for `ap-northeast-1` as a location constraint when creating a new bucket.

### AmazonSDB
* **New:** Added a new _class_ constant: `REGION_APAC_NE1`.

### AmazonSNS
* **New:** Added a new _class_ constant: `REGION_APAC_NE1`.

### AmazonSQS
* **New:** Added a new _class_ constant: `REGION_APAC_NE1`.

## Utility classes
### CFRuntime
* **New:** Support for registering callback functions for read/write streams has been added to the SDK.
* **New:** Future-proofed for future regional endpoints.

### RequestCore
* **New:** Support for registering callback functions for read/write streams has been added to the SDK.
* **Fixed:** Improvements to avoid triggering warnings when PHP is in Safe Mode.

## Samples
* **New:** A sample demonstrating how to add a command-line progress bar for S3 transfers has been added to the SDK.


----

# Changelog: 1.2.5 "Heidegger"
<http://finalfantasy.wikia.com/wiki/Heidegger>

Launched Thursday, February 24, 2011

## New Features & Highlights (Summary)
* Support for AWS CloudFormation has been added to the SDK.
* Bug fixes and enhancements:
	* [PHP API change_content_type() broken](https://forums.aws.amazon.com/thread.jspa?threadID=59532)
	* [Bug setting OriginAccessIdentity for a Cloudfront distribution config](https://forums.aws.amazon.com/thread.jspa?threadID=60989)

## Service Classes
### AmazonCloudFormation
* **New:** Support for AWS CloudFormation has been added to the SDK.

### AmazonCloudFront
* **Fixed:** Issues around `update_xml_config()` have been resolved.

### AmazonS3
* **Fixed:** Issues around `change_content_type()` have been resolved.


----

# Changelog: 1.2.4 "Goltanna"
<http://finalfantasy.wikia.com/wiki/Druksmald_Goltanna>

Launched Wednesday, February 16, 2011

## New Features & Highlights (Summary)
* Support for IAM account aliases and server certificates has been added to the SDK.
* Support for Amazon S3 Website Configuration has been added to the SDK.
* Documentation updates for Amazon RDS and AWS Import/Export.
* Updated all documentation blocks to adhere to the PHPDoc format. This enables a greater number of tools to take advantage of the SDK documentation.
* Rolled out a major update to the SDK API Reference.

## Service Classes
### AmazonIAM
* **New:** Support for IAM account aliases and server certificates has been added to the SDK.

### AmazonImportExport
* **New:** Documentation has been updated to note the new US West region support.

### AmazonRDS
* **New:** Documentation has been updated to note the new support for MySQL 5.5.

### AmazonS3
* **New:** Support for Amazon S3 Website Configuration has been added to the SDK.


----

# Changelog: 1.2.3 "Fayth"
<http://finalfantasy.wikia.com/wiki/Fayth>

Launched Tuesday, January 25, 2010

## New Features & Highlights (Summary)
* Support for Amazon Simple Email Service has been added to the SDK.

## Service Classes
### AmazonSES
* **New:** Support for Amazon Simple Email Service has been added to the SDK.


----

# Changelog: 1.2.2 "Esper"
<http://finalfantasy.wikia.com/wiki/Esper>

Launched Tuesday, January 18, 2011

## New Features & Highlights (Summary)
* Support for Amazon Elastic Beanstalk has been added to the SDK.
* Bug fixes and enhancements:
	* [AWS PHP S3 Library is not working out of the box](https://forums.aws.amazon.com/thread.jspa?threadID=55174)
	* [Problem with create_mpu_object() and streaming_read_callback() in S3](https://forums.aws.amazon.com/thread.jspa?threadID=54541)
	* [Integrated Uranium235's GitHub contributions](https://github.com/Uranium235/aws-sdk-for-php/compare/Streaming)

## Service Classes
### AmazonElasticBeanstalk
* **New:** Support for AWS Elastic Beanstalk has been added to the SDK.

### AmazonS3
* **Fixed:** Major improvements to transferring data over streams.

## Utility classes
###RequestCore
* **New:** Upgraded to version 1.4.
* **Fixed:** Major improvements to transferring data over streams.


----

# Changelog: 1.2.1 "Dio"
<http://finalfantasy.wikia.com/wiki/Dio>

Launched Friday, January 14, 2011


## New Features & Highlights (Summary)
* Support for S3 Response Headers has been added to the SDK.
* Bug fixes and enhancements:
	* [copy_object failed between regions](https://forums.aws.amazon.com/thread.jspa?threadID=56893)
	* [Possible S3 bug with multiple buckets?](https://forums.aws.amazon.com/thread.jspa?threadID=56561)

## Service Classes
### AmazonS3
* **New:** Support for S3 Response Headers has been added to the SDK.
* **New:** Documentation for Amazon S3 has been updated to include large object support details.
* **New:** The `abort_multipart_uploads_by_date()` method has been added to the SDK, which aborts multipart uploads that were initiated before a specific date.
* **Fixed:** Resolved an issue where the resource prefix wasn't being reset correctly.

## Utility classes
### CFArray
* **New:** Instantiating the class without passing an array will use an empty array instead.
* **New:** Added the `compress()` method which removes null values from the array.
* **New:** Added the `reindex()` method which reindexes all array elements starting at zero.

## Compatibility Test
* **New:** The command-line compatibility test now color-codes the responses.


----

# Changelog: 1.2 "Cloud"
<http://finalfantasy.wikia.com/wiki/Cloud_Strife>

Launched Friday, December 3, 2010


## New Features & Highlights (Summary)
* Support for Amazon AutoScaling, Amazon Elastic MapReduce, and Amazon Import/Export Service has been added to the SDK.
* Support for metric alarms has been added to Amazon CloudWatch.
* Support for batch deletion has been added to Amazon SimpleDB.
* Bug fixes and enhancements:
	* [EU Region DNS problem](https://forums.aws.amazon.com/thread.jspa?threadID=53028)
	* [[SimpleDB] Conditional PUT](https://forums.aws.amazon.com/thread.jspa?threadID=55884)
	* [Suggestions for the PHP SDK](https://forums.aws.amazon.com/thread.jspa?threadID=55210)
	* [Updating a distribution config](https://forums.aws.amazon.com/thread.jspa?threadID=54888)
	* [Problem with curlopt parameter in S3](https://forums.aws.amazon.com/thread.jspa?threadID=54532)
	* [AmazonS3::get_object_list() doesn't consider max-keys option](https://forums.aws.amazon.com/thread.jspa?threadID=55169)

## Base/Runtime class
* **New:** Added support for an alternate approach to instantiating classes which allows for method chaining (PHP 5.3+).
* **Changed:** Moved `CHANGELOG.md`, `CONTRIBUTORS.md`, `LICENSE.md` and `NOTICE.md` into a new `_docs` folder.
* **Changed:** Renamed the `samples` directory to `_samples`.
* **Changed:** Changed the permissions for the SDK files from `0755` to `0644`.
* **Fixed:** Resolved an issue where attempting to merge cURL options would fail.

## Service Classes
### AmazonAS
* **New:** Support for the Amazon AutoScaling Service has been added to the SDK.

### AmazonCloudFront
* **Fixed:** Resolved an issue where the incorrect formatting of an XML element prevented the ability to update the list of trusted signers.

### AmazonCloudWatch
* **New:** Support for the Amazon CloudWatch `2010-08-01` service release expands Amazon's cloud monitoring offerings with custom alarms.
* **Changed:** The changes made to the `get_metric_statistics()` method are backwards-incompatible with the previous release. The `Namespace` and `Period` parameters are now required and the parameter order has changed.

### AmazonEMR
* **New:** Support for the Amazon Elastic MapReduce Service has been added to the SDK.

### AmazonImportExport
* **New:** Support for the Amazon Import/Export Service has been added to the SDK.

### AmazonS3
* **Fixed:** Resolved an issue in the `create_bucket()` method that caused the regional endpoint to be reset to US-Standard.
* **Fixed:** Resolved an issue in the `get_object_list()` method where the `max-keys` parameter was ignored.

### AmazonSDB
* **New:** Support for `BatchDeleteAttributes` has been added to the SDK.
* **Fixed:** Resolved an issue where the `Expected` condition was not respected by `put_attributes()` or `delete_attributes()`.


## Utility classes
### CFComplexType
* **New:** You can now assign a `member` parameter to prefix all list identifiers.
* **Changed:** The `option_group()` method is now `public` instead of `private`.
* **Changed:** Rewrote the `to_query_string()` method to avoid the use of PHP's `http_build_query()` function because it uses `urlencode()` internally instead of `rawurlencode()`.

### CFHadoopStep
* **New:** Simplifies the process of working with Hadoop steps in Amazon EMR.

### CFManifest
* **New:** Simplifies the process of constructing YAML manifest documents for Amazon Import/Export Service.

### CFStepConfig
* **New:** Simplifies the process of working with step configuration in Amazon EMR.


## Third-party Libraries
### CacheCore
* **Changed:** The `generate_timestamp()` method is now `protected` instead of `private`.


----

# Changelog: 1.1 "Barret"
<http://finalfantasy.wikia.com/wiki/Barret_Wallace>

Launched Wednesday, November 10, 2010


## New Features & Highlights (Summary)
* Support for Amazon ELB, Amazon RDS and Amazon VPC has been added to the SDK.
* Support for the Amazon S3 multipart upload feature has been added to the SDK. This feature enables developers upload large objects in a series of requests for improved upload reliability.
* Support for the Amazon CloudFront custom origin (2010-11-01 release) feature has been added to the SDK. This feature enables developers to use custom domains as sources for Amazon CloudFront distributions.
* The `AmazonS3` class now supports reading from and writing to open file resources in addition to the already-supported file system paths.
* You can now seek to a specific byte-position within a file or file resource and begin streaming from that point when uploading or downloading objects.
* The methods `get_bucket_filesize()`, `get_object_list()`, `delete_all_objects()` and `delete_all_object_versions()` are no longer limited to 1000 entries and will work correctly for all entries.
* Requests that have errors at the cURL level now throw exceptions containing the error message and error code returned by cURL.
* Bug fixes and enhancements:
	* [Bug in Samples](http://developer.amazonwebservices.com/connect/thread.jspa?threadID=52748)
	* [EU Region DNS problem](http://developer.amazonwebservices.com/connect/thread.jspa?threadID=53028)
	* [AmazonS3 get_bucket_object_count](http://developer.amazonwebservices.com/connect/thread.jspa?threadID=52976)
	* [S3: get_object_list() fatal error](http://developer.amazonwebservices.com/connect/thread.jspa?threadID=53418)
	* [S3 get_object_metadata() problems](http://developer.amazonwebservices.com/connect/thread.jspa?threadID=54244)
	* [Bug in authenticate in sdk.class.php](http://developer.amazonwebservices.com/connect/thread.jspa?threadID=53117)
	* [How to use Prefix with "get_object_list"?](http://developer.amazonwebservices.com/connect/thread.jspa?threadID=52987)
	* [SignatureDoesNotMatch with utf-8 in SimpleDB](http://developer.amazonwebservices.com/connect/thread.jspa?threadID=52798)
	* [Suggestion for the PHP SDK concerning streaming](http://developer.amazonwebservices.com/connect/thread.jspa?threadID=52787)
	* [get_bucket_filesize only returns filesize for first 1000 objects](http://developer.amazonwebservices.com/connect/thread.jspa?threadID=53786)


## Base/Runtime class
* **Changed:** Port numbers other than 80 and 443 are now part of the signature.
* **Changed:** When putting UTF-8 characters via HTTP `POST`, a `SignatureDoesNotMatch` error would be returned. This was resolved by specifying the character set in the `Content-Type` header.


## Service Classes
### AmazonCloudFront
* **New:** Support for the Amazon CloudFront non-S3 origin feature (2010-11-01 release) has been added to the SDK. This feature enables developers to use non-S3 domains as sources for Amazon CloudFront distributions.

### AmazonEC2
* **New:** Support for Amazon Virtual Private Cloud has been added to the SDK.

### AmazonELB
* **New:** Support for Amazon Elastic Load Balancing Service has been added to the SDK.

### AmazonIAM
* **Fixed:** Removed `set_region()` as IAM only supports a single endpoint.

### AmazonRDS
* **New:** Support for Amazon Relational Database Service has been added to the SDK.

### AmazonS3
* **New:** Support for the Amazon S3 multipart upload feature has been added to the SDK. This feature enables developers upload large objects in a series of requests for improved upload reliability.
* **New:** The `fileUpload` and `fileDownload` options now support reading from and writing to open file resources in addition to the already-supported file system paths.
* **Fixed:** In Amazon S3, requests directly to the eu-west endpoint must use the path-style URI. The set_region() method now takes this into account.
* **Fixed:** As of version 1.0.1, CFSimpleXML extends SimpleXMLIterator instead of SimpleXMLElement. This prevented the `__call()` magic method from firing when `get_object_list()` was used.
* **Fixed:** The `preauth` option for the `get_object_list()` method has been removed from the documentation as it is not supported.
* **Fixed:** The methods `get_bucket_filesize()`, `get_object_list()`, `delete_all_objects()` and `delete_all_object_versions()` are no longer limited to 1000 entries and will work correctly for all entries.
* **Fixed:** Using `delete_bucket()` to force-delete a bucket now works correctly for buckets with more than 1000 versions.
* **Fixed:** The response from the `get_object_metadata()` method now includes all supported HTTP headers, including metadata stored in `x-amz-meta-` headers.
* **Fixed:** Previously, if the `get_object_metadata()` method was called on a non-existant object, metadata for the alphabetically-next object would be returned.

### AmazonSQS
* **New:** The `get_queue_arn()` method has been added to the `AmazonSQS` class, which converts a queue URI to a queue ARN.


## Utility classes
### CFSimpleXML
* **New:** Added `to_string()` and `to_array()` methods.


## Third-party Libraries
### RequestCore
* **New:** Upgraded to version 1.3.
* **New:** Added `set_seek_position()` for seeking to a byte-position in a file or file resource before starting an upload.
* **New:** Added support for reading from and writing to open file resources.
* **Fixed:** Improved the reporting for cURL errors.


## Compatibility Test
* **Fixed:** Fixed the links to the Getting Started Guide.


----

# Changelog: 1.0.1 "Aeris"
<http://finalfantasy.wikia.com/wiki/Aerith_Gainsborough>

Launched Tuesday, October 12, 2010


## New Features & Highlights (Summary)
* Improved support for running XPath queries against the service response bodies.
* Added support for request retries and exponential backoff.
* Added support for HTTP request/response header logging.
* Bug fixes and enhancements:
	* [Bug in Samples](http://developer.amazonwebservices.com/connect/thread.jspa?threadID=52748)
	* [Can't set ACL on object using the SDK](http://developer.amazonwebservices.com/connect/thread.jspa?threadID=52305)
	* [Range requests for S3 - status codes 200, 206](http://developer.amazonwebservices.com/connect/thread.jspa?threadID=52738)
	* [S3 change_storage_redundancy() function clears public-read ACL](http://developer.amazonwebservices.com/connect/thread.jspa?threadID=52652)


## Base/Runtime class
* **New:** Added support for request retries and exponential backoff for all `500` and `503` HTTP status codes.
* **New:** Added the `enable_debug_mode()` method to enable HTTP request/response header logging to `STDERR`.


## Service Classes
### AmazonS3
* **Fixed:** Lots of tweaks to the documentation.
* **Fixed:** The `change_content_type()`, `change_storage_redundancy()`, `set_object_acl()`, and `update_object()` methods now respect the existing content-type, storage redundancy, and ACL settings when updating.
* **New:** Added the `get_object_metadata()` method has been added as a singular interface for obtaining all available metadata for an object.


## Utility Classes
### CFArray
* **New:** Added the `each()` method which accepts a callback function to execute for each entry in the array. Works similarly to [jQuery's each()](http://api.jquery.com/each).
* **New:** Added the `map()` method which accepts a callback function to execute for each entry in the array. Works similarly to [jQuery's map()](http://api.jquery.com/map).
* **New:** Added the `reduce()` method which accepts a callback function to execute for each entry in the array. Works similarly to [DomCrawler reduce()](http://github.com/symfony/symfony/blob/master/src/Symfony/Component/DomCrawler/Crawler.php) from the [Symfony 2](http://symfony-reloaded.org) Preview Release.
* **New:** Added the `first()` and `last()` methods to return the first and last nodes in the array, respectively.

### CFInfo
* **New:** Retrieves information about the current installation of the AWS SDK for PHP.

### CFSimpleXML
* **New:** Added the `query()` method, which allows for XPath queries while the results are wrapped in a `CFArray` response.
* **New:** Added the `parent()` method, which allows for traversing back up the document tree.
* **New:** Added the `stringify()` method, which typecasts the value as a string.
* **New:** Added the `is()` and `contains()` methods, which allow for testing whether the XML value is or contains a given value, respectively.
* **Changed:** Now extends the `SimpleXMLIterator` class, which in-turn extends the `SimpleXMLElement` class. This adds new iterator methods to the `CFSimpleXML` class.


## Third-party Libraries
### CacheCore
* **New:** Upgraded to version 1.2.
* **New:** Added a static `init` method that allows for chainable cache initialization (5.3+).

### RequestCore
* **New:** Added `206` as a successful status code (i.e., Range GET).


## Compatibility Test
* **Fixed:** Some of the links in the compatibility test were missing. These have been fixed.


----

# Changelog: AWS SDK for PHP 1.0

Launched Tuesday, September 28, 2010

This is a complete list of changes since we forked from the CloudFusion 2.5.x trunk build.


## New Features & Highlights (Summary)
* The new file to include is `sdk.class.php` rather than `cloudfusion.class.php`.
* Because of the increased reliance on [JSON](http://json.org) across AWS services, the minimum supported version is now PHP 5.2 ([Released in November 2006](http://www.php.net/ChangeLog-5.php#5.2.0); Justified by these [WordPress usage statistics](http://wpdevel.wordpress.com/2010/07/09/suggest-topics-for-the-july-15-2010-dev/comment-page-1/#comment-8542) and the fact that [PHP 5.2 has been end-of-life'd](http://www.php.net/archive/2010.php#id2010-07-22-1) in favor of 5.3).
* Up-to-date service support for [EC2](http://aws.amazon.com/ec2), [S3](http://aws.amazon.com/s3), [SQS](http://aws.amazon.com/sqs), [SimpleDB](http://aws.amazon.com/simpledb), [CloudWatch](http://aws.amazon.com/cloudwatch), and [CloudFront](http://aws.amazon.com/cloudfront).
* Added service support for [SNS](http://aws.amazon.com/sns).
* Limited testing for third-party API-compatible services such as [Eucalyptus](http://open.eucalyptus.com), [Walrus](http://open.eucalyptus.com) and [Google Storage](http://sandbox.google.com/storage).
* Improved the consistency of setting complex data types across services. (Required some backwards-incompatible changes.)
* Added new APIs and syntactic sugar for SimpleXML responses, batch requests and response caching.
* Moved away from _global_ constants in favor of _class_ constants.
* Minor, but notable improvements to the monkey patching support.
* Added a complete list of bug fix and patch contributors. Give credit where credit is due. ;)

**Note: ALL backwards-incompatible changes are noted below. Please review the changes if you are upgrading.** We're making a small number of backwards-incompatible changes in order to improve the consistency across services. We're making these changes _now_ so that we can ensure that future versions will always be backwards-compatible with the next major version change.


## File structure
The package file structure has been refined in a few ways:

* All service-specific classes are inside the `/services/` directory.
* All utility-specific classes are inside the `/utilities/` directory.
* All third-party classes are inside the `/lib/` directory.


## Base/Runtime class
* **Fixed:** Resolved issues: [#206](http://code.google.com/p/tarzan-aws/issues/detail?id=206).
* **New:** The following global constants have been added: `CFRUNTIME_NAME`, `CFRUNTIME_VERSION`, `CFRUNTIME_BUILD`, `CFRUNTIME_URL`, and `CFRUNTIME_USERAGENT`
* **New:** Now supports camelCase versions of the snake_case method names. (e.g. `getObjectList()` will get translated to `get_object_list()` behind the scenes.)
* **New:** Added `set_resource_prefix()` and `allow_hostname_override()` (in addition to `set_hostname()`) to support third-party, API-compatible services.
* **New:** Added new caching APIs: `cache()` and `delete_cache()`, which work differently from the methods they replace. See docs for more information.
* **New:** Added new batch request APIs, `batch()` and `CFBatchRequest` which are intended to replace the old `returnCurlHandle` optional parameter.
* **New:** Will look for the `config.inc.php` file first in the same directory (`./config.inc.php`), and then fallback to `~/.aws/sdk/config.inc.php`.
* **Changed:** Renamed the `CloudFusion` base class to `CFRuntime`.
* **Changed:** `CloudFusion_Exception` has been renamed as `CFRuntime_Exception`.
* **Changed:** Renamed the `CloudFusion::$enable_ssl` property to `CFRuntime::$use_ssl`.
* **Changed:** Renamed the `CloudFusion::$set_proxy` property to `CFRuntime::$proxy`.
* **Changed:** `CFRuntime::disable_ssl()` no longer takes any parameters. Once SSL is off, it is always off for that class instance.
* **Changed:** All date-related constants are now class constants of the `CFUtilities` class (e.g. `CFUtilities::DATE_FORMAT_ISO8601`).
	* Use `CFUtilities::konst()` if you're extending classes and need to do something such as `$this->util::DATE_FORMAT_ISO8601` but keep getting the `T_PAAMAYIM_NEKUDOTAYIMM` error.
* **Changed:** All `x-cloudfusion-` and `x-tarzan-` HTTP headers are now `x-aws-`.
* **Changed:** `CloudFusion::autoloader()` is now in its own separate class: `CFLoader::autoloader()`. This prevents it from being incorrectly inherited by extending classes.
* **Changed:** `RequestCore`, `ResponseCore` and `SimpleXMLElement` are now extended by `CFRequest`, `CFResponse` and `CFSimpleXML`, respectively. These new classes are now used by default.
* **Changed:** Changes to monkey patching:
	* You must now extend `CFRequest` instead of `RequestCore`, and then pass that class name to `set_request_class()`.
	* You must now extend `CFResponse` instead of `ResponseCore`, and then pass that class name to `set_response_class()`.
	* You can now monkey patch `CFSimpleXML` (extended from `SimpleXMLElement`) with `set_parser_class()`.
	* You can now monkey patch `CFBatchRequest` with `set_batch_class()`.
	* No changes for monkey patching `CFUtilities` with `set_utilities_class()`.
* **Removed:** Removed ALL existing _global_ constants and replaced them with _class_ constants.
* **Removed:** Removed `cache_response()` and `delete_cache_response()`.


## Service classes

### AmazonCloudFront
* **Fixed:** Resolved issues: [#124](http://code.google.com/p/tarzan-aws/issues/detail?id=124), [#225](http://code.google.com/p/tarzan-aws/issues/detail?id=225), [#229](http://code.google.com/p/tarzan-aws/issues/detail?id=229), [#232](http://code.google.com/p/tarzan-aws/issues/detail?id=232), [#239](http://code.google.com/p/tarzan-aws/issues/detail?id=239).
* **Fixed:** Fixed an issue where `AmazonCloudFront` sent a `RequestCore` user agent in requests.
* **New:** Class is now up-to-date with the [2010-07-15](http://docs.amazonwebservices.com/AmazonCloudFront/2010-07-15/APIReference/) API release.
* **New:** Added _class_ constants for deployment states: `STATE_INPROGRESS` and `STATE_DEPLOYED`.
* **New:** Now supports streaming distributions.
* **New:** Now supports HTTPS (as well as HTTPS-only) access.
* **New:** Now supports Origin Access Identities. Added `create_oai()`, `list_oais()`, `get_oai()`, `delete_oai()`, `generate_oai_xml()` and `update_oai_xml()`.
* **New:** Now supports private (signed) URLs. Added `get_private_object_url()`.
* **New:** Now supports default root objects.
* **New:** Now supports invalidation.
* **New:** Added `get_distribution_list()`, `get_streaming_distribution_list()` and `get_oai_list()` which return simplified arrays of identifiers.
* **Changed:** Replaced all of the remaining `CDN_*` constants with _class_ constants.

### AmazonCloudWatch
* **New:** Added new _class_ constants: `DEFAULT_URL`, `REGION_US_E1`, `REGION_US_W1`, `REGION_EU_W1`, and `REGION_APAC_SE1`.
* **New:** Now supports the _Northern California_, _European_ and _Asia-Pacific_ regions.
* **New:** The _global_ `CW_DEFAULT_URL` constant has been replaced by `AmazonCloudFront::DEFAULT_URL`.

### AmazonEC2
* **Fixed:** Resolved issues: [#124](http://code.google.com/p/tarzan-aws/issues/detail?id=124), [#131](http://code.google.com/p/tarzan-aws/issues/detail?id=131), [#138](http://code.google.com/p/tarzan-aws/issues/detail?id=138), [#139](http://code.google.com/p/tarzan-aws/issues/detail?id=139), [#154](http://code.google.com/p/tarzan-aws/issues/detail?id=154), [#173](http://code.google.com/p/tarzan-aws/issues/detail?id=173), [#200](http://code.google.com/p/tarzan-aws/issues/detail?id=200), [#233](http://code.google.com/p/tarzan-aws/issues/detail?id=233).
* **New:** Class is now up-to-date with the [2010-06-15](http://docs.amazonwebservices.com/AWSEC2/2010-06-15/APIReference/) API release.
* **New:** Now supports [Paid AMIs](http://developer.amazonwebservices.com/connect/entry.jspa?externalID=865&categoryID=87).
* **New:** Now supports [Multiple instance types](http://developer.amazonwebservices.com/connect/entry.jspa?externalID=992&categoryID=87).
* **New:** Now supports [Elastic IPs](http://developer.amazonwebservices.com/connect/entry.jspa?externalID=1344&categoryID=87).
* **New:** Now supports [Availability Zones](http://developer.amazonwebservices.com/connect/entry.jspa?externalID=1344&categoryID=87).
* **New:** Now supports [Elastic Block Store](http://developer.amazonwebservices.com/connect/entry.jspa?externalID=1665&categoryID=87).
* **New:** Now supports [Windows instances](http://developer.amazonwebservices.com/connect/entry.jspa?externalID=1765&categoryID=87).
* **New:** Now supports the [European region](http://developer.amazonwebservices.com/connect/entry.jspa?externalID=1926&categoryID=87).
* **New:** Now supports the _Northern California_ and _Asia-Pacific_ regions.
* **New:** Now supports [Reserved instances](http://developer.amazonwebservices.com/connect/entry.jspa?externalID=2213&categoryID=87).
* **New:** Now supports [Shared snapshots](http://developer.amazonwebservices.com/connect/entry.jspa?externalID=2843&categoryID=87).
* **New:** Now supports [EBS AMIs](http://developer.amazonwebservices.com/connect/entry.jspa?externalID=3105&categoryID=87).
* **New:** Now supports [Spot instances](http://developer.amazonwebservices.com/connect/entry.jspa?externalID=3215&categoryID=87).
* **New:** Now supports [Cluster Compute Instances](http://developer.amazonwebservices.com/connect/entry.jspa?externalID=3965&categoryID=87).
* **New:** Now supports [Placement Groups](http://developer.amazonwebservices.com/connect/entry.jspa?externalID=3965&categoryID=87).
* **New:** Added new _class_ constants for regions: `REGION_US_E1`, `REGION_US_W1`, `REGION_EU_W1`, `REGION_APAC_SE1`.
* **New:** Added new _class_ constants for run-state codes: `STATE_PENDING`, `STATE_RUNNING`, `STATE_SHUTTING_DOWN`, `STATE_TERMINATED`, `STATE_STOPPING`, `STATE_STOPPED`.
* **New:** Added support for decrypting the Administrator password for Microsoft Windows instances.
* **New:** Instead of needing to pass `Parameter.0`, `Parameter.1`, ...`Parameter.n` individually to certain methods, you can now reliably pass a string for a single value or an indexed array for a list of values.
* **New:** Limited tested has been done with the Eucalyptus EC2-clone.
* **Changed:** The `$account_id` parameter has been removed from the constructor.
* **Changed:** The _global_ `EC2_LOCATION_US` and `EC2_LOCATION_EU` constants have been replaced.
* **Changed:** The `set_locale()` method has been renamed to `set_region()`. It accepts any of the region constants.

### AmazonIAM
* **New:** Up-to-date with the [2010-03-31](http://docs.amazonwebservices.com/sns/2010-03-31/api/) API release.

### AmazonS3
* **Fixed:** Resolved issues: [#31](http://code.google.com/p/tarzan-aws/issues/detail?id=31), [#72](http://code.google.com/p/tarzan-aws/issues/detail?id=72), [#123](http://code.google.com/p/tarzan-aws/issues/detail?id=123), [#156](http://code.google.com/p/tarzan-aws/issues/detail?id=156), [#199](http://code.google.com/p/tarzan-aws/issues/detail?id=199), [#201](http://code.google.com/p/tarzan-aws/issues/detail?id=201), [#203](http://code.google.com/p/tarzan-aws/issues/detail?id=203), [#207](http://code.google.com/p/tarzan-aws/issues/detail?id=207), [#208](http://code.google.com/p/tarzan-aws/issues/detail?id=208), [#209](http://code.google.com/p/tarzan-aws/issues/detail?id=209), [#210](http://code.google.com/p/tarzan-aws/issues/detail?id=210), [#212](http://code.google.com/p/tarzan-aws/issues/detail?id=212), [#216](http://code.google.com/p/tarzan-aws/issues/detail?id=216), [#217](http://code.google.com/p/tarzan-aws/issues/detail?id=217), [#226](http://code.google.com/p/tarzan-aws/issues/detail?id=226), [#228](http://code.google.com/p/tarzan-aws/issues/detail?id=228), [#234](http://code.google.com/p/tarzan-aws/issues/detail?id=234), [#235](http://code.google.com/p/tarzan-aws/issues/detail?id=235).
* **Fixed:** Fixed an issue where `AmazonS3` sent a `RequestCore` user agent in requests.
* **New:** Now supports the _Northern California_ and _Asia-Pacific_ regions.
* **New:** Now supports the new _EU (Ireland)_ REST endpoint.
* **New:** Now supports MFA Delete.
* **New:** Now supports Conditional Copy.
* **New:** Now supports Reduced Redundancy Storage (RRS). Added `change_storage_redundancy()`.
* **New:** Now supports Object Versioning. Added `enable_versioning()`, `disable_versioning`, `get_versioning_status()`, and `list_bucket_object_versions()`.
* **New:** Now supports Bucket Policies. Added `set_bucket_policy()`, `get_bucket_policy()`, and `delete_bucket_policy()`.
* **New:** Now supports Bucket Notifications. Added `create_bucket_notification()`, `get_bucket_notifications()`, and `delete_bucket_notification()`.
* **New:** Added _class_ constants for regions: `REGION_US_E1`, `REGION_US_W1`, `REGION_EU_W1`, `REGION_APAC_SE1`.
* **New:** Added _class_ constants for storage types: `STORAGE_STANDARD` and `STORAGE_REDUCED`.
* **New:** Enhanced `create_object()` with the ability to upload a file from the file system.
* **New:** Enhanced `get_object()` with the ability to download a file to the file system.
* **New:** Enhanced `get_bucket_list()` and `get_object_list()` with performance improvements.
* **New:** Enhanced all GET operations with the ability to generate pre-authenticated URLs. This is the same feature as `get_object_url()` has had, applied to all GET operations.
* **New:** Limited testing with Walrus, the Eucalyptus S3-clone.
* **New:** Limited testing with Google Storage.
* **Changed:** Replaced all of the remaining `S3_*` constants with _class_ constants: `self::ACL_*`, `self::GRANT_*`, `self::USERS_*`, and `self::PCRE_ALL`.
* **Changed:** Changed the function signature for `create_object()`. The filename is now passed as the second parameter, while the remaining options are now passed as the third parameter. This behavior now matches all of the other object-related methods.
* **Changed:** Changed the function signature for `head_object()`, `delete_object()`, and `get_object_acl()`. The methods now accept optional parameters as the third parameter instead of simply `returnCurlHandle`.
* **Changed:** Changed the function signature for `get_object_url()` and `get_torrent_url()`. Instead of passing a number of seconds until the URL expires, you now pass a string that `strtotime()` understands (including `60 seconds`).
* **Changed:** Changed the function signature for `get_object_url()`. Instead of passing a boolean value for `$torrent`, the last parameter is now an `$opt` variable which allows you to set `torrent` and `method` parameters.
* **Changed:** Changed how `returnCurlHandle` is used. Instead of passing `true` as the last parameter to most methods, you now need to explicitly set `array('returnCurlHandle' => true)`. This behavior is consistent with the implementation in other classes.
* **Changed:** Optional parameter names changed in `list_objects()`: `maxKeys` is now `max-keys`.
* **Changed:** `get_bucket_locale()` is now called `get_bucket_region()`, and returns the response body as a _string_ for easier comparison with class constants.
* **Changed:** `get_bucket_size()` is now called `get_bucket_object_count()`. Everything else about it is identical.
* **Changed:** `head_bucket()` is now called `get_bucket_headers()`. Everything else about it is identical.
* **Changed:** `head_object()` is now called `get_object_headers()`. Everything else about it is identical.
* **Changed:** `create_bucket()` has two backward-incompatible changes:
	* Method now **requires** the region (formerly _locale_) to be set.
	* Method takes an `$acl` parameter so that the ACL can be set directly when creating a new bucket.
* **Changed:** Bucket names are now validated. Creating a new bucket now requires the more stringent DNS-valid guidelines, while the process of reading existing buckets follows the looser path-style guidelines. This change also means that the reading of path-style bucket names is now supported, when previously they weren’t.
* **Removed:** Removed `store_remote_file()` because its intended usage repeatedly confused users, and had potential for misuse. If you were using it to upload from the local file system, you should be using `create_object` instead.
* **Removed:** Removed `copy_bucket()`, `replace_bucket()`, `duplicate_object()`, `move_object()`, and `rename_object()` because only a small number of users used them, and they weren't very robust anyway.
* **Removed:** Removed `get_bucket()` because it was just an alias for `list_objects()` anyway. Use the latter from now on -- it's identical.

### AmazonSDB
* **Fixed:** Resolved issues: [#205](http://code.google.com/p/tarzan-aws/issues/detail?id=205).
* **New:** Class is now up-to-date with the [2009-04-15](http://docs.amazonwebservices.com/AmazonSimpleDB/2009-04-15/DeveloperGuide/) API release.
* **Changed:** Changed the function signatures for `get_attributes()` and `delete_attributes()` to improve consistency.

### AmazonSNS
* **New:** Up-to-date with the [2010-03-31](http://docs.amazonwebservices.com/sns/2010-03-31/api/) API release.

### AmazonSQS
* **Fixed:** Resolved issues: [#137](http://code.google.com/p/tarzan-aws/issues/detail?id=137), [#213](http://code.google.com/p/tarzan-aws/issues/detail?id=213), [#219](http://code.google.com/p/tarzan-aws/issues/detail?id=219), [#220](http://code.google.com/p/tarzan-aws/issues/detail?id=220), [#221](http://code.google.com/p/tarzan-aws/issues/detail?id=221), [#222](http://code.google.com/p/tarzan-aws/issues/detail?id=222).
* **Fixed:** In CloudFusion 2.5, neither `add_permission()` nor `remove_permission()` were functional. They are now working.
* **New:** Now supports the _Northern California_ and _Asia-Pacific_ regions.
* **New:** Now supports the new _US-East (N. Virginia)_ endpoint.
* **New:** Now supports the new _EU (Ireland)_ endpoint.
* **New:** Added new _class_ constants for regions: `REGION_US_E1`, `REGION_US_W1`, `REGION_EU_W1`, and `REGION_APAC_SE1`.
* **Changed:** Because we now support multiple region endpoints, queue names alone are no longer sufficient for referencing your queues. As such, you must now use a full-queue URL instead of just the queue name.
* **Changed:** The _global_ `SQS_LOCATION_US` and `SQS_LOCATION_EU` constants have been replaced.
* **Changed:** Renamed `set_locale()` as `set_region()`. It accepts any of the region constants.
* **Changed:** Changed the function signature for `list_queues()`. See the updated API reference.
* **Changed:** Changed the function signature for `set_queue_attributes()`. See the updated API reference.
* **Changed:** Changed how `returnCurlHandle` is used. Instead of passing `true` as the last parameter to most methods, you now need to explicitly set `array('returnCurlHandle' => true)`. This behavior is consistent with the implementation in other classes.
* **Changed:** Function signature changed in `get_queue_attributes()`. The `$attribute_name` parameter is now passed as a value in the `$opt` parameter.

### AmazonSQSQueue
* **Removed:** `AmazonSQSQueue` was a simple wrapper around the AmazonSDB class. It generally failed as an object-centric approach to working with SQS, and as such, has been eliminated. Use the `AmazonSQS` class instead.


## Utility Classes
### CFArray
* **New:** Extends `ArrayObject`.
* **New:** Simplified typecasting of SimpleXML nodes to native types (e.g. integers, strings).

### CFBatchRequest
* **New:** Provides a higher-level API for executing batch requests.

### CFComplexType
* **New:** Used internally by several classes to handle various complex data-types (e.g. single or multiple values, `Key.x.Subkey.y.Value` combinations).
* **New:** Introduces a way to convert between JSON, YAML, and the PHP equivalent of Lists and Maps (nested associative arrays).

### CFRequest
* **New:** Sets some project-specific settings and passes them to the lower-level RequestCore.

### CFResponse
* **New:** No additional changes from the base `ResponseCore` class.

### CFPolicy
* **New:** Used for constructing Base64-encoded, JSON policy documents to be passed around to other methods.

### CFSimpleXML
* **New:** Extends `SimpleXMLElement`.
* **New:** Simplified node retrieval. All SimpleXML-based objects (e.g. `$response->body`) now have magic methods that allow you to quickly retrieve nodes with the same name
	* e.g. `$response->body->Name()` will return an array of all SimpleXML nodes that match the `//Name` XPath expression.

### CFUtilities
* **Fixed:** `to_query_string()` now explicitly passes a `&` character to `http_build_query()` to avoid configuration issues with MAMP/WAMP/XAMP installations.
* **Fixed:** `convert_response_to_array()` has been fixed to correctly return an all-array response under both PHP 5.2 and 5.3. Previously, PHP 5.3 returned a mix of `array`s and `stdClass` objects.
* **New:** Added `konst()` to retrieve the value of a class constant, while avoiding the `T_PAAMAYIM_NEKUDOTAYIM` error. Misspelled because `const` is a reserved word.
* **New:** Added `is_base64()` to determine whether or not a string is Base64-encoded data.
* **New:** Added `decode_uhex()` to decode `\uXXXX` entities back into their unicode equivalents.
* **Changed:** Changed `size_readable()`. Now supports units up to exabytes.
* **Changed:** Moved the `DATE_FORMAT_*` _global_ constants into this class as _class_ constants.
* **Removed:** Removed `json_encode_php51()` now that the minimum required version is PHP 5.2 (which includes the JSON extension by default).
* **Removed:** Removed `hex_to_base64()`.


## Third-party Libraries
### CacheCore
* **New:** Upgraded to version 1.1.1.
* **New:** Now supports both the [memcache](http://php.net/memcache) extension, but also the newer, faster [memcached](http://php.net/memcached) extension. Prefers `memcached` if both are installed.
* **Deprecated:** Support for MySQL and PostgreSQL as storage mechanisms has been **deprecated**. Since they're using PDO, they'll continue to function (as we're maintaining SQLite support via PDO), but we recommend migrating to using APC, XCache, Memcache or SQLite if you'd like to continue using response caching.
* New BSD licensed
* <http://github.com/skyzyx/cachecore>

### RequestCore
* **New:** Upgraded to version 1.2.
* **New:** Now supports streaming up and down.
* **New:** Now supports "rolling" requests for better scalability.
* New BSD licensed
* <http://github.com/skyzyx/requestcore>
