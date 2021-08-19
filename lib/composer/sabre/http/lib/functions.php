<?php

declare(strict_types=1);

namespace Sabre\HTTP;

use DateTime;
use InvalidArgumentException;

/**
 * A collection of useful helpers for parsing or generating various HTTP
 * headers.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */

/**
 * Parses a HTTP date-string.
 *
 * This method returns false if the date is invalid.
 *
 * The following formats are supported:
 *    Sun, 06 Nov 1994 08:49:37 GMT    ; IMF-fixdate
 *    Sunday, 06-Nov-94 08:49:37 GMT   ; obsolete RFC 850 format
 *    Sun Nov  6 08:49:37 1994         ; ANSI C's asctime() format
 *
 * See:
 *   http://tools.ietf.org/html/rfc7231#section-7.1.1.1
 *
 * @return bool|DateTime
 */
function parseDate(string $dateString)
{
    // Only the format is checked, valid ranges are checked by strtotime below
    $month = '(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)';
    $weekday = '(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)';
    $wkday = '(Mon|Tue|Wed|Thu|Fri|Sat|Sun)';
    $time = '([0-1]\d|2[0-3])(\:[0-5]\d){2}';
    $date3 = $month.' ([12]\d|3[01]| [1-9])';
    $date2 = '(0[1-9]|[12]\d|3[01])\-'.$month.'\-\d{2}';
    // 4-digit year cannot begin with 0 - unix timestamp begins in 1970
    $date1 = '(0[1-9]|[12]\d|3[01]) '.$month.' [1-9]\d{3}';

    // ANSI C's asctime() format
    // 4-digit year cannot begin with 0 - unix timestamp begins in 1970
    $asctime_date = $wkday.' '.$date3.' '.$time.' [1-9]\d{3}';
    // RFC 850, obsoleted by RFC 1036
    $rfc850_date = $weekday.', '.$date2.' '.$time.' GMT';
    // RFC 822, updated by RFC 1123
    $rfc1123_date = $wkday.', '.$date1.' '.$time.' GMT';
    // allowed date formats by RFC 2616
    $HTTP_date = "($rfc1123_date|$rfc850_date|$asctime_date)";

    // allow for space around the string and strip it
    $dateString = trim($dateString, ' ');
    if (!preg_match('/^'.$HTTP_date.'$/', $dateString)) {
        return false;
    }

    // append implicit GMT timezone to ANSI C time format
    if (false === strpos($dateString, ' GMT')) {
        $dateString .= ' GMT';
    }

    try {
        return new DateTime($dateString, new \DateTimeZone('UTC'));
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Transforms a DateTime object to a valid HTTP/1.1 Date header value.
 */
function toDate(DateTime $dateTime): string
{
    // We need to clone it, as we don't want to affect the existing
    // DateTime.
    $dateTime = clone $dateTime;
    $dateTime->setTimezone(new \DateTimeZone('GMT'));

    return $dateTime->format('D, d M Y H:i:s \G\M\T');
}

/**
 * This function can be used to aid with content negotiation.
 *
 * It takes 2 arguments, the $acceptHeaderValue, which usually comes from
 * an Accept header, and $availableOptions, which contains an array of
 * items that the server can support.
 *
 * The result of this function will be the 'best possible option'. If no
 * best possible option could be found, null is returned.
 *
 * When it's null you can according to the spec either return a default, or
 * you can choose to emit 406 Not Acceptable.
 *
 * The method also accepts sending 'null' for the $acceptHeaderValue,
 * implying that no accept header was sent.
 *
 * @param string|null $acceptHeaderValue
 *
 * @return string|null
 */
function negotiateContentType($acceptHeaderValue, array $availableOptions)
{
    if (!$acceptHeaderValue) {
        // Grabbing the first in the list.
        return reset($availableOptions);
    }

    $proposals = array_map(
        'Sabre\HTTP\parseMimeType',
        explode(',', $acceptHeaderValue)
    );

    // Ensuring array keys are reset.
    $availableOptions = array_values($availableOptions);

    $options = array_map(
        'Sabre\HTTP\parseMimeType',
        $availableOptions
    );

    $lastQuality = 0;
    $lastSpecificity = 0;
    $lastOptionIndex = 0;
    $lastChoice = null;

    foreach ($proposals as $proposal) {
        // Ignoring broken values.
        if (null === $proposal) {
            continue;
        }

        // If the quality is lower we don't have to bother comparing.
        if ($proposal['quality'] < $lastQuality) {
            continue;
        }

        foreach ($options as $optionIndex => $option) {
            if ('*' !== $proposal['type'] && $proposal['type'] !== $option['type']) {
                // no match on type.
                continue;
            }
            if ('*' !== $proposal['subType'] && $proposal['subType'] !== $option['subType']) {
                // no match on subtype.
                continue;
            }

            // Any parameters appearing on the options must appear on
            // proposals.
            foreach ($option['parameters'] as $paramName => $paramValue) {
                if (!array_key_exists($paramName, $proposal['parameters'])) {
                    continue 2;
                }
                if ($paramValue !== $proposal['parameters'][$paramName]) {
                    continue 2;
                }
            }

            // If we got here, we have a match on parameters, type and
            // subtype. We need to calculate a score for how specific the
            // match was.
            $specificity =
                ('*' !== $proposal['type'] ? 20 : 0) +
                ('*' !== $proposal['subType'] ? 10 : 0) +
                count($option['parameters']);

            // Does this entry win?
            if (
                ($proposal['quality'] > $lastQuality) ||
                ($proposal['quality'] === $lastQuality && $specificity > $lastSpecificity) ||
                ($proposal['quality'] === $lastQuality && $specificity === $lastSpecificity && $optionIndex < $lastOptionIndex)
            ) {
                $lastQuality = $proposal['quality'];
                $lastSpecificity = $specificity;
                $lastOptionIndex = $optionIndex;
                $lastChoice = $availableOptions[$optionIndex];
            }
        }
    }

    return $lastChoice;
}

/**
 * Parses the Prefer header, as defined in RFC7240.
 *
 * Input can be given as a single header value (string) or multiple headers
 * (array of string).
 *
 * This method will return a key->value array with the various Prefer
 * parameters.
 *
 * Prefer: return=minimal will result in:
 *
 * [ 'return' => 'minimal' ]
 *
 * Prefer: foo, wait=10 will result in:
 *
 * [ 'foo' => true, 'wait' => '10']
 *
 * This method also supports the formats from older drafts of RFC7240, and
 * it will automatically map them to the new values, as the older values
 * are still pretty common.
 *
 * Parameters are currently discarded. There's no known prefer value that
 * uses them.
 *
 * @param string|string[] $input
 */
function parsePrefer($input): array
{
    $token = '[!#$%&\'*+\-.^_`~A-Za-z0-9]+';

    // Work in progress
    $word = '(?: [a-zA-Z0-9]+ | "[a-zA-Z0-9]*" )';

    $regex = <<<REGEX
/
^
(?<name> $token)      # Prefer property name
\s*                   # Optional space
(?: = \s*             # Prefer property value
   (?<value> $word)
)?
(?: \s* ; (?: .*))?   # Prefer parameters (ignored)
$
/x
REGEX;

    $output = [];
    foreach (getHeaderValues($input) as $value) {
        if (!preg_match($regex, $value, $matches)) {
            // Ignore
            continue;
        }

        // Mapping old values to their new counterparts
        switch ($matches['name']) {
            case 'return-asynch':
                $output['respond-async'] = true;
                break;
            case 'return-representation':
                $output['return'] = 'representation';
                break;
            case 'return-minimal':
                $output['return'] = 'minimal';
                break;
            case 'strict':
                $output['handling'] = 'strict';
                break;
            case 'lenient':
                $output['handling'] = 'lenient';
                break;
            default:
                if (isset($matches['value'])) {
                    $value = trim($matches['value'], '"');
                } else {
                    $value = true;
                }
                $output[strtolower($matches['name'])] = empty($value) ? true : $value;
                break;
        }
    }

    return $output;
}

/**
 * This method splits up headers into all their individual values.
 *
 * A HTTP header may have more than one header, such as this:
 *   Cache-Control: private, no-store
 *
 * Header values are always split with a comma.
 *
 * You can pass either a string, or an array. The resulting value is always
 * an array with each spliced value.
 *
 * If the second headers argument is set, this value will simply be merged
 * in. This makes it quicker to merge an old list of values with a new set.
 *
 * @param string|string[] $values
 * @param string|string[] $values2
 */
function getHeaderValues($values, $values2 = null): array
{
    $values = (array) $values;
    if ($values2) {
        $values = array_merge($values, (array) $values2);
    }

    $result = [];
    foreach ($values as $l1) {
        foreach (explode(',', $l1) as $l2) {
            $result[] = trim($l2);
        }
    }

    return $result;
}

/**
 * Parses a mime-type and splits it into:.
 *
 * 1. type
 * 2. subtype
 * 3. quality
 * 4. parameters
 */
function parseMimeType(string $str): array
{
    $parameters = [];
    // If no q= parameter appears, then quality = 1.
    $quality = 1;

    $parts = explode(';', $str);

    // The first part is the mime-type.
    $mimeType = trim(array_shift($parts));

    if ('*' === $mimeType) {
        $mimeType = '*/*';
    }

    $mimeType = explode('/', $mimeType);
    if (2 !== count($mimeType)) {
        // Illegal value
        var_dump($mimeType);
        die();
        throw new InvalidArgumentException('Not a valid mime-type: '.$str);
    }
    list($type, $subType) = $mimeType;

    foreach ($parts as $part) {
        $part = trim($part);
        if (strpos($part, '=')) {
            list($partName, $partValue) =
                explode('=', $part, 2);
        } else {
            $partName = $part;
            $partValue = null;
        }

        // The quality parameter, if it appears, also marks the end of
        // the parameter list. Anything after the q= counts as an
        // 'accept extension' and could introduce new semantics in
        // content-negotation.
        if ('q' !== $partName) {
            $parameters[$partName] = $part;
        } else {
            $quality = (float) $partValue;
            break; // Stop parsing parts
        }
    }

    return [
        'type' => $type,
        'subType' => $subType,
        'quality' => $quality,
        'parameters' => $parameters,
    ];
}

/**
 * Encodes the path of a url.
 *
 * slashes (/) are treated as path-separators.
 */
function encodePath(string $path): string
{
    return preg_replace_callback('/([^A-Za-z0-9_\-\.~\(\)\/:@])/', function ($match) {
        return '%'.sprintf('%02x', ord($match[0]));
    }, $path);
}

/**
 * Encodes a 1 segment of a path.
 *
 * Slashes are considered part of the name, and are encoded as %2f
 */
function encodePathSegment(string $pathSegment): string
{
    return preg_replace_callback('/([^A-Za-z0-9_\-\.~\(\):@])/', function ($match) {
        return '%'.sprintf('%02x', ord($match[0]));
    }, $pathSegment);
}

/**
 * Decodes a url-encoded path.
 */
function decodePath(string $path): string
{
    return decodePathSegment($path);
}

/**
 * Decodes a url-encoded path segment.
 */
function decodePathSegment(string $path): string
{
    $path = rawurldecode($path);
    $encoding = mb_detect_encoding($path, ['UTF-8', 'ISO-8859-1']);

    switch ($encoding) {
        case 'ISO-8859-1':
            $path = utf8_encode($path);
    }

    return $path;
}
