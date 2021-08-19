<?php

namespace Safe;

use Safe\Exceptions\NetworkException;

/**
 * closelog closes the descriptor being used to write to
 * the system logger.  The use of closelog is optional.
 *
 * @throws NetworkException
 *
 */
function closelog(): void
{
    error_clear_last();
    $result = \closelog();
    if ($result === false) {
        throw NetworkException::createFromPhpError();
    }
}


/**
 * Fetch DNS Resource Records associated with the given
 * hostname.
 *
 * @param string $hostname hostname should be a valid DNS hostname such
 * as "www.example.com". Reverse lookups can be generated
 * using in-addr.arpa notation, but
 * gethostbyaddr is more suitable for
 * the majority of reverse lookups.
 *
 * Per DNS standards, email addresses are given in user.host format (for
 * example: hostmaster.example.com as opposed to hostmaster@example.com),
 * be sure to check this value and modify if necessary before using it
 * with a functions such as mail.
 * @param int $type By default, dns_get_record will search for any
 * resource records associated with hostname.
 * To limit the query, specify the optional type
 * parameter. May be any one of the following:
 * DNS_A, DNS_CNAME,
 * DNS_HINFO, DNS_CAA,
 * DNS_MX, DNS_NS,
 * DNS_PTR, DNS_SOA,
 * DNS_TXT, DNS_AAAA,
 * DNS_SRV, DNS_NAPTR,
 * DNS_A6, DNS_ALL
 * or DNS_ANY.
 *
 * Because of eccentricities in the performance of libresolv
 * between platforms, DNS_ANY will not
 * always return every record, the slower DNS_ALL
 * will collect all records more reliably.
 *
 * DNS_CAA is not supported on Windows.
 * @param array|null $authns Passed by reference and, if given, will be populated with Resource
 * Records for the Authoritative Name Servers.
 * @param array|null $addtl Passed by reference and, if given, will be populated with any
 * Additional Records.
 * @param bool $raw The type will be interpreted as a raw DNS type ID
 * (the DNS_* constants cannot be used).
 * The return value will contain a data key, which needs
 * to be manually parsed.
 * @return array This function returns an array of associative arrays. Each associative array contains
 * at minimum the following keys:
 *
 * Basic DNS attributes
 *
 *
 *
 * Attribute
 * Meaning
 *
 *
 *
 *
 * host
 *
 * The record in the DNS namespace to which the rest of the associated data refers.
 *
 *
 *
 * class
 *
 * dns_get_record only returns Internet class records and as
 * such this parameter will always return IN.
 *
 *
 *
 * type
 *
 * String containing the record type.  Additional attributes will also be contained
 * in the resulting array dependant on the value of type. See table below.
 *
 *
 *
 * ttl
 *
 * "Time To Live" remaining for this record. This will not equal
 * the record's original ttl, but will rather equal the original ttl minus whatever
 * length of time has passed since the authoritative name server was queried.
 *
 *
 *
 *
 *
 *
 *
 * Other keys in associative arrays dependant on 'type'
 *
 *
 *
 * Type
 * Extra Columns
 *
 *
 *
 *
 * A
 *
 * ip: An IPv4 addresses in dotted decimal notation.
 *
 *
 *
 * MX
 *
 * pri: Priority of mail exchanger.
 * Lower numbers indicate greater priority.
 * target: FQDN of the mail exchanger.
 * See also dns_get_mx.
 *
 *
 *
 * CNAME
 *
 * target: FQDN of location in DNS namespace to which
 * the record is aliased.
 *
 *
 *
 * NS
 *
 * target: FQDN of the name server which is authoritative
 * for this hostname.
 *
 *
 *
 * PTR
 *
 * target: Location within the DNS namespace to which
 * this record points.
 *
 *
 *
 * TXT
 *
 * txt: Arbitrary string data associated with this record.
 *
 *
 *
 * HINFO
 *
 * cpu: IANA number designating the CPU of the machine
 * referenced by this record.
 * os: IANA number designating the Operating System on
 * the machine referenced by this record.
 * See IANA's Operating System
 * Names for the meaning of these values.
 *
 *
 *
 * CAA
 *
 * flags: A one-byte bitfield; currently only bit 0 is defined,
 * meaning 'critical'; other bits are reserved and should be ignored.
 * tag: The CAA tag name (alphanumeric ASCII string).
 * value: The CAA tag value (binary string, may use subformats).
 * For additional information see: RFC 6844
 *
 *
 *
 * SOA
 *
 * mname: FQDN of the machine from which the resource
 * records originated.
 * rname: Email address of the administrative contact
 * for this domain.
 * serial: Serial # of this revision of the requested
 * domain.
 * refresh: Refresh interval (seconds) secondary name
 * servers should use when updating remote copies of this domain.
 * retry: Length of time (seconds) to wait after a
 * failed refresh before making a second attempt.
 * expire: Maximum length of time (seconds) a secondary
 * DNS server should retain remote copies of the zone data without a
 * successful refresh before discarding.
 * minimum-ttl: Minimum length of time (seconds) a
 * client can continue to use a DNS resolution before it should request
 * a new resolution from the server.  Can be overridden by individual
 * resource records.
 *
 *
 *
 * AAAA
 *
 * ipv6: IPv6 address
 *
 *
 *
 * A6(PHP &gt;= 5.1.0)
 *
 * masklen: Length (in bits) to inherit from the target
 * specified by chain.
 * ipv6: Address for this specific record to merge with
 * chain.
 * chain: Parent record to merge with
 * ipv6 data.
 *
 *
 *
 * SRV
 *
 * pri: (Priority) lowest priorities should be used first.
 * weight: Ranking to weight which of commonly prioritized
 * targets should be chosen at random.
 * target and port: hostname and port
 * where the requested service can be found.
 * For additional information see: RFC 2782
 *
 *
 *
 * NAPTR
 *
 * order and pref: Equivalent to
 * pri and weight above.
 * flags, services, regex,
 * and replacement: Parameters as defined by
 * RFC 2915.
 *
 *
 *
 *
 *
 * @throws NetworkException
 *
 */
function dns_get_record(string $hostname, int $type = DNS_ANY, ?array &$authns = null, ?array &$addtl = null, bool $raw = false): array
{
    error_clear_last();
    $result = \dns_get_record($hostname, $type, $authns, $addtl, $raw);
    if ($result === false) {
        throw NetworkException::createFromPhpError();
    }
    return $result;
}


/**
 * Initiates a socket connection to the resource specified by
 * hostname.
 *
 * PHP supports targets in the Internet and Unix domains as described in
 * .  A list of supported transports can also be
 * retrieved using stream_get_transports.
 *
 * The socket will by default be opened in blocking mode.  You can
 * switch it to non-blocking mode by using
 * stream_set_blocking.
 *
 * The function stream_socket_client is similar but
 * provides a richer set of options, including non-blocking connection and the
 * ability to provide a stream context.
 *
 * @param string $hostname If OpenSSL support is
 * installed, you may prefix the hostname
 * with either ssl:// or tls:// to
 * use an SSL or TLS client connection over TCP/IP to connect to the
 * remote host.
 * @param int $port The port number. This can be omitted and skipped with
 * -1 for transports that do not use ports, such as
 * unix://.
 * @param int|null $errno If provided, holds the system level error number that occurred in the
 * system-level connect() call.
 *
 * If the value returned in errno is
 * 0 and the function returned FALSE, it is an
 * indication that the error occurred before the
 * connect() call. This is most likely due to a
 * problem initializing the socket.
 * @param string|null $errstr The error message as a string.
 * @param float $timeout The connection timeout, in seconds.
 *
 * If you need to set a timeout for reading/writing data over the
 * socket, use stream_set_timeout, as the
 * timeout parameter to
 * fsockopen only applies while connecting the
 * socket.
 * @return resource fsockopen returns a file pointer which may be used
 * together with the other file functions (such as
 * fgets, fgetss,
 * fwrite, fclose, and
 * feof). If the call fails, it will return FALSE
 * @throws NetworkException
 *
 */
function fsockopen(string $hostname, int $port = -1, ?int &$errno = null, ?string &$errstr = null, float $timeout = null)
{
    error_clear_last();
    if ($timeout !== null) {
        $result = \fsockopen($hostname, $port, $errno, $errstr, $timeout);
    } else {
        $result = \fsockopen($hostname, $port, $errno, $errstr);
    }
    if ($result === false) {
        throw NetworkException::createFromPhpError();
    }
    return $result;
}


/**
 * gethostname gets the standard host name for
 * the local machine.
 *
 * @return string Returns a string with the hostname on success, otherwise FALSE is
 * returned.
 * @throws NetworkException
 *
 */
function gethostname(): string
{
    error_clear_last();
    $result = \gethostname();
    if ($result === false) {
        throw NetworkException::createFromPhpError();
    }
    return $result;
}


/**
 * getprotobyname returns the protocol number
 * associated with the protocol name as per
 * /etc/protocols.
 *
 * @param string $name The protocol name.
 * @return int Returns the protocol number.
 * @throws NetworkException
 *
 */
function getprotobyname(string $name): int
{
    error_clear_last();
    $result = \getprotobyname($name);
    if ($result === false) {
        throw NetworkException::createFromPhpError();
    }
    return $result;
}


/**
 * getprotobynumber returns the protocol name
 * associated with protocol number as per
 * /etc/protocols.
 *
 * @param int $number The protocol number.
 * @return string Returns the protocol name as a string.
 * @throws NetworkException
 *
 */
function getprotobynumber(int $number): string
{
    error_clear_last();
    $result = \getprotobynumber($number);
    if ($result === false) {
        throw NetworkException::createFromPhpError();
    }
    return $result;
}


/**
 * Registers a function that will be called when PHP starts sending output.
 *
 * The callback is executed just after PHP prepares all
 * headers to be sent, and before any other output is sent, creating a window
 * to manipulate the outgoing headers before being sent.
 *
 * @param callable $callback Function called just before the headers are sent. It gets no parameters
 * and the return value is ignored.
 * @throws NetworkException
 *
 */
function header_register_callback(callable $callback): void
{
    error_clear_last();
    $result = \header_register_callback($callback);
    if ($result === false) {
        throw NetworkException::createFromPhpError();
    }
}


/**
 *
 *
 * @param string $in_addr A 32bit IPv4, or 128bit IPv6 address.
 * @return string Returns a string representation of the address.
 * @throws NetworkException
 *
 */
function inet_ntop(string $in_addr): string
{
    error_clear_last();
    $result = \inet_ntop($in_addr);
    if ($result === false) {
        throw NetworkException::createFromPhpError();
    }
    return $result;
}


/**
 * openlog opens a connection to the system
 * logger for a program.
 *
 * The use of openlog is optional. It
 * will automatically be called by syslog if
 * necessary, in which case ident will default
 * to FALSE.
 *
 * @param string $ident The string ident is added to each message.
 * @param int $option The option argument is used to indicate
 * what logging options will be used when generating a log message.
 *
 * openlog Options
 *
 *
 *
 * Constant
 * Description
 *
 *
 *
 *
 * LOG_CONS
 *
 * if there is an error while sending data to the system logger,
 * write directly to the system console
 *
 *
 *
 * LOG_NDELAY
 *
 * open the connection to the logger immediately
 *
 *
 *
 * LOG_ODELAY
 *
 * (default) delay opening the connection until the first
 * message is logged
 *
 *
 *
 * LOG_PERROR
 * print log message also to standard error
 *
 *
 * LOG_PID
 * include PID with each message
 *
 *
 *
 *
 * You can use one or more of these options. When using multiple options
 * you need to OR them, i.e. to open the connection
 * immediately, write to the console and include the PID in each message,
 * you will use: LOG_CONS | LOG_NDELAY | LOG_PID
 * @param int $facility The facility argument is used to specify what
 * type of program is logging the message. This allows you to specify
 * (in your machine's syslog configuration) how messages coming from
 * different facilities will be handled.
 *
 * openlog Facilities
 *
 *
 *
 * Constant
 * Description
 *
 *
 *
 *
 * LOG_AUTH
 *
 * security/authorization messages (use
 * LOG_AUTHPRIV instead
 * in systems where that constant is defined)
 *
 *
 *
 * LOG_AUTHPRIV
 * security/authorization messages (private)
 *
 *
 * LOG_CRON
 * clock daemon (cron and at)
 *
 *
 * LOG_DAEMON
 * other system daemons
 *
 *
 * LOG_KERN
 * kernel messages
 *
 *
 * LOG_LOCAL0 ... LOG_LOCAL7
 * reserved for local use, these are not available in Windows
 *
 *
 * LOG_LPR
 * line printer subsystem
 *
 *
 * LOG_MAIL
 * mail subsystem
 *
 *
 * LOG_NEWS
 * USENET news subsystem
 *
 *
 * LOG_SYSLOG
 * messages generated internally by syslogd
 *
 *
 * LOG_USER
 * generic user-level messages
 *
 *
 * LOG_UUCP
 * UUCP subsystem
 *
 *
 *
 *
 *
 * LOG_USER is the only valid log type under Windows
 * operating systems
 * @throws NetworkException
 *
 */
function openlog(string $ident, int $option, int $facility): void
{
    error_clear_last();
    $result = \openlog($ident, $option, $facility);
    if ($result === false) {
        throw NetworkException::createFromPhpError();
    }
}


/**
 * syslog generates a log message that will be
 * distributed by the system logger.
 *
 * For information on setting up a user defined log handler, see the
 * syslog.conf
 * 5 Unix manual page.  More
 * information on the syslog facilities and option can be found in the man
 * pages for syslog
 * 3 on Unix machines.
 *
 * @param int $priority priority is a combination of the facility and
 * the level. Possible values are:
 *
 * syslog Priorities (in descending order)
 *
 *
 *
 * Constant
 * Description
 *
 *
 *
 *
 * LOG_EMERG
 * system is unusable
 *
 *
 * LOG_ALERT
 * action must be taken immediately
 *
 *
 * LOG_CRIT
 * critical conditions
 *
 *
 * LOG_ERR
 * error conditions
 *
 *
 * LOG_WARNING
 * warning conditions
 *
 *
 * LOG_NOTICE
 * normal, but significant, condition
 *
 *
 * LOG_INFO
 * informational message
 *
 *
 * LOG_DEBUG
 * debug-level message
 *
 *
 *
 *
 * @param string $message The message to send, except that the two characters
 * %m will be replaced by the error message string
 * (strerror) corresponding to the present value of
 * errno.
 * @throws NetworkException
 *
 */
function syslog(int $priority, string $message): void
{
    error_clear_last();
    $result = \syslog($priority, $message);
    if ($result === false) {
        throw NetworkException::createFromPhpError();
    }
}
