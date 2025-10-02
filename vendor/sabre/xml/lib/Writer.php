<?php

declare(strict_types=1);

namespace Sabre\Xml;

use XMLWriter;

/**
 * The XML Writer class.
 *
 * This class works exactly as PHP's built-in XMLWriter, with a few additions.
 *
 * Namespaces can be registered beforehand, globally. When the first element is
 * written, namespaces will automatically be declared.
 *
 * The writeAttribute, startElement and writeElement can now take a
 * clark-notation element name (example: {http://www.w3.org/2005/Atom}link).
 *
 * If, when writing the namespace is a known one a prefix will automatically be
 * selected, otherwise a random prefix will be generated.
 *
 * Instead of standard string values, the writer can take Element classes (as
 * defined by this library) to delegate the serialization.
 *
 * The write() method can take array structures to quickly write out simple xml
 * trees.
 *
 * @copyright Copyright (C) 2009-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Writer extends \XMLWriter
{
    use ContextStackTrait;

    /**
     * Any namespace that the writer is asked to write, will be added here.
     *
     * Any of these elements will get a new namespace definition *every single
     * time* they are used, but this array allows the writer to make sure that
     * the prefixes are consistent anyway.
     *
     * @var array
     */
    protected $adhocNamespaces = [];

    /**
     * When the first element is written, this flag is set to true.
     *
     * This ensures that the namespaces in the namespaces map are only written
     * once.
     *
     * @var bool
     */
    protected $namespacesWritten = false;

    /**
     * Writes a value to the output stream.
     *
     * The following values are supported:
     *   1. Scalar values will be written as-is, as text.
     *   2. Null values will be skipped (resulting in a short xml tag).
     *   3. If a value is an instance of an Element class, writing will be
     *      delegated to the object.
     *   4. If a value is an array, two formats are supported.
     *
     *  Array format 1:
     *  [
     *    "{namespace}name1" => "..",
     *    "{namespace}name2" => "..",
     *  ]
     *
     *  One element will be created for each key in this array. The values of
     *  this array support any format this method supports (this method is
     *  called recursively).
     *
     *  Array format 2:
     *
     *  [
     *    [
     *      "name" => "{namespace}name1"
     *      "value" => "..",
     *      "attributes" => [
     *          "attr" => "attribute value",
     *      ]
     *    ],
     *    [
     *      "name" => "{namespace}name1"
     *      "value" => "..",
     *      "attributes" => [
     *          "attr" => "attribute value",
     *      ]
     *    ]
     * ]
     */
    public function write($value)
    {
        Serializer\standardSerializer($this, $value);
    }

    /**
     * Opens a new element.
     *
     * You can either just use a local elementname, or you can use clark-
     * notation to start a new element.
     *
     * Example:
     *
     *     $writer->startElement('{http://www.w3.org/2005/Atom}entry');
     *
     * Would result in something like:
     *
     *     <entry xmlns="http://w3.org/2005/Atom">
     *
     * Note: this function doesn't have the string typehint, because PHP's
     * XMLWriter::startElement doesn't either.
     *
     * @param string $name
     */
    public function startElement($name): bool
    {
        if ('{' === $name[0]) {
            list($namespace, $localName) =
                Service::parseClarkNotation($name);

            if (array_key_exists($namespace, $this->namespaceMap)) {
                $result = $this->startElementNS(
                    '' === $this->namespaceMap[$namespace] ? null : $this->namespaceMap[$namespace],
                    $localName,
                    null
                );
            } else {
                // An empty namespace means it's the global namespace. This is
                // allowed, but it mustn't get a prefix.
                if ('' === $namespace || null === $namespace) {
                    $result = $this->startElement($localName);
                    $this->writeAttribute('xmlns', '');
                } else {
                    if (!isset($this->adhocNamespaces[$namespace])) {
                        $this->adhocNamespaces[$namespace] = 'x'.(count($this->adhocNamespaces) + 1);
                    }
                    $result = $this->startElementNS($this->adhocNamespaces[$namespace], $localName, $namespace);
                }
            }
        } else {
            $result = parent::startElement($name);
        }

        if (!$this->namespacesWritten) {
            foreach ($this->namespaceMap as $namespace => $prefix) {
                $this->writeAttribute($prefix ? 'xmlns:'.$prefix : 'xmlns', $namespace);
            }
            $this->namespacesWritten = true;
        }

        return $result;
    }

    /**
     * Write a full element tag and it's contents.
     *
     * This method automatically closes the element as well.
     *
     * The element name may be specified in clark-notation.
     *
     * Examples:
     *
     *    $writer->writeElement('{http://www.w3.org/2005/Atom}author',null);
     *    becomes:
     *    <author xmlns="http://www.w3.org/2005" />
     *
     *    $writer->writeElement('{http://www.w3.org/2005/Atom}author', [
     *       '{http://www.w3.org/2005/Atom}name' => 'Evert Pot',
     *    ]);
     *    becomes:
     *    <author xmlns="http://www.w3.org/2005" /><name>Evert Pot</name></author>
     *
     * Note: this function doesn't have the string typehint, because PHP's
     * XMLWriter::startElement doesn't either.
     *
     * @param array|string|object|null $content
     */
    public function writeElement($name, $content = null): bool
    {
        $this->startElement($name);
        if (!is_null($content)) {
            $this->write($content);
        }
        $this->endElement();

        return true;
    }

    /**
     * Writes a list of attributes.
     *
     * Attributes are specified as a key->value array.
     *
     * The key is an attribute name. If the key is a 'localName', the current
     * xml namespace is assumed. If it's a 'clark notation key', this namespace
     * will be used instead.
     */
    public function writeAttributes(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            $this->writeAttribute($name, $value);
        }
    }

    /**
     * Writes a new attribute.
     *
     * The name may be specified in clark-notation.
     *
     * Returns true when successful.
     *
     * Note: this function doesn't have typehints, because for some reason
     * PHP's XMLWriter::writeAttribute doesn't either.
     *
     * @param string $name
     * @param string $value
     */
    public function writeAttribute($name, $value): bool
    {
        if ('{' !== $name[0]) {
            return parent::writeAttribute($name, $value);
        }

        list(
            $namespace,
            $localName
        ) = Service::parseClarkNotation($name);

        if (array_key_exists($namespace, $this->namespaceMap)) {
            // It's an attribute with a namespace we know
            return $this->writeAttribute(
                $this->namespaceMap[$namespace].':'.$localName,
                $value
            );
        }

        // We don't know the namespace, we must add it in-line
        if (!isset($this->adhocNamespaces[$namespace])) {
            $this->adhocNamespaces[$namespace] = 'x'.(count($this->adhocNamespaces) + 1);
        }

        return $this->writeAttributeNS(
            $this->adhocNamespaces[$namespace],
            $localName,
            $namespace,
            $value
        );
    }
}
