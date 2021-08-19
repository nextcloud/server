<?php

declare(strict_types=1);

namespace Sabre\DAV\Xml\Element;

use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Sharing\Plugin;
use Sabre\DAV\Xml\Property\Href;
use Sabre\DAV\Xml\Property\ShareAccess;
use Sabre\Xml\Deserializer;
use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;

/**
 * This class represents the {DAV:}sharee element.
 *
 * @copyright Copyright (C) fruux GmbH. (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Sharee implements Element
{
    /**
     * A URL. Usually a mailto: address, could also be a principal url.
     * This uniquely identifies the sharee.
     *
     * @var string
     */
    public $href;

    /**
     * A local principal path. The server will do its best to locate the
     * principal uri based on the given uri. If we could find a local matching
     * principal uri, this property will contain the value.
     *
     * @var string|null
     */
    public $principal;

    /**
     * A list of WebDAV properties that describe the sharee. This might for
     * example contain a {DAV:}displayname with the real name of the user.
     *
     * @var array
     */
    public $properties = [];

    /**
     * Share access level. One of the Sabre\DAV\Sharing\Plugin::ACCESS
     * constants.
     *
     * Can be one of:
     *
     * ACCESS_READ
     * ACCESS_READWRITE
     * ACCESS_SHAREDOWNER
     * ACCESS_NOACCESS
     *
     * depending on context.
     *
     * @var int
     */
    public $access;

    /**
     * When a sharee is originally invited to a share, the sharer may add
     * a comment. This will be placed in this property.
     *
     * @var string
     */
    public $comment;

    /**
     * The status of the invite, should be one of the
     * Sabre\DAV\Sharing\Plugin::INVITE constants.
     *
     * @var int
     */
    public $inviteStatus;

    /**
     * Creates the object.
     *
     * $properties will be used to populate all internal properties.
     */
    public function __construct(array $properties = [])
    {
        foreach ($properties as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            } else {
                throw new \InvalidArgumentException('Unknown property: '.$k);
            }
        }
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
        $writer->write([
            new Href($this->href),
            '{DAV:}prop' => $this->properties,
            '{DAV:}share-access' => new ShareAccess($this->access),
        ]);
        switch ($this->inviteStatus) {
            case Plugin::INVITE_NORESPONSE:
                $writer->writeElement('{DAV:}invite-noresponse');
                break;
            case Plugin::INVITE_ACCEPTED:
                $writer->writeElement('{DAV:}invite-accepted');
                break;
            case Plugin::INVITE_DECLINED:
                $writer->writeElement('{DAV:}invite-declined');
                break;
            case Plugin::INVITE_INVALID:
                $writer->writeElement('{DAV:}invite-invalid');
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
        // Temporarily override configuration
        $reader->pushContext();
        $reader->elementMap['{DAV:}share-access'] = 'Sabre\DAV\Xml\Property\ShareAccess';
        $reader->elementMap['{DAV:}prop'] = 'Sabre\Xml\Deserializer\keyValue';

        $elems = Deserializer\keyValue($reader, 'DAV:');

        // Restore previous configuration
        $reader->popContext();

        $sharee = new self();
        if (!isset($elems['href'])) {
            throw new BadRequest('Every {DAV:}sharee must have a {DAV:}href child-element');
        }
        $sharee->href = $elems['href'];

        if (isset($elems['prop'])) {
            $sharee->properties = $elems['prop'];
        }
        if (isset($elems['comment'])) {
            $sharee->comment = $elems['comment'];
        }
        if (!isset($elems['share-access'])) {
            throw new BadRequest('Every {DAV:}sharee must have a {DAV:}share-access child element');
        }
        $sharee->access = $elems['share-access']->getValue();

        return $sharee;
    }
}
