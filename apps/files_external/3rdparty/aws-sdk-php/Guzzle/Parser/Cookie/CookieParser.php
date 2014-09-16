<?php

namespace Guzzle\Parser\Cookie;

/**
 * Default Guzzle implementation of a Cookie parser
 */
class CookieParser implements CookieParserInterface
{
    /** @var array Cookie part names to snake_case array values */
    protected static $cookieParts = array(
        'domain'      => 'Domain',
        'path'        => 'Path',
        'max_age'     => 'Max-Age',
        'expires'     => 'Expires',
        'version'     => 'Version',
        'secure'      => 'Secure',
        'port'        => 'Port',
        'discard'     => 'Discard',
        'comment'     => 'Comment',
        'comment_url' => 'Comment-Url',
        'http_only'   => 'HttpOnly'
    );

    public function parseCookie($cookie, $host = null, $path = null, $decode = false)
    {
        // Explode the cookie string using a series of semicolons
        $pieces = array_filter(array_map('trim', explode(';', $cookie)));

        // The name of the cookie (first kvp) must include an equal sign.
        if (empty($pieces) || !strpos($pieces[0], '=')) {
            return false;
        }

        // Create the default return array
        $data = array_merge(array_fill_keys(array_keys(self::$cookieParts), null), array(
            'cookies'   => array(),
            'data'      => array(),
            'path'      => null,
            'http_only' => false,
            'discard'   => false,
            'domain'    => $host
        ));
        $foundNonCookies = 0;

        // Add the cookie pieces into the parsed data array
        foreach ($pieces as $part) {

            $cookieParts = explode('=', $part, 2);
            $key = trim($cookieParts[0]);

            if (count($cookieParts) == 1) {
                // Can be a single value (e.g. secure, httpOnly)
                $value = true;
            } else {
                // Be sure to strip wrapping quotes
                $value = trim($cookieParts[1], " \n\r\t\0\x0B\"");
                if ($decode) {
                    $value = urldecode($value);
                }
            }

            // Only check for non-cookies when cookies have been found
            if (!empty($data['cookies'])) {
                foreach (self::$cookieParts as $mapValue => $search) {
                    if (!strcasecmp($search, $key)) {
                        $data[$mapValue] = $mapValue == 'port' ? array_map('trim', explode(',', $value)) : $value;
                        $foundNonCookies++;
                        continue 2;
                    }
                }
            }

            // If cookies have not yet been retrieved, or this value was not found in the pieces array, treat it as a
            // cookie. IF non-cookies have been parsed, then this isn't a cookie, it's cookie data. Cookies then data.
            $data[$foundNonCookies ? 'data' : 'cookies'][$key] = $value;
        }

        // Calculate the expires date
        if (!$data['expires'] && $data['max_age']) {
            $data['expires'] = time() + (int) $data['max_age'];
        }

        // Check path attribute according RFC6265 http://tools.ietf.org/search/rfc6265#section-5.2.4
        // "If the attribute-value is empty or if the first character of the
        // attribute-value is not %x2F ("/"):
        //   Let cookie-path be the default-path.
        // Otherwise:
        //   Let cookie-path be the attribute-value."
        if (!$data['path'] || substr($data['path'], 0, 1) !== '/') {
            $data['path'] = $this->getDefaultPath($path);
        }

        return $data;
    }

    /**
     * Get default cookie path according to RFC 6265
     * http://tools.ietf.org/search/rfc6265#section-5.1.4 Paths and Path-Match
     *
     * @param string $path Request uri-path
     *
     * @return string
     */
    protected function getDefaultPath($path) {
        // "The user agent MUST use an algorithm equivalent to the following algorithm
        // to compute the default-path of a cookie:"

        // "2. If the uri-path is empty or if the first character of the uri-path is not
        // a %x2F ("/") character, output %x2F ("/") and skip the remaining steps.
        if (empty($path) || substr($path, 0, 1) !== '/') {
            return '/';
        }

        // "3. If the uri-path contains no more than one %x2F ("/") character, output
        // %x2F ("/") and skip the remaining step."
        if ($path === "/") {
            return $path;
        }

        $rightSlashPos = strrpos($path, '/');
        if ($rightSlashPos === 0) {
            return "/";
        }

        // "4. Output the characters of the uri-path from the first character up to,
        // but not including, the right-most %x2F ("/")."
        return substr($path, 0, $rightSlashPos);

    }
}
