<?php

declare(strict_types=1);

namespace Sabre\CardDAV;

use Sabre\DAV;
use Sabre\DAVACL;

/**
 * The Card object represents a single Card from an addressbook.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Card extends DAV\File implements ICard, DAVACL\IACL
{
    use DAVACL\ACLTrait;

    /**
     * CardDAV backend.
     *
     * @var Backend\BackendInterface
     */
    protected $carddavBackend;

    /**
     * Array with information about this Card.
     *
     * @var array
     */
    protected $cardData;

    /**
     * Array with information about the containing addressbook.
     *
     * @var array
     */
    protected $addressBookInfo;

    /**
     * Constructor.
     */
    public function __construct(Backend\BackendInterface $carddavBackend, array $addressBookInfo, array $cardData)
    {
        $this->carddavBackend = $carddavBackend;
        $this->addressBookInfo = $addressBookInfo;
        $this->cardData = $cardData;
    }

    /**
     * Returns the uri for this object.
     *
     * @return string
     */
    public function getName()
    {
        return $this->cardData['uri'];
    }

    /**
     * Returns the VCard-formatted object.
     *
     * @return string
     */
    public function get()
    {
        // Pre-populating 'carddata' is optional. If we don't yet have it
        // already, we fetch it from the backend.
        if (!isset($this->cardData['carddata'])) {
            $this->cardData = $this->carddavBackend->getCard($this->addressBookInfo['id'], $this->cardData['uri']);
        }

        return $this->cardData['carddata'];
    }

    /**
     * Updates the VCard-formatted object.
     *
     * @param string $cardData
     *
     * @return string|null
     */
    public function put($cardData)
    {
        if (is_resource($cardData)) {
            $cardData = stream_get_contents($cardData);
        }

        // Converting to UTF-8, if needed
        $cardData = DAV\StringUtil::ensureUTF8($cardData);

        $etag = $this->carddavBackend->updateCard($this->addressBookInfo['id'], $this->cardData['uri'], $cardData);
        $this->cardData['carddata'] = $cardData;
        $this->cardData['etag'] = $etag;

        return $etag;
    }

    /**
     * Deletes the card.
     */
    public function delete()
    {
        $this->carddavBackend->deleteCard($this->addressBookInfo['id'], $this->cardData['uri']);
    }

    /**
     * Returns the mime content-type.
     *
     * @return string
     */
    public function getContentType()
    {
        return 'text/vcard; charset=utf-8';
    }

    /**
     * Returns an ETag for this object.
     *
     * @return string
     */
    public function getETag()
    {
        if (isset($this->cardData['etag'])) {
            return $this->cardData['etag'];
        } else {
            $data = $this->get();
            if (is_string($data)) {
                return '"'.md5($data).'"';
            } else {
                // We refuse to calculate the md5 if it's a stream.
                return null;
            }
        }
    }

    /**
     * Returns the last modification date as a unix timestamp.
     *
     * @return int
     */
    public function getLastModified()
    {
        return isset($this->cardData['lastmodified']) ? $this->cardData['lastmodified'] : null;
    }

    /**
     * Returns the size of this object in bytes.
     *
     * @return int
     */
    public function getSize()
    {
        if (array_key_exists('size', $this->cardData)) {
            return $this->cardData['size'];
        } else {
            return strlen($this->get());
        }
    }

    /**
     * Returns the owner principal.
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getOwner()
    {
        return $this->addressBookInfo['principaluri'];
    }

    /**
     * Returns a list of ACE's for this node.
     *
     * Each ACE has the following properties:
     *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
     *     currently the only supported privileges
     *   * 'principal', a url to the principal who owns the node
     *   * 'protected' (optional), indicating that this ACE is not allowed to
     *      be updated.
     *
     * @return array
     */
    public function getACL()
    {
        // An alternative acl may be specified through the cardData array.
        if (isset($this->cardData['acl'])) {
            return $this->cardData['acl'];
        }

        return [
            [
                'privilege' => '{DAV:}all',
                'principal' => $this->addressBookInfo['principaluri'],
                'protected' => true,
            ],
        ];
    }
}
