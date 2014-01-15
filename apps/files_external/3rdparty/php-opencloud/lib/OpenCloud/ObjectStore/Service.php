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
use OpenCloud\Common\Lang;

/**
 * The ObjectStore (Cloud Files) service.
 */
class Service extends AbstractService 
{
    
    /**
     * This holds the associated CDN service (for Rackspace public cloud)
     * or is NULL otherwise. The existence of an object here is
     * indicative that the CDN service is available.
     */
    private $cdn;

    /**
     * Creates a new ObjectStore service object.
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
        $urltype = RAXSDK_OBJSTORE_URLTYPE
    ) {
        $this->getLogger()->info('Initializing Container Service...');

        parent::__construct(
            $connection,
            'object-store',
            $serviceName,
            $serviceRegion,
            $urltype
        );

        // establish the CDN container, if available
        try {
            $this->cdn = new CDNService(
                $connection,
                $serviceName . 'CDN',
                $serviceRegion,
                $urltype
            );
        } catch (Exceptions\EndpointError $e) {
             // If we have an endpoint error, then the CDN functionality is not 
             // available. In this case, we silently ignore  it.
        }
    }

    /** 
     * Sets the shared secret value for the TEMP_URL
     *
     * @param string $secret the shared secret
     * @return HttpResponse
     */
    public function setTempUrlSecret($secret) 
    {
        $response = $this->request(
            $this->url(), 
            'POST',
            array('X-Account-Meta-Temp-Url-Key' => $secret)
        );
        
        // @codeCoverageIgnoreStart
        if ($response->httpStatus() > 204) {
            throw new Exceptions\HttpError(sprintf(
                Lang::translate('Error in request, status [%d] for URL [%s] [%s]'),
                $response->httpStatus(),
                $this->url(),
                $response->httpBody()
            ));
        }
        // @codeCoverageIgnoreEnd

        return $response;
    }

    /**
     * Get the CDN service.
     * 
     * @return null|CDNService
     */
    public function getCDNService() 
    {
        return $this->cdn;
    }
    
    /**
     * Backwards compability.
     */
    public function CDN()
    {
        return $this->getCDNService();
    }
    
}
