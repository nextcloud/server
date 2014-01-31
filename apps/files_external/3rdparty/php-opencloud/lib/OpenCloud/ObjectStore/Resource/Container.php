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

use OpenCloud\Common\Exceptions;
use OpenCloud\Common\Lang;

/**
 * A container is a storage compartment for your data and provides a way for you 
 * to organize your data. You can think of a container as a folder in Windows® 
 * or a directory in UNIX®. The primary difference between a container and these 
 * other file system concepts is that containers cannot be nested.
 * 
 * A container can also be CDN-enabled (for public access), in which case you
 * will need to interact with a CDNContainer object instead of this one.
 */
class Container extends CDNContainer
{

    /**
     * CDN container (if set).
     * 
     * @var CDNContainer|null 
     */
    private $cdn;
    
    /**
     * Sets the CDN container.
     * 
     * @param OpenCloud\ObjectStore\Resource\CDNContainer $cdn
     */
    public function setCDN(CDNContainer $cdn)
    {
        $this->cdn = $cdn;
    }
    
    /**
     * Returns the CDN container.
     * 
     * @returns CDNContainer
     */
    public function getCDN()
    {
        if (!$this->cdn) {
            throw new Exceptions\CdnNotAvailableError(
            	Lang::translate('CDN-enabled container is not available')
            );
        }
        
        return $this->cdn;
    }
    
    /**
     * Backwards compatability.
     */
    public function CDN()
    {
        return $this->getCDN();
    }
    
    /**
     * Makes the container public via the CDN
     *
     * @api
     * @param integer $TTL the Time-To-Live for the CDN container; if NULL,
     *      then the cloud's default value will be used for caching.
     * @throws CDNNotAvailableError if CDN services are not available
     * @return CDNContainer
     */
    public function enableCDN($ttl = null)
    {
        $url = $this->getService()->CDN()->url() . '/' . rawurlencode($this->name);

        $headers = $this->metadataHeaders();

        if ($ttl) {
           
            // Make sure we're dealing with a real figure
            if (!is_integer($ttl)) {
                throw new Exceptions\CdnTtlError(sprintf(
                    Lang::translate('TTL value [%s] must be an integer'), 
                    $ttl
                ));
            }
            
            $headers['X-TTL'] = $ttl;
        }

        $headers['X-Log-Retention'] = 'True';
        $headers['X-CDN-Enabled']   = 'True';

        // PUT to the CDN container
        $response = $this->getService()->request($url, 'PUT', $headers);

        // check the response status
        // @codeCoverageIgnoreStart
        if ($response->httpStatus() > 202) {
            throw new Exceptions\CdnHttpError(sprintf(
                Lang::translate('HTTP error publishing to CDN, status [%d] response [%s]'),
                $response->httpStatus(),
                $response->httpBody()
            ));
        }
        // @codeCoverageIgnoreEnd

        // refresh the data
        $this->refresh();

        // return the CDN container object
        $cdn = new CDNContainer($this->getService()->getCDNService(), $this->name);
        $this->setCDN($cdn);
        
        return $cdn;
    }

    /**
     * Backwards compatability.
     */
    public function publishToCDN($ttl = null)
    {
        return $this->enableCDN($ttl);
    }

    /**
     * Disables the containers CDN function.
     *
     * Note that the container will still be available on the CDN until
     * its TTL expires.
     *
     * @api
     * @return void
     */
    public function disableCDN()
    {
        // Set necessary headers
        $headers['X-Log-Retention'] = 'False';
        $headers['X-CDN-Enabled']   = 'False';

        // PUT it to the CDN service
        $response = $this->getService()->request($this->CDNURL(), 'PUT', $headers);

        // check the response status
        // @codeCoverageIgnoreStart
        if ($response->httpStatus() != 201) {
            throw new Exceptions\CdnHttpError(sprintf(
                Lang::translate('HTTP error disabling CDN, status [%d] response [%s]'),
                $response->httpStatus(),
                $response->httpBody()
            ));
        }
        // @codeCoverageIgnoreEnd
        
        return true;
    }

    /**
     * Creates a static website from the container
     *
     * @api
     * @link http://docs.rackspace.com/files/api/v1/cf-devguide/content/Create_Static_Website-dle4000.html
     * @param string $index the index page (starting page) of the website
     * @return \OpenCloud\HttpResponse
     */
    public function createStaticSite($indexHtml)
    {
        $headers = array('X-Container-Meta-Web-Index' => $indexHtml);
        $response = $this->getService()->request($this->url(), 'POST', $headers);

        // check return code
        // @codeCoverageIgnoreStart
        if ($response->HttpStatus() > 204) {
            throw new Exceptions\ContainerError(sprintf(
                Lang::translate('Error creating static website for [%s], status [%d] response [%s]'),
                $this->name,
                $response->httpStatus(),
                $response->httpBody()
            ));
        }
        // @codeCoverageIgnoreEnd

        return $response;
    }

    /**
     * Sets the error page(s) for the static website
     *
     * @api
     * @link http://docs.rackspace.com/files/api/v1/cf-devguide/content/Set_Error_Pages_for_Static_Website-dle4005.html
     * @param string $name the name of the error page
     * @return \OpenCloud\HttpResponse
     */
    public function staticSiteErrorPage($name)
    {
        $headers = array('X-Container-Meta-Web-Error' => $name);
        $response = $this->getService()->request($this->url(), 'POST', $headers);

        // check return code
        // @codeCoverageIgnoreStart
        if ($response->httpStatus() > 204) {
            throw new Exceptions\ContainerError(sprintf(
                Lang::translate('Error creating static site error page for [%s], status [%d] response [%s]'),
                $this->name,
                $response->httpStatus(),
                $response->httpBody()
            ));
        }

        return $response;
        // @codeCoverageIgnoreEnd
    }

    /**
     * Returns the CDN URL of the container (if enabled)
     *
     * The CDNURL() is used to manage the container. Note that it is different
     * from the PublicURL() of the container, which is the publicly-accessible
     * URL on the network.
     *
     * @api
     * @return string
     */
    public function CDNURL()
    {
        return $this->getCDN()->url();
    }

    /**
     * Returns the Public URL of the container (on the CDN network)
     *
     */
    public function publicURL()
    {
        return $this->CDNURI();
    }

    /**
     * Returns the CDN info about the container
     *
     * @api
     * @return stdClass
     */
    public function CDNinfo($property = null)
    {
        // Not quite sure why this is here...
        // @codeCoverageIgnoreStart
		if ($this->getService() instanceof CDNService) {
			return $this->metadata;
        }
        // @codeCoverageIgnoreEnd
        
        // return NULL if the CDN container is not enabled
        if (!isset($this->getCDN()->metadata->Enabled) 
            || $this->getCDN()->metadata->Enabled == 'False'
        ) {
            return null;
        }

        // check to see if it's set
        if (isset($this->getCDN()->metadata->$property)) {
            return trim($this->getCDN()->metadata->$property);
        } elseif ($property !== null) {
            return null;
        }

        // otherwise, return the whole metadata object
        return $this->getCDN()->metadata;
    }

    /**
     * Returns the CDN container URI prefix
     *
     * @api
     * @return string
     */
    public function CDNURI()
    {
        return $this->CDNinfo('Uri');
    }

    /**
     * Returns the SSL URI for the container
     *
     * @api
     * @return string
     */
    public function SSLURI()
    {
        return $this->CDNinfo('Ssl-Uri');
    }

    /**
     * Returns the streaming URI for the container
     *
     * @api
     * @return string
     */
    public function streamingURI()
    {
        return $this->CDNinfo('Streaming-Uri');
    }

    /**
     * Returns the IOS streaming URI for the container
     *
     * @api
     * @link http://docs.rackspace.com/files/api/v1/cf-devguide/content/iOS-Streaming-d1f3725.html
     * @return string
     */
    public function iosStreamingURI()
    {
        return $this->CDNinfo('Ios-Uri');
    }

    /**
     * Creates a Collection of objects in the container
     *
     * @param array $params associative array of parameter values.
     * * account/tenant - The unique identifier of the account/tenant.
     * * container- The unique identifier of the container.
     * * limit (Optional) - The number limit of results.
     * * marker (Optional) - Value of the marker, that the object names
     *      greater in value than are returned.
     * * end_marker (Optional) - Value of the marker, that the object names
     *      less in value than are returned.
     * * prefix (Optional) - Value of the prefix, which the returned object
     *      names begin with.
     * * format (Optional) - Value of the serialized response format, either
     *      json or xml.
     * * delimiter (Optional) - Value of the delimiter, that all the object
     *      names nested in the container are returned.
     * @link http://api.openstack.org for a list of possible parameter
     *      names and values
     * @return OpenCloud\Collection
     * @throws ObjFetchError
     */
    public function objectList($params = array())
    {
        // construct a query string out of the parameters
        $params['format'] = 'json';
        
        $queryString = $this->makeQueryString($params);

        // append the query string to the URL
        $url = $this->url();
        if (strlen($queryString) > 0) {
            $url .= '?' . $queryString;
        }
        
        return $this->getService()->collection(
        	'OpenCloud\ObjectStore\Resource\DataObject', $url, $this
        );
    }

    /**
     * Returns a new DataObject associated with this container
     *
     * @param string $name if supplied, the name of the object to return
     * @return DataObject
     */
    public function dataObject($name = null)
    {
        return new DataObject($this, $name);
    }

    /**
     * Refreshes, then associates the CDN container
     */
    public function refresh($id = null, $url = null)
    {
        parent::refresh($id, $url);
        
        // @codeCoverageIgnoreStart
		if ($this->getService() instanceof CDNService) {
			return;
        }
        
        
        if (null !== ($cdn = $this->getService()->CDN())) {
            try {
                $this->cdn = new CDNContainer(
                    $cdn,
                    $this->name
                );
            } catch (Exceptions\ContainerNotFoundError $e) {
                $this->cdn = new CDNContainer($cdn);
                $this->cdn->name = $this->name;
            }
        }
        // @codeCoverageIgnoreEnd
    }

}
