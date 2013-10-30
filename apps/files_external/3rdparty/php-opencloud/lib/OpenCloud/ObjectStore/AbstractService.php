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

namespace OpenCloud\ObjectStore;

use OpenCloud\Common\Service as CommonService;

define('SWIFT_MAX_OBJECT_SIZE', 5 * 1024 * 1024 * 1024 + 1);

/**
 * An abstract base class for common code shared between ObjectStore\Service
 * (container) and ObjectStore\CDNService (CDN containers).
 * 
 * @todo Maybe we use Traits instead of this small abstract class?
 */
abstract class AbstractService extends CommonService
{

    const MAX_CONTAINER_NAME_LEN    = 256;
    const MAX_OBJECT_NAME_LEN       = 1024;
    const MAX_OBJECT_SIZE           = SWIFT_MAX_OBJECT_SIZE;

    /**
     * Creates a Container resource object.
     * 
     * @param  mixed $cdata  The name of the container or an object from which to set values
     * @return OpenCloud\ObjectStore\Resource\Container
     */
    public function container($cdata = null)
    {
        return new Resource\Container($this, $cdata);
    }

    /**
     * Returns a Collection of Container objects.
     *
     * @param  array $filter  An array to filter the results
     * @return OpenCloud\Common\Collection
     */
    public function containerList(array $filter = array())
    {
        $filter['format'] = 'json';
        
        return $this->collection(
        	'OpenCloud\ObjectStore\Resource\Container', $this->url(null, $filter)
        );
    }

}
