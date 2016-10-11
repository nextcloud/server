<?php

namespace Guzzle\Http\Message;

use Guzzle\Common\Version;
use Guzzle\Http\Message\Header\HeaderInterface;

/**
 * Represents a header and all of the values stored by that header
 */
class Header implements HeaderInterface
{
    protected $values = array();
    protected $header;
    protected $glue;

    /**
     * @param string       $header Name of the header
     * @param array|string $values Values of the header as an array or a scalar
     * @param string       $glue   Glue used to combine multiple values into a string
     */
    public function __construct($header, $values = array(), $glue = ',')
    {
        $this->header = trim($header);
        $this->glue = $glue;

        foreach ((array) $values as $value) {
            foreach ((array) $value as $v) {
                $this->values[] = $v;
            }
        }
    }

    public function __toString()
    {
        return implode($this->glue . ' ', $this->toArray());
    }

    public function add($value)
    {
        $this->values[] = $value;

        return $this;
    }

    public function getName()
    {
        return $this->header;
    }

    public function setName($name)
    {
        $this->header = $name;

        return $this;
    }

    public function setGlue($glue)
    {
        $this->glue = $glue;

        return $this;
    }

    public function getGlue()
    {
        return $this->glue;
    }

    /**
     * Normalize the header to be a single header with an array of values.
     *
     * If any values of the header contains the glue string value (e.g. ","), then the value will be exploded into
     * multiple entries in the header.
     *
     * @return self
     */
    public function normalize()
    {
        $values = $this->toArray();

        for ($i = 0, $total = count($values); $i < $total; $i++) {
            if (strpos($values[$i], $this->glue) !== false) {
                // Explode on glue when the glue is not inside of a comma
                foreach (preg_split('/' . preg_quote($this->glue) . '(?=([^"]*"[^"]*")*[^"]*$)/', $values[$i]) as $v) {
                    $values[] = trim($v);
                }
                unset($values[$i]);
            }
        }

        $this->values = array_values($values);

        return $this;
    }

    public function hasValue($searchValue)
    {
        return in_array($searchValue, $this->toArray());
    }

    public function removeValue($searchValue)
    {
        $this->values = array_values(array_filter($this->values, function ($value) use ($searchValue) {
            return $value != $searchValue;
        }));

        return $this;
    }

    public function toArray()
    {
        return $this->values;
    }

    public function count()
    {
        return count($this->toArray());
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->toArray());
    }

    public function parseParams()
    {
        $params = $matches = array();
        $callback = array($this, 'trimHeader');

        // Normalize the header into a single array and iterate over all values
        foreach ($this->normalize()->toArray() as $val) {
            $part = array();
            foreach (preg_split('/;(?=([^"]*"[^"]*")*[^"]*$)/', $val) as $kvp) {
                if (!preg_match_all('/<[^>]+>|[^=]+/', $kvp, $matches)) {
                    continue;
                }
                $pieces = array_map($callback, $matches[0]);
                $part[$pieces[0]] = isset($pieces[1]) ? $pieces[1] : '';
            }
            if ($part) {
                $params[] = $part;
            }
        }

        return $params;
    }

    /**
     * @deprecated
     * @codeCoverageIgnore
     */
    public function hasExactHeader($header)
    {
        Version::warn(__METHOD__ . ' is deprecated');
        return $this->header == $header;
    }

    /**
     * @deprecated
     * @codeCoverageIgnore
     */
    public function raw()
    {
        Version::warn(__METHOD__ . ' is deprecated. Use toArray()');
        return $this->toArray();
    }

    /**
     * Trim a header by removing excess spaces and wrapping quotes
     *
     * @param $str
     *
     * @return string
     */
    protected function trimHeader($str)
    {
        static $trimmed = "\"'  \n\t";

        return trim($str, $trimmed);
    }
}
