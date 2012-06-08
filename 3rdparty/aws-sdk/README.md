# AWS SDK for PHP

The AWS SDK for PHP enables developers to build solutions for Amazon Simple Storage Service (Amazon S3),
Amazon Elastic Compute Cloud (Amazon EC2), Amazon SimpleDB, and more. With the AWS SDK for PHP, developers
can get started in minutes with a single, downloadable package.

The SDK features:

* **AWS PHP Libraries:** Build PHP applications on top of APIs that take the complexity out of coding directly
  against a web service interface. The toolkit provides APIs that hide much of the lower-level implementation.
* **Code Samples:** Practical examples for how to use the toolkit to build applications.
* **Documentation:** Complete SDK reference documentation with samples demonstrating how to use the SDK.
* **PEAR package:** The ability to install the AWS SDK for PHP as a PEAR package.
* **SDK Compatibility Test:** Includes both an HTML-based and a CLI-based SDK Compatibility Test that you can
  run on your server to determine whether or not your PHP environment meets the minimum requirements.

For more information about the AWS SDK for PHP, including a complete list of supported services, see
[aws.amazon.com/sdkforphp](http://aws.amazon.com/sdkforphp).


## Signing up for Amazon Web Services

Before you can begin, you must sign up for each service you want to use.

To sign up for a service:

* Go to the home page for the service. You can find a list of services on
  [aws.amazon.com/products](http://aws.amazon.com/products).
* Click the Sign Up button on the top right corner of the page. If you don't already have an AWS account, you
  are prompted to create one as part of the sign up process.
* Follow the on-screen instructions.
* AWS sends you a confirmation e-mail after the sign-up process is complete. At any time, you can view your
  current account activity and manage your account by going to [aws.amazon.com](http://aws.amazon.com) and
  clicking "Your Account".


## Source
The source tree for includes the following files and directories:

* `_compatibility_test` -- Includes both an HTML-based and a CLI-based SDK Compatibility Test that you can
  run on your server to determine whether or not your PHP environment meets the minimum requirements.
* `_docs` -- Informational documents, the contents of which should be fairly self-explanatory.
* `_samples` -- Code samples that you can run out of the box.
* `extensions` -- Extra code that can be used to enhance usage of the SDK, but isn't a service class or a
  third-party library.
* `lib` -- Contains any third-party libraries that the SDK depends on. The licenses for these projects will
  always be Apache 2.0-compatible.
* `services` -- Contains the service-specific classes that communicate with AWS. These classes are always
  prefixed with `Amazon`.
* `utilities` -- Contains any utility-type methods that the SDK uses. Includes extensions to built-in PHP
  classes, as well as new functionality that is entirely custom. These classes are always prefixed with `CF`.
* `README` -- The document you're reading right now.
* `config-sample.inc.php` -- A sample configuration file that should be filled out and renamed to `config.inc.php`.
* `sdk.class.php` -- The SDK loader that you would include in your projects. Contains the base functionality
  that the rest of the SDK depends on.


## Minimum Requirements in a nutshell

* You are at least an intermediate-level PHP developer and have a basic understanding of object-oriented PHP.
* You have a valid AWS account, and you've already signed up for the services you want to use.
* The PHP interpreter, version 5.2 or newer. PHP 5.2.17 or 5.3.x is highly recommended for use with the AWS SDK for PHP.
* The cURL PHP extension (compiled with the [OpenSSL](http://openssl.org) libraries for HTTPS support).
* The ability to read from and write to the file system via [file_get_contents()](http://php.net/file_get_contents) and [file_put_contents()](http://php.net/file_put_contents).

If you're not sure whether your PHP environment meets these requirements, run the
[SDK Compatibility Test](http://github.com/amazonwebservices/aws-sdk-for-php/tree/master/_compatibility_test/) script
included in the SDK download.


## Installation

### Via GitHub

[Git](http://git-scm.com) is an extremely fast, efficient, distributed version control system ideal for the
collaborative development of software. [GitHub](http://github.com/amazonwebservices) is the best way to
collaborate with others. Fork, send pull requests and manage all your public and private git repositories.
We believe that GitHub is the ideal service for working collaboratively with the open source PHP community.

Git is primarily a command-line tool. GitHub provides instructions for installing Git on
[Mac OS X](http://help.github.com/mac-git-installation/), [Windows](http://help.github.com/win-git-installation/),
and [Linux](http://help.github.com/linux-git-installation/). If you're unfamiliar with Git, there are a variety
of resources on the net that will help you learn more:

* [Git Immersion](http://gitimmersion.com) is a guided tour that walks through the fundamentals of Git, inspired
  by the premise that to know a thing is to do it.
* The [PeepCode screencast on Git](https://peepcode.com/products/git) ($12) will teach you how to install and
  use Git. You'll learn how to create a repository, use branches, and work with remote repositories.
* [Git Reference](http://gitref.org) is meant to be a quick reference for learning and remembering the most
  important and commonly used Git commands.
* [Git Ready](http://gitready.com) provides a collection of Git tips and tricks.
* If you want to dig even further, I've [bookmarked other Git references](http://pinboard.in/u:skyzyx/t:git).

If you're comfortable working with Git and/or GitHub, you can pull down the source code as follows:

    git clone git://github.com/amazonwebservices/aws-sdk-for-php.git AWSSDKforPHP
    cd ./AWSSDKforPHP

### Via PEAR

[PEAR](http://pear.php.net) stands for the _PHP Extension and Application Repository_ and is a framework and
distribution system for reusable PHP components. It is the PHP equivalent to package management software such as
[MacPorts](http://macports.org) and [Homebrew](https://github.com/mxcl/homebrew) for Mac OS X,
[Yum](http://fedoraproject.org/wiki/Tools/yum) and [Apt](http://wiki.debian.org/Apt) for GNU/Linux,
[RubyGems](http://rubygems.org) for Ruby, [Easy Install](http://packages.python.org/distribute/easy_install.html)
for Python, [Maven](http://maven.apache.org) for Java, and [NPM](http://npm.mape.me) for Node.js.

PEAR packages are very easy to install, and are available in your PHP environment path so that they are accessible
to any PHP project. PEAR packages are not specific to your project, but rather to the machine that they're
installed on.

From the command-line, you can install the SDK with PEAR as follows:

    pear channel-discover pear.amazonwebservices.com
    pear install aws/sdk

You may need to use `sudo` for the above commands. Once the SDK has been installed via PEAR, you can load it into
your project with:

	require_once 'AWSSDKforPHP/sdk.class.php';

### Configuration

1. Copy the contents of [config-sample.inc.php](https://github.com/amazonwebservices/aws-sdk-for-php/raw/master/config-sample.inc.php)
   and add your credentials as instructed in the file.
2. Move your file to `~/.aws/sdk/config.inc.php`.
3. Make sure that `getenv('HOME')` points to your user directory. If not you'll need to set
   `putenv('HOME=<your-user-directory>')`.


## Additional Information

* AWS SDK for PHP: <http://aws.amazon.com/sdkforphp>
* Documentation: <http://docs.amazonwebservices.com/AWSSDKforPHP/latest/>
* License: <http://aws.amazon.com/apache2.0/>
* Discuss: <http://aws.amazon.com/forums>
