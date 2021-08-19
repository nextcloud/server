<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Property;

use DateTime;
use DateTimeZone;
use Sabre\HTTP;
use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;

/**
 * This property represents the {DAV:}getlastmodified property.
 *
 * Defined in:
 * http://tools.ietf.org/html/rfc4918#section-15.7
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class GetLastModified implements Element
{
    /**
     * time.
     *
     * @var DateTime
     */
    public $time;

    /**
     * Constructor.
     *
     * @param int|DateTime $time
     */
    public function __construct($time)
    {
        if ($time instanceof DateTime) {
            $this->time = clone $time;
        } else {
            $this->time = new DateTime('@'.$time);
        }

        // Setting timezone to UTC
        $this->time->setTimezone(new DateTimeZone('UTC'));
    }

    /**
     * getTime.
     *
     * @return DateTime
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * The serialize method is called during xml writing.
     *
     * It should use the $writer argument to encode this object into XML.
     *
     * Important note: it is not needed to create the parent element. The
     * parent element is already created, and we only have to worry about
     * attributes, child elements and text (if any).
     *
     * Important note 2: If you are writing any new elements, you are also
     * responsible for closing them.
     */
    public function xmlSerialize(Writer $writer)
    {
        $writer->write(
            HTTP\toDate($this->time)
        );
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
     * Important note 2: You are responsible for advancing the reader to the
     * next element. Not doing anything will result in a never-ending loop.
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
        return new self(new DateTime($reader->parseInnerTree()));
    }
}
