<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV\Plugin;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * AllowedSharingModes.
 *
 * This property encodes the 'allowed-sharing-modes' property, as defined by
 * the 'caldav-sharing-02' spec, in the http://calendarserver.org/ns/
 * namespace.
 *
 * This property is a representation of the supported-calendar_component-set
 * property in the CalDAV namespace. It simply requires an array of components,
 * such as VEVENT, VTODO
 *
 * @see https://trac.calendarserver.org/browser/CalendarServer/trunk/doc/Extensions/caldav-sharing-02.txt
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class AllowedSharingModes implements XmlSerializable
{
    /**
     * Whether or not a calendar can be shared with another user.
     *
     * @var bool
     */
    protected $canBeShared;

    /**
     * Whether or not the calendar can be placed on a public url.
     *
     * @var bool
     */
    protected $canBePublished;

    /**
     * Constructor.
     *
     * @param bool $canBeShared
     * @param bool $canBePublished
     */
    public function __construct($canBeShared, $canBePublished)
    {
        $this->canBeShared = $canBeShared;
        $this->canBePublished = $canBePublished;
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
        if ($this->canBeShared) {
            $writer->writeElement('{'.Plugin::NS_CALENDARSERVER.'}can-be-shared');
        }
        if ($this->canBePublished) {
            $writer->writeElement('{'.Plugin::NS_CALENDARSERVER.'}can-be-published');
        }
    }
}
