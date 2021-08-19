<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\ASN1;

use ArrayAccess;
use ArrayIterator;
use Countable;
use FG\ASN1\Exception\ParserException;
use Iterator;

abstract class Construct extends ASNObject implements Countable, ArrayAccess, Iterator, Parsable
{
    /** @var \FG\ASN1\ASNObject[] */
    protected $children;
    private $iteratorPosition;

    /**
     * @param \FG\ASN1\ASNObject[] $children the variadic type hint is commented due to https://github.com/facebook/hhvm/issues/4858
     */
    public function __construct(/* HH_FIXME[4858]: variadic + strict */ ...$children)
    {
        $this->children = $children;
        $this->iteratorPosition = 0;
    }

    public function getContent()
    {
        return $this->children;
    }

    public function rewind()
    {
        $this->iteratorPosition = 0;
    }

    public function current()
    {
        return $this->children[$this->iteratorPosition];
    }

    public function key()
    {
        return $this->iteratorPosition;
    }

    public function next()
    {
        $this->iteratorPosition++;
    }

    public function valid()
    {
        return isset($this->children[$this->iteratorPosition]);
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->children);
    }

    public function offsetGet($offset)
    {
        return $this->children[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $offset = count($this->children);
        }

        $this->children[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->children[$offset]);
    }

    protected function calculateContentLength()
    {
        $length = 0;
        foreach ($this->children as $component) {
            $length += $component->getObjectLength();
        }

        return $length;
    }

    protected function getEncodedValue()
    {
        $result = '';
        foreach ($this->children as $component) {
            $result .= $component->getBinary();
        }

        return $result;
    }

    public function addChild(ASNObject $child)
    {
        $this->children[] = $child;
    }

    public function addChildren(array $children)
    {
        foreach ($children as $child) {
            $this->addChild($child);
        }
    }

    public function __toString()
    {
        $nrOfChildren = $this->getNumberOfChildren();
        $childString = $nrOfChildren == 1 ? 'child' : 'children';

        return "[{$nrOfChildren} {$childString}]";
    }

    public function getNumberOfChildren()
    {
        return count($this->children);
    }

    /**
     * @return \FG\ASN1\ASNObject[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return \FG\ASN1\ASNObject
     */
    public function getFirstChild()
    {
        return $this->children[0];
    }

    /**
     * @param string $binaryData
     * @param int $offsetIndex
     *
     * @throws Exception\ParserException
     *
     * @return Construct|static
     */
    public static function fromBinary(&$binaryData, &$offsetIndex = 0)
    {
        $parsedObject = new static();
        self::parseIdentifier($binaryData[$offsetIndex], $parsedObject->getType(), $offsetIndex++);
        $contentLength = self::parseContentLength($binaryData, $offsetIndex);
        $startIndex = $offsetIndex;

        $children = [];
        $octetsToRead = $contentLength;
        while ($octetsToRead > 0) {
            $newChild = ASNObject::fromBinary($binaryData, $offsetIndex);
            $octetsToRead -= $newChild->getObjectLength();
            $children[] = $newChild;
        }

        if ($octetsToRead !== 0) {
            throw new ParserException("Sequence length incorrect", $startIndex);
        }

        $parsedObject->addChildren($children);
        $parsedObject->setContentLength($contentLength);

        return $parsedObject;
    }

    public function count($mode = COUNT_NORMAL)
    {
        return count($this->children, $mode);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->children);
    }
}
