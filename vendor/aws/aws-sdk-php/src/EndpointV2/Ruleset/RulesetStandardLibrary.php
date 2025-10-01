<?php

namespace Aws\EndpointV2\Ruleset;

use Aws\Exception\UnresolvedEndpointException;

/**
 * Provides functions and actions to be performed for endpoint evaluation.
 * This is an internal only class and is not subject to backwards-compatibility guarantees.
 *
 * @internal
 */
class RulesetStandardLibrary
{
    const IPV4_RE = '/^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/';
    const IPV6_RE = '/([0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|
                    . ([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]
                    . {1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:)
                    . {1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|
                    . [0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:
                    . (:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|
                    . 1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]
                    . {1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3,3}(25[0-5]|(2[0-4]
                    . |1{0,1}[0-9]){0,1}[0-9])/';
    const TEMPLATE_ESCAPE_RE = '/{\{\s*(.*?)\s*\}\}/';
    const TEMPLATE_SEARCH_RE = '/\{[a-zA-Z#]+\}/';
    const TEMPLATE_PARSE_RE = '#\{((?>[^\{\}]+)|(?R))*\}#x';
    const HOST_LABEL_RE = '/^(?!-)[a-zA-Z\d-]{1,63}(?<!-)$/';

    private $partitions;

    public function __construct($partitions)
    {
        $this->partitions = $partitions;
    }

    /**
     * Determines if a value is set.
     *
     * @return boolean
     */
    public function is_set($value)
    {
        return isset($value);
    }

    /**
     * Function implementation of logical operator `not`
     *
     * @return boolean
     */
    public function not($value)
    {
        return !$value;
    }

    /**
     * Find an attribute within a value given a path string.
     *
     * @return mixed
     */
    public function getAttr($from, $path)
    {
        // Handles the case where "[<int|string]" is provided as the top-level path
        if (preg_match('/^\[(\w+)\]$/', $path, $matches)) {
            $index = is_numeric($matches[1]) ? (int) $matches[1] : $matches[1];

            return $from[$index] ?? null;
        }

        $parts = explode('.', $path);
        foreach ($parts as $part) {
            $sliceIdx = strpos($part, '[');
            if ($sliceIdx !== false) {
                if (substr($part, -1) !== ']') {
                    return null;
                }
                $slice = (int) substr($part, $sliceIdx + 1, strlen($part) - 1);
                $fromIndex = substr($part, 0, $sliceIdx);
                $from = $from[$fromIndex][$slice] ?? null;
            } else {
                $from = $from[$part];
            }
        }
        return $from;
    }

    /**
     * Computes a substring given the start index and end index. If `reverse` is
     * true, slice the string from the end instead.
     *
     * @return mixed
     */
    public function substring($input, $start, $stop, $reverse)
    {
        if (!is_string($input)) {
            throw new UnresolvedEndpointException(
                'Input passed to `substring` must be `string`.'
            );
        }

        if (preg_match('/[^\x00-\x7F]/', $input)) {
            return null;
        }
        if ($start >= $stop or strlen($input) < $stop) {
            return null;
        }
        if (!$reverse) {
            return substr($input, $start, $stop - $start);
        } else {
            $offset = strlen($input) - $stop;
            $length = $stop - $start;
            return substr($input, $offset, $length);
        }
    }

    /**
     * Evaluates two strings for equality.
     *
     * @return boolean
     */
    public function stringEquals($string1, $string2)
    {
        if (!is_string($string1) || !is_string($string2)) {
            throw new UnresolvedEndpointException(
                'Values passed to StringEquals must be `string`.'
            );
        }
        return $string1 === $string2;
    }

    /**
     * Evaluates two booleans for equality.
     *
     * @return boolean
     */
    public function booleanEquals($boolean1, $boolean2)
    {
        return
            filter_var($boolean1, FILTER_VALIDATE_BOOLEAN)
            === filter_var($boolean2, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Percent-encodes an input string.
     *
     * @return mixed
     */
    public function uriEncode($input)
    {
        if (is_null($input)) {
            return null;
        }
        return str_replace('%7E', '~', rawurlencode($input));
    }

    /**
     * Parses URL string into components.
     *
     * @return mixed
     */
    public function parseUrl($url)
    {
        if (is_null($url)) {
            return null;
        }

        $parsed = parse_url($url);

        if ($parsed === false || !empty($parsed['query'])) {
            return null;
        } elseif (!isset($parsed['scheme'])) {
            return null;
        }

        if ($parsed['scheme'] !== 'http'
            && $parsed['scheme'] !== 'https'
        ) {
            return null;
        }

        $urlInfo = [];
        $urlInfo['scheme'] = $parsed['scheme'];
        $urlInfo['authority'] = isset($parsed['host']) ? $parsed['host'] : '';
        if (isset($parsed['port'])) {
            $urlInfo['authority'] = $urlInfo['authority'] . ":" . $parsed['port'];
        }
        $urlInfo['path'] = isset($parsed['path']) ? $parsed['path'] : '';
        $urlInfo['normalizedPath'] = !empty($parsed['path'])
            ? rtrim($urlInfo['path'] ?: '', '/' .  "/") . '/'
            : '/';
        $urlInfo['isIp'] = !isset($parsed['host']) ?
            'false' : $this->isValidIp($parsed['host']);

        return $urlInfo;
    }

    /**
     * Evaluates whether a value is a valid host label per
     * RFC 1123. If allow_subdomains is true, split on `.` and validate
     * each subdomain separately.
     *
     * @return boolean
     */
    public function isValidHostLabel($hostLabel, $allowSubDomains)
    {
        if (!isset($hostLabel)
            || (!$allowSubDomains && strpos($hostLabel, '.') != false)
        ) {
            return false;
        }

        if ($allowSubDomains) {
            foreach (explode('.', $hostLabel) as $subdomain) {
                if (!$this->validateHostLabel($subdomain)) {
                    return false;
                }
            }
            return true;
        } else {
            return $this->validateHostLabel($hostLabel);
        }
    }

    /**
     * Parse and validate string for ARN components.
     *
     * @return array|null
     */
    public function parseArn($arnString)
    {
        if (is_null($arnString)
            || substr( $arnString, 0, 3 ) !== "arn"
        ) {
            return null;
        }

        $arn = [];
        $parts = explode(':', $arnString, 6);
        if (sizeof($parts) < 6) {
            return null;
        }

        $arn['partition'] = isset($parts[1]) ? $parts[1] : null;
        $arn['service'] = isset($parts[2]) ? $parts[2] : null;
        $arn['region'] = isset($parts[3]) ? $parts[3] : null;
        $arn['accountId'] = isset($parts[4]) ? $parts[4] : null;
        $arn['resourceId'] = isset($parts[5]) ? $parts[5] : null;

        if (empty($arn['partition'])
            || empty($arn['service'])
            || empty($arn['resourceId'])
        ) {
            return null;
        }
        $resource = $arn['resourceId'];
        $arn['resourceId'] = preg_split("/[:\/]/", $resource);

        return $arn;
    }

    /**
     * Matches a region string to an AWS partition.
     *
     * @return mixed
     */
    public function partition($region)
    {
        if (!is_string($region)) {
            throw new UnresolvedEndpointException(
                'Value passed to `partition` must be `string`.'
            );
        }

        $partitions = $this->partitions;
        foreach ($partitions['partitions'] as $partition) {
            if (array_key_exists($region, $partition['regions'])
                || preg_match("/{$partition['regionRegex']}/", $region)
            ) {
                return $partition['outputs'];
            }
        }
        //return `aws` partition if no match is found.
        return $partitions['partitions'][0]['outputs'];
    }

    /**
     * Evaluates whether a value is a valid bucket name for virtual host
     * style bucket URLs.
     *
     * @return boolean
     */
    public function isVirtualHostableS3Bucket($bucketName, $allowSubdomains)
    {
        if ((is_null($bucketName)
            || (strlen($bucketName) < 3 || strlen($bucketName) > 63))
            || preg_match(self::IPV4_RE, $bucketName)
            || strtolower($bucketName) !== $bucketName
        ) {
            return false;
        }

        if ($allowSubdomains) {
            $labels = explode('.', $bucketName);
            $results = [];
            forEach($labels as $label) {
                $results[] = $this->isVirtualHostableS3Bucket($label, false);
            }
            return !in_array(false, $results);
        }
        return $this->isValidHostLabel($bucketName, false);
    }

    public function callFunction($funcCondition, &$inputParameters)
    {
        $funcArgs = [];

        forEach($funcCondition['argv'] as $arg) {
            $funcArgs[] = $this->resolveValue($arg, $inputParameters);
        }

        $funcName = str_replace('aws.', '', $funcCondition['fn']);
        if ($funcName === 'isSet') {
            $funcName = 'is_set';
        }

        $result = call_user_func_array(
            [RulesetStandardLibrary::class, $funcName],
            $funcArgs
        );

        if (isset($funcCondition['assign'])) {
            $assign = $funcCondition['assign'];
            if (isset($inputParameters[$assign])){
                throw new UnresolvedEndpointException(
                    "Assignment `{$assign}` already exists in input parameters" .
                    " or has already been assigned by an endpoint rule and cannot be overwritten."
                );
            }
            $inputParameters[$assign] = $result;
        }
        return $result;
    }

    public function resolveValue($value, $inputParameters)
    {
        //Given a value, check if it's a function, reference or template.
        //returns resolved value
        if ($this->isFunc($value)) {
            return $this->callFunction($value, $inputParameters);
        } elseif ($this->isRef($value)) {
            return isset($inputParameters[$value['ref']]) ? $inputParameters[$value['ref']] : null;
        } elseif ($this->isTemplate($value)) {
            return $this->resolveTemplateString($value, $inputParameters);
        }
        return $value;
    }

    public function isFunc($arg)
    {
        return is_array($arg) && isset($arg['fn']);
    }

    public function isRef($arg)
    {
        return is_array($arg) && isset($arg['ref']);
    }

    public function isTemplate($arg)
    {
        return is_string($arg) && !empty(preg_match(self::TEMPLATE_SEARCH_RE, $arg));
    }

    public function resolveTemplateString($value, $inputParameters)
    {
        return preg_replace_callback(
            self::TEMPLATE_PARSE_RE,
            function ($match) use ($inputParameters) {
                if (preg_match(self::TEMPLATE_ESCAPE_RE, $match[0])) {
                    return $match[1];
                }

                $notFoundMessage = 'Resolved value was null.  Please check rules and ' .
                    'input parameters and try again.';

                $parts = explode("#", $match[1]);
                if (count($parts) > 1) {
                    $resolvedValue = $inputParameters;
                    foreach($parts as $part) {
                        if (!isset($resolvedValue[$part])) {
                            throw new UnresolvedEndpointException($notFoundMessage);
                        }
                        $resolvedValue = $resolvedValue[$part];
                    }
                    return $resolvedValue;
                } else {
                    if (!isset($inputParameters[$parts[0]])) {
                        throw new UnresolvedEndpointException($notFoundMessage);
                    }
                    return $inputParameters[$parts[0]];
                }
            },
            $value
        );
    }

    private function validateHostLabel ($hostLabel)
    {
        if (empty($hostLabel) || strlen($hostLabel) > 63) {
            return false;
        }
        if (preg_match(self::HOST_LABEL_RE, $hostLabel)) {
            return true;
        }
        return false;
    }

    private function isValidIp($hostName)
    {
        $isWrapped = strpos($hostName, '[') === 0
            && strrpos($hostName, ']') === strlen($hostName) - 1;

        return preg_match(
                self::IPV4_RE,
            $hostName
        )
        //IPV6 enclosed in brackets
        || ($isWrapped && preg_match(
            self::IPV6_RE,
            $hostName
        ))
            ? 'true' : 'false';
    }
}
