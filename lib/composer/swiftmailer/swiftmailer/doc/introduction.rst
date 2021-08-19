Introduction
============

Swift Mailer is a component based library for sending e-mails from PHP applications.

System Requirements
-------------------

Swift Mailer requires PHP 7.0 or higher (``proc_*`` functions must be
available).

Swift Mailer does not work when used with function overloading as implemented
by ``mbstring`` when ``mbstring.func_overload`` is set to ``2``.

Installation
------------

The recommended way to install Swiftmailer is via Composer:

.. code-block:: bash

    $ composer require "swiftmailer/swiftmailer:^6.0"

Basic Usage
-----------

Here is the simplest way to send emails with Swift Mailer::

    require_once '/path/to/vendor/autoload.php';

    // Create the Transport
    $transport = (new Swift_SmtpTransport('smtp.example.org', 25))
      ->setUsername('your username')
      ->setPassword('your password')
    ;

    // Create the Mailer using your created Transport
    $mailer = new Swift_Mailer($transport);

    // Create a message
    $message = (new Swift_Message('Wonderful Subject'))
      ->setFrom(['john@doe.com' => 'John Doe'])
      ->setTo(['receiver@domain.org', 'other@domain.org' => 'A name'])
      ->setBody('Here is the message itself')
      ;

    // Send the message
    $result = $mailer->send($message);

You can also use Sendmail as a transport::

    // Sendmail
    $transport = new Swift_SendmailTransport('/usr/sbin/sendmail -bs');

Getting Help
------------

For general support, use `Stack Overflow <https://stackoverflow.com>`_.

For bug reports and feature requests, create a new ticket in `GitHub
<https://github.com/swiftmailer/swiftmailer/issues>`_.
