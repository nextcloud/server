<?php

namespace Guzzle\Http;

use Guzzle\Common\Collection;
use Guzzle\Common\Exception\RuntimeException;
use Guzzle\Http\QueryAggregator\DuplicateAggregator;
use Guzzle\Http\QueryAggregator\QueryAggregatorInterface;
use Guzzle\Http\QueryAggregator\PhpAggregator;

/**
 * Query string object to handle managing query string parameters and aggregating those parameters together as a string.
 */
class QueryString extends Collection
{
    /** @var string Used to URL encode with rawurlencode */
    const RFC_3986 = 'RFC 3986';

    /** @var string Used to encode with urlencode */
    const FORM_URLENCODED = 'application/x-www-form-urlencoded';

    /** @var string Constant used to create blank query string values (e.g. ?foo) */
    const BLANK = "_guzzle_blank_";

    /** @var string The query string field separator (e.g. '&') */
    protected $fieldSeparator = '&';

    /** @var string The query string value separator (e.g. '=') */
    protected $valueSeparator = '=';

    /** @var bool URL encode fields and values */
    protected $urlEncode = 'RFC 3986';

    /** @var QueryAggregatorInterface */
    protected $aggregator;

    /** @var array Cached PHP aggregator */
    private static $defaultAggregator = null;

    /**
     * Parse a query string into a QueryString object
     *
     * @param string $query Query string to parse
     *
     * @return self
     */
    public static function fromString($query)
    {
        $q = new static();
        if ($query === '') {
            return $q;
        }

        $foundDuplicates = $foundPhpStyle = false;

        foreach (explode('&', $query) as $kvp) {
            $parts = explode('=', $kvp, 2);
            $key = rawurldecode($parts[0]);
            if ($paramIsPhpStyleArray = substr($key, -2) == '[]') {
                $foundPhpStyle = true;
                $key = substr($key, 0, -2);
            }
            if (isset($parts[1])) {
                $value = rawurldecode(str_replace('+', '%20', $parts[1]));
                if (isset($q[$key])) {
                    $q->add($key, $value);
                    $foundDuplicates = true;
                } elseif ($paramIsPhpStyleArray) {
                    $q[$key] = array($value);
                } else {
                    $q[$key] = $value;
                }
            } else {
                // Uses false by default to represent keys with no trailing "=" sign.
                $q->add($key, false);
            }
        }

        // Use the duplicate aggregator if duplicates were found and not using PHP style arrays
        if ($foundDuplicates && !$foundPhpStyle) {
            $q->setAggregator(new DuplicateAggregator());
        }

        return $q;
    }

    /**
     * Convert the query string parameters to a query string string
     *
     * @return string
     * @throws RuntimeException
     */
    public function __toString()
    {
        if (!$this->data) {
            return '';
        }

        $queryList = array();
        foreach ($this->prepareData($this->data) as $name => $value) {
            $queryList[] = $this->convertKvp($name, $value);
        }

        return implode($this->fieldSeparator, $queryList);
    }

    /**
     * Get the query string field separator
     *
     * @return string
     */
    public function getFieldSeparator()
    {
        return $this->fieldSeparator;
    }

    /**
     * Get the query string value separator
     *
     * @return string
     */
    public function getValueSeparator()
    {
        return $this->valueSeparator;
    }

    /**
     * Returns the type of URL encoding used by the query string
     *
     * One of: false, "RFC 3986", or "application/x-www-form-urlencoded"
     *
     * @return bool|string
     */
    public function getUrlEncoding()
    {
        return $this->urlEncode;
    }

    /**
     * Returns true or false if using URL encoding
     *
     * @return bool
     */
    public function isUrlEncoding()
    {
        return $this->urlEncode !== false;
    }

    /**
     * Provide a function for combining multi-valued query string parameters into a single or multiple fields
     *
     * @param null|QueryAggregatorInterface $aggregator Pass in a QueryAggregatorInterface object to handle converting
     *                                                  deeply nested query string variables into a flattened array.
     *                                                  Pass null to use the default PHP style aggregator. For legacy
     *                                                  reasons, this function accepts a callable that must accepts a
     *                                                  $key, $value, and query object.
     * @return self
     * @see \Guzzle\Http\QueryString::aggregateUsingComma()
     */
    public function setAggregator(QueryAggregatorInterface $aggregator = null)
    {
        // Use the default aggregator if none was set
        if (!$aggregator) {
            if (!self::$defaultAggregator) {
                self::$defaultAggregator = new PhpAggregator();
            }
            $aggregator = self::$defaultAggregator;
        }

        $this->aggregator = $aggregator;

        return $this;
    }

    /**
     * Set whether or not field names and values should be rawurlencoded
     *
     * @param bool|string $encode Set to TRUE to use RFC 3986 encoding (rawurlencode), false to disable encoding, or
     *                            form_urlencoding to use application/x-www-form-urlencoded encoding (urlencode)
     * @return self
     */
    public function useUrlEncoding($encode)
    {
        $this->urlEncode = ($encode === true) ? self::RFC_3986 : $encode;

        return $this;
    }

    /**
     * Set the query string separator
     *
     * @param string $separator The query string separator that will separate fields
     *
     * @return self
     */
    public function setFieldSeparator($separator)
    {
        $this->fieldSeparator = $separator;

        return $this;
    }

    /**
     * Set the query string value separator
     *
     * @param string $separator The query string separator that will separate values from fields
     *
     * @return self
     */
    public function setValueSeparator($separator)
    {
        $this->valueSeparator = $separator;

        return $this;
    }

    /**
     * Returns an array of url encoded field names and values
     *
     * @return array
     */
    public function urlEncode()
    {
        return $this->prepareData($this->data);
    }

    /**
     * URL encodes a value based on the url encoding type of the query string object
     *
     * @param string $value Value to encode
     *
     * @return string
     */
    public function encodeValue($value)
    {
        if ($this->urlEncode == self::RFC_3986) {
            return rawurlencode($value);
        } elseif ($this->urlEncode == self::FORM_URLENCODED) {
            return urlencode($value);
        } else {
            return (string) $value;
        }
    }

    /**
     * Url encode parameter data and convert nested query strings into a flattened hash.
     *
     * @param array $data The data to encode
     *
     * @return array Returns an array of encoded values and keys
     */
    protected function prepareData(array $data)
    {
        // If no aggregator is present then set the default
        if (!$this->aggregator) {
            $this->setAggregator(null);
        }

        $temp = array();
        foreach ($data as $key => $value) {
            if ($value === false || $value === null) {
                // False and null will not include the "=". Use an empty string to include the "=".
                $temp[$this->encodeValue($key)] = $value;
            } elseif (is_array($value)) {
                $temp = array_merge($temp, $this->aggregator->aggregate($key, $value, $this));
            } else {
                $temp[$this->encodeValue($key)] = $this->encodeValue($value);
            }
        }

        return $temp;
    }

    /**
     * Converts a key value pair that can contain strings, nulls, false, or arrays
     * into a single string.
     *
     * @param string $name  Name of the field
     * @param mixed  $value Value of the field
     * @return string
     */
    private function convertKvp($name, $value)
    {
        if ($value === self::BLANK || $value === null || $value === false) {
            return $name;
        } elseif (!is_array($value)) {
            return $name . $this->valueSeparator . $value;
        }

        $result = '';
        foreach ($value as $v) {
            $result .= $this->convertKvp($name, $v) . $this->fieldSeparator;
        }

        return rtrim($result, $this->fieldSeparator);
    }
}
