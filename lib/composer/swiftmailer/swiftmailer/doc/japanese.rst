Using Swift Mailer for Japanese Emails
======================================

To send emails in Japanese, you need to tweak the default configuration.

Call the ``Swift::init()`` method with the following code as early as possible
in your code::

    Swift::init(function () {
        Swift_DependencyContainer::getInstance()
            ->register('mime.qpheaderencoder')
            ->asAliasOf('mime.base64headerencoder');

        Swift_Preferences::getInstance()->setCharset('iso-2022-jp');
    });

    /* rest of code goes here */

That's all!
