<?php
/**
 * PHP OpenCloud library.
 * 
 * @copyright Copyright 2013 Rackspace US, Inc. See COPYING for licensing information.
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @version   1.6.0
 * @author    Glen Campbell <glen.campbell@rackspace.com>
 * @author    Jamie Hannaford <jamie.hannaford@rackspace.com>
 */

namespace OpenCloud\ObjectStore\Resource;

use OpenCloud\Common\Base;
use OpenCloud\Common\Metadata;
use OpenCloud\Common\Exceptions\NameError;
use OpenCloud\Common\Exceptions\MetadataPrefixError;
use OpenCloud\Common\Request\Response\Http;

/**
 * Abstract base class which implements shared functionality of ObjectStore 
 * resources. Provides support, for example, for metadata-handling and other 
 * features that are common to the ObjectStore components.
 */
abstract class AbstractStorageObject extends Base
{

    const ACCOUNT_META_PREFIX      = 'X-Account-';
    const CONTAINER_META_PREFIX    = 'X-Container-Meta-';
    const OBJECT_META_PREFIX       = 'X-Object-Meta-';
    const CDNCONTAINER_META_PREFIX = 'X-Cdn-';
    
    /**
     * Metadata belonging to a resource.
     * 
     * @var OpenCloud\Common\Metadata 
     */
    public $metadata;

    /**
     * Initializes the metadata component
     */
    public function __construct()
    {
        $this->metadata = new Metadata;
    }

    /**
     * Given an Http response object, converts the appropriate headers
     * to metadata
     *
     * @param  OpenCloud\Common\Request\Response\Http
     * @return void
     */
    public function getMetadata(Http $response)
    {
        $this->metadata = new Metadata;
        $this->metadata->setArray($response->headers(), $this->prefix());
    }

    /**
     * If object has metadata, return an associative array of headers.
     *
     * For example, if a DataObject has a metadata item named 'FOO',
     * then this would return array('X-Object-Meta-FOO'=>$value);
     *
     * @return array
     */
    public function metadataHeaders()
    {
        $headers = array();

        // only build if we have metadata
        if (is_object($this->metadata)) {
            foreach ($this->metadata as $key => $value) {
                $headers[$this->prefix() . $key] = $value;
            }
        }

        return $headers;
    }

    /**
     * Returns the displayable name of the object
     *
     * Can be overridden by child objects; *must* be overridden by child
     * objects if the object does not have a `name` attribute defined.
     *
     * @api
     * @throws NameError if attribute 'name' is not defined
     */
    public function name()
    {
        if (property_exists($this, 'name')) {
            return $this->name;
        } else {
            throw new NameError(sprintf(
                'Name attribute does not exist for [%s]', 
                get_class($this)
            ));
        }
    }
    
    /**
     * Override parent method.
     * 
     * @return null
     */
    public static function jsonName()
    {
        return null;
    }
    
    /**
     * Override parent method.
     * 
     * @return null
     */
    public static function jsonCollectionName()
    {
        return null;
    }
    
    /**
     * Override parent method.
     * 
     * @return null
     */
    public static function jsonCollectionElement()
    {
        return null;
    }

    /**
     * Returns the proper prefix for the specified type of object
     *
     * @param string $type The type of object; derived from `get_class()` if not
     *      specified.
     * @codeCoverageIgnore
     */
    private function prefix($type = null)
    {
        if ($type === null) {
            $parts = preg_split('/\\\/', get_class($this));
            $type  = $parts[count($parts)-1];
        }

        switch($type) {
            case 'Account':
                $prefix = self::ACCOUNT_META_PREFIX;
                break;
            case 'CDNContainer':
                $prefix = self::CDNCONTAINER_META_PREFIX;
                break;
            case 'Container':
                $prefix = self::CONTAINER_META_PREFIX;
                break;
            case 'DataObject':
                $prefix = self::OBJECT_META_PREFIX;
                break;
            default:
                throw new MetadataPrefixError(sprintf(
                    'Unrecognized metadata type [%s]', 
                    $type
                ));
        }
        
        return $prefix;
    }
}
