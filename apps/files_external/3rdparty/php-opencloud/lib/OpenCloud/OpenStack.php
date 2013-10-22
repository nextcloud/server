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

namespace OpenCloud;

require_once __DIR__ . '/Globals.php';

use OpenCloud\Common\Base;
use OpenCloud\Common\Lang;
use OpenCloud\Common\Exceptions;
use OpenCloud\Common\ServiceCatalogItem;

/**
 * The OpenStack class represents a relationship (or "connection")
 * between a user and a service.
 *
 * This is the primary entry point into an OpenStack system, and the only one
 * where the developer is required to know and provide the endpoint URL (in
 * all other cases, the endpoint is derived from the Service Catalog provided
 * by the authentication system).
 *
 * Since various providers have different mechanisms for authentication, users
 * will often use a subclass of OpenStack. For example, the Rackspace
 * class is provided for users of Rackspace's cloud services, and other cloud
 * providers are welcome to add their own subclasses as well.
 *
 * General usage example:
 * <code>
 *  $username = 'My Username';
 *  $secret = 'My Secret';
 *  $connection = new OpenCloud\OpenStack($username, $secret);
 *  // having established the connection, we can set some defaults
 *  // this sets the default name and region of the Compute service
 *  $connection->SetDefaults('Compute', 'cloudServersOpenStack', 'ORD');
 *  // access a Compute service
 *  $chicago = $connection->Compute();
 *  // if we want to access a different service, we can:
 *  $dallas = $connection->Compute('cloudServersOpenStack', 'DFW');
 * </code>
 */
class OpenStack extends Base
{

    /**
     * This holds the HTTP User-Agent: used for all requests to the services. It 
     * is public so that, if necessary, it can be entirely overridden by the 
     * developer. However, it's strongly recomended that you use the 
     * appendUserAgent() method to APPEND your own User Agent identifier to the 
     * end of this string; the user agent information can be very valuable to 
     * service providers to track who is using their service.
     * 
     * @var string 
     */
    public $useragent = RAXSDK_USER_AGENT;

    protected $url;
    protected $secret = array();
    protected $token;
    protected $expiration = 0;
    protected $tenant;
    protected $catalog;
    protected $connectTimeout = RAXSDK_CONNECTTIMEOUT;
    protected $httpTimeout = RAXSDK_TIMEOUT;
    protected $overlimitTimeout = RAXSDK_OVERLIMIT_TIMEOUT;

    /**
     * This associative array holds default values used to identify each
     * service (and to select it from the Service Catalog). Use the
     * Compute::SetDefaults() method to change the default values, or
     * define the global constants (for example, RAXSDK_COMPUTE_NAME)
     * BEFORE loading the OpenCloud library:
     *
     * <code>
     * define('RAXSDK_COMPUTE_NAME', 'cloudServersOpenStack');
     * include('openstack.php');
     * </code>
     */
    protected $defaults = array(
        'Compute' => array(
            'name'      => RAXSDK_COMPUTE_NAME,
            'region'    => RAXSDK_COMPUTE_REGION,
            'urltype'   => RAXSDK_COMPUTE_URLTYPE
        ),
        'ObjectStore' => array(
            'name'      => RAXSDK_OBJSTORE_NAME,
            'region'    => RAXSDK_OBJSTORE_REGION,
            'urltype'   => RAXSDK_OBJSTORE_URLTYPE
        ),
        'Database' => array(
            'name'      => RAXSDK_DATABASE_NAME,
            'region'    => RAXSDK_DATABASE_REGION,
            'urltype'   => RAXSDK_DATABASE_URLTYPE
        ),
        'Volume' => array(
            'name'      => RAXSDK_VOLUME_NAME,
            'region'    => RAXSDK_VOLUME_REGION,
            'urltype'   => RAXSDK_VOLUME_URLTYPE
        ),
        'LoadBalancer' => array(
            'name'      => RAXSDK_LBSERVICE_NAME,
            'region'    => RAXSDK_LBSERVICE_REGION,
            'urltype'   => RAXSDK_LBSERVICE_URLTYPE
        ),
        'DNS' => array(
            'name'      => RAXSDK_DNS_NAME,
            'region'    => RAXSDK_DNS_REGION,
            'urltype'   => RAXSDK_DNS_URLTYPE
        ),
        'Orchestration' => array(
            'name'      => RAXSDK_ORCHESTRATION_NAME,
            'region'    => RAXSDK_ORCHESTRATION_REGION,
            'urltype'   => RAXSDK_ORCHESTRATION_URLTYPE
        ),
        'CloudMonitoring' => array(
            'name'      => RAXSDK_MONITORING_NAME,
            'region'    => RAXSDK_MONITORING_REGION,
            'urltype'   => RAXSDK_MONITORING_URLTYPE
        ),
        'Autoscale' => array(
        	'name'		=> RAXSDK_AUTOSCALE_NAME,
        	'region'	=> RAXSDK_AUTOSCALE_REGION,
        	'urltype'	=> RAXSDK_AUTOSCALE_URLTYPE
        )
    );

    private $_user_write_progress_callback_func;
    private $_user_read_progress_callback_func;

    /**
     * Tracks file descriptors used by streaming downloads
     *
     * This will permit multiple simultaneous streaming downloads; the
     * key is the URL of the object, and the value is its file descriptor.
     *
     * To prevent memory overflows, each array element is deleted when
     * the end of the file is reached.
     */
    private $fileDescriptors = array();

    /**
     * array of options to pass to the CURL request object
     */
    private $curlOptions = array();

    /**
     * list of attributes to export/import
     */
    private $exportItems = array(
        'token',
        'expiration',
        'tenant',
        'catalog'
    );

    /**
     * Creates a new OpenStack object
     *
     * The OpenStack object needs two bits of information: the URL to
     * authenticate against, and a "secret", which is an associative array
     * of name/value pairs. Usually, the secret will be a username and a
     * password, but other values may be required by different authentication
     * systems. For example, OpenStack Keystone requires a username and
     * password, but Rackspace uses a username, tenant ID, and API key.
     * (See OpenCloud\Rackspace for that.)
     *
     * @param string $url - the authentication endpoint URL
     * @param array $secret - an associative array of auth information:
     * * username
     * * password
     * @param array $options - CURL options to pass to the HttpRequest object
     */
    public function __construct($url, array $secret, array $options = array())
    {
    	// check for supported version
        // @codeCoverageIgnoreStart
        $version = phpversion();
    	if ($version < '5.3.1') {
    		throw new Exceptions\UnsupportedVersionError(sprintf(
                Lang::translate('PHP version [%s] is not supported'),
                $version
            ));
        }
        // @codeCoverageIgnoreEnd
        
    	// Start processing
        $this->getLogger()->info(Lang::translate('Initializing OpenStack client'));
        
        // Set properties
        $this->setUrl($url);
        $this->setSecret($secret);
        $this->setCurlOptions($options);
    }
    
    /**
     * Set user agent.
     * 
     * @param  string $useragent
     * @return OpenCloud\OpenStack
     */
    public function setUserAgent($useragent)
    {
        $this->useragent = $useragent;
        
        return $this;
    }
    
    /**
     * Allows the user to append a user agent string
     *
     * Programs that are using these bindings are encouraged to add their
     * user agent to the one supplied by this SDK. This will permit cloud
     * providers to track users so that they can provide better service.
     *
     * @param string $agent an arbitrary user-agent string; e.g. "My Cloud App"
     * @return OpenCloud\OpenStack
     */
    public function appendUserAgent($useragent)
    {
        $this->useragent .= ';' . $useragent;
        
        return $this;
    }
    
    /**
     * Get user agent.
     * 
     * @return string
     */
    public function getUserAgent()
    {
        return $this->useragent;
    }
    
    /**
     * Sets the URL which the client will access.
     * 
     * @param string $url
     * @return OpenCloud\OpenStack
     */
    public function setUrl($url)
    {
        $this->url = $url;
        
        return $this;
    }
    
    /**
     * Get the URL.
     * 
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
    
    /**
     * Set the secret for the client.
     * 
     * @param  array $secret
     * @return OpenCloud\OpenStack
     */
    public function setSecret(array $secret = array())
    {
        $this->secret = $secret;
        
        return $this;
    }
    
    /**
     * Get the secret.
     * 
     * @return array
     */
    public function getSecret()
    {
        return $this->secret;
    }
    
    /**
     * Set the token for this client.
     * 
     * @param  string $token
     * @return OpenCloud\OpenStack
     */
    public function setToken($token)
    {
        $this->token = $token;
        
        return $this;
    }
    
    /**
     * Get the token for this client.
     * 
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }
    
    /**
     * Set the expiration for this token.
     * 
     * @param  int $expiration
     * @return OpenCloud\OpenStack
     */
    public function setExpiration($expiration)
    {
        $this->expiration = $expiration;
        
        return $this;
    }
    
    /**
     * Get the expiration time.
     * 
     * @return int
     */
    public function getExpiration()
    {
        return $this->expiration;
    }
    
    /**
     * Set the tenant for this client.
     * 
     * @param  string $tenant
     * @return OpenCloud\OpenStack
     */
    public function setTenant($tenant)
    {
        $this->tenant = $tenant;
        
        return $this;
    }
    
    /**
     * Get the tenant for this client.
     * 
     * @return string
     */
    public function getTenant()
    {
        return $this->tenant;
    }
    
    /**
     * Set the service catalog.
     * 
     * @param  mixed $catalog
     * @return OpenCloud\OpenStack
     */
    public function setCatalog($catalog)
    {
        $this->catalog = $catalog;
        
        return $this;
    }
    
    /**
     * Get the service catalog.
     * 
     * @return array
     */
    public function getCatalog()
    {
        return $this->catalog;
    }
    
    /**
     * Set (all) the cURL options.
     * 
     * @param  array $options
     * @return OpenCloud\OpenStack
     */
    public function setCurlOptions(array $options)
    {
        $this->curlOptions = $options;
        
        return $this;
    }
    
    /**
     * Get the cURL options.
     * 
     * @return array
     */
    public function getCurlOptions()
    {
        return $this->curlOptions;
    }
    
    /**
     * Set a specific file descriptor (associated with a URL)
     * 
     * @param  string $key
     * @param  resource $value
     * @return OpenCloud\OpenStack
     */
    public function setFileDescriptor($key, $value)
    {
        $this->descriptors[$key] = $value;
        
        return $this;
    }
    
    /**
     * Get a specific file descriptor (associated with a URL)
     * 
     * @param  string $key
     * @return resource|false
     */
    public function getFileDescriptor($key)
    {
        return (!isset($this->descriptors[$key])) ? false : $this->descriptors[$key];
    }
    
    /**
     * Get the items to be exported.
     * 
     * @return array
     */
    public function getExportItems()
    {
        return $this->exportItems;
    }
    
    /**
     * Sets the connect timeout.
     * 
     * @param  int $timeout
     * @return OpenCloud\OpenStack
     */
    public function setConnectTimeout($timeout)
    {
        $this->connectTimeout = $timeout;
        
        return $this;
    }
    
    /**
     * Get the connect timeout.
     * 
     * @return int
     */
    public function getConnectTimeout()
    {
        return $this->connectTimeout;
    }
    
    /**
     * Set the HTTP timeout.
     * 
     * @param  int $timeout
     * @return OpenCloud\OpenStack
     */
    public function setHttpTimeout($timeout)
    {
        $this->httpTimeout = $timeout;
        
        return $this;
    }
    
    /**
     * Get the HTTP timeout.
     * 
     * @return int
     */
    public function getHttpTimeout()
    {
        return $this->httpTimeout;
    }
    
    /**
     * Set the overlimit timeout.
     * 
     * @param  int $timeout
     * @return OpenCloud\OpenStack
     */
    public function setOverlimitTimeout($timeout)
    {
        $this->overlimitTimeout = $timeout;
        
        return $this;
    }
    
    /**
     * Get the overlimit timeout.
     * 
     * @return int
     */
    public function getOverlimitTimeout()
    {
        return $this->overlimitTimeout;
    }
    
    /**
     * Sets default values (an array) for a service. Each array must contain a
     * "name", "region" and "urltype" key.
     * 
     * @param string $service
     * @param array $value
     * @return OpenCloud\OpenStack
     */
    public function setDefault($service, array $value = array())
    {
        if (isset($value['name']) && isset($value['region']) && isset($value['urltype'])) {
            $this->defaults[$service] = $value;
        }
        
        return $this;
    }
    
    /**
     * Get a specific default value for a service. If none exist, return FALSE.
     * 
     * @param  string $service
     * @return array|false
     */
    public function getDefault($service)
    {
        return (!isset($this->defaults[$service])) ? false : $this->defaults[$service];
    }
    
/**
     * Sets the timeouts for the current connection
     *
     * @api
     * @param integer $t_http the HTTP timeout value (the max period that
     *      the OpenStack object will wait for any HTTP request to complete).
     *      Value is in seconds.
     * @param integer $t_conn the Connect timeout value (the max period
     *      that the OpenStack object will wait to establish an HTTP
     *      connection). Value is in seconds.
     * @param integer $t_overlimit the overlimit timeout value (the max period
     *      that the OpenStack object will wait to retry on an overlimit
     *      condition). Value is in seconds.
     * @return void
     */
    public function setTimeouts($httpTimeout, $connectTimeout = null, $overlimitTimeout = null)
    {
        $this->setHttpTimeout($httpTimeout);

        if (isset($connectTimeout)) {
            $this->setConnectTimeout($connectTimeout);
        }

        if (isset($overlimitTimeout)) {
            $this->setOverlimitTimeout($overlimitTimeout);
        }
    }
    
    /**
     * Returns the URL of this object
     *
     * @api
     * @param string $subresource specified subresource
     * @return string
     */
    public function url($subresource='tokens')
    {
        return Lang::noslash($this->url) . '/' . $subresource;
    }

    /**
     * Returns the stored secret
     *
     * @return array
     */
    public function secret()
    {
        return $this->getSecret();
    }
   
    /**
     * Re-authenticates session if expired.
     */
    public function checkExpiration()
    {
        if ($this->hasExpired()) {
            $this->authenticate();
        }
    }
    
    /**
     * Checks whether token has expired.
     * 
     * @return bool
     */
    public function hasExpired()
    {
        return time() > ($this->getExpiration() - RAXSDK_FUDGE);
    }
    
    /**
     * Returns the cached token; if it has expired, then it re-authenticates
     *
     * @api
     * @return string
     */
    public function token()
    {
        $this->checkExpiration();
        
        return $this->getToken();
    }

    /**
     * Returns the cached expiration time;
     * if it has expired, then it re-authenticates
     *
     * @api
     * @return string
     */
    public function expiration()
    {
        $this->checkExpiration();
        
        return $this->getExpiration();
    }

    /**
     * Returns the tenant ID, re-authenticating if necessary
     *
     * @api
     * @return string
     */
    public function tenant()
    {
        $this->checkExpiration();
        
        return $this->getTenant();
    }

    /**
     * Returns the service catalog object from the auth service
     *
     * @return \stdClass
     */
    public function serviceCatalog()
    {
        $this->checkExpiration();
        
        return $this->getCatalog();
    }

    /**
     * Returns a Collection of objects with information on services
     *
     * Note that these are informational (read-only) and are not actually
     * 'Service'-class objects.
     */
    public function serviceList()
    {
        return new Common\Collection($this, 'ServiceCatalogItem', $this->serviceCatalog());
    }

    /**
     * Creates and returns the formatted credentials to POST to the auth
     * service.
     *
     * @return string
     */
    public function credentials()
    {
        if (isset($this->secret['username']) && isset($this->secret['password'])) {
            
            $credentials = array(
                'auth' => array(
                    'passwordCredentials' => array(
                        'username' => $this->secret['username'],
                        'password' => $this->secret['password']
                    )
                )
            );

            if (isset($this->secret['tenantName'])) {
                $credentials['auth']['tenantName'] = $this->secret['tenantName'];
            }

            return json_encode($credentials);
            
        } else {
            throw new Exceptions\CredentialError(
               Lang::translate('Unrecognized credential secret')
            );
        }
    }

    /**
     * Authenticates using the supplied credentials
     *
     * @api
     * @return void
     * @throws AuthenticationError
     */
    public function authenticate()
    {
        // try to auth
        $response = $this->request(
            $this->url(),
            'POST',
            array('Content-Type'=>'application/json'),
            $this->credentials()
        );

        $json = $response->httpBody();

        // check for errors
        if ($response->HttpStatus() >= 400) {
            throw new Exceptions\AuthenticationError(sprintf(
                Lang::translate('Authentication failure, status [%d], response [%s]'),
                $response->httpStatus(),
                $json
            ));
        }

        // Decode and check
        $object = json_decode($json);
        $this->checkJsonError();
        
        // Save the token information as well as the ServiceCatalog
        $this->setToken($object->access->token->id);
        $this->setExpiration(strtotime($object->access->token->expires));
        $this->setCatalog($object->access->serviceCatalog);

        /**
         * In some cases, the tenant name/id is not returned
         * as part of the auth token, so we check for it before
         * we set it. This occurs with pure Keystone, but not
         * with the Rackspace auth.
         */
        if (isset($object->access->token->tenant)) {
            $this->setTenant($object->access->token->tenant->id);
        }
    }

    /**
     * Performs a single HTTP request
     *
     * The request() method is one of the most frequently-used in the entire
     * library. It performs an HTTP request using the specified URL, method,
     * and with the supplied headers and body. It handles error and
     * exceptions for the request.
     *
     * @api
     * @param string url - the URL of the request
     * @param string method - the HTTP method (defaults to GET)
     * @param array headers - an associative array of headers
     * @param string data - either a string or a resource (file pointer) to
     *      use as the request body (for PUT or POST)
     * @return HttpResponse object
     * @throws HttpOverLimitError, HttpUnauthorizedError, HttpForbiddenError
     */
    public function request($url, $method = 'GET', $headers = array(), $data = null)
    {
        $this->getLogger()->info('Resource [{url}] method [{method}] body [{body}]', array(
            'url'    => $url, 
            'method' => $method, 
            'data'   => $data
        ));

        // get the request object
        $http = $this->getHttpRequestObject($url, $method, $this->getCurlOptions());

        // set various options
        $this->getLogger()->info('Headers: [{headers}]', array(
            'headers' => print_r($headers, true)
        ));
        
        $http->setheaders($headers);
        $http->setHttpTimeout($this->getHttpTimeout());
        $http->setConnectTimeout($this->getConnectTimeout());
        $http->setOption(CURLOPT_USERAGENT, $this->getUserAgent());

        // data can be either a resource or a string
        if (is_resource($data)) {
            // loading from or writing to a file
            // set the appropriate callback functions
            switch($method) {
                // @codeCoverageIgnoreStart
                case 'GET':
                    // need to save the file descriptor
                    $this->setFileDescriptor($url, $data);
                    // set the CURL options
                    $http->setOption(CURLOPT_FILE, $data);
                    $http->setOption(CURLOPT_WRITEFUNCTION, array($this, '_write_cb'));
                    break;
                // @codeCoverageIgnoreEnd
                case 'PUT':
                case 'POST':
                    // need to save the file descriptor
                    $this->setFileDescriptor($url, $data);
                    if (!isset($headers['Content-Length'])) {
                        throw new Exceptions\HttpError(
                            Lang::translate('The Content-Length: header must be specified for file uploads')
                        );
                    }
                    $http->setOption(CURLOPT_UPLOAD, TRUE);
                    $http->setOption(CURLOPT_INFILE, $data);
                    $http->setOption(CURLOPT_INFILESIZE, $headers['Content-Length']);
                    $http->setOption(CURLOPT_READFUNCTION, array($this, '_read_cb'));
                    break;
                default:
                    // do nothing
                    break;
            }
        } elseif (is_string($data)) {
            $http->setOption(CURLOPT_POSTFIELDS, $data);
        } elseif (isset($data)) {
            throw new Exceptions\HttpError(
                Lang::translate('Unrecognized data type for PUT/POST body, must be string or resource')
            );
        }
        
        // perform the HTTP request; returns an HttpResult object
        $response = $http->execute();

        // handle and retry on overlimit errors
        if ($response->httpStatus() == 413) {
            
            $object = json_decode($response->httpBody());
            $this->checkJsonError();
            
            // @codeCoverageIgnoreStart
            if (isset($object->overLimit)) {
                /**
                 * @TODO(glen) - The documentation says "retryAt", but
                 * the field returned is "retryAfter". If the doc changes,
                 * then there's no problem, but we'll need to fix this if
                 * they change the code to match the docs.
                 */
                $retryAfter    = $object->overLimit->retryAfter;
                $sleepInterval = strtotime($retryAfter) - time();

                if ($sleepInterval && $sleepInterval <= $this->getOverlimitTimeout()) {
                    sleep($sleepInterval);
                    $response = $http->Execute();
                } else {
                    throw new Exceptions\HttpOverLimitError(sprintf(
                        Lang::translate('Over limit; next available request [%s][%s] is not for [%d] seconds at [%s]'),
                        $method,
                        $url,
                        $sleepInterval,
                        $retryAfter
                    ));
                }
            }
            // @codeCoverageIgnoreEnd
        }

        // do some common error checking
        switch ($response->httpStatus()) {
            case 401:
                throw new Exceptions\HttpUnauthorizedError(sprintf(
                    Lang::translate('401 Unauthorized for [%s] [%s]'),
                    $url,
                    $response->HttpBody()
                ));
                break;
            case 403:
                throw new Exceptions\HttpForbiddenError(sprintf(
                    Lang::translate('403 Forbidden for [%s] [%s]'),
                    $url,
                    $response->HttpBody()
                ));
                break;
            case 413:   // limit
                throw new Exceptions\HttpOverLimitError(sprintf(
                    Lang::translate('413 Over limit for [%s] [%s]'),
                    $url,
                    $response->HttpBody()
                ));
                break;
            default:
                // everything is fine here, we're fine, how are you?
                break;
        }

        // free the handle
        $http->close();

        // return the HttpResponse object
        $this->getLogger()->info('HTTP STATUS [{code}]', array(
            'code' => $response->httpStatus()
        ));

        return $response;
    }

    /**
     * Sets default values for name, region, URL type for a service
     *
     * Once these are set (and they can also be set by defining global
     * constants), then you do not need to specify these values when
     * creating new service objects.
     *
     * @api
     * @param string $service the name of a supported service; e.g. 'Compute'
     * @param string $name the service name; e.g., 'cloudServersOpenStack'
     * @param string $region the region name; e.g., 'LON'
     * @param string $urltype the type of URL to use; e.g., 'internalURL'
     * @return void
     * @throws UnrecognizedServiceError
     */
    public function setDefaults(
        $service,
        $name = null,
        $region = null,
        $urltype = null
    ) {

        if (!isset($this->defaults[$service])) {
            throw new Exceptions\UnrecognizedServiceError(sprintf(
                Lang::translate('Service [%s] is not recognized'), $service
            ));
        }

        if (isset($name)) {
            $this->defaults[$service]['name'] = $name;
        }

        if (isset($region)) {
            $this->defaults[$service]['region'] = $region;
        }

        if (isset($urltype)) {
            $this->defaults[$service]['urltype'] = $urltype;
        }
    }

    /**
     * Allows the user to define a function for tracking uploads
     *
     * This can be used to implement a progress bar or similar function. The
     * callback function is called with a single parameter, the length of the
     * data that is being uploaded on this call.
     *
     * @param callable $callback the name of a global callback function, or an
     *      array($object, $functionname)
     * @return void
     */
    public function setUploadProgressCallback($callback)
    {
        $this->_user_write_progress_callback_func = $callback;
    }

    /**
     * Allows the user to define a function for tracking downloads
     *
     * This can be used to implement a progress bar or similar function. The
     * callback function is called with a single parameter, the length of the
     * data that is being downloaded on this call.
     *
     * @param callable $callback the name of a global callback function, or an
     *      array($object, $functionname)
     * @return void
     */
    public function setDownloadProgressCallback($callback)
    {
        $this->_user_read_progress_callback_func = $callback;
    }

    /**
     * Callback function to handle reads for file uploads
     *
     * Internal function for handling file uploads. Note that, although this
     * function's visibility is public, this is only because it must be called
     * from the HttpRequest interface. This should NOT be called by users
     * directly.
     *
     * @param resource $ch a CURL handle
     * @param resource $fd a file descriptor
     * @param integer $length the amount of data to read
     * @return string the data read
     * @codeCoverageIgnore
     */
    public function _read_cb($ch, $fd, $length)
    {
        $data = fread($fd, $length);
        $len = strlen($data);
        if (isset($this->_user_write_progress_callback_func)) {
            call_user_func($this->_user_write_progress_callback_func, $len);
        }
        return $data;
    }

    /**
     * Callback function to handle writes for file downloads
     *
     * Internal function for handling file downloads. Note that, although this
     * function's visibility is public, this is only because it must be called
     * via the HttpRequest interface. This should NOT be called by users
     * directly.
     *
     * @param resource $ch a CURL handle
     * @param string $data the data to be written to a file
     * @return integer the number of bytes written
     * @codeCoverageIgnore
     */
    public function _write_cb($ch, $data)
    {
        $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        if (false === ($fp = $this->getFileDescriptor($url))) {
            throw new Exceptions\HttpUrlError(sprintf(
                Lang::translate('Cannot find file descriptor for URL [%s]'), $url)
            );
        }

        $dlen = strlen($data);
        fwrite($fp, $data, $dlen);

        // call used callback function
        if (isset($this->_user_read_progress_callback_func)) {
            call_user_func($this->_user_read_progress_callback_func, $dlen);
        }

        // MUST return the length to CURL
        return $dlen;
    }

    /**
     * exports saved token, expiration, tenant, and service catalog as an array
     *
     * This could be stored in a cache (APC or disk file) and reloaded using
     * ImportCredentials()
     *
     * @return array
     */
    public function exportCredentials()
    {
    	$this->authenticate();
    	
        $array = array();
        
        foreach ($this->getExportItems() as $key) {
            $array[$key] = $this->$key;
        }
        
        return $array;
    }

    /**
     * imports credentials from an array
     *
     * Takes the same values as ExportCredentials() and reuses them.
     *
     * @return void
     */
    public function importCredentials(array $values)
    {
        foreach ($this->getExportItems() as $item) {
            $this->$item = $values[$item];
        }
    }

    /********** FACTORY METHODS **********
     * 
     * These methods are provided to permit easy creation of services
     * (for example, Nova or Swift) from a connection object. As new
     * services are supported, factory methods should be provided here.
     */

    /**
     * Creates a new ObjectStore object (Swift/Cloud Files)
     *
     * @api
     * @param string $name the name of the Object Storage service to attach to
     * @param string $region the name of the region to use
     * @param string $urltype the URL type (normally "publicURL")
     * @return ObjectStore
     */
    public function objectStore($name = null, $region = null, $urltype = null)
    {
        return $this->service('ObjectStore', $name, $region, $urltype);
    }

    /**
     * Creates a new Compute object (Nova/Cloud Servers)
     *
     * @api
     * @param string $name the name of the Compute service to attach to
     * @param string $region the name of the region to use
     * @param string $urltype the URL type (normally "publicURL")
     * @return Compute
     */
    public function compute($name = null, $region = null, $urltype = null)
    {
        return $this->service('Compute', $name, $region, $urltype);
    }

    /**
     * Creates a new Orchestration (heat) service object
     *
     * @api
     * @param string $name the name of the Compute service to attach to
     * @param string $region the name of the region to use
     * @param string $urltype the URL type (normally "publicURL")
     * @return Orchestration\Service
     * @codeCoverageIgnore
     */
    public function orchestration($name = null, $region = null, $urltype = null)
    {
        return $this->service('Orchestration', $name, $region, $urltype);
    }

    /**
     * Creates a new VolumeService (cinder) service object
     *
     * This is a factory method that is Rackspace-only (NOT part of OpenStack).
     *
     * @param string $name the name of the service (e.g., 'cloudBlockStorage')
     * @param string $region the region (e.g., 'DFW')
     * @param string $urltype the type of URL (e.g., 'publicURL');
     */
    public function volumeService($name = null, $region = null, $urltype = null)
    {
        return $this->service('Volume', $name, $region, $urltype);
    }

    /**
     * Generic Service factory method
     *
     * Contains code reused by the other service factory methods.
     *
     * @param string $class the name of the Service class to produce
     * @param string $name the name of the Compute service to attach to
     * @param string $region the name of the region to use
     * @param string $urltype the URL type (normally "publicURL")
     * @return Service (or subclass such as Compute, ObjectStore)
     * @throws ServiceValueError
     */
    public function service($class, $name = null, $region = null, $urltype = null)
    {
        // debug message
        $this->getLogger()->info('Factory for class [{class}] [{name}/{region}/{urlType}]', array(
            'class'   => $class, 
            'name'    => $name, 
            'region'  => $region, 
            'urlType' => $urltype
        ));

        // Strips off base namespace 
        $class = preg_replace('#\\\?OpenCloud\\\#', '', $class);

        // check for defaults
        $default = $this->getDefault($class);

        // report errors
        if (!$name = $name ?: $default['name']) {
            throw new Exceptions\ServiceValueError(sprintf(
                Lang::translate('No value for %s name'),
                $class
            ));
        }

        if (!$region = $region ?: $default['region']) {
            throw new Exceptions\ServiceValueError(sprintf(
                Lang::translate('No value for %s region'),
                $class
            ));
        }

        if (!$urltype = $urltype ?: $default['urltype']) {
            throw new Exceptions\ServiceValueError(sprintf(
                Lang::translate('No value for %s URL type'),
                $class
            ));
        }

        // return the object
        $fullclass = 'OpenCloud\\' . $class . '\\Service';

        return new $fullclass($this, $name, $region, $urltype);
    }

    /**
     * returns a service catalog item
     *
     * This is a helper function used to list service catalog items easily
     */
    public function serviceCatalogItem($info = array())
    {
        return new ServiceCatalogItem($info);
    }
    
}
