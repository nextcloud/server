<?php

namespace Guzzle\Parser\UriTemplate;

/**
 * Expands URI templates using an array of variables
 *
 * @link http://tools.ietf.org/html/draft-gregorio-uritemplate-08
 */
class UriTemplate implements UriTemplateInterface
{
    const DEFAULT_PATTERN = '/\{([^\}]+)\}/';

    /** @var string URI template */
    private $template;

    /** @var array Variables to use in the template expansion */
    private $variables;

    /** @var string Regex used to parse expressions */
    private $regex = self::DEFAULT_PATTERN;

    /** @var array Hash for quick operator lookups */
    private static $operatorHash = array(
        '+' => true, '#' => true, '.' => true, '/' => true, ';' => true, '?' => true, '&' => true
    );

    /** @var array Delimiters */
    private static $delims = array(
        ':', '/', '?', '#', '[', ']', '@', '!', '$', '&', '\'', '(', ')', '*', '+', ',', ';', '='
    );

    /** @var array Percent encoded delimiters */
    private static $delimsPct = array(
        '%3A', '%2F', '%3F', '%23', '%5B', '%5D', '%40', '%21', '%24', '%26', '%27', '%28', '%29', '%2A', '%2B', '%2C',
        '%3B', '%3D'
    );

    public function expand($template, array $variables)
    {
        if ($this->regex == self::DEFAULT_PATTERN && false === strpos($template, '{')) {
            return $template;
        }

        $this->template = $template;
        $this->variables = $variables;

        return preg_replace_callback($this->regex, array($this, 'expandMatch'), $this->template);
    }

    /**
     * Set the regex patten used to expand URI templates
     *
     * @param string $regexPattern
     */
    public function setRegex($regexPattern)
    {
        $this->regex = $regexPattern;
    }

    /**
     * Parse an expression into parts
     *
     * @param string $expression Expression to parse
     *
     * @return array Returns an associative array of parts
     */
    private function parseExpression($expression)
    {
        // Check for URI operators
        $operator = '';

        if (isset(self::$operatorHash[$expression[0]])) {
            $operator = $expression[0];
            $expression = substr($expression, 1);
        }

        $values = explode(',', $expression);
        foreach ($values as &$value) {
            $value = trim($value);
            $varspec = array();
            $substrPos = strpos($value, ':');
            if ($substrPos) {
                $varspec['value'] = substr($value, 0, $substrPos);
                $varspec['modifier'] = ':';
                $varspec['position'] = (int) substr($value, $substrPos + 1);
            } elseif (substr($value, -1) == '*') {
                $varspec['modifier'] = '*';
                $varspec['value'] = substr($value, 0, -1);
            } else {
                $varspec['value'] = (string) $value;
                $varspec['modifier'] = '';
            }
            $value = $varspec;
        }

        return array(
            'operator' => $operator,
            'values'   => $values
        );
    }

    /**
     * Process an expansion
     *
     * @param array $matches Matches met in the preg_replace_callback
     *
     * @return string Returns the replacement string
     */
    private function expandMatch(array $matches)
    {
        static $rfc1738to3986 = array(
            '+'   => '%20',
            '%7e' => '~'
        );

        $parsed = self::parseExpression($matches[1]);
        $replacements = array();

        $prefix = $parsed['operator'];
        $joiner = $parsed['operator'];
        $useQueryString = false;
        if ($parsed['operator'] == '?') {
            $joiner = '&';
            $useQueryString = true;
        } elseif ($parsed['operator'] == '&') {
            $useQueryString = true;
        } elseif ($parsed['operator'] == '#') {
            $joiner = ',';
        } elseif ($parsed['operator'] == ';') {
            $useQueryString = true;
        } elseif ($parsed['operator'] == '' || $parsed['operator'] == '+') {
            $joiner = ',';
            $prefix = '';
        }

        foreach ($parsed['values'] as $value) {

            if (!array_key_exists($value['value'], $this->variables) || $this->variables[$value['value']] === null) {
                continue;
            }

            $variable = $this->variables[$value['value']];
            $actuallyUseQueryString = $useQueryString;
            $expanded = '';

            if (is_array($variable)) {

                $isAssoc = $this->isAssoc($variable);
                $kvp = array();
                foreach ($variable as $key => $var) {

                    if ($isAssoc) {
                        $key = rawurlencode($key);
                        $isNestedArray = is_array($var);
                    } else {
                        $isNestedArray = false;
                    }

                    if (!$isNestedArray) {
                        $var = rawurlencode($var);
                        if ($parsed['operator'] == '+' || $parsed['operator'] == '#') {
                            $var = $this->decodeReserved($var);
                        }
                    }

                    if ($value['modifier'] == '*') {
                        if ($isAssoc) {
                            if ($isNestedArray) {
                                // Nested arrays must allow for deeply nested structures
                                $var = strtr(http_build_query(array($key => $var)), $rfc1738to3986);
                            } else {
                                $var = $key . '=' . $var;
                            }
                        } elseif ($key > 0 && $actuallyUseQueryString) {
                            $var = $value['value'] . '=' . $var;
                        }
                    }

                    $kvp[$key] = $var;
                }

                if (empty($variable)) {
                    $actuallyUseQueryString = false;
                } elseif ($value['modifier'] == '*') {
                    $expanded = implode($joiner, $kvp);
                    if ($isAssoc) {
                        // Don't prepend the value name when using the explode modifier with an associative array
                        $actuallyUseQueryString = false;
                    }
                } else {
                    if ($isAssoc) {
                        // When an associative array is encountered and the explode modifier is not set, then the
                        // result must be a comma separated list of keys followed by their respective values.
                        foreach ($kvp as $k => &$v) {
                            $v = $k . ',' . $v;
                        }
                    }
                    $expanded = implode(',', $kvp);
                }

            } else {
                if ($value['modifier'] == ':') {
                    $variable = substr($variable, 0, $value['position']);
                }
                $expanded = rawurlencode($variable);
                if ($parsed['operator'] == '+' || $parsed['operator'] == '#') {
                    $expanded = $this->decodeReserved($expanded);
                }
            }

            if ($actuallyUseQueryString) {
                if (!$expanded && $joiner != '&') {
                    $expanded = $value['value'];
                } else {
                    $expanded = $value['value'] . '=' . $expanded;
                }
            }

            $replacements[] = $expanded;
        }

        $ret = implode($joiner, $replacements);
        if ($ret && $prefix) {
            return $prefix . $ret;
        }

        return $ret;
    }

    /**
     * Determines if an array is associative
     *
     * @param array $array Array to check
     *
     * @return bool
     */
    private function isAssoc(array $array)
    {
        return (bool) count(array_filter(array_keys($array), 'is_string'));
    }

    /**
     * Removes percent encoding on reserved characters (used with + and # modifiers)
     *
     * @param string $string String to fix
     *
     * @return string
     */
    private function decodeReserved($string)
    {
        return str_replace(self::$delimsPct, self::$delims, $string);
    }
}
