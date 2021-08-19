<?php

declare(strict_types=1);

namespace OpenStack\Common;

/**
 * This class allows arbitrary data structures to be inserted into, and extracted from, deep arrays
 * and JSON-serialized strings. Say, for example, that you have this array as an input:.
 *
 * <pre><code>['foo' => ['bar' => ['baz' => 'some_value']]]</code></pre>
 *
 * and you wanted to insert or extract an element. Usually, you would use:
 *
 * <pre><code>$array['foo']['bar']['baz'] = 'new_value';</code></pre>
 *
 * but sometimes you do not have access to the variable - so a string representation is needed. Using
 * XPath-like syntax, this class allows you to do this:
 *
 * <pre><code>$jsonPath = new JsonPath($array);
 * $jsonPath->set('foo.bar.baz', 'new_value');
 * $val = $jsonPath->get('foo.bar.baz');
 * </code></pre>
 */
class JsonPath
{
    /** @var array */
    private $jsonStructure;

    /**
     * @param $structure The initial data structure to extract from and insert into. Typically this will be a
     *                   multidimensional associative array; but well-formed JSON strings are also acceptable.
     */
    public function __construct($structure)
    {
        $this->jsonStructure = is_string($structure) ? json_decode($structure, true) : $structure;
    }

    /**
     * Set a node in the structure.
     *
     * @param $path  The XPath to use
     * @param $value The new value of the node
     */
    public function set(string $path, $value)
    {
        $this->jsonStructure = $this->setPath($path, $value, $this->jsonStructure);
    }

    /**
     * Internal method for recursive calls.
     *
     * @param $path
     * @param $value
     * @param $json
     *
     * @return mixed
     */
    private function setPath(string $path, $value, array $json): array
    {
        $nodes = explode('.', $path);
        $point = array_shift($nodes);

        if (!isset($json[$point])) {
            $json[$point] = [];
        }

        if (!empty($nodes)) {
            $json[$point] = $this->setPath(implode('.', $nodes), $value, $json[$point]);
        } else {
            $json[$point] = $value;
        }

        return $json;
    }

    /**
     * Return the updated structure.
     *
     * @return mixed
     */
    public function getStructure()
    {
        return $this->jsonStructure;
    }

    /**
     * Get a path's value. If no path can be matched, NULL is returned.
     *
     * @param $path
     *
     * @return mixed|null
     */
    public function get(string $path)
    {
        return $this->getPath($path, $this->jsonStructure);
    }

    /**
     * Internal method for recursion.
     *
     * @param $path
     * @param $json
     */
    private function getPath(string $path, $json)
    {
        $nodes = explode('.', $path);
        $point = array_shift($nodes);

        if (!isset($json[$point])) {
            return null;
        }

        if (empty($nodes)) {
            return $json[$point];
        } else {
            return $this->getPath(implode('.', $nodes), $json[$point]);
        }
    }
}
