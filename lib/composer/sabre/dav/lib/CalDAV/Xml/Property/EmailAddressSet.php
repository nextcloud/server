<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Property;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;

/**
 * email-address-set property.
 *
 * This property represents the email-address-set property in the
 * http://calendarserver.org/ns/ namespace.
 *
 * It's a list of email addresses associated with a user.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class EmailAddressSet implements XmlSerializable
{
    /**
     * emails.
     *
     * @var array
     */
    private $emails;

    /**
     * __construct.
     */
    public function __construct(array $emails)
    {
        $this->emails = $emails;
    }

    /**
     * Returns the email addresses.
     *
     * @return array
     */
    public function getValue()
    {
        return $this->emails;
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
        foreach ($this->emails as $email) {
            $writer->writeElement('{http://calendarserver.org/ns/}email-address', $email);
        }
    }
}
