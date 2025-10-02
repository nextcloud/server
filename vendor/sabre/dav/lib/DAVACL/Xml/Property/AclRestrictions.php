<?php

declare(strict_types=1);

namespace Sabre\DAVACL\Xml\Property;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * AclRestrictions property.
 *
 * This property represents {DAV:}acl-restrictions, as defined in RFC3744.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class AclRestrictions implements XmlSerializable
{
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
        $writer->writeElement('{DAV:}grant-only');
        $writer->writeElement('{DAV:}no-invert');
    }
}
