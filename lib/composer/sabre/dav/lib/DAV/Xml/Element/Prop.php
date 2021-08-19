<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Element;

use Sabre\DAV\Xml\Property\Complex;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

/**
 * This class is responsible for decoding the {DAV:}prop element as it appears
 * in {DAV:}property-update.
 *
 * This class doesn't return an instance of itself. It just returns a
 * key->value array.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Prop implements XmlDeserializable
{
    /**
     * The deserialize method is called during xml parsing.
     *
     * This method is called statically, this is because in theory this method
     * may be used as a type of constructor, or factory method.
     *
     * Often you want to return an instance of the current class, but you are
     * free to return other data as well.
     *
     * You are responsible for advancing the reader to the next element. Not
     * doing anything will result in a never-ending loop.
     *
     * If you just want to skip parsing for this element altogether, you can
     * just call $reader->next();
     *
     * $reader->parseInnerTree() will parse the entire sub-tree, and advance to
     * the next element.
     *
     * @return mixed
     */
    public static function xmlDeserialize(Reader $reader)
    {
        // If there's no children, we don't do anything.
        if ($reader->isEmptyElement) {
            $reader->next();

            return [];
        }

        $values = [];

        $reader->read();
        do {
            if (Reader::ELEMENT === $reader->nodeType) {
                $clark = $reader->getClark();
                $values[$clark] = self::parseCurrentElement($reader)['value'];
            } else {
                $reader->read();
            }
        } while (Reader::END_ELEMENT !== $reader->nodeType);

        $reader->read();

        return $values;
    }

    /**
     * This function behaves similar to Sabre\Xml\Reader::parseCurrentElement,
     * but instead of creating deep xml array structures, it will turn any
     * top-level element it doesn't recognize into either a string, or an
     * XmlFragment class.
     *
     * This method returns arn array with 2 properties:
     *   * name - A clark-notation XML element name.
     *   * value - The parsed value.
     *
     * @return array
     */
    private static function parseCurrentElement(Reader $reader)
    {
        $name = $reader->getClark();

        if (array_key_exists($name, $reader->elementMap)) {
            $deserializer = $reader->elementMap[$name];
            if (is_subclass_of($deserializer, 'Sabre\\Xml\\XmlDeserializable')) {
                $value = call_user_func([$deserializer, 'xmlDeserialize'], $reader);
            } elseif (is_callable($deserializer)) {
                $value = call_user_func($deserializer, $reader);
            } else {
                $type = gettype($deserializer);
                if ('string' === $type) {
                    $type .= ' ('.$deserializer.')';
                } elseif ('object' === $type) {
                    $type .= ' ('.get_class($deserializer).')';
                }
                throw new \LogicException('Could not use this type as a deserializer: '.$type);
            }
        } else {
            $value = Complex::xmlDeserialize($reader);
        }

        return [
            'name' => $name,
            'value' => $value,
        ];
    }
}
