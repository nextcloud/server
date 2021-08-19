<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Property;

use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Sharing\Plugin as SharingPlugin;
use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;

/**
 * This class represents the {DAV:}share-access property.
 *
 * This property is defined here:
 * https://tools.ietf.org/html/draft-pot-webdav-resource-sharing-03#section-4.4.1
 *
 * This property is used to indicate if a resource is a shared resource, and
 * whether the instance of the shared resource is the original instance, or
 * an instance belonging to a sharee.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ShareAccess implements Element
{
    /**
     * Either SHARED or SHAREDOWNER.
     *
     * @var int
     */
    protected $value;

    /**
     * Creates the property.
     *
     * The constructor value must be one of the
     * \Sabre\DAV\Sharing\Plugin::ACCESS_ constants.
     *
     * @param int $shareAccess
     */
    public function __construct($shareAccess)
    {
        $this->value = $shareAccess;
    }

    /**
     * Returns the current value.
     *
     * @return int
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
            case SharingPlugin::ACCESS_NOTSHARED:
                $writer->writeElement('{DAV:}not-shared');
                break;
            case SharingPlugin::ACCESS_SHAREDOWNER:
                $writer->writeElement('{DAV:}shared-owner');
                break;
            case SharingPlugin::ACCESS_READ:
                $writer->writeElement('{DAV:}read');
                break;
            case SharingPlugin::ACCESS_READWRITE:
                $writer->writeElement('{DAV:}read-write');
                break;
            case SharingPlugin::ACCESS_NOACCESS:
                $writer->writeElement('{DAV:}no-access');
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
        $elems = $reader->parseInnerTree();
        foreach ($elems as $elem) {
            switch ($elem['name']) {
                case '{DAV:}not-shared':
                    return new self(SharingPlugin::ACCESS_NOTSHARED);
                case '{DAV:}shared-owner':
                    return new self(SharingPlugin::ACCESS_SHAREDOWNER);
                case '{DAV:}read':
                    return new self(SharingPlugin::ACCESS_READ);
                case '{DAV:}read-write':
                    return new self(SharingPlugin::ACCESS_READWRITE);
                case '{DAV:}no-access':
                    return new self(SharingPlugin::ACCESS_NOACCESS);
            }
        }
        throw new BadRequest('Invalid value for {DAV:}share-access element');
    }
}
