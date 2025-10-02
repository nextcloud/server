<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Request;

use Sabre\Xml\Element\KeyValue;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

/**
 * WebDAV PROPFIND request parser.
 *
 * This class parses the {DAV:}propfind request, as defined in:
 *
 * https://tools.ietf.org/html/rfc4918#section-14.20
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class PropFind implements XmlDeserializable
{
    /**
     * If this is set to true, this was an 'allprop' request.
     *
     * @var bool
     */
    public $allProp = false;

    /**
     * The property list.
     *
     * @var array|null
     */
    public $properties;

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
        $self = new self();

        $reader->pushContext();
        $reader->elementMap['{DAV:}prop'] = 'Sabre\Xml\Element\Elements';

        foreach (KeyValue::xmlDeserialize($reader) as $k => $v) {
            switch ($k) {
                case '{DAV:}prop':
                    $self->properties = $v;
                    break;
                case '{DAV:}allprop':
                    $self->allProp = true;
            }
        }

        $reader->popContext();

        return $self;
    }
}
