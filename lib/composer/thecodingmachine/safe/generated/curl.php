<?php

namespace Safe;

use Safe\Exceptions\CurlException;

/**
 * This function URL encodes the given string according to RFC 3986.
 *
 * @param resource $ch A cURL handle returned by
 * curl_init.
 * @param string $str The string to be encoded.
 * @return string Returns escaped string.
 * @throws CurlException
 *
 */
function curl_escape($ch, string $str): string
{
    error_clear_last();
    $result = \curl_escape($ch, $str);
    if ($result === false) {
        throw CurlException::createFromCurlResource($ch);
    }
    return $result;
}


/**
 * Execute the given cURL session.
 *
 * This function should be called after initializing a cURL session and all
 * the options for the session are set.
 *
 * @param resource $ch A cURL handle returned by
 * curl_init.
 * @return bool|string Returns TRUE on success. However, if the CURLOPT_RETURNTRANSFER
 * option is set, it will return
 * the result on success, FALSE on failure.
 * @throws CurlException
 *
 */
function curl_exec($ch)
{
    error_clear_last();
    $result = \curl_exec($ch);
    if ($result === false) {
        throw CurlException::createFromCurlResource($ch);
    }
    return $result;
}


/**
 * Gets information about the last transfer.
 *
 * @param resource $ch A cURL handle returned by
 * curl_init.
 * @param int $opt This may be one of the following constants:
 *
 *
 *
 * CURLINFO_EFFECTIVE_URL - Last effective URL
 *
 *
 *
 *
 * CURLINFO_HTTP_CODE -  The last response code.
 * As of PHP 5.5.0 and cURL 7.10.8, this is a legacy alias of
 * CURLINFO_RESPONSE_CODE
 *
 *
 *
 *
 * CURLINFO_FILETIME - Remote time of the retrieved document, with the CURLOPT_FILETIME enabled; if -1 is returned the time of the document is unknown
 *
 *
 *
 *
 * CURLINFO_TOTAL_TIME - Total transaction time in seconds for last transfer
 *
 *
 *
 *
 * CURLINFO_NAMELOOKUP_TIME - Time in seconds until name resolving was complete
 *
 *
 *
 *
 * CURLINFO_CONNECT_TIME - Time in seconds it took to establish the connection
 *
 *
 *
 *
 * CURLINFO_PRETRANSFER_TIME - Time in seconds from start until just before file transfer begins
 *
 *
 *
 *
 * CURLINFO_STARTTRANSFER_TIME - Time in seconds until the first byte is about to be transferred
 *
 *
 *
 *
 * CURLINFO_REDIRECT_COUNT - Number of redirects, with the CURLOPT_FOLLOWLOCATION option enabled
 *
 *
 *
 *
 * CURLINFO_REDIRECT_TIME - Time in seconds of all redirection steps before final transaction was started, with the CURLOPT_FOLLOWLOCATION option enabled
 *
 *
 *
 *
 * CURLINFO_REDIRECT_URL - With the CURLOPT_FOLLOWLOCATION option disabled: redirect URL found in the last transaction, that should be requested manually next. With the CURLOPT_FOLLOWLOCATION option enabled: this is empty. The redirect URL in this case is available in CURLINFO_EFFECTIVE_URL
 *
 *
 *
 *
 * CURLINFO_PRIMARY_IP - IP address of the most recent connection
 *
 *
 *
 *
 * CURLINFO_PRIMARY_PORT - Destination port of the most recent connection
 *
 *
 *
 *
 * CURLINFO_LOCAL_IP - Local (source) IP address of the most recent connection
 *
 *
 *
 *
 * CURLINFO_LOCAL_PORT - Local (source) port of the most recent connection
 *
 *
 *
 *
 * CURLINFO_SIZE_UPLOAD - Total number of bytes uploaded
 *
 *
 *
 *
 * CURLINFO_SIZE_DOWNLOAD - Total number of bytes downloaded
 *
 *
 *
 *
 * CURLINFO_SPEED_DOWNLOAD - Average download speed
 *
 *
 *
 *
 * CURLINFO_SPEED_UPLOAD - Average upload speed
 *
 *
 *
 *
 * CURLINFO_HEADER_SIZE - Total size of all headers received
 *
 *
 *
 *
 * CURLINFO_HEADER_OUT - The request string sent. For this to
 * work, add the CURLINFO_HEADER_OUT option to the handle by calling
 * curl_setopt
 *
 *
 *
 *
 * CURLINFO_REQUEST_SIZE - Total size of issued requests, currently only for HTTP requests
 *
 *
 *
 *
 * CURLINFO_SSL_VERIFYRESULT - Result of SSL certification verification requested by setting CURLOPT_SSL_VERIFYPEER
 *
 *
 *
 *
 * CURLINFO_CONTENT_LENGTH_DOWNLOAD - Content length of download, read from Content-Length: field
 *
 *
 *
 *
 * CURLINFO_CONTENT_LENGTH_UPLOAD - Specified size of upload
 *
 *
 *
 *
 * CURLINFO_CONTENT_TYPE - Content-Type: of the requested document. NULL indicates server did not send valid Content-Type: header
 *
 *
 *
 *
 * CURLINFO_PRIVATE - Private data associated with this cURL handle, previously set with the CURLOPT_PRIVATE option of curl_setopt
 *
 *
 *
 *
 * CURLINFO_RESPONSE_CODE - The last response code
 *
 *
 *
 *
 * CURLINFO_HTTP_CONNECTCODE - The CONNECT response code
 *
 *
 *
 *
 * CURLINFO_HTTPAUTH_AVAIL - Bitmask indicating the authentication method(s) available according to the previous response
 *
 *
 *
 *
 * CURLINFO_PROXYAUTH_AVAIL - Bitmask indicating the proxy authentication method(s) available according to the previous response
 *
 *
 *
 *
 * CURLINFO_OS_ERRNO - Errno from a connect failure. The number is OS and system specific.
 *
 *
 *
 *
 * CURLINFO_NUM_CONNECTS - Number of connections curl had to create to achieve the previous transfer
 *
 *
 *
 *
 * CURLINFO_SSL_ENGINES - OpenSSL crypto-engines supported
 *
 *
 *
 *
 * CURLINFO_COOKIELIST - All known cookies
 *
 *
 *
 *
 * CURLINFO_FTP_ENTRY_PATH - Entry path in FTP server
 *
 *
 *
 *
 * CURLINFO_APPCONNECT_TIME - Time in seconds it took from the start until the SSL/SSH connect/handshake to the remote host was completed
 *
 *
 *
 *
 * CURLINFO_CERTINFO - TLS certificate chain
 *
 *
 *
 *
 * CURLINFO_CONDITION_UNMET - Info on unmet time conditional
 *
 *
 *
 *
 * CURLINFO_RTSP_CLIENT_CSEQ - Next RTSP client CSeq
 *
 *
 *
 *
 * CURLINFO_RTSP_CSEQ_RECV - Recently received CSeq
 *
 *
 *
 *
 * CURLINFO_RTSP_SERVER_CSEQ - Next RTSP server CSeq
 *
 *
 *
 *
 * CURLINFO_RTSP_SESSION_ID - RTSP session ID
 *
 *
 *
 *
 * CURLINFO_CONTENT_LENGTH_DOWNLOAD_T - The content-length of the download. This is the value read from the Content-Type: field. -1 if the size isn't known
 *
 *
 *
 *
 * CURLINFO_CONTENT_LENGTH_UPLOAD_T - The specified size of the upload. -1 if the size isn't known
 *
 *
 *
 *
 * CURLINFO_HTTP_VERSION - The version used in the last HTTP connection. The return value will be one of the defined CURL_HTTP_VERSION_* constants or 0 if the version can't be determined
 *
 *
 *
 *
 * CURLINFO_PROTOCOL - The protocol used in the last HTTP connection. The returned value will be exactly one of the CURLPROTO_* values
 *
 *
 *
 *
 * CURLINFO_PROXY_SSL_VERIFYRESULT - The result of the certificate verification that was requested (using the CURLOPT_PROXY_SSL_VERIFYPEER option). Only used for HTTPS proxies
 *
 *
 *
 *
 * CURLINFO_SCHEME - The URL scheme used for the most recent connection
 *
 *
 *
 *
 * CURLINFO_SIZE_DOWNLOAD_T - Total number of bytes that were downloaded. The number is only for the latest transfer and will be reset again for each new transfer
 *
 *
 *
 *
 * CURLINFO_SIZE_UPLOAD_T - Total number of bytes that were uploaded
 *
 *
 *
 *
 * CURLINFO_SPEED_DOWNLOAD_T - The average download speed in bytes/second that curl measured for the complete download
 *
 *
 *
 *
 * CURLINFO_SPEED_UPLOAD_T - The average upload speed in bytes/second that curl measured for the complete upload
 *
 *
 *
 *
 * CURLINFO_APPCONNECT_TIME_T - Time, in microseconds, it took from the start until the SSL/SSH connect/handshake to the remote host was completed
 *
 *
 *
 *
 * CURLINFO_CONNECT_TIME_T - Total time taken, in microseconds, from the start until the connection to the remote host (or proxy) was completed
 *
 *
 *
 *
 * CURLINFO_FILETIME_T - Remote time of the retrieved document (as Unix timestamp), an alternative to CURLINFO_FILETIME to allow systems with 32 bit long variables to extract dates outside of the 32bit timestamp range
 *
 *
 *
 *
 * CURLINFO_NAMELOOKUP_TIME_T - Time in microseconds from the start until the name resolving was completed
 *
 *
 *
 *
 * CURLINFO_PRETRANSFER_TIME_T - Time taken from the start until the file transfer is just about to begin, in microseconds
 *
 *
 *
 *
 * CURLINFO_REDIRECT_TIME_T - Total time, in microseconds, it took for all redirection steps include name lookup, connect, pretransfer and transfer before final transaction was started
 *
 *
 *
 *
 * CURLINFO_STARTTRANSFER_TIME_T - Time, in microseconds, it took from the start until the first byte is received
 *
 *
 *
 *
 * CURLINFO_TOTAL_TIME_T - Total time in microseconds for the previous transfer, including name resolving, TCP connect etc.
 *
 *
 *
 * @return mixed If opt is given, returns its value.
 * Otherwise, returns an associative array with the following elements
 * (which correspond to opt):
 *
 *
 *
 * "url"
 *
 *
 *
 *
 * "content_type"
 *
 *
 *
 *
 * "http_code"
 *
 *
 *
 *
 * "header_size"
 *
 *
 *
 *
 * "request_size"
 *
 *
 *
 *
 * "filetime"
 *
 *
 *
 *
 * "ssl_verify_result"
 *
 *
 *
 *
 * "redirect_count"
 *
 *
 *
 *
 * "total_time"
 *
 *
 *
 *
 * "namelookup_time"
 *
 *
 *
 *
 * "connect_time"
 *
 *
 *
 *
 * "pretransfer_time"
 *
 *
 *
 *
 * "size_upload"
 *
 *
 *
 *
 * "size_download"
 *
 *
 *
 *
 * "speed_download"
 *
 *
 *
 *
 * "speed_upload"
 *
 *
 *
 *
 * "download_content_length"
 *
 *
 *
 *
 * "upload_content_length"
 *
 *
 *
 *
 * "starttransfer_time"
 *
 *
 *
 *
 * "redirect_time"
 *
 *
 *
 *
 * "certinfo"
 *
 *
 *
 *
 * "primary_ip"
 *
 *
 *
 *
 * "primary_port"
 *
 *
 *
 *
 * "local_ip"
 *
 *
 *
 *
 * "local_port"
 *
 *
 *
 *
 * "redirect_url"
 *
 *
 *
 *
 * "request_header" (This is only set if the CURLINFO_HEADER_OUT
 * is set by a previous call to curl_setopt)
 *
 *
 *
 * Note that private data is not included in the associative array and must be retrieved individually with the CURLINFO_PRIVATE option.
 * @throws CurlException
 *
 */
function curl_getinfo($ch, int $opt = null)
{
    error_clear_last();
    if ($opt !== null) {
        $result = \curl_getinfo($ch, $opt);
    } else {
        $result = \curl_getinfo($ch);
    }
    if ($result === false) {
        throw CurlException::createFromCurlResource($ch);
    }
    return $result;
}


/**
 * Initializes a new session and return a cURL handle for use with the
 * curl_setopt, curl_exec,
 * and curl_close functions.
 *
 * @param string $url If provided, the CURLOPT_URL option will be set
 * to its value. You can manually set this using the
 * curl_setopt function.
 *
 * The file protocol is disabled by cURL if
 * open_basedir is set.
 * @return resource Returns a cURL handle on success, FALSE on errors.
 * @throws CurlException
 *
 */
function curl_init(string $url = null)
{
    error_clear_last();
    $result = \curl_init($url);
    if ($result === false) {
        throw CurlException::createFromPhpError();
    }
    return $result;
}


/**
 * Return an integer containing the last multi curl error number.
 *
 * @param resource $mh A cURL multi handle returned by
 * curl_multi_init.
 * @return int Return an integer containing the last multi curl error number.
 * @throws CurlException
 *
 */
function curl_multi_errno($mh): int
{
    error_clear_last();
    $result = \curl_multi_errno($mh);
    if ($result === false) {
        throw CurlException::createFromPhpError();
    }
    return $result;
}


/**
 * Ask the multi handle if there are any messages or information from the individual transfers.
 * Messages may include information such as an error code from the transfer or just the fact
 * that a transfer is completed.
 *
 * Repeated calls to this function will return a new result each time, until a FALSE is returned
 * as a signal that there is no more to get at this point. The integer pointed to with
 * msgs_in_queue will contain the number of remaining messages after this
 * function was called.
 *
 * @param resource $mh A cURL multi handle returned by
 * curl_multi_init.
 * @param int|null $msgs_in_queue Number of messages that are still in the queue
 * @return array On success, returns an associative array for the message, FALSE on failure.
 *
 *
 * Contents of the returned array
 *
 *
 *
 * Key:
 * Value:
 *
 *
 *
 *
 * msg
 * The CURLMSG_DONE constant. Other return values
 * are currently not available.
 *
 *
 * result
 * One of the CURLE_* constants. If everything is
 * OK, the CURLE_OK will be the result.
 *
 *
 * handle
 * Resource of type curl indicates the handle which it concerns.
 *
 *
 *
 *
 * @throws CurlException
 *
 */
function curl_multi_info_read($mh, ?int &$msgs_in_queue = null): array
{
    error_clear_last();
    $result = \curl_multi_info_read($mh, $msgs_in_queue);
    if ($result === false) {
        throw CurlException::createFromPhpError();
    }
    return $result;
}


/**
 * Allows the processing of multiple cURL handles asynchronously.
 *
 * @return resource Returns a cURL multi handle resource on success, FALSE on failure.
 * @throws CurlException
 *
 */
function curl_multi_init()
{
    error_clear_last();
    $result = \curl_multi_init();
    if ($result === false) {
        throw CurlException::createFromPhpError();
    }
    return $result;
}


/**
 * Sets an option on the given cURL session handle.
 *
 * @param resource $ch A cURL handle returned by
 * curl_init.
 * @param int $option The CURLOPT_XXX option to set.
 * @param mixed $value The value to be set on option.
 *
 * value should be a bool for the
 * following values of the option parameter:
 *
 *
 *
 *
 * Option
 * Set value to
 * Notes
 *
 *
 *
 *
 * CURLOPT_AUTOREFERER
 *
 * TRUE to automatically set the Referer: field in
 * requests where it follows a Location: redirect.
 *
 *
 *
 *
 *
 * CURLOPT_BINARYTRANSFER
 *
 * TRUE to return the raw output when
 * CURLOPT_RETURNTRANSFER is used.
 *
 *
 * From PHP 5.1.3, this option has no effect: the raw output will
 * always be returned when
 * CURLOPT_RETURNTRANSFER is used.
 *
 *
 *
 * CURLOPT_COOKIESESSION
 *
 * TRUE to mark this as a new cookie "session". It will force libcurl
 * to ignore all cookies it is about to load that are "session cookies"
 * from the previous session. By default, libcurl always stores and
 * loads all cookies, independent if they are session cookies or not.
 * Session cookies are cookies without expiry date and they are meant
 * to be alive and existing for this "session" only.
 *
 *
 *
 *
 *
 * CURLOPT_CERTINFO
 *
 * TRUE to output SSL certification information to STDERR
 * on secure transfers.
 *
 *
 * Added in cURL 7.19.1.
 * Available since PHP 5.3.2.
 * Requires CURLOPT_VERBOSE to be on to have an effect.
 *
 *
 *
 * CURLOPT_CONNECT_ONLY
 *
 * TRUE tells the library to perform all the required proxy authentication
 * and connection setup, but no data transfer. This option is implemented for
 * HTTP, SMTP and POP3.
 *
 *
 * Added in 7.15.2.
 * Available since PHP 5.5.0.
 *
 *
 *
 * CURLOPT_CRLF
 *
 * TRUE to convert Unix newlines to CRLF newlines
 * on transfers.
 *
 *
 *
 *
 *
 * CURLOPT_DISALLOW_USERNAME_IN_URL
 *
 * TRUE to not allow URLs that include a username. Usernames are allowed by default (0).
 *
 *
 * Added in cURL 7.61.0. Available since PHP 7.3.0.
 *
 *
 *
 * CURLOPT_DNS_SHUFFLE_ADDRESSES
 *
 * TRUE to shuffle the order of all returned addresses so that they will be used
 * in a random order, when a name is resolved and more than one IP address is returned.
 * This may cause IPv4 to be used before IPv6 or vice versa.
 *
 *
 * Added in cURL 7.60.0. Available since PHP 7.3.0.
 *
 *
 *
 * CURLOPT_HAPROXYPROTOCOL
 *
 * TRUE to send an HAProxy PROXY protocol v1 header at the start of the connection.
 * The default action is not to send this header.
 *
 *
 * Added in cURL 7.60.0. Available since PHP 7.3.0.
 *
 *
 *
 * CURLOPT_SSH_COMPRESSION
 *
 * TRUE to enable built-in SSH compression. This is a request, not an order;
 * the server may or may not do it.
 *
 *
 * Added in cURL 7.56.0. Available since PHP 7.3.0.
 *
 *
 *
 * CURLOPT_DNS_USE_GLOBAL_CACHE
 *
 * TRUE to use a global DNS cache. This option is not thread-safe.
 * It is conditionally enabled by default if PHP is built for non-threaded use
 * (CLI, FCGI, Apache2-Prefork, etc.).
 *
 *
 *
 *
 *
 * CURLOPT_FAILONERROR
 *
 * TRUE to fail verbosely if the HTTP code returned
 * is greater than or equal to 400. The default behavior is to return
 * the page normally, ignoring the code.
 *
 *
 *
 *
 *
 * CURLOPT_SSL_FALSESTART
 *
 * TRUE to enable TLS false start.
 *
 *
 * Added in cURL 7.42.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_FILETIME
 *
 * TRUE to attempt to retrieve the modification
 * date of the remote document. This value can be retrieved using
 * the CURLINFO_FILETIME option with
 * curl_getinfo.
 *
 *
 *
 *
 *
 * CURLOPT_FOLLOWLOCATION
 *
 * TRUE to follow any
 * "Location: " header that the server sends as
 * part of the HTTP header (note this is recursive, PHP will follow as
 * many "Location: " headers that it is sent,
 * unless CURLOPT_MAXREDIRS is set).
 *
 *
 *
 *
 *
 * CURLOPT_FORBID_REUSE
 *
 * TRUE to force the connection to explicitly
 * close when it has finished processing, and not be pooled for reuse.
 *
 *
 *
 *
 *
 * CURLOPT_FRESH_CONNECT
 *
 * TRUE to force the use of a new connection
 * instead of a cached one.
 *
 *
 *
 *
 *
 * CURLOPT_FTP_USE_EPRT
 *
 * TRUE to use EPRT (and LPRT) when doing active
 * FTP downloads. Use FALSE to disable EPRT and LPRT and use PORT
 * only.
 *
 *
 *
 *
 *
 * CURLOPT_FTP_USE_EPSV
 *
 * TRUE to first try an EPSV command for FTP
 * transfers before reverting back to PASV. Set to FALSE
 * to disable EPSV.
 *
 *
 *
 *
 *
 * CURLOPT_FTP_CREATE_MISSING_DIRS
 *
 * TRUE to create missing directories when an FTP operation
 * encounters a path that currently doesn't exist.
 *
 *
 *
 *
 *
 * CURLOPT_FTPAPPEND
 *
 * TRUE to append to the remote file instead of
 * overwriting it.
 *
 *
 *
 *
 *
 * CURLOPT_TCP_NODELAY
 *
 * TRUE to disable TCP's Nagle algorithm, which tries to minimize
 * the number of small packets on the network.
 *
 *
 * Available since PHP 5.2.1 for versions compiled with libcurl 7.11.2 or
 * greater.
 *
 *
 *
 * CURLOPT_FTPASCII
 *
 * An alias of
 * CURLOPT_TRANSFERTEXT. Use that instead.
 *
 *
 *
 *
 *
 * CURLOPT_FTPLISTONLY
 *
 * TRUE to only list the names of an FTP
 * directory.
 *
 *
 *
 *
 *
 * CURLOPT_HEADER
 *
 * TRUE to include the header in the output.
 *
 *
 *
 *
 *
 * CURLINFO_HEADER_OUT
 *
 * TRUE to track the handle's request string.
 *
 *
 * Available since PHP 5.1.3. The CURLINFO_
 * prefix is intentional.
 *
 *
 *
 * CURLOPT_HTTP09_ALLOWED
 *
 * Whether to allow HTTP/0.9 responses. Defaults to FALSE as of libcurl 7.66.0;
 * formerly it defaulted to TRUE.
 *
 *
 * Available since PHP 7.3.15 and 7.4.3, respectively, if built against libcurl &gt;= 7.64.0
 *
 *
 *
 * CURLOPT_HTTPGET
 *
 * TRUE to reset the HTTP request method to GET.
 * Since GET is the default, this is only necessary if the request
 * method has been changed.
 *
 *
 *
 *
 *
 * CURLOPT_HTTPPROXYTUNNEL
 *
 * TRUE to tunnel through a given HTTP proxy.
 *
 *
 *
 *
 *
 * CURLOPT_HTTP_CONTENT_DECODING
 *
 * FALSE to get the raw HTTP response body.
 *
 *
 * Available as of PHP 5.5.0 if built against libcurl &gt;= 7.16.2.
 *
 *
 *
 * CURLOPT_KEEP_SENDING_ON_ERROR
 *
 * TRUE to keep sending the request body if the HTTP code returned is
 * equal to or larger than 300. The default action would be to stop sending
 * and close the stream or connection. Suitable for manual NTLM authentication.
 * Most applications do not need this option.
 *
 *
 * Available as of PHP 7.3.0 if built against libcurl &gt;= 7.51.0.
 *
 *
 *
 * CURLOPT_MUTE
 *
 * TRUE to be completely silent with regards to
 * the cURL functions.
 *
 *
 * Removed in cURL 7.15.5 (You can use CURLOPT_RETURNTRANSFER instead)
 *
 *
 *
 * CURLOPT_NETRC
 *
 * TRUE to scan the ~/.netrc
 * file to find a username and password for the remote site that
 * a connection is being established with.
 *
 *
 *
 *
 *
 * CURLOPT_NOBODY
 *
 * TRUE to exclude the body from the output.
 * Request method is then set to HEAD. Changing this to FALSE does
 * not change it to GET.
 *
 *
 *
 *
 *
 * CURLOPT_NOPROGRESS
 *
 * TRUE to disable the progress meter for cURL transfers.
 *
 *
 * PHP automatically sets this option to TRUE, this should only be
 * changed for debugging purposes.
 *
 *
 *
 *
 *
 *
 *
 * CURLOPT_NOSIGNAL
 *
 * TRUE to ignore any cURL function that causes a
 * signal to be sent to the PHP process. This is turned on by default
 * in multi-threaded SAPIs so timeout options can still be used.
 *
 *
 * Added in cURL 7.10.
 *
 *
 *
 * CURLOPT_PATH_AS_IS
 *
 * TRUE to not handle dot dot sequences.
 *
 *
 * Added in cURL 7.42.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_PIPEWAIT
 *
 * TRUE to wait for pipelining/multiplexing.
 *
 *
 * Added in cURL 7.43.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_POST
 *
 * TRUE to do a regular HTTP POST. This POST is the
 * normal application/x-www-form-urlencoded kind,
 * most commonly used by HTML forms.
 *
 *
 *
 *
 *
 * CURLOPT_PUT
 *
 * TRUE to HTTP PUT a file. The file to PUT must
 * be set with CURLOPT_INFILE and
 * CURLOPT_INFILESIZE.
 *
 *
 *
 *
 *
 * CURLOPT_RETURNTRANSFER
 *
 * TRUE to return the transfer as a string of the
 * return value of curl_exec instead of outputting
 * it directly.
 *
 *
 *
 *
 *
 * CURLOPT_SAFE_UPLOAD
 *
 * TRUE to disable support for the @ prefix for
 * uploading files in CURLOPT_POSTFIELDS, which
 * means that values starting with @ can be safely
 * passed as fields. CURLFile may be used for
 * uploads instead.
 *
 *
 * Added in PHP 5.5.0 with FALSE as the default value. PHP 5.6.0
 * changes the default value to TRUE. PHP 7 removes this option;
 * the CURLFile interface must be used to upload files.
 *
 *
 *
 * CURLOPT_SASL_IR
 *
 * TRUE to enable sending the initial response in the first packet.
 *
 *
 * Added in cURL 7.31.10. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_SSL_ENABLE_ALPN
 *
 * FALSE to disable ALPN in the SSL handshake (if the SSL backend
 * libcurl is built to use supports it), which can be used to
 * negotiate http2.
 *
 *
 * Added in cURL 7.36.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_SSL_ENABLE_NPN
 *
 * FALSE to disable NPN in the SSL handshake (if the SSL backend
 * libcurl is built to use supports it), which can be used to
 * negotiate http2.
 *
 *
 * Added in cURL 7.36.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_SSL_VERIFYPEER
 *
 * FALSE to stop cURL from verifying the peer's
 * certificate. Alternate certificates to verify against can be
 * specified with the CURLOPT_CAINFO option
 * or a certificate directory can be specified with the
 * CURLOPT_CAPATH option.
 *
 *
 * TRUE by default as of cURL 7.10. Default bundle installed as of
 * cURL 7.10.
 *
 *
 *
 * CURLOPT_SSL_VERIFYSTATUS
 *
 * TRUE to verify the certificate's status.
 *
 *
 * Added in cURL 7.41.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_PROXY_SSL_VERIFYPEER
 *
 * FALSE to stop cURL from verifying the peer's certificate.
 * Alternate certificates to verify against can be
 * specified with the CURLOPT_CAINFO option
 * or a certificate directory can be specified with the
 * CURLOPT_CAPATH option.
 * When set to false, the peer certificate verification succeeds regardless.
 *
 *
 * TRUE by default. Available since PHP 7.3.0 and libcurl &gt;= cURL 7.52.0.
 *
 *
 *
 * CURLOPT_SUPPRESS_CONNECT_HEADERS
 *
 * TRUE to suppress proxy CONNECT response headers from the user callback functions
 * CURLOPT_HEADERFUNCTION and CURLOPT_WRITEFUNCTION,
 * when CURLOPT_HTTPPROXYTUNNEL is used and a CONNECT request is made.
 *
 *
 * Added in cURL 7.54.0. Available since PHP 7.3.0.
 *
 *
 *
 * CURLOPT_TCP_FASTOPEN
 *
 * TRUE to enable TCP Fast Open.
 *
 *
 * Added in cURL 7.49.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_TFTP_NO_OPTIONS
 *
 * TRUE to not send TFTP options requests.
 *
 *
 * Added in cURL 7.48.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_TRANSFERTEXT
 *
 * TRUE to use ASCII mode for FTP transfers.
 * For LDAP, it retrieves data in plain text instead of HTML. On
 * Windows systems, it will not set STDOUT to binary
 * mode.
 *
 *
 *
 *
 *
 * CURLOPT_UNRESTRICTED_AUTH
 *
 * TRUE to keep sending the username and password
 * when following locations (using
 * CURLOPT_FOLLOWLOCATION), even when the
 * hostname has changed.
 *
 *
 *
 *
 *
 * CURLOPT_UPLOAD
 *
 * TRUE to prepare for an upload.
 *
 *
 *
 *
 *
 * CURLOPT_VERBOSE
 *
 * TRUE to output verbose information. Writes
 * output to STDERR, or the file specified using
 * CURLOPT_STDERR.
 *
 *
 *
 *
 *
 *
 *
 *
 * TRUE to disable the progress meter for cURL transfers.
 *
 *
 * PHP automatically sets this option to TRUE, this should only be
 * changed for debugging purposes.
 *
 *
 *
 * PHP automatically sets this option to TRUE, this should only be
 * changed for debugging purposes.
 *
 * value should be an integer for the
 * following values of the option parameter:
 *
 *
 *
 *
 * Option
 * Set value to
 * Notes
 *
 *
 *
 *
 * CURLOPT_BUFFERSIZE
 *
 * The size of the buffer to use for each read. There is no guarantee
 * this request will be fulfilled, however.
 *
 *
 * Added in cURL 7.10.
 *
 *
 *
 * CURLOPT_CLOSEPOLICY
 *
 * One of the CURLCLOSEPOLICY_* values.
 *
 *
 * This option is deprecated, as it was never implemented in cURL and
 * never had any effect.
 *
 *
 *
 *
 * Removed in PHP 5.6.0.
 *
 *
 *
 * CURLOPT_CONNECTTIMEOUT
 *
 * The number of seconds to wait while trying to connect. Use 0 to
 * wait indefinitely.
 *
 *
 *
 *
 *
 * CURLOPT_CONNECTTIMEOUT_MS
 *
 * The number of milliseconds to wait while trying to connect. Use 0 to
 * wait indefinitely.
 *
 * If libcurl is built to use the standard system name resolver, that
 * portion of the connect will still use full-second resolution for
 * timeouts with a minimum timeout allowed of one second.
 *
 *
 * Added in cURL 7.16.2. Available since PHP 5.2.3.
 *
 *
 *
 * CURLOPT_DNS_CACHE_TIMEOUT
 *
 * The number of seconds to keep DNS entries in memory. This
 * option is set to 120 (2 minutes) by default.
 *
 *
 *
 *
 *
 * CURLOPT_EXPECT_100_TIMEOUT_MS
 *
 * The timeout for Expect: 100-continue responses in milliseconds.
 * Defaults to 1000 milliseconds.
 *
 *
 * Added in cURL 7.36.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_HAPPY_EYEBALLS_TIMEOUT_MS
 *
 * Head start for ipv6 for the happy eyeballs algorithm. Happy eyeballs attempts
 * to connect to both IPv4 and IPv6 addresses for dual-stack hosts,
 * preferring IPv6 first for timeout milliseconds.
 * Defaults to CURL_HET_DEFAULT, which is currently 200 milliseconds.
 *
 *
 * Added in cURL 7.59.0. Available since PHP 7.3.0.
 *
 *
 *
 * CURLOPT_FTPSSLAUTH
 *
 * The FTP authentication method (when is activated):
 * CURLFTPAUTH_SSL (try SSL first),
 * CURLFTPAUTH_TLS (try TLS first), or
 * CURLFTPAUTH_DEFAULT (let cURL decide).
 *
 *
 * Added in cURL 7.12.2.
 *
 *
 *
 * CURLOPT_HEADEROPT
 *
 * How to deal with headers. One of the following constants:
 *
 * CURLHEADER_UNIFIED: the headers specified in
 * CURLOPT_HTTPHEADER will be used in requests
 * both to servers and proxies. With this option enabled,
 * CURLOPT_PROXYHEADER will not have any effect.
 *
 *
 * CURLHEADER_SEPARATE: makes
 * CURLOPT_HTTPHEADER headers only get sent to
 * a server and not to a proxy. Proxy headers must be set with
 * CURLOPT_PROXYHEADER to get used. Note that if
 * a non-CONNECT request is sent to a proxy, libcurl will send both
 * server headers and proxy headers. When doing CONNECT, libcurl will
 * send CURLOPT_PROXYHEADER headers only to the
 * proxy and then CURLOPT_HTTPHEADER headers
 * only to the server.
 *
 *
 * Defaults to CURLHEADER_SEPARATE as of cURL
 * 7.42.1, and CURLHEADER_UNIFIED before.
 *
 *
 *
 * Added in cURL 7.37.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_HTTP_VERSION
 *
 * CURL_HTTP_VERSION_NONE (default, lets CURL
 * decide which version to use),
 * CURL_HTTP_VERSION_1_0 (forces HTTP/1.0),
 * CURL_HTTP_VERSION_1_1 (forces HTTP/1.1),
 * CURL_HTTP_VERSION_2_0 (attempts HTTP 2),
 * CURL_HTTP_VERSION_2  (alias of CURL_HTTP_VERSION_2_0),
 * CURL_HTTP_VERSION_2TLS (attempts HTTP 2 over TLS (HTTPS) only) or
 * CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE (issues non-TLS HTTP requests using HTTP/2 without HTTP/1.1 Upgrade).
 *
 *
 *
 *
 *
 * CURLOPT_HTTPAUTH
 *
 *
 * The HTTP authentication method(s) to use. The options are:
 * CURLAUTH_BASIC,
 * CURLAUTH_DIGEST,
 * CURLAUTH_GSSNEGOTIATE,
 * CURLAUTH_NTLM,
 * CURLAUTH_ANY, and
 * CURLAUTH_ANYSAFE.
 *
 *
 * The bitwise | (or) operator can be used to combine
 * more than one method. If this is done, cURL will poll the server to see
 * what methods it supports and pick the best one.
 *
 *
 * CURLAUTH_ANY is an alias for
 * CURLAUTH_BASIC | CURLAUTH_DIGEST | CURLAUTH_GSSNEGOTIATE | CURLAUTH_NTLM.
 *
 *
 * CURLAUTH_ANYSAFE is an alias for
 * CURLAUTH_DIGEST | CURLAUTH_GSSNEGOTIATE | CURLAUTH_NTLM.
 *
 *
 *
 *
 *
 *
 * CURLOPT_INFILESIZE
 *
 * The expected size, in bytes, of the file when uploading a file to
 * a remote site. Note that using this option will not stop libcurl
 * from sending more data, as exactly what is sent depends on
 * CURLOPT_READFUNCTION.
 *
 *
 *
 *
 *
 * CURLOPT_LOW_SPEED_LIMIT
 *
 * The transfer speed, in bytes per second, that the transfer should be
 * below during the count of CURLOPT_LOW_SPEED_TIME
 * seconds before PHP considers the transfer too slow and aborts.
 *
 *
 *
 *
 *
 * CURLOPT_LOW_SPEED_TIME
 *
 * The number of seconds the transfer speed should be below
 * CURLOPT_LOW_SPEED_LIMIT before PHP considers
 * the transfer too slow and aborts.
 *
 *
 *
 *
 *
 * CURLOPT_MAXCONNECTS
 *
 * The maximum amount of persistent connections that are allowed.
 * When the limit is reached,
 * CURLOPT_CLOSEPOLICY is used to determine
 * which connection to close.
 *
 *
 *
 *
 *
 * CURLOPT_MAXREDIRS
 *
 * The maximum amount of HTTP redirections to follow. Use this option
 * alongside CURLOPT_FOLLOWLOCATION.
 *
 *
 *
 *
 *
 * CURLOPT_PORT
 *
 * An alternative port number to connect to.
 *
 *
 *
 *
 *
 * CURLOPT_POSTREDIR
 *
 * A bitmask of 1 (301 Moved Permanently), 2 (302 Found)
 * and 4 (303 See Other) if the HTTP POST method should be maintained
 * when CURLOPT_FOLLOWLOCATION is set and a
 * specific type of redirect occurs.
 *
 *
 * Added in cURL 7.19.1. Available since PHP 5.3.2.
 *
 *
 *
 * CURLOPT_PROTOCOLS
 *
 *
 * Bitmask of CURLPROTO_* values. If used, this bitmask
 * limits what protocols libcurl may use in the transfer. This allows you to have
 * a libcurl built to support a wide range of protocols but still limit specific
 * transfers to only be allowed to use a subset of them. By default libcurl will
 * accept all protocols it supports.
 * See also CURLOPT_REDIR_PROTOCOLS.
 *
 *
 * Valid protocol options are:
 * CURLPROTO_HTTP,
 * CURLPROTO_HTTPS,
 * CURLPROTO_FTP,
 * CURLPROTO_FTPS,
 * CURLPROTO_SCP,
 * CURLPROTO_SFTP,
 * CURLPROTO_TELNET,
 * CURLPROTO_LDAP,
 * CURLPROTO_LDAPS,
 * CURLPROTO_DICT,
 * CURLPROTO_FILE,
 * CURLPROTO_TFTP,
 * CURLPROTO_ALL
 *
 *
 *
 * Added in cURL 7.19.4.
 *
 *
 *
 * CURLOPT_PROXYAUTH
 *
 * The HTTP authentication method(s) to use for the proxy connection.
 * Use the same bitmasks as described in
 * CURLOPT_HTTPAUTH. For proxy authentication,
 * only CURLAUTH_BASIC and
 * CURLAUTH_NTLM are currently supported.
 *
 *
 * Added in cURL 7.10.7.
 *
 *
 *
 * CURLOPT_PROXYPORT
 *
 * The port number of the proxy to connect to. This port number can
 * also be set in CURLOPT_PROXY.
 *
 *
 *
 *
 *
 * CURLOPT_PROXYTYPE
 *
 * Either CURLPROXY_HTTP (default),
 * CURLPROXY_SOCKS4,
 * CURLPROXY_SOCKS5,
 * CURLPROXY_SOCKS4A or
 * CURLPROXY_SOCKS5_HOSTNAME.
 *
 *
 * Added in cURL 7.10.
 *
 *
 *
 * CURLOPT_REDIR_PROTOCOLS
 *
 * Bitmask of CURLPROTO_* values. If used, this bitmask
 * limits what protocols libcurl may use in a transfer that it follows to in
 * a redirect when CURLOPT_FOLLOWLOCATION is enabled.
 * This allows you to limit specific transfers to only be allowed to use a subset
 * of protocols in redirections. By default libcurl will allow all protocols
 * except for FILE and SCP. This is a difference compared to pre-7.19.4 versions
 * which unconditionally would follow to all protocols supported.
 * See also CURLOPT_PROTOCOLS for protocol constant values.
 *
 *
 * Added in cURL 7.19.4.
 *
 *
 *
 * CURLOPT_RESUME_FROM
 *
 * The offset, in bytes, to resume a transfer from.
 *
 *
 *
 *
 *
 * CURLOPT_SOCKS5_AUTH
 *
 *
 * The SOCKS5 authentication method(s) to use. The options are:
 * CURLAUTH_BASIC,
 * CURLAUTH_GSSAPI,
 * CURLAUTH_NONE.
 *
 *
 * The bitwise | (or) operator can be used to combine
 * more than one method. If this is done, cURL will poll the server to see
 * what methods it supports and pick the best one.
 *
 *
 * CURLAUTH_BASIC allows username/password authentication.
 *
 *
 * CURLAUTH_GSSAPI allows GSS-API authentication.
 *
 *
 * CURLAUTH_NONE allows no authentication.
 *
 *
 * Defaults to CURLAUTH_BASIC|CURLAUTH_GSSAPI.
 * Set the actual username and password with the CURLOPT_PROXYUSERPWD option.
 *
 *
 *
 * Available as of 7.3.0 and curl &gt;= 7.55.0.
 *
 *
 *
 * CURLOPT_SSL_OPTIONS
 *
 * Set SSL behavior options, which is a bitmask of any of the following constants:
 *
 * CURLSSLOPT_ALLOW_BEAST: do not attempt to use
 * any workarounds for a security flaw in the SSL3 and TLS1.0 protocols.
 *
 *
 * CURLSSLOPT_NO_REVOKE: disable certificate
 * revocation checks for those SSL backends where such behavior is
 * present.
 *
 *
 *
 * Added in cURL 7.25.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_SSL_VERIFYHOST
 *
 * 1 to check the existence of a common name in the
 * SSL peer certificate. 2 to check the existence of
 * a common name and also verify that it matches the hostname
 * provided. 0 to not check the names. In production environments the value of this option
 * should be kept at 2 (default value).
 *
 *
 * Support for value 1 removed in cURL 7.28.1.
 *
 *
 *
 * CURLOPT_SSLVERSION
 *
 * One of CURL_SSLVERSION_DEFAULT (0),
 * CURL_SSLVERSION_TLSv1 (1),
 * CURL_SSLVERSION_SSLv2 (2),
 * CURL_SSLVERSION_SSLv3 (3),
 * CURL_SSLVERSION_TLSv1_0 (4),
 * CURL_SSLVERSION_TLSv1_1 (5) or
 * CURL_SSLVERSION_TLSv1_2 (6).
 * The maximum TLS version can be set by using one of the CURL_SSLVERSION_MAX_*
 * constants. It is also possible to OR one of the CURL_SSLVERSION_*
 * constants with one of the CURL_SSLVERSION_MAX_* constants.
 * CURL_SSLVERSION_MAX_DEFAULT (the maximum version supported by the library),
 * CURL_SSLVERSION_MAX_TLSv1_0,
 * CURL_SSLVERSION_MAX_TLSv1_1,
 * CURL_SSLVERSION_MAX_TLSv1_2, or
 * CURL_SSLVERSION_MAX_TLSv1_3.
 *
 *
 * Your best bet is to not set this and let it use the default.
 * Setting it to 2 or 3 is very dangerous given the known
 * vulnerabilities in SSLv2 and SSLv3.
 *
 *
 *
 *
 *
 *
 *
 * CURLOPT_PROXY_SSL_OPTIONS
 *
 * Set proxy SSL behavior options, which is a bitmask of any of the following constants:
 *
 * CURLSSLOPT_ALLOW_BEAST: do not attempt to use
 * any workarounds for a security flaw in the SSL3 and TLS1.0 protocols.
 *
 *
 * CURLSSLOPT_NO_REVOKE: disable certificate
 * revocation checks for those SSL backends where such behavior is
 * present. (curl &gt;= 7.44.0)
 *
 *
 * CURLSSLOPT_NO_PARTIALCHAIN: do not accept "partial"
 * certificate chains, which it otherwise does by default. (curl &gt;= 7.68.0)
 *
 *
 *
 * Available since PHP 7.3.0 and libcurl &gt;= cURL 7.52.0.
 *
 *
 *
 * CURLOPT_PROXY_SSL_VERIFYHOST
 *
 * Set to 2 to verify in the HTTPS proxy's certificate name fields against the proxy name.
 * When set to 0 the connection succeeds regardless of the names used in the certificate.
 * Use that ability with caution!
 * 1 treated as a debug option in curl 7.28.0 and earlier.
 * From curl 7.28.1 to 7.65.3 CURLE_BAD_FUNCTION_ARGUMENT is returned.
 * From curl 7.66.0 onwards 1 and 2 is treated as the same value.
 * In production environments the value of this option should be kept at 2 (default value).
 *
 *
 * Available since PHP 7.3.0 and libcurl &gt;= cURL 7.52.0.
 *
 *
 *
 * CURLOPT_PROXY_SSLVERSION
 *
 * One of CURL_SSLVERSION_DEFAULT,
 * CURL_SSLVERSION_TLSv1,
 * CURL_SSLVERSION_TLSv1_0,
 * CURL_SSLVERSION_TLSv1_1,
 * CURL_SSLVERSION_TLSv1_2,
 * CURL_SSLVERSION_TLSv1_3,
 * CURL_SSLVERSION_MAX_DEFAULT,
 * CURL_SSLVERSION_MAX_TLSv1_0,
 * CURL_SSLVERSION_MAX_TLSv1_1,
 * CURL_SSLVERSION_MAX_TLSv1_2,
 * CURL_SSLVERSION_MAX_TLSv1_3 or
 * CURL_SSLVERSION_SSLv3.
 *
 *
 * Your best bet is to not set this and let it use the default CURL_SSLVERSION_DEFAULT
 * which will attempt to figure out the remote SSL protocol version.
 *
 *
 *
 *
 * Available since PHP 7.3.0 and libcurl &gt;= cURL 7.52.0.
 *
 *
 *
 * CURLOPT_STREAM_WEIGHT
 *
 * Set the numerical stream weight (a number between 1 and 256).
 *
 *
 * Added in cURL 7.46.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_TCP_KEEPALIVE
 *
 * If set to 1, TCP keepalive probes will be sent. The delay and
 * frequency of these probes can be controlled by the CURLOPT_TCP_KEEPIDLE
 * and CURLOPT_TCP_KEEPINTVL options, provided the operating system
 * supports them. If set to 0 (default) keepalive probes are disabled.
 *
 *
 * Added in cURL 7.25.0. Available since PHP 5.5.0.
 *
 *
 *
 * CURLOPT_TCP_KEEPIDLE
 *
 * Sets the delay, in seconds, that the operating system will wait while the connection is
 * idle before sending keepalive probes, if CURLOPT_TCP_KEEPALIVE is
 * enabled. Not all operating systems support this option.
 * The default is 60.
 *
 *
 * Added in cURL 7.25.0. Available since PHP 5.5.0.
 *
 *
 *
 * CURLOPT_TCP_KEEPINTVL
 *
 * Sets the interval, in seconds, that the operating system will wait between sending
 * keepalive probes, if CURLOPT_TCP_KEEPALIVE is enabled.
 * Not all operating systems support this option.
 * The default is 60.
 *
 *
 * Added in cURL 7.25.0. Available since PHP 5.5.0.
 *
 *
 *
 * CURLOPT_TIMECONDITION
 *
 * How CURLOPT_TIMEVALUE is treated.
 * Use CURL_TIMECOND_IFMODSINCE to return the
 * page only if it has been modified since the time specified in
 * CURLOPT_TIMEVALUE. If it hasn't been modified,
 * a "304 Not Modified" header will be returned
 * assuming CURLOPT_HEADER is TRUE.
 * Use CURL_TIMECOND_IFUNMODSINCE for the reverse
 * effect. CURL_TIMECOND_IFMODSINCE is the
 * default.
 *
 *
 *
 *
 *
 * CURLOPT_TIMEOUT
 *
 * The maximum number of seconds to allow cURL functions to execute.
 *
 *
 *
 *
 *
 * CURLOPT_TIMEOUT_MS
 *
 * The maximum number of milliseconds to allow cURL functions to
 * execute.
 *
 * If libcurl is built to use the standard system name resolver, that
 * portion of the connect will still use full-second resolution for
 * timeouts with a minimum timeout allowed of one second.
 *
 *
 * Added in cURL 7.16.2. Available since PHP 5.2.3.
 *
 *
 *
 * CURLOPT_TIMEVALUE
 *
 * The time in seconds since January 1st, 1970. The time will be used
 * by CURLOPT_TIMECONDITION. By default,
 * CURL_TIMECOND_IFMODSINCE is used.
 *
 *
 *
 *
 *
 * CURLOPT_TIMEVALUE_LARGE
 *
 * The time in seconds since January 1st, 1970. The time will be used
 * by CURLOPT_TIMECONDITION. Defaults to zero.
 * The difference between this option and CURLOPT_TIMEVALUE
 * is the type of the argument. On systems where 'long' is only 32 bit wide,
 * this option has to be used to set dates beyond the year 2038.
 *
 *
 * Added in cURL 7.59.0. Available since PHP 7.3.0.
 *
 *
 *
 * CURLOPT_MAX_RECV_SPEED_LARGE
 *
 * If a download exceeds this speed (counted in bytes per second) on
 * cumulative average during the transfer, the transfer will pause to
 * keep the average rate less than or equal to the parameter value.
 * Defaults to unlimited speed.
 *
 *
 * Added in cURL 7.15.5. Available since PHP 5.4.0.
 *
 *
 *
 * CURLOPT_MAX_SEND_SPEED_LARGE
 *
 * If an upload exceeds this speed (counted in bytes per second) on
 * cumulative average during the transfer, the transfer will pause to
 * keep the average rate less than or equal to the parameter value.
 * Defaults to unlimited speed.
 *
 *
 * Added in cURL 7.15.5. Available since PHP 5.4.0.
 *
 *
 *
 * CURLOPT_SSH_AUTH_TYPES
 *
 * A bitmask consisting of one or more of
 * CURLSSH_AUTH_PUBLICKEY,
 * CURLSSH_AUTH_PASSWORD,
 * CURLSSH_AUTH_HOST,
 * CURLSSH_AUTH_KEYBOARD. Set to
 * CURLSSH_AUTH_ANY to let libcurl pick one.
 *
 *
 * Added in cURL 7.16.1.
 *
 *
 *
 * CURLOPT_IPRESOLVE
 *
 * Allows an application to select what kind of IP addresses to use when
 * resolving host names. This is only interesting when using host names that
 * resolve addresses using more than one version of IP, possible values are
 * CURL_IPRESOLVE_WHATEVER,
 * CURL_IPRESOLVE_V4,
 * CURL_IPRESOLVE_V6, by default
 * CURL_IPRESOLVE_WHATEVER.
 *
 *
 * Added in cURL 7.10.8.
 *
 *
 *
 * CURLOPT_FTP_FILEMETHOD
 *
 * Tell curl which method to use to reach a file on a FTP(S) server. Possible values are
 * CURLFTPMETHOD_MULTICWD,
 * CURLFTPMETHOD_NOCWD and
 * CURLFTPMETHOD_SINGLECWD.
 *
 *
 * Added in cURL 7.15.1. Available since PHP 5.3.0.
 *
 *
 *
 *
 *
 *
 * This option is deprecated, as it was never implemented in cURL and
 * never had any effect.
 *
 * The HTTP authentication method(s) to use. The options are:
 * CURLAUTH_BASIC,
 * CURLAUTH_DIGEST,
 * CURLAUTH_GSSNEGOTIATE,
 * CURLAUTH_NTLM,
 * CURLAUTH_ANY, and
 * CURLAUTH_ANYSAFE.
 *
 * The bitwise | (or) operator can be used to combine
 * more than one method. If this is done, cURL will poll the server to see
 * what methods it supports and pick the best one.
 *
 * CURLAUTH_ANY is an alias for
 * CURLAUTH_BASIC | CURLAUTH_DIGEST | CURLAUTH_GSSNEGOTIATE | CURLAUTH_NTLM.
 *
 * CURLAUTH_ANYSAFE is an alias for
 * CURLAUTH_DIGEST | CURLAUTH_GSSNEGOTIATE | CURLAUTH_NTLM.
 *
 * Bitmask of CURLPROTO_* values. If used, this bitmask
 * limits what protocols libcurl may use in the transfer. This allows you to have
 * a libcurl built to support a wide range of protocols but still limit specific
 * transfers to only be allowed to use a subset of them. By default libcurl will
 * accept all protocols it supports.
 * See also CURLOPT_REDIR_PROTOCOLS.
 *
 * Valid protocol options are:
 * CURLPROTO_HTTP,
 * CURLPROTO_HTTPS,
 * CURLPROTO_FTP,
 * CURLPROTO_FTPS,
 * CURLPROTO_SCP,
 * CURLPROTO_SFTP,
 * CURLPROTO_TELNET,
 * CURLPROTO_LDAP,
 * CURLPROTO_LDAPS,
 * CURLPROTO_DICT,
 * CURLPROTO_FILE,
 * CURLPROTO_TFTP,
 * CURLPROTO_ALL
 *
 * The SOCKS5 authentication method(s) to use. The options are:
 * CURLAUTH_BASIC,
 * CURLAUTH_GSSAPI,
 * CURLAUTH_NONE.
 *
 * The bitwise | (or) operator can be used to combine
 * more than one method. If this is done, cURL will poll the server to see
 * what methods it supports and pick the best one.
 *
 * CURLAUTH_BASIC allows username/password authentication.
 *
 * CURLAUTH_GSSAPI allows GSS-API authentication.
 *
 * CURLAUTH_NONE allows no authentication.
 *
 * Defaults to CURLAUTH_BASIC|CURLAUTH_GSSAPI.
 * Set the actual username and password with the CURLOPT_PROXYUSERPWD option.
 *
 * Your best bet is to not set this and let it use the default.
 * Setting it to 2 or 3 is very dangerous given the known
 * vulnerabilities in SSLv2 and SSLv3.
 *
 * Your best bet is to not set this and let it use the default CURL_SSLVERSION_DEFAULT
 * which will attempt to figure out the remote SSL protocol version.
 *
 * value should be a string for the
 * following values of the option parameter:
 *
 *
 *
 *
 * Option
 * Set value to
 * Notes
 *
 *
 *
 *
 * CURLOPT_ABSTRACT_UNIX_SOCKET
 *
 * Enables the use of an abstract Unix domain socket instead of
 * establishing a TCP connection to a host and sets the path to
 * the given string. This option shares the same semantics
 * as CURLOPT_UNIX_SOCKET_PATH. These two options
 * share the same storage and therefore only one of them can be set
 * per handle.
 *
 *
 * Available since PHP 7.3.0 and cURL 7.53.0
 *
 *
 *
 * CURLOPT_CAINFO
 *
 * The name of a file holding one or more certificates to verify the
 * peer with. This only makes sense when used in combination with
 * CURLOPT_SSL_VERIFYPEER.
 *
 *
 * Might require an absolute path.
 *
 *
 *
 * CURLOPT_CAPATH
 *
 * A directory that holds multiple CA certificates. Use this option
 * alongside CURLOPT_SSL_VERIFYPEER.
 *
 *
 *
 *
 *
 * CURLOPT_COOKIE
 *
 * The contents of the "Cookie: " header to be
 * used in the HTTP request.
 * Note that multiple cookies are separated with a semicolon followed
 * by a space (e.g., "fruit=apple; colour=red")
 *
 *
 *
 *
 *
 * CURLOPT_COOKIEFILE
 *
 * The name of the file containing the cookie data. The cookie file can
 * be in Netscape format, or just plain HTTP-style headers dumped into
 * a file.
 * If the name is an empty string, no cookies are loaded, but cookie
 * handling is still enabled.
 *
 *
 *
 *
 *
 * CURLOPT_COOKIEJAR
 *
 * The name of a file to save all internal cookies to when the handle is closed,
 * e.g. after a call to curl_close.
 *
 *
 *
 *
 *
 * CURLOPT_COOKIELIST
 *
 * A cookie string (i.e. a single line in Netscape/Mozilla format, or a regular
 * HTTP-style Set-Cookie header) adds that single cookie to the internal cookie store.
 * "ALL" erases all cookies held in memory.
 * "SESS" erases all session cookies held in memory.
 * "FLUSH" writes all known cookies to the file specified by CURLOPT_COOKIEJAR.
 * "RELOAD" loads all cookies from the files specified by CURLOPT_COOKIEFILE.
 *
 *
 * Available since PHP 5.5.0 and cURL 7.14.1.
 *
 *
 *
 * CURLOPT_CUSTOMREQUEST
 *
 * A custom request method to use instead of
 * "GET" or "HEAD" when doing
 * a HTTP request. This is useful for doing
 * "DELETE" or other, more obscure HTTP requests.
 * Valid values are things like "GET",
 * "POST", "CONNECT" and so on;
 * i.e. Do not enter a whole HTTP request line here. For instance,
 * entering "GET /index.html HTTP/1.0\r\n\r\n"
 * would be incorrect.
 *
 *
 * Don't do this without making sure the server supports the custom
 * request method first.
 *
 *
 *
 *
 *
 *
 *
 * CURLOPT_DEFAULT_PROTOCOL
 *
 * The default protocol to use if the URL is missing a scheme name.
 *
 *
 * Added in cURL 7.45.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_DNS_INTERFACE
 *
 * Set the name of the network interface that the DNS resolver should bind to.
 * This must be an interface name (not an address).
 *
 *
 * Added in cURL 7.33.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_DNS_LOCAL_IP4
 *
 * Set the local IPv4 address that the resolver should bind to. The argument
 * should contain a single numerical IPv4 address as a string.
 *
 *
 * Added in cURL 7.33.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_DNS_LOCAL_IP6
 *
 * Set the local IPv6 address that the resolver should bind to. The argument
 * should contain a single numerical IPv6 address as a string.
 *
 *
 * Added in cURL 7.33.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_EGDSOCKET
 *
 * Like CURLOPT_RANDOM_FILE, except a filename
 * to an Entropy Gathering Daemon socket.
 *
 *
 *
 *
 *
 * CURLOPT_ENCODING
 *
 * The contents of the "Accept-Encoding: " header.
 * This enables decoding of the response. Supported encodings are
 * "identity", "deflate", and
 * "gzip". If an empty string, "",
 * is set, a header containing all supported encoding types is sent.
 *
 *
 * Added in cURL 7.10.
 *
 *
 *
 * CURLOPT_FTPPORT
 *
 * The value which will be used to get the IP address to use
 * for the FTP "PORT" instruction. The "PORT" instruction tells
 * the remote server to connect to our specified IP address.  The
 * string may be a plain IP address, a hostname, a network
 * interface name (under Unix), or just a plain '-' to use the
 * systems default IP address.
 *
 *
 *
 *
 *
 * CURLOPT_INTERFACE
 *
 * The name of the outgoing network interface to use. This can be an
 * interface name, an IP address or a host name.
 *
 *
 *
 *
 *
 * CURLOPT_KEYPASSWD
 *
 * The password required to use the CURLOPT_SSLKEY
 * or CURLOPT_SSH_PRIVATE_KEYFILE private key.
 *
 *
 * Added in cURL 7.16.1.
 *
 *
 *
 * CURLOPT_KRB4LEVEL
 *
 * The KRB4 (Kerberos 4) security level. Any of the following values
 * (in order from least to most powerful) are valid:
 * "clear",
 * "safe",
 * "confidential",
 * "private"..
 * If the string does not match one of these,
 * "private" is used. Setting this option to NULL
 * will disable KRB4 security. Currently KRB4 security only works
 * with FTP transactions.
 *
 *
 *
 *
 *
 * CURLOPT_LOGIN_OPTIONS
 *
 * Can be used to set protocol specific login options, such as the
 * preferred authentication mechanism via "AUTH=NTLM" or "AUTH=*",
 * and should be used in conjunction with the
 * CURLOPT_USERNAME option.
 *
 *
 * Added in cURL 7.34.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_PINNEDPUBLICKEY
 *
 * Set the pinned public key.
 * The string can be the file name of your pinned public key. The file
 * format expected is "PEM" or "DER". The string can also be any
 * number of base64 encoded sha256 hashes preceded by "sha256//" and
 * separated by ";".
 *
 *
 * Added in cURL 7.39.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_POSTFIELDS
 *
 *
 * The full data to post in a HTTP "POST" operation.
 * To post a file, prepend a filename with @ and
 * use the full path. The filetype can be explicitly specified by
 * following the filename with the type in the format
 * ';type=mimetype'. This parameter can either be
 * passed as a urlencoded string like 'para1=val1&amp;para2=val2&amp;...'
 * or as an array with the field name as key and field data as value.
 * If value is an array, the
 * Content-Type header will be set to
 * multipart/form-data.
 *
 *
 * As of PHP 5.2.0, value must be an array if
 * files are passed to this option with the @ prefix.
 *
 *
 * As of PHP 5.5.0, the @ prefix is deprecated and
 * files can be sent using CURLFile. The
 * @ prefix can be disabled for safe passing of
 * values beginning with @ by setting the
 * CURLOPT_SAFE_UPLOAD option to TRUE.
 *
 *
 *
 *
 *
 *
 * CURLOPT_PRIVATE
 *
 * Any data that should be associated with this cURL handle. This data
 * can subsequently be retrieved with the
 * CURLINFO_PRIVATE option of
 * curl_getinfo. cURL does nothing with this data.
 * When using a cURL multi handle, this private data is typically a
 * unique key to identify a standard cURL handle.
 *
 *
 * Added in cURL 7.10.3.
 *
 *
 *
 * CURLOPT_PRE_PROXY
 *
 * Set a string holding the host name or dotted numerical
 * IP address to be used as the preproxy that curl connects to before
 * it connects to the HTTP(S) proxy specified in the
 * CURLOPT_PROXY option for the upcoming request.
 * The preproxy can only be a SOCKS proxy and it should be prefixed with
 * [scheme]:// to specify which kind of socks is used.
 * A numerical IPv6 address must be written within [brackets].
 * Setting the preproxy to an empty string explicitly disables the use of a preproxy.
 * To specify port number in this string, append :[port]
 * to the end of the host name. The proxy's port number may optionally be
 * specified with the separate option CURLOPT_PROXYPORT.
 * Defaults to using port 1080 for proxies if a port is not specified.
 *
 *
 * Available since PHP 7.3.0 and libcurl &gt;= cURL 7.52.0.
 *
 *
 *
 * CURLOPT_PROXY
 *
 * The HTTP proxy to tunnel requests through.
 *
 *
 *
 *
 *
 * CURLOPT_PROXY_SERVICE_NAME
 *
 * The proxy authentication service name.
 *
 *
 * Added in cURL 7.34.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_PROXY_CAINFO
 *
 * The path to proxy Certificate Authority (CA) bundle. Set the path as a
 * string naming a file holding one or more certificates to
 * verify the HTTPS proxy with.
 * This option is for connecting to an HTTPS proxy, not an HTTPS server.
 * Defaults set to the system path where libcurl's cacert bundle is assumed
 * to be stored.
 *
 *
 * Available since PHP 7.3.0 and libcurl &gt;= cURL 7.52.0.
 *
 *
 *
 * CURLOPT_PROXY_CAPATH
 *
 * The directory holding multiple CA certificates to verify the HTTPS proxy with.
 *
 *
 * Available since PHP 7.3.0 and libcurl &gt;= cURL 7.52.0.
 *
 *
 *
 * CURLOPT_PROXY_CRLFILE
 *
 * Set the file name with the concatenation of CRL (Certificate Revocation List)
 * in PEM format to use in the certificate validation that occurs during
 * the SSL exchange.
 *
 *
 * Available since PHP 7.3.0 and libcurl &gt;= cURL 7.52.0.
 *
 *
 *
 * CURLOPT_PROXY_KEYPASSWD
 *
 * Set the string be used as the password required to use the
 * CURLOPT_PROXY_SSLKEY private key. You never needed a
 * passphrase to load a certificate but you need one to load your private key.
 * This option is for connecting to an HTTPS proxy, not an HTTPS server.
 *
 *
 * Available since PHP 7.3.0 and libcurl &gt;= cURL 7.52.0.
 *
 *
 *
 * CURLOPT_PROXY_PINNEDPUBLICKEY
 *
 * Set the pinned public key for HTTPS proxy. The string can be the file name
 * of your pinned public key. The file format expected is "PEM" or "DER".
 * The string can also be any number of base64 encoded sha256 hashes preceded by
 * "sha256//" and separated by ";"
 *
 *
 * Available since PHP 7.3.0 and libcurl &gt;= cURL 7.52.0.
 *
 *
 *
 * CURLOPT_PROXY_SSLCERT
 *
 * The file name of your client certificate used to connect to the HTTPS proxy.
 * The default format is "P12" on Secure Transport and "PEM" on other engines,
 * and can be changed with CURLOPT_PROXY_SSLCERTTYPE.
 * With NSS or Secure Transport, this can also be the nickname of the certificate
 * you wish to authenticate with as it is named in the security database.
 * If you want to use a file from the current directory, please precede it with
 * "./" prefix, in order to avoid confusion with a nickname.
 *
 *
 * Available since PHP 7.3.0 and libcurl &gt;= cURL 7.52.0.
 *
 *
 *
 * CURLOPT_PROXY_SSLCERTTYPE
 *
 * The format of your client certificate used when connecting to an HTTPS proxy.
 * Supported formats are "PEM" and "DER", except with Secure Transport.
 * OpenSSL (versions 0.9.3 and later) and Secure Transport
 * (on iOS 5 or later, or OS X 10.7 or later) also support "P12" for
 * PKCS#12-encoded files. Defaults to "PEM".
 *
 *
 * Available since PHP 7.3.0 and libcurl &gt;= cURL 7.52.0.
 *
 *
 *
 * CURLOPT_PROXY_SSL_CIPHER_LIST
 *
 * The list of ciphers to use for the connection to the HTTPS proxy.
 * The list must be syntactically correct, it consists of one or more cipher
 * strings separated by colons. Commas or spaces are also acceptable separators
 * but colons are normally used, !, - and + can be used as operators.
 *
 *
 * Available since PHP 7.3.0 and libcurl &gt;= cURL 7.52.0.
 *
 *
 *
 * CURLOPT_PROXY_TLS13_CIPHERS
 *
 * The list of cipher suites to use for the TLS 1.3 connection to a proxy.
 * The list must be syntactically correct, it consists of one or more
 * cipher suite strings separated by colons. This option is currently used
 * only when curl is built to use OpenSSL 1.1.1 or later.
 * If you are using a different SSL backend you can try setting
 * TLS 1.3 cipher suites by using the CURLOPT_PROXY_SSL_CIPHER_LIST option.
 *
 *
 * Available since PHP 7.3.0 and libcurl &gt;= cURL 7.61.0. Available when built with OpenSSL &gt;= 1.1.1.
 *
 *
 *
 * CURLOPT_PROXY_SSLKEY
 *
 * The file name of your private key used for connecting to the HTTPS proxy.
 * The default format is "PEM" and can be changed with
 * CURLOPT_PROXY_SSLKEYTYPE.
 * (iOS and Mac OS X only) This option is ignored if curl was built against Secure Transport.
 *
 *
 * Available since PHP 7.3.0 and libcurl &gt;= cURL 7.52.0. Available if built TLS enabled.
 *
 *
 *
 * CURLOPT_PROXY_SSLKEYTYPE
 *
 * The format of your private key. Supported formats are "PEM", "DER" and "ENG".
 *
 *
 * Available since PHP 7.3.0 and libcurl &gt;= cURL 7.52.0.
 *
 *
 *
 * CURLOPT_PROXY_TLSAUTH_PASSWORD
 *
 * The password to use for the TLS authentication method specified with the
 * CURLOPT_PROXY_TLSAUTH_TYPE option. Requires that the
 * CURLOPT_PROXY_TLSAUTH_USERNAME option to also be set.
 *
 *
 * Available since PHP 7.3.0 and libcurl &gt;= cURL 7.52.0.
 *
 *
 *
 * CURLOPT_PROXY_TLSAUTH_TYPE
 *
 * The method of the TLS authentication used for the HTTPS connection. Supported method is "SRP".
 *
 *
 * Secure Remote Password (SRP) authentication for TLS provides mutual authentication
 * if both sides have a shared secret. To use TLS-SRP, you must also set the
 * CURLOPT_PROXY_TLSAUTH_USERNAME and
 * CURLOPT_PROXY_TLSAUTH_PASSWORD options.
 *
 *
 *
 *
 * Available since PHP 7.3.0 and libcurl &gt;= cURL 7.52.0.
 *
 *
 *
 * CURLOPT_PROXY_TLSAUTH_USERNAME
 *
 * Tusername to use for the HTTPS proxy TLS authentication method specified with the
 * CURLOPT_PROXY_TLSAUTH_TYPE option. Requires that the
 * CURLOPT_PROXY_TLSAUTH_PASSWORD option to also be set.
 *
 *
 * Available since PHP 7.3.0 and libcurl &gt;= cURL 7.52.0.
 *
 *
 *
 * CURLOPT_PROXYUSERPWD
 *
 * A username and password formatted as
 * "[username]:[password]" to use for the
 * connection to the proxy.
 *
 *
 *
 *
 *
 * CURLOPT_RANDOM_FILE
 *
 * A filename to be used to seed the random number generator for SSL.
 *
 *
 *
 *
 *
 * CURLOPT_RANGE
 *
 * Range(s) of data to retrieve in the format
 * "X-Y" where X or Y are optional. HTTP transfers
 * also support several intervals, separated with commas in the format
 * "X-Y,N-M".
 *
 *
 *
 *
 *
 * CURLOPT_REFERER
 *
 * The contents of the "Referer: " header to be used
 * in a HTTP request.
 *
 *
 *
 *
 *
 * CURLOPT_SERVICE_NAME
 *
 * The authentication service name.
 *
 *
 * Added in cURL 7.43.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_SSH_HOST_PUBLIC_KEY_MD5
 *
 * A string containing 32 hexadecimal digits. The string should be the
 * MD5 checksum of the remote host's public key, and libcurl will reject
 * the connection to the host unless the md5sums match.
 * This option is only for SCP and SFTP transfers.
 *
 *
 * Added in cURL 7.17.1.
 *
 *
 *
 * CURLOPT_SSH_PUBLIC_KEYFILE
 *
 * The file name for your public key. If not used, libcurl defaults to
 * $HOME/.ssh/id_dsa.pub if the HOME environment variable is set,
 * and just "id_dsa.pub" in the current directory if HOME is not set.
 *
 *
 * Added in cURL 7.16.1.
 *
 *
 *
 * CURLOPT_SSH_PRIVATE_KEYFILE
 *
 * The file name for your private key. If not used, libcurl defaults to
 * $HOME/.ssh/id_dsa if the HOME environment variable is set,
 * and just "id_dsa" in the current directory if HOME is not set.
 * If the file is password-protected, set the password with
 * CURLOPT_KEYPASSWD.
 *
 *
 * Added in cURL 7.16.1.
 *
 *
 *
 * CURLOPT_SSL_CIPHER_LIST
 *
 * A list of ciphers to use for SSL. For example,
 * RC4-SHA and TLSv1 are valid
 * cipher lists.
 *
 *
 *
 *
 *
 * CURLOPT_SSLCERT
 *
 * The name of a file containing a PEM formatted certificate.
 *
 *
 *
 *
 *
 * CURLOPT_SSLCERTPASSWD
 *
 * The password required to use the
 * CURLOPT_SSLCERT certificate.
 *
 *
 *
 *
 *
 * CURLOPT_SSLCERTTYPE
 *
 * The format of the certificate. Supported formats are
 * "PEM" (default), "DER",
 * and "ENG".
 * As of OpenSSL 0.9.3, "P12" (for PKCS#12-encoded files)
 * is also supported.
 *
 *
 * Added in cURL 7.9.3.
 *
 *
 *
 * CURLOPT_SSLENGINE
 *
 * The identifier for the crypto engine of the private SSL key
 * specified in CURLOPT_SSLKEY.
 *
 *
 *
 *
 *
 * CURLOPT_SSLENGINE_DEFAULT
 *
 * The identifier for the crypto engine used for asymmetric crypto
 * operations.
 *
 *
 *
 *
 *
 * CURLOPT_SSLKEY
 *
 * The name of a file containing a private SSL key.
 *
 *
 *
 *
 *
 * CURLOPT_SSLKEYPASSWD
 *
 * The secret password needed to use the private SSL key specified in
 * CURLOPT_SSLKEY.
 *
 *
 * Since this option contains a sensitive password, remember to keep
 * the PHP script it is contained within safe.
 *
 *
 *
 *
 *
 *
 *
 * CURLOPT_SSLKEYTYPE
 *
 * The key type of the private SSL key specified in
 * CURLOPT_SSLKEY. Supported key types are
 * "PEM" (default), "DER",
 * and "ENG".
 *
 *
 *
 *
 *
 * CURLOPT_TLS13_CIPHERS
 *
 * The list of cipher suites to use for the TLS 1.3 connection. The list must be
 * syntactically correct, it consists of one or more cipher suite strings separated by colons.
 * This option is currently used only when curl is built to use OpenSSL 1.1.1 or later.
 * If you are using a different SSL backend you can try setting
 * TLS 1.3 cipher suites by using the CURLOPT_SSL_CIPHER_LIST option.
 *
 *
 * Available since PHP 7.3.0 and libcurl &gt;= cURL 7.61.0. Available when built with OpenSSL &gt;= 1.1.1.
 *
 *
 *
 * CURLOPT_UNIX_SOCKET_PATH
 *
 * Enables the use of Unix domain sockets as connection endpoint and
 * sets the path to the given string.
 *
 *
 * Added in cURL 7.40.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_URL
 *
 * The URL to fetch. This can also be set when initializing a
 * session with curl_init.
 *
 *
 *
 *
 *
 * CURLOPT_USERAGENT
 *
 * The contents of the "User-Agent: " header to be
 * used in a HTTP request.
 *
 *
 *
 *
 *
 * CURLOPT_USERNAME
 *
 * The user name to use in authentication.
 *
 *
 * Added in cURL 7.19.1. Available since PHP 5.5.0.
 *
 *
 *
 * CURLOPT_USERPWD
 *
 * A username and password formatted as
 * "[username]:[password]" to use for the
 * connection.
 *
 *
 *
 *
 *
 * CURLOPT_XOAUTH2_BEARER
 *
 * Specifies the OAuth 2.0 access token.
 *
 *
 * Added in cURL 7.33.0. Available since PHP 7.0.7.
 *
 *
 *
 *
 *
 *
 * A custom request method to use instead of
 * "GET" or "HEAD" when doing
 * a HTTP request. This is useful for doing
 * "DELETE" or other, more obscure HTTP requests.
 * Valid values are things like "GET",
 * "POST", "CONNECT" and so on;
 * i.e. Do not enter a whole HTTP request line here. For instance,
 * entering "GET /index.html HTTP/1.0\r\n\r\n"
 * would be incorrect.
 *
 *
 * Don't do this without making sure the server supports the custom
 * request method first.
 *
 *
 *
 * Don't do this without making sure the server supports the custom
 * request method first.
 *
 * The default protocol to use if the URL is missing a scheme name.
 *
 * Set the name of the network interface that the DNS resolver should bind to.
 * This must be an interface name (not an address).
 *
 * Set the local IPv4 address that the resolver should bind to. The argument
 * should contain a single numerical IPv4 address as a string.
 *
 * Set the local IPv6 address that the resolver should bind to. The argument
 * should contain a single numerical IPv6 address as a string.
 *
 * Secure Remote Password (SRP) authentication for TLS provides mutual authentication
 * if both sides have a shared secret. To use TLS-SRP, you must also set the
 * CURLOPT_PROXY_TLSAUTH_USERNAME and
 * CURLOPT_PROXY_TLSAUTH_PASSWORD options.
 *
 * The secret password needed to use the private SSL key specified in
 * CURLOPT_SSLKEY.
 *
 *
 * Since this option contains a sensitive password, remember to keep
 * the PHP script it is contained within safe.
 *
 *
 *
 * Since this option contains a sensitive password, remember to keep
 * the PHP script it is contained within safe.
 *
 * value should be an array for the
 * following values of the option parameter:
 *
 *
 *
 *
 * Option
 * Set value to
 * Notes
 *
 *
 *
 *
 * CURLOPT_CONNECT_TO
 *
 * Connect to a specific host and port instead of the URL's host and port.
 * Accepts an array of strings with the format
 * HOST:PORT:CONNECT-TO-HOST:CONNECT-TO-PORT.
 *
 *
 * Added in cURL 7.49.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_HTTP200ALIASES
 *
 * An array of HTTP 200 responses that will be treated as valid
 * responses and not as errors.
 *
 *
 * Added in cURL 7.10.3.
 *
 *
 *
 * CURLOPT_HTTPHEADER
 *
 * An array of HTTP header fields to set, in the format
 *
 * array('Content-type: text/plain', 'Content-length: 100')
 *
 *
 *
 *
 *
 *
 * CURLOPT_POSTQUOTE
 *
 * An array of FTP commands to execute on the server after the FTP
 * request has been performed.
 *
 *
 *
 *
 *
 * CURLOPT_PROXYHEADER
 *
 * An array of custom HTTP headers to pass to proxies.
 *
 *
 * Added in cURL 7.37.0. Available since PHP 7.0.7.
 *
 *
 *
 * CURLOPT_QUOTE
 *
 * An array of FTP commands to execute on the server prior to the FTP
 * request.
 *
 *
 *
 *
 *
 * CURLOPT_RESOLVE
 *
 * Provide a custom address for a specific host and port pair. An array
 * of hostname, port, and IP address strings, each element separated by
 * a colon. In the format:
 *
 * array("example.com:80:127.0.0.1")
 *
 *
 *
 * Added in cURL 7.21.3. Available since PHP 5.5.0.
 *
 *
 *
 *
 *
 *
 * value should be a stream resource (using
 * fopen, for example) for the following values of the
 * option parameter:
 *
 *
 *
 *
 * Option
 * Set value to
 *
 *
 *
 *
 * CURLOPT_FILE
 *
 * The file that the transfer should be written to. The default
 * is STDOUT (the browser window).
 *
 *
 *
 * CURLOPT_INFILE
 *
 * The file that the transfer should be read from when uploading.
 *
 *
 *
 * CURLOPT_STDERR
 *
 * An alternative location to output errors to instead of
 * STDERR.
 *
 *
 *
 * CURLOPT_WRITEHEADER
 *
 * The file that the header part of the transfer is written to.
 *
 *
 *
 *
 *
 *
 * value should be the name of a valid function or a Closure
 * for the following values of the option parameter:
 *
 *
 *
 *
 * Option
 * Set value to
 *
 *
 *
 *
 * CURLOPT_HEADERFUNCTION
 *
 * A callback accepting two parameters.
 * The first is the cURL resource, the second is a
 * string with the header data to be written. The header data must
 * be written by this callback. Return the number of
 * bytes written.
 *
 *
 *
 * CURLOPT_PASSWDFUNCTION
 *
 * A callback accepting three parameters.
 * The first is the cURL resource, the second is a
 * string containing a password prompt, and the third is the maximum
 * password length. Return the string containing the password.
 *
 *
 *
 * CURLOPT_PROGRESSFUNCTION
 *
 *
 * A callback accepting five parameters.
 * The first is the cURL resource, the second is the total number of
 * bytes expected to be downloaded in this transfer, the third is
 * the number of bytes downloaded so far, the fourth is the total
 * number of bytes expected to be uploaded in this transfer, and the
 * fifth is the number of bytes uploaded so far.
 *
 *
 *
 * The callback is only called when the CURLOPT_NOPROGRESS
 * option is set to FALSE.
 *
 *
 *
 * Return a non-zero value to abort the transfer. In which case, the
 * transfer will set a CURLE_ABORTED_BY_CALLBACK
 * error.
 *
 *
 *
 *
 * CURLOPT_READFUNCTION
 *
 * A callback accepting three parameters.
 * The first is the cURL resource, the second is a
 * stream resource provided to cURL through the option
 * CURLOPT_INFILE, and the third is the maximum
 * amount of data to be read. The callback must return a string
 * with a length equal or smaller than the amount of data requested,
 * typically by reading it from the passed stream resource. It should
 * return an empty string to signal EOF.
 *
 *
 *
 * CURLOPT_WRITEFUNCTION
 *
 * A callback accepting two parameters.
 * The first is the cURL resource, and the second is a
 * string with the data to be written. The data must be saved by
 * this callback. It must return the exact number of bytes written
 * or the transfer will be aborted with an error.
 *
 *
 *
 *
 *
 *
 * A callback accepting five parameters.
 * The first is the cURL resource, the second is the total number of
 * bytes expected to be downloaded in this transfer, the third is
 * the number of bytes downloaded so far, the fourth is the total
 * number of bytes expected to be uploaded in this transfer, and the
 * fifth is the number of bytes uploaded so far.
 *
 * The callback is only called when the CURLOPT_NOPROGRESS
 * option is set to FALSE.
 *
 * Return a non-zero value to abort the transfer. In which case, the
 * transfer will set a CURLE_ABORTED_BY_CALLBACK
 * error.
 *
 * Other values:
 *
 *
 *
 *
 * Option
 * Set value to
 *
 *
 *
 *
 * CURLOPT_SHARE
 *
 * A result of curl_share_init. Makes the cURL
 * handle to use the data from the shared handle.
 *
 *
 *
 *
 *
 * @throws CurlException
 *
 */
function curl_setopt($ch, int $option, $value): void
{
    error_clear_last();
    $result = \curl_setopt($ch, $option, $value);
    if ($result === false) {
        throw CurlException::createFromCurlResource($ch);
    }
}


/**
 * Return an integer containing the last share curl error number.
 *
 * @param resource $sh A cURL share handle returned by curl_share_init.
 * @return int Returns an integer containing the last share curl error number.
 * @throws CurlException
 *
 */
function curl_share_errno($sh): int
{
    error_clear_last();
    $result = \curl_share_errno($sh);
    if ($result === false) {
        throw CurlException::createFromPhpError();
    }
    return $result;
}


/**
 * Sets an option on the given cURL share handle.
 *
 * @param resource $sh A cURL share handle returned by curl_share_init.
 * @param int $option
 *
 *
 *
 * Option
 * Description
 *
 *
 *
 *
 * CURLSHOPT_SHARE
 *
 * Specifies a type of data that should be shared.
 *
 *
 *
 * CURLSHOPT_UNSHARE
 *
 * Specifies a type of data that will be no longer shared.
 *
 *
 *
 *
 *
 * @param string $value
 *
 *
 *
 * Value
 * Description
 *
 *
 *
 *
 * CURL_LOCK_DATA_COOKIE
 *
 * Shares cookie data.
 *
 *
 *
 * CURL_LOCK_DATA_DNS
 *
 * Shares DNS cache. Note that when you use cURL multi handles,
 * all handles added to the same multi handle will share DNS cache
 * by default.
 *
 *
 *
 * CURL_LOCK_DATA_SSL_SESSION
 *
 * Shares SSL session IDs, reducing the time spent on the SSL
 * handshake when reconnecting to the same server. Note that SSL
 * session IDs are reused within the same handle by default.
 *
 *
 *
 *
 *
 * @throws CurlException
 *
 */
function curl_share_setopt($sh, int $option, string $value): void
{
    error_clear_last();
    $result = \curl_share_setopt($sh, $option, $value);
    if ($result === false) {
        throw CurlException::createFromPhpError();
    }
}


/**
 * This function decodes the given URL encoded string.
 *
 * @param resource $ch A cURL handle returned by
 * curl_init.
 * @param string $str The URL encoded string to be decoded.
 * @return string Returns decoded string.
 * @throws CurlException
 *
 */
function curl_unescape($ch, string $str): string
{
    error_clear_last();
    $result = \curl_unescape($ch, $str);
    if ($result === false) {
        throw CurlException::createFromCurlResource($ch);
    }
    return $result;
}
