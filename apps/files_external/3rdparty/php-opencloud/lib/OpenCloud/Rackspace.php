<?php
/**
 * The Rackspace cloud/connection class (which uses different authentication
 * than the pure OpenStack class)
 *
 * @copyright 2012-2013 Rackspace Hosting, Inc.
 * See COPYING for licensing information
 *
 * @package phpOpenCloud
 * @version 1.0
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */

namespace OpenCloud;

/**
 * Rackspace extends the OpenStack class with support for Rackspace's
 * API key and tenant requirements.
 *
 * The only difference between Rackspace and OpenStack is that the
 * Rackspace class generates credentials using the username
 * and API key, as required by the Rackspace authentication
 * service.
 *
 * Example:
 * <code>
 * $username = 'FRED';
 * $apiKey = '0900af093093788912388fc09dde090ffee09';
 * $conn = new Rackspace(
 *      'https://identity.api.rackspacecloud.com/v2.0/',
 *      array(
 *          'username' => $username,
 *          'apiKey' => $apiKey
 *      ));
 * </code>
 */
class Rackspace extends OpenStack
{

    //this is the JSON string for our new credentials
const APIKEYTEMPLATE = <<<ENDCRED
{ "auth": { "RAX-KSKEY:apiKeyCredentials": { "username": "%s",
          "apiKey": "%s"
        }
    }
}
ENDCRED;

    /**
     * Generates Rackspace API key credentials
     *
     * @return string
     */
    public function Credentials()
    {
        $sec = $this->Secret();
        if (isset($sec['username'])
            && isset($sec['apiKey'])
        ) {
            return sprintf(
                self::APIKEYTEMPLATE,
                $sec['username'],
                $sec['apiKey']
           );
        } else {
            return parent::Credentials();
        }
    }

    /**
     * Creates a new DbService (Database as a Service) object
     *
     * This is a factory method that is Rackspace-only (NOT part of OpenStack).
     *
     * @param string $name the name of the service (e.g., 'Cloud Databases')
     * @param string $region the region (e.g., 'DFW')
     * @param string $urltype the type of URL (e.g., 'publicURL');
     */
    public function DbService($name = null, $region = null, $urltype = null)
    {
        return $this->Service('Database', $name, $region, $urltype);
    }

    /**
     * Creates a new LoadBalancerService object
     *
     * This is a factory method that is Rackspace-only (NOT part of OpenStack).
     *
     * @param string $name the name of the service
     *      (e.g., 'Cloud Load Balancers')
     * @param string $region the region (e.g., 'DFW')
     * @param string $urltype the type of URL (e.g., 'publicURL');
     */
    public function LoadBalancerService($name = null, $region = null, $urltype = null)
    {
        return $this->Service('LoadBalancer', $name, $region, $urltype);
    }

    /**
     * creates a new DNS service object
     *
     * This is a factory method that is currently Rackspace-only
     * (not available via the OpenStack class)
     */
    public function DNS($name = null, $region = null, $urltype = null)
    {
        return $this->Service('DNS', $name, $region, $urltype);
    }

    /**
     * creates a new CloudMonitoring service object
     *
     * This is a factory method that is currently Rackspace-only
     * (not available via the OpenStack class)
     */
    public function CloudMonitoring($name=null, $region=null, $urltype=null)
    {
        return $this->Service('CloudMonitoring', $name, $region, $urltype);
    }

    /**
     * creates a new Autoscale service object
     *
     * This is a factory method that is currently Rackspace-only
     * (not available via the OpenStack class)
     */
    public function Autoscale($name=null, $region=null, $urltype=null)
    {
        return $this->Service('Autoscale', $name, $region, $urltype);
    }

}
