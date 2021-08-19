Sending Messages
================

Quick Reference for Sending a Message
-------------------------------------

Sending a message is very straightforward. You create a Transport, use it to
create the Mailer, then you use the Mailer to send the message.

When using ``send()`` the message will be sent just like it would be sent if
you used your mail client. An integer is returned which includes the number of
successful recipients. If none of the recipients could be sent to then zero
will be returned, which equates to a boolean ``false``. If you set two ``To:``
recipients and three ``Bcc:`` recipients in the message and all of the
recipients are delivered to successfully then the value 5 will be returned::

    // Create the Transport
    $transport = (new Swift_SmtpTransport('smtp.example.org', 25))
      ->setUsername('your username')
      ->setPassword('your password')
      ;

    /*
    You could alternatively use a different transport such as Sendmail:

    // Sendmail
    $transport = new Swift_SendmailTransport('/usr/sbin/sendmail -bs');
    */

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

Transport Types
~~~~~~~~~~~~~~~

Transports are the classes in Swift Mailer that are responsible for
communicating with a service in order to deliver a Message. There are several
types of Transport in Swift Mailer, all of which implement the
``Swift_Transport`` interface::

* ``Swift_SmtpTransport``: Sends messages over SMTP; Supports Authentication;
  Supports Encryption. Very portable; Pleasingly predictable results; Provides
  good feedback;

* ``Swift_SendmailTransport``: Communicates with a locally installed
  ``sendmail`` executable (Linux/UNIX). Quick time-to-run; Provides
  less-accurate feedback than SMTP; Requires ``sendmail`` installation;

* ``Swift_LoadBalancedTransport``: Cycles through a collection of the other
  Transports to manage load-reduction. Provides graceful fallback if one
  Transport fails (e.g. an SMTP server is down); Keeps the load on remote
  services down by spreading the work;

* ``Swift_FailoverTransport``: Works in conjunction with a collection of the
  other Transports to provide high-availability. Provides graceful fallback if
  one Transport fails (e.g. an SMTP server is down).

The SMTP Transport
..................

The SMTP Transport sends messages over the (standardized) Simple Message
Transfer Protocol. It can deal with encryption and authentication.

The SMTP Transport, ``Swift_SmtpTransport`` is without doubt the most commonly
used Transport because it will work on 99% of web servers (I just made that
number up, but you get the idea). All the server needs is the ability to
connect to a remote (or even local) SMTP server on the correct port number
(usually 25).

SMTP servers often require users to authenticate with a username and password
before any mail can be sent to other domains. This is easily achieved using
Swift Mailer with the SMTP Transport.

SMTP is a protocol -- in other words it's a "way" of communicating a job to be
done (i.e. sending a message). The SMTP protocol is the fundamental basis on
which messages are delivered all over the internet 7 days a week, 365 days a
year. For this reason it's the most "direct" method of sending messages you can
use and it's the one that will give you the most power and feedback (such as
delivery failures) when using Swift Mailer.

Because SMTP is generally run as a remote service (i.e. you connect to it over
the network/internet) it's extremely portable from server-to-server. You can
easily store the SMTP server address and port number in a configuration file
within your application and adjust the settings accordingly if the code is
moved or if the SMTP server is changed.

Some SMTP servers -- Google for example -- use encryption for security reasons.
Swift Mailer supports using both ``ssl`` (SMTPS = SMTP over TLS) and ``tls``
(SMTP with STARTTLS) encryption settings.

Using the SMTP Transport
^^^^^^^^^^^^^^^^^^^^^^^^

The SMTP Transport is easy to use. Most configuration options can be set with
the constructor.

To use the SMTP Transport you need to know which SMTP server your code needs to
connect to. Ask your web host if you're not sure. Lots of people ask me who to
connect to -- I really can't answer that since it's a setting that's extremely
specific to your hosting environment.

A connection to the SMTP server will be established upon the first call to
``send()``::

    // Create the Transport
    $transport = new Swift_SmtpTransport('smtp.example.org', 25);

    // Create the Mailer using your created Transport
    $mailer = new Swift_Mailer($transport);

    /*
    It's also possible to use multiple method calls

    $transport = (new Swift_SmtpTransport())
      ->setHost('smtp.example.org')
      ->setPort(25)
      ;
    */

Encrypted SMTP
^^^^^^^^^^^^^^

You can use ``ssl`` (SMTPS) or ``tls`` (STARTTLS) encryption with the SMTP Transport
by specifying it as a parameter or with a method call::

    // Create the Transport
    // Option #1: SMTPS = SMTP over TLS (always encrypted):
    $transport = new Swift_SmtpTransport('smtp.example.org', 587, 'ssl');
    // Option #2: SMTP with STARTTLS (best effort encryption):
    $transport = new Swift_SmtpTransport('smtp.example.org', 587, 'tls');

    // Create the Mailer using your created Transport
    $mailer = new Swift_Mailer($transport);

A connection to the SMTP server will be established upon the first call to
``send()``. The connection will be initiated with the correct encryption
settings.

.. note::
    For SMTPS or STARTTLS encryption to work your PHP installation must have
    appropriate OpenSSL transports wrappers. You can check if "tls" and/or
    "ssl" are present in your PHP installation by using the PHP function
    ``stream_get_transports()``.

.. note::
    If you are using Mailcatcher_, make sure you do not set the encryption
    for the ``Swift_SmtpTransport``, since Mailcatcher does not support encryption.

.. note::
    When in doubt, try ``ssl`` first for higher security, since the communication
    is always encrypted.

.. note::
    Usually, port 587 or 465 is used for encrypted SMTP. Check the documentation
    of your mail provider.

SMTP with a Username and Password
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Some servers require authentication. You can provide a username and password
with ``setUsername()`` and ``setPassword()`` methods::

    // Create the Transport the call setUsername() and setPassword()
    $transport = (new Swift_SmtpTransport('smtp.example.org', 25))
      ->setUsername('username')
      ->setPassword('password')
      ;

    // Create the Mailer using your created Transport
    $mailer = new Swift_Mailer($transport);

Your username and password will be used to authenticate upon first connect when
``send()`` are first used on the Mailer.

If authentication fails, an Exception of type ``Swift_TransportException`` will
be thrown.

.. note::

    If you need to know early whether or not authentication has failed and an
    Exception is going to be thrown, call the ``start()`` method on the
    created Transport.

The Sendmail Transport
......................

The Sendmail Transport sends messages by communicating with a locally installed
MTA -- such as ``sendmail``.

The Sendmail Transport, ``Swift_SendmailTransport`` does not directly connect
to any remote services. It is designed for Linux servers that have ``sendmail``
installed. The Transport starts a local ``sendmail`` process and sends messages
to it. Usually the ``sendmail`` process will respond quickly as it spools your
messages to disk before sending them.

The Transport is named the Sendmail Transport for historical reasons
(``sendmail`` was the "standard" UNIX tool for sending e-mail for years). It
will send messages using other transfer agents such as Exim or Postfix despite
its name, provided they have the relevant sendmail wrappers so that they can be
started with the correct command-line flags.

It's a common misconception that because the Sendmail Transport returns a
result very quickly it must therefore deliver messages to recipients quickly --
this is not true. It's not slow by any means, but it's certainly not faster
than SMTP when it comes to getting messages to the intended recipients. This is
because sendmail itself sends the messages over SMTP once they have been
quickly spooled to disk.

The Sendmail Transport has the potential to be just as smart of the SMTP
Transport when it comes to notifying Swift Mailer about which recipients were
rejected, but in reality the majority of locally installed ``sendmail``
instances are not configured well enough to provide any useful feedback. As
such Swift Mailer may report successful deliveries where they did in fact fail
before they even left your server.

You can run the Sendmail Transport in two different modes specified by command
line flags:

* "``-bs``" runs in SMTP mode so theoretically it will act like the SMTP
  Transport

* "``-t``" runs in piped mode with no feedback, but theoretically faster,
  though not advised

You can think of the Sendmail Transport as a sort of asynchronous SMTP
Transport -- though if you have problems with delivery failures you should try
using the SMTP Transport instead. Swift Mailer isn't doing the work here, it's
simply passing the work to somebody else (i.e. ``sendmail``).

Using the Sendmail Transport
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

To use the Sendmail Transport you simply need to call ``new
Swift_SendmailTransport()`` with the command as a parameter.

To use the Sendmail Transport you need to know where ``sendmail`` or another
MTA exists on the server. Swift Mailer uses a default value of
``/usr/sbin/sendmail``, which should work on most systems.

You specify the entire command as a parameter (i.e. including the command line
flags). Swift Mailer supports operational modes of "``-bs``" (default) and
"``-t``".

.. note::

    If you run sendmail in "``-t``" mode you will get no feedback as to whether
    or not sending has succeeded. Use "``-bs``" unless you have a reason not to.

A sendmail process will be started upon the first call to ``send()``. If the
process cannot be started successfully an Exception of type
``Swift_TransportException`` will be thrown::

    // Create the Transport
    $transport = new Swift_SendmailTransport('/usr/sbin/exim -bs');

    // Create the Mailer using your created Transport
    $mailer = new Swift_Mailer($transport);

Available Methods for Sending Messages
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The Mailer class offers one method for sending Messages -- ``send()``.

When a message is sent in Swift Mailer, the Mailer class communicates with
whichever Transport class you have chosen to use.

Each recipient in the message should either be accepted or rejected by the
Transport. For example, if the domain name on the email address is not
reachable the SMTP Transport may reject the address because it cannot process
it. ``send()`` will return an integer indicating the number of accepted
recipients.

.. note::

    It's possible to find out which recipients were rejected -- we'll cover that
    later in this chapter.

Using the ``send()`` Method
...........................

The ``send()`` method of the ``Swift_Mailer`` class sends a message using
exactly the same logic as your Desktop mail client would use. Just pass it a
Message and get a result.

The message will be sent just like it would be sent if you used your mail
client. An integer is returned which includes the number of successful
recipients. If none of the recipients could be sent to then zero will be
returned, which equates to a boolean ``false``. If you set two
``To:`` recipients and three ``Bcc:`` recipients in the message and all of the
recipients are delivered to successfully then the value 5 will be returned::

    // Create the Transport
    $transport = new Swift_SmtpTransport('localhost', 25);

    // Create the Mailer using your created Transport
    $mailer = new Swift_Mailer($transport);

    // Create a message
    $message = (new Swift_Message('Wonderful Subject'))
      ->setFrom(['john@doe.com' => 'John Doe'])
      ->setTo(['receiver@domain.org', 'other@domain.org' => 'A name'])
      ->setBody('Here is the message itself')
      ;

    // Send the message
    $numSent = $mailer->send($message);

    printf("Sent %d messages\n", $numSent);

    /* Note that often that only the boolean equivalent of the
       return value is of concern (zero indicates FALSE)

    if ($mailer->send($message))
    {
      echo "Sent\n";
    }
    else
    {
      echo "Failed\n";
    }

    */

Sending Emails in Batch
.......................

If you want to send a separate message to each recipient so that only their own
address shows up in the ``To:`` field, follow the following recipe:

* Create a Transport from one of the provided Transports --
  ``Swift_SmtpTransport``, ``Swift_SendmailTransport``,
  or one of the aggregate Transports.

* Create an instance of the ``Swift_Mailer`` class, using the Transport as
  it's constructor parameter.

* Create a Message.

* Iterate over the recipients and send message via the ``send()`` method on
  the Mailer object.

Each recipient of the messages receives a different copy with only their own
email address on the ``To:`` field.

Make sure to add only valid email addresses as recipients. If you try to add an
invalid email address with ``setTo()``, ``setCc()`` or ``setBcc()``, Swift
Mailer will throw a ``Swift_RfcComplianceException``.

If you add recipients automatically based on a data source that may contain
invalid email addresses, you can prevent possible exceptions by validating the
addresses using ``Egulias\EmailValidator\EmailValidator`` (a dependency that is
installed with Swift Mailer) and only adding addresses that validate. Another
way would be to wrap your ``setTo()``, ``setCc()`` and ``setBcc()`` calls in a
try-catch block and handle the ``Swift_RfcComplianceException`` in the catch
block.

Handling invalid addresses properly is especially important when sending emails
in large batches since a single invalid address might cause an unhandled
exception and stop the execution or your script early.

.. note::

    In the following example, two emails are sent. One to each of
    ``receiver@domain.org`` and ``other@domain.org``. These recipients will
    not be aware of each other::

        // Create the Transport
        $transport = new Swift_SmtpTransport('localhost', 25);

        // Create the Mailer using your created Transport
        $mailer = new Swift_Mailer($transport);

        // Create a message
        $message = (new Swift_Message('Wonderful Subject'))
          ->setFrom(['john@doe.com' => 'John Doe'])
          ->setBody('Here is the message itself')
          ;

        // Send the message
        $failedRecipients = [];
        $numSent = 0;
        $to = ['receiver@domain.org', 'other@domain.org' => 'A name'];

        foreach ($to as $address => $name)
        {
          if (is_int($address)) {
            $message->setTo($name);
          } else {
            $message->setTo([$address => $name]);
          }

          $numSent += $mailer->send($message, $failedRecipients);
        }

        printf("Sent %d messages\n", $numSent);

Finding out Rejected Addresses
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

It's possible to get a list of addresses that were rejected by the Transport by
using a by-reference parameter to ``send()``.

As Swift Mailer attempts to send the message to each address given to it, if a
recipient is rejected it will be added to the array. You can pass an existing
array, otherwise one will be created by-reference.

Collecting the list of recipients that were rejected can be useful in
circumstances where you need to "prune" a mailing list for example when some
addresses cannot be delivered to.

Getting Failures By-reference
.............................

Collecting delivery failures by-reference with the ``send()`` method is as
simple as passing a variable name to the method call::

    $mailer = new Swift_Mailer( ... );

    $message = (new Swift_Message( ... ))
      ->setFrom( ... )
      ->setTo([
        'receiver@bad-domain.org' => 'Receiver Name',
        'other@domain.org' => 'A name',
        'other-receiver@bad-domain.org' => 'Other Name'
      ))
      ->setBody( ... )
      ;

    // Pass a variable name to the send() method
    if (!$mailer->send($message, $failures))
    {
      echo "Failures:";
      print_r($failures);
    }

    /*
    Failures:
    Array (
      0 => receiver@bad-domain.org,
      1 => other-receiver@bad-domain.org
    )
    */

If the Transport rejects any of the recipients, the culprit addresses will be
added to the array provided by-reference.

.. note::

    If the variable name does not yet exist, it will be initialized as an
    empty array and then failures will be added to that array. If the variable
    already exists it will be type-cast to an array and failures will be added
    to it.

.. _Mailcatcher: https://mailcatcher.me/
