<?php

declare(strict_types=1);

namespace Sabre\Xml\Element;

use Sabre\Xml;

/**
 * CDATA element.
 *
 * This element allows you to easily inject CDATA.
 *
 * Note that we strongly recommend avoiding CDATA nodes, unless you definitely
 * know what you're doing, or you're working with unchangeable systems that
 * require CDATA.
 *
 * @copyright Copyright (C) 2009-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Cdata implements Xml\XmlSerializable
{
    /**
     * CDATA element value.
     *
     * @var string
     */
    protected $value;

    /**
     * Constructor.
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * The xmlSerialize method is called during xml writing.
     *
     * Use the $writer argument to write its own xml serialization.
     *
     * An important note: do _not_ create a parent element. Any element
     * implementing XmlSerializable should only ever write what's considered
     * its 'inner xml'.
     *
     * The parent of the current element is responsible for writing a
     * containing element.
     *
     * This allows serializers to be re-used for different element names.
     *
     * If you are opening new elements, you must also close them again.
     */
    public function xmlSerialize(Xml\Writer $writer)
    {
        $writer->writeCData($this->value);
    }
}
