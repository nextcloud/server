<?php
/**
 * A metadata object, used by other components in Compute and Object Storage
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

namespace OpenCloud\Common;

/**
 * The Metadata class represents either Server or Image metadata
 *
 * @api
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */
class Metadata extends Base 
{

    // array holding the names of keys that were set
    private $_keylist = array();    

    /**
     * This setter overrides the base one, since the metadata key can be
     * anything
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function __set($key, $value) 
    {
        // set the value and track the keys
        if (!in_array($key, $this->_keylist)) {
            $this->_keylist[] = $key;
        }

        $this->$key = $value;
    }

    /**
     * Returns the list of keys defined
     *
     * @return array
     */
    public function Keylist() 
    {
        return $this->_keylist;
    }

    /**
     * Sets metadata values from an array, with optional prefix
     *
     * If $prefix is provided, then only array keys that match the prefix
     * are set as metadata values, and $prefix is stripped from the key name.
     *
     * @param array $values an array of key/value pairs to set
     * @param string $prefix if provided, a prefix that is used to identify
     *      metadata values. For example, you can set values from headers
     *      for a Container by using $prefix='X-Container-Meta-'.
     * @return void
     */
    public function setArray($values, $prefix = null) 
    {
        if (empty($values)) {
            return false;
        }
        
        foreach ($values as $key => $value) {
            if ($prefix) {
                if (strpos($key, $prefix) === 0) {
                    $name = substr($key, strlen($prefix));
                    $this->getLogger()->info(
                        Lang::translate('Setting [{name}] to [{value}]'), 
                    	array(
                            'name'  => $name, 
                            'value' => $value
                        )
                    );
                    $this->$name = $value;
                }
            } else {
                $this->$key = $value;
            }
        }
    }

}
