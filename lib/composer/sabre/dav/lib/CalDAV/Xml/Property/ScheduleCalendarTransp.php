<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV\Plugin;
use Sabre\Xml\Deserializer;
use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;

/**
 * schedule-calendar-transp property.
 *
 * This property is a representation of the schedule-calendar-transp property.
 * This property is defined in:
 *
 * http://tools.ietf.org/html/rfc6638#section-9.1
 *
 * Its values are either 'transparent' or 'opaque'. If it's transparent, it
 * means that this calendar will not be taken into consideration when a
 * different user queries for free-busy information. If it's 'opaque', it will.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ScheduleCalendarTransp implements Element
{
    const TRANSPARENT = 'transparent';
    const OPAQUE = 'opaque';

    /**
     * value.
     *
     * @var string
     */
    protected $value;

    /**
     * Creates the property.
     *
     * @param string $value
     */
    public function __construct($value)
    {
        if (self::TRANSPARENT !== $value && self::OPAQUE !== $value) {
            throw new \InvalidArgumentException('The value must either be specified as "transparent" or "opaque"');
        }
        $this->value = $value;
    }

    /**
     * Returns the current value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
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
        switch ($this->value) {
            case self::TRANSPARENT:
                $writer->writeElement('{'.Plugin::NS_CALDAV.'}transparent');
                break;
            case self::OPAQUE:
                $writer->writeElement('{'.Plugin::NS_CALDAV.'}opaque');
                break;
        }
    }

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
        $elems = Deserializer\enum($reader, Plugin::NS_CALDAV);

        if (in_array('transparent', $elems)) {
            $value = self::TRANSPARENT;
        } else {
            $value = self::OPAQUE;
        }

        return new self($value);
    }
}
