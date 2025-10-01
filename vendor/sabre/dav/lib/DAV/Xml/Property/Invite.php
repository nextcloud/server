<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Property;

use Sabre\DAV\Xml\Element\Sharee;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * This class represents the {DAV:}invite property.
 *
 * This property is defined here:
 * https://tools.ietf.org/html/draft-pot-webdav-resource-sharing-03#section-4.4.2
 *
 * This property is used by clients to determine who currently has access to
 * a shared resource, what their access level is and what their invite status
 * is.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Invite implements XmlSerializable
{
    /**
     * A list of sharees.
     *
     * @var Sharee[]
     */
    public $sharees = [];

    /**
     * Creates the property.
     *
     * @param Sharee[] $sharees
     */
    public function __construct(array $sharees)
    {
        $this->sharees = $sharees;
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
    public function xmlSerialize(Writer $writer)
    {
        foreach ($this->sharees as $sharee) {
            $writer->writeElement('{DAV:}sharee', $sharee);
        }
    }
}
