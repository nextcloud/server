<?php

declare(strict_types=1);

namespace Sabre\Xml\Serializer;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * This file provides a number of 'serializer' helper functions.
 *
 * These helper functions can be used to easily xml-encode common PHP
 * data structures, or can be placed in the $classMap.
 */

/**
 * The 'enum' serializer writes simple list of elements.
 *
 * For example, calling:
 *
 * enum($writer, [
 *   "{http://sabredav.org/ns}elem1",
 *   "{http://sabredav.org/ns}elem2",
 *   "{http://sabredav.org/ns}elem3",
 *   "{http://sabredav.org/ns}elem4",
 *   "{http://sabredav.org/ns}elem5",
 * ]);
 *
 * Will generate something like this (if the correct namespace is declared):
 *
 * <s:elem1 />
 * <s:elem2 />
 * <s:elem3 />
 * <s:elem4>content</s:elem4>
 * <s:elem5 attr="val" />
 *
 * @param string[] $values
 */
function enum(Writer $writer, array $values)
{
    foreach ($values as $value) {
        $writer->writeElement($value);
    }
}

/**
 * The valueObject serializer turns a simple PHP object into a classname.
 *
 * Every public property will be encoded as an xml element with the same
 * name, in the XML namespace as specified.
 *
 * Values that are set to null or an empty array are not serialized. To
 * serialize empty properties, you must specify them as an empty string.
 *
 * @param object $valueObject
 */
function valueObject(Writer $writer, $valueObject, string $namespace)
{
    foreach (get_object_vars($valueObject) as $key => $val) {
        if (is_array($val)) {
            // If $val is an array, it has a special meaning. We need to
            // generate one child element for each item in $val
            foreach ($val as $child) {
                $writer->writeElement('{'.$namespace.'}'.$key, $child);
            }
        } elseif (null !== $val) {
            $writer->writeElement('{'.$namespace.'}'.$key, $val);
        }
    }
}

/**
 * This serializer helps you serialize xml structures that look like
 * this:.
 *
 * <collection>
 *    <item>...</item>
 *    <item>...</item>
 *    <item>...</item>
 * </collection>
 *
 * In that previous example, this serializer just serializes the item element,
 * and this could be called like this:
 *
 * repeatingElements($writer, $items, '{}item');
 */
function repeatingElements(Writer $writer, array $items, string $childElementName)
{
    foreach ($items as $item) {
        $writer->writeElement($childElementName, $item);
    }
}

/**
 * This function is the 'default' serializer that is able to serialize most
 * things, and delegates to other serializers if needed.
 *
 * The standardSerializer supports a wide-array of values.
 *
 * $value may be a string or integer, it will just write out the string as text.
 * $value may be an instance of XmlSerializable or Element, in which case it
 *    calls it's xmlSerialize() method.
 * $value may be a PHP callback/function/closure, in case we call the callback
 *    and give it the Writer as an argument.
 * $value may be a an object, and if it's in the classMap we automatically call
 *    the correct serializer for it.
 * $value may be null, in which case we do nothing.
 *
 * If $value is an array, the array must look like this:
 *
 * [
 *    [
 *       'name' => '{namespaceUri}element-name',
 *       'value' => '...',
 *       'attributes' => [ 'attName' => 'attValue' ]
 *    ]
 *    [,
 *       'name' => '{namespaceUri}element-name2',
 *       'value' => '...',
 *    ]
 * ]
 *
 * This would result in xml like:
 *
 * <element-name xmlns="namespaceUri" attName="attValue">
 *   ...
 * </element-name>
 * <element-name2>
 *   ...
 * </element-name2>
 *
 * The value property may be any value standardSerializer supports, so you can
 * nest data-structures this way. Both value and attributes are optional.
 *
 * Alternatively, you can also specify the array using this syntax:
 *
 * [
 *    [
 *       '{namespaceUri}element-name' => '...',
 *       '{namespaceUri}element-name2' => '...',
 *    ]
 * ]
 *
 * This is excellent for simple key->value structures, and here you can also
 * specify anything for the value.
 *
 * You can even mix the two array syntaxes.
 *
 * @param string|int|float|bool|array|object $value
 */
function standardSerializer(Writer $writer, $value)
{
    if (is_scalar($value)) {
        // String, integer, float, boolean
        $writer->text((string) $value);
    } elseif ($value instanceof XmlSerializable) {
        // XmlSerializable classes or Element classes.
        $value->xmlSerialize($writer);
    } elseif (is_object($value) && isset($writer->classMap[get_class($value)])) {
        // It's an object which class appears in the classmap.
        $writer->classMap[get_class($value)]($writer, $value);
    } elseif (is_callable($value)) {
        // A callback
        $value($writer);
    } elseif (is_array($value) && array_key_exists('name', $value)) {
        // if the array had a 'name' element, we assume that this array
        // describes a 'name' and optionally 'attributes' and 'value'.

        $name = $value['name'];
        $attributes = isset($value['attributes']) ? $value['attributes'] : [];
        $value = isset($value['value']) ? $value['value'] : null;

        $writer->startElement($name);
        $writer->writeAttributes($attributes);
        $writer->write($value);
        $writer->endElement();
    } elseif (is_array($value)) {
        foreach ($value as $name => $item) {
            if (is_int($name)) {
                // This item has a numeric index. We just loop through the
                // array and throw it back in the writer.
                standardSerializer($writer, $item);
            } elseif (is_string($name) && is_array($item) && isset($item['attributes'])) {
                // The key is used for a name, but $item has 'attributes' and
                // possibly 'value'
                $writer->startElement($name);
                $writer->writeAttributes($item['attributes']);
                if (isset($item['value'])) {
                    $writer->write($item['value']);
                }
                $writer->endElement();
            } elseif (is_string($name)) {
                // This was a plain key-value array.
                $writer->startElement($name);
                $writer->write($item);
                $writer->endElement();
            } else {
                throw new \InvalidArgumentException('The writer does not know how to serialize arrays with keys of type: '.gettype($name));
            }
        }
    } elseif (is_object($value)) {
        throw new \InvalidArgumentException('The writer cannot serialize objects of class: '.get_class($value));
    } elseif (!is_null($value)) {
        throw new \InvalidArgumentException('The writer cannot serialize values of type: '.gettype($value));
    }
}
