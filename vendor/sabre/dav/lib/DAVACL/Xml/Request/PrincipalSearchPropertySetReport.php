<?php

declare(strict_types=1);

namespace Sabre\DAVACL\Xml\Request;

use Sabre\DAV\Exception\BadRequest;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

/**
 * PrincipalSearchPropertySetReport request parser.
 *
 * This class parses the {DAV:}principal-search-property-set REPORT, as defined
 * in:
 *
 * https://tools.ietf.org/html/rfc3744#section-9.5
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class PrincipalSearchPropertySetReport implements XmlDeserializable
{
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
        if (!$reader->isEmptyElement) {
            throw new BadRequest('The {DAV:}principal-search-property-set element must be empty');
        }

        // The element is actually empty, so there's not much to do.
        $reader->next();

        $self = new self();

        return $self;
    }
}
