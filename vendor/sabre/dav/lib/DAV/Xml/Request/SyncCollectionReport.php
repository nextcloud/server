<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Request;

use Sabre\DAV\Exception\BadRequest;
use Sabre\Xml\Element\KeyValue;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;

/**
 * SyncCollection request parser.
 *
 * This class parses the {DAV:}sync-collection reprot, as defined in:
 *
 * http://tools.ietf.org/html/rfc6578#section-3.2
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class SyncCollectionReport implements XmlDeserializable
{
    /**
     * The sync-token the client supplied for the report.
     *
     * @var string|null
     */
    public $syncToken;

    /**
     * The 'depth' of the sync the client is interested in.
     *
     * @var int
     */
    public $syncLevel;

    /**
     * Maximum amount of items returned.
     *
     * @var int|null
     */
    public $limit;

    /**
     * The list of properties that are being requested for every change.
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
        $elems = KeyValue::xmlDeserialize($reader);

        $reader->popContext();

        $required = [
            '{DAV:}sync-token',
            '{DAV:}prop',
            ];

        foreach ($required as $elem) {
            if (!array_key_exists($elem, $elems)) {
                throw new BadRequest('The '.$elem.' element in the {DAV:}sync-collection report is required');
            }
        }

        $self->properties = $elems['{DAV:}prop'];
        $self->syncToken = $elems['{DAV:}sync-token'];

        if (isset($elems['{DAV:}limit'])) {
            $nresults = null;
            foreach ($elems['{DAV:}limit'] as $child) {
                if ('{DAV:}nresults' === $child['name']) {
                    $nresults = (int) $child['value'];
                }
            }
            $self->limit = $nresults;
        }

        if (isset($elems['{DAV:}sync-level'])) {
            $value = $elems['{DAV:}sync-level'];
            if ('infinity' === $value) {
                $value = \Sabre\DAV\Server::DEPTH_INFINITY;
            }
            $self->syncLevel = $value;
        }

        return $self;
    }
}
