<?php

declare(strict_types=1);

namespace Sabre\Xml;

/**
 * Context Stack.
 *
 * The Context maintains information about a document during either reading or
 * writing.
 *
 * During this process, it may be necessary to override this context
 * information.
 *
 * This trait allows easy access to the context, and allows the end-user to
 * override its settings for document fragments, and easily restore it again
 * later.
 *
 * @copyright Copyright (C) 2009-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
trait ContextStackTrait
{
    /**
     * This is the element map. It contains a list of XML elements (in clark
     * notation) as keys and PHP class names as values.
     *
     * The PHP class names must implement Sabre\Xml\Element.
     *
     * Values may also be a callable. In that case the function will be called
     * directly.
     *
     * @var array
     */
    public $elementMap = [];

    /**
     * A contextUri pointing to the document being parsed / written.
     * This uri may be used to resolve relative urls that may appear in the
     * document.
     *
     * The reader and writer don't use this property, but as it's an extremely
     * common use-case for parsing XML documents, it's added here as a
     * convenience.
     *
     * @var string|null
     */
    public $contextUri;

    /**
     * This is a list of namespaces that you want to give default prefixes.
     *
     * You must make sure you create this entire list before starting to write.
     * They should be registered on the root element.
     *
     * @var array
     */
    public $namespaceMap = [];

    /**
     * This is a list of custom serializers for specific classes.
     *
     * The writer may use this if you attempt to serialize an object with a
     * class that does not implement XmlSerializable.
     *
     * Instead it will look at this classmap to see if there is a custom
     * serializer here. This is useful if you don't want your value objects
     * to be responsible for serializing themselves.
     *
     * The keys in this classmap need to be fully qualified PHP class names,
     * the values must be callbacks. The callbacks take two arguments. The
     * writer class, and the value that must be written.
     *
     * function (Writer $writer, object $value)
     *
     * @var array
     */
    public $classMap = [];

    /**
     * Backups of previous contexts.
     *
     * @var array
     */
    protected $contextStack = [];

    /**
     * Create a new "context".
     *
     * This allows you to safely modify the elementMap, contextUri or
     * namespaceMap. After you're done, you can restore the old data again
     * with popContext.
     */
    public function pushContext()
    {
        $this->contextStack[] = [
            $this->elementMap,
            $this->contextUri,
            $this->namespaceMap,
            $this->classMap,
        ];
    }

    /**
     * Restore the previous "context".
     */
    public function popContext()
    {
        list(
            $this->elementMap,
            $this->contextUri,
            $this->namespaceMap,
            $this->classMap
        ) = array_pop($this->contextStack);
    }
}
