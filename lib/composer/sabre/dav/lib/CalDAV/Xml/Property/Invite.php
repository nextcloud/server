<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV\Plugin;
use Sabre\DAV;
use Sabre\DAV\Xml\Element\Sharee;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * Invite property.
 *
 * This property encodes the 'invite' property, as defined by
 * the 'caldav-sharing-02' spec, in the http://calendarserver.org/ns/
 * namespace.
 *
 * @see https://trac.calendarserver.org/browser/CalendarServer/trunk/doc/Extensions/caldav-sharing-02.txt
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Invite implements XmlSerializable
{
    /**
     * The list of users a calendar has been shared to.
     *
     * @var Sharee[]
     */
    protected $sharees;

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
     * Returns the list of users, as it was passed to the constructor.
     *
     * @return array
     */
    public function getValue()
    {
        return $this->sharees;
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
        $cs = '{'.Plugin::NS_CALENDARSERVER.'}';

        foreach ($this->sharees as $sharee) {
            if (DAV\Sharing\Plugin::ACCESS_SHAREDOWNER === $sharee->access) {
                $writer->startElement($cs.'organizer');
            } else {
                $writer->startElement($cs.'user');

                switch ($sharee->inviteStatus) {
                    case DAV\Sharing\Plugin::INVITE_ACCEPTED:
                        $writer->writeElement($cs.'invite-accepted');
                        break;
                    case DAV\Sharing\Plugin::INVITE_DECLINED:
                        $writer->writeElement($cs.'invite-declined');
                        break;
                    case DAV\Sharing\Plugin::INVITE_NORESPONSE:
                        $writer->writeElement($cs.'invite-noresponse');
                        break;
                    case DAV\Sharing\Plugin::INVITE_INVALID:
                        $writer->writeElement($cs.'invite-invalid');
                        break;
                }

                $writer->startElement($cs.'access');
                switch ($sharee->access) {
                    case DAV\Sharing\Plugin::ACCESS_READWRITE:
                        $writer->writeElement($cs.'read-write');
                        break;
                    case DAV\Sharing\Plugin::ACCESS_READ:
                        $writer->writeElement($cs.'read');
                        break;
                }
                $writer->endElement(); // access
            }

            $href = new DAV\Xml\Property\Href($sharee->href);
            $href->xmlSerialize($writer);

            if (isset($sharee->properties['{DAV:}displayname'])) {
                $writer->writeElement($cs.'common-name', $sharee->properties['{DAV:}displayname']);
            }
            if ($sharee->comment) {
                $writer->writeElement($cs.'summary', $sharee->comment);
            }
            $writer->endElement(); // organizer or user
        }
    }
}
