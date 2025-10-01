<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Request;

use Sabre\DAV\Xml\Element\Sharee;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

/**
 * ShareResource request parser.
 *
 * This class parses the {DAV:}share-resource POST request as defined in:
 *
 * https://tools.ietf.org/html/draft-pot-webdav-resource-sharing-01#section-5.3.2.1
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ShareResource implements XmlDeserializable
{
    /**
     * The list of new people added or updated or removed from the share.
     *
     * @var Sharee[]
     */
    public $sharees = [];

    /**
     * Constructor.
     *
     * @param Sharee[] $sharees
     */
    public function __construct(array $sharees)
    {
        $this->sharees = $sharees;
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
        $elems = $reader->parseInnerTree([
            '{DAV:}sharee' => 'Sabre\DAV\Xml\Element\Sharee',
            '{DAV:}share-access' => 'Sabre\DAV\Xml\Property\ShareAccess',
            '{DAV:}prop' => 'Sabre\Xml\Deserializer\keyValue',
        ]);

        $sharees = [];

        foreach ($elems as $elem) {
            if ('{DAV:}sharee' !== $elem['name']) {
                continue;
            }
            $sharees[] = $elem['value'];
        }

        return new self($sharees);
    }
}
