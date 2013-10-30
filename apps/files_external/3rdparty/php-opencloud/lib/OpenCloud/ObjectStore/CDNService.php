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

use OpenCloud\OpenStack;
use OpenCloud\Common\Exceptions;

/**
 * This is the CDN version of the ObjectStore service. 
 */
class CDNService extends AbstractService
{

    /**
     * Creates a new CDNService object.
     *
     * This is a simple wrapper function around the parent Service construct,
     * but supplies defaults for the service type.
     *
     * @param OpenCloud\OpenStack $connection    The connection object
     * @param string              $serviceName   The name of the service
     * @param string              $serviceRegion The service's region
     * @param string              $urlType       The type of URL (normally 'publicURL')
     */
    public function __construct(
        OpenStack $connection,
        $serviceName = RAXSDK_OBJSTORE_NAME,
        $serviceRegion = RAXSDK_OBJSTORE_REGION,
        $urltype = RAXSDK_URL_PUBLIC
    ) {
        $this->getLogger()->info('Initializing CDN Service...');
        
        parent::__construct(
            $connection,
            'rax:object-cdn',
            $serviceName,
            $serviceRegion,
            $urltype
        );
    }

    /**
     * Helps catch errors if someone calls the method on the
     * wrong object
     */
    public function CDN()
    {
        throw new Exceptions\CdnError(
        	'Invalid method call; no CDN() on the CDN object'
        );
    }

}
