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

use finfo as FileInfo;
use OpenCloud\Common\Lang;
use OpenCloud\Common\Exceptions;
use OpenCloud\ObjectStore\AbstractService;
use OpenCloud\Common\Request\Response\Http;

/**
 * Objects are the basic storage entities in Cloud Files. They represent the 
 * files and their optional metadata you upload to the system. When you upload 
 * objects to Cloud Files, the data is stored as-is (without compression or 
 * encryption) and consists of a location (container), the object's name, and 
 * any metadata you assign consisting of key/value pairs.
 */
class DataObject extends AbstractStorageObject
{
    /**
     * Object name. The only restriction on object names is that they must be 
     * less than 1024 bytes in length after URL encoding.
     * 
     * @var string 
     */
    public $name;
    
    /**
     * Hash value of the object.
     * 
     * @var string 
     */
    public $hash;
    
    /**
     * Size of object in bytes.
     * 
     * @var string 
     */
    public $bytes;
    
    /**
     * Date of last modification.
     * 
     * @var string 
     */
    public $last_modified;
    
    /**
     * Object's content type.
     * 
     * @var string 
     */
    public $content_type;
    
    /**
     * Object's content length.
     * 
     * @var string
     */
    public $content_length;
    
    /**
     * Other headers set for this object (e.g. Access-Control-Allow-Origin)
     * 
     * @var array 
     */
    public $extra_headers = array();
    
    /**
     * Whether or not to calculate and send an ETag on create.
     * 
     * @var bool 
     */
    public $send_etag = true;

    /**
     * The data contained by the object.
     * 
     * @var string 
     */
    private $data;
    
    /**
     * The ETag value.
     * 
     * @var string 
     */
    private $etag;
    
    /**
     * The parent container of this object.
     * 
     * @var CDNContainer 
     */
    private $container;

    /**
     * Is this data object a pseudo directory?
     * 
     * @var bool 
     */
    private $directory = false;
    
    /**
     * Used to translate header values (returned by requests) into properties.
     * 
     * @var array 
     */
    private $headerTranslate = array(
        'Etag'           => 'hash',
        'ETag'           => 'hash',
        'Last-Modified'  => 'last_modified',
        'Content-Length' => array('bytes', 'content_length'),
    );
    
    /**
     * These properties can be freely set by the user for CRUD operations.
     * 
     * @var array 
     */
    private $allowedProperties = array(
        'name',
        'content_type',
        'extra_headers',
        'send_etag'
    );
    
    /**
     * Option for clearing the status cache when objects are uploaded to API.
     * By default, it is set to FALSE for performance; but if you have files
     * that are rapidly and very often updated, you might want to clear the status
     * cache so PHP reads the files directly, instead of relying on the cache.
     * 
     * @link http://php.net/manual/en/function.clearstatcache.php
     * @var  bool 
     */
    public $clearStatusCache = false;

    /**
     * A DataObject is related to a container and has a name
     *
     * If `$name` is specified, then it attempts to retrieve the object from the
     * object store.
     *
     * @param Container $container the container holding this object
     * @param mixed $cdata if an object or array, it is treated as values
     *      with which to populate the object. If it is a string, it is
     *      treated as a name and the object's info is retrieved from
     *      the service.
     * @return void
     */
    public function __construct($container, $cdata = null)
    {
        parent::__construct();

        $this->container = $container;
   
        // For pseudo-directories, we need to ensure the name is set
        if (!empty($cdata->subdir)) {
            $this->name = $cdata->subdir;
            $this->directory = true;
        } else {
            $this->populate($cdata);
        }
    }
    
    /**
     * Is this data object a pseudo-directory?
     * 
     * @return bool
     */
    public function isDirectory()
    {
        return $this->directory;
    }
    
    /**
     * Allow other objects to know what the primary key is.
     * 
     * @return string
     */
    public function primaryKeyField()
    {
        return 'name';
    }
    
    /**
     * Is this a real file?
     * 
     * @param  string $filename
     * @return bool
     */
    private function isRealFile($filename)
    {
        return $filename != '/dev/null' && $filename != 'NUL';
    }
    
    /**
     * Set this file's content type.
     * 
     * @param string $contentType
     */
    public function setContentType($contentType)
    {
        $this->content_type = $contentType;
    }
    
    /**
     * Return the content type.
     * 
     * @return string
     */
    public function getContentType()
    {
        return $this->content_type;
    }

    /**
     * Returns the URL of the data object
     *
     * If the object is new and doesn't have a name, then an exception is
     * thrown.
     *
     * @param string $subresource Not used
     * @return string
     * @throws NoNameError
     */
    public function url($subresource = '')
    {
        if (!$this->name) {
            throw new Exceptions\NoNameError(Lang::translate('Object has no name'));
        }

        return Lang::noslash(
            $this->container->url()) . '/' . str_replace('%2F', '/', rawurlencode($this->name)
        );
    }

    /**
     * Creates (or updates; both the same) an instance of the object
     *
     * @api
     * @param array $params an optional associative array that can contain the
     *      'name' and 'content_type' of the object
     * @param string $filename if provided, then the object is loaded from the
     *      specified file
     * @return boolean
     * @throws CreateUpdateError
     */
    public function create($params = array(), $filename = null, $extractArchive = null)
    {
        // Set and validate params
        $this->setParams($params);

        // assume no file upload
        $fp = false;

        // if the filename is provided, process it
        if ($filename) {

            if (!$fp = @fopen($filename, 'r')) {
                throw new Exceptions\IOError(sprintf(
                    Lang::translate('Could not open file [%s] for reading'),
                    $filename
                ));
            }

            // @todo Maybe, for performance, we could set the "clear status cache"
            // feature to false by default - but allow users to set to true if required
            clearstatcache($this->clearStatusCache === true, $filename);

            // Cast filesize as a floating point
            $filesize = (float) filesize($filename);
            
            // Check it's below a reasonable size, and set
            // @codeCoverageIgnoreStart
            if ($filesize > AbstractService::MAX_OBJECT_SIZE) {
                throw new Exceptions\ObjectError("File size exceeds maximum object size.");
            }
            // @codeCoverageIgnoreEnd
            $this->content_length = $filesize;
            
            // Guess the content type if necessary
            if (!$this->getContentType() && $this->isRealFile($filename)) {
                $this->setContentType($this->inferContentType($filename));
            }
            
            // Send ETag checksum if necessary
            if ($this->send_etag) {
                $this->etag = md5_file($filename);
            }

            // Announce to the world
            $this->getLogger()->info('Uploading {size} bytes from {name}', array(
                'size' => $filesize, 
                'name' => $filename
            ));
            
        } else {
            // compute the length
            $this->content_length = strlen($this->data);

            if ($this->send_etag) {
                $this->etag = md5($this->data);
            }
        }

        // Only allow supported archive types
        // http://docs.rackspace.com/files/api/v1/cf-devguide/content/Extract_Archive-d1e2338.html
        $extractArchiveUrlArg = '';
        
        if ($extractArchive) {
            if ($extractArchive !== "tar.gz" && $extractArchive !== "tar.bz2") {
                throw new Exceptions\ObjectError(
                    "Extract Archive only supports tar.gz and tar.bz2"
                );
            } else {
                $extractArchiveUrlArg = "?extract-archive=" . $extractArchive;
                $this->etag = null;
                $this->setContentType('');
            }
        }

        // Set headers
        $headers = $this->metadataHeaders();
        
        if (!empty($this->etag)) {
            $headers['ETag'] = $this->etag;
        }

		// Content-Type is no longer required; if not specified, it will
		// attempt to guess based on the file extension.
		if (!$this->getContentType()) {
        	$headers['Content-Type'] = $this->getContentType();
        }
        
        $headers['Content-Length'] = $this->content_length;

        // Merge in extra headers
        if (!empty($this->extra_headers)) {
            $headers = $this->extra_headers + $headers;
        }

        // perform the request
        $response = $this->getService()->request(
            $this->url() . $extractArchiveUrlArg,
            'PUT',
            $headers,
            $fp ? $fp : $this->data
        );

        // check the status
        // @codeCoverageIgnoreStart
        if (($status = $response->httpStatus()) >= 300) {
            throw new Exceptions\CreateUpdateError(sprintf(
                Lang::translate('Problem saving/updating object [%s] HTTP status [%s] response [%s]'),
                $this->url() . $extractArchiveUrlArg,
                $status,
                $response->httpBody()
            ));
        }
        // @codeCoverageIgnoreEnd

        // set values from response
        $this->saveResponseHeaders($response);

        // close the file handle
        if ($fp) {
            fclose($fp);
        }

        return $response;
    }

    /**
     * Update() is provided as an alias for the Create() method
     *
     * Since update and create both use a PUT request, the different functions
     * may allow the developer to distinguish between the semantics in his or
     * her application.
     *
     * @api
     * @param array $params an optional associative array that can contain the
     *      'name' and 'type' of the object
     * @param string $filename if provided, the object is loaded from the file
     * @return boolean
     */
    public function update($params = array(), $filename = '')
    {
        return $this->create($params, $filename);
    }

    /**
     * UpdateMetadata() - updates headers
     *
     * Updates metadata headers
     *
     * @api
     * @param array $params an optional associative array that can contain the
     *      'name' and 'type' of the object
     * @return boolean
     */
    public function updateMetadata($params = array())
    {
        $this->setParams($params);

        // set the headers
        $headers = $this->metadataHeaders();
        $headers['Content-Type'] = $this->getContentType();

        $response = $this->getService()->request(
            $this->url(),
            'POST',
            $headers
        );

        // check the status
        // @codeCoverageIgnoreStart
        if (($stat = $response->httpStatus()) >= 204) {
            throw new Exceptions\UpdateError(sprintf(
                Lang::translate('Problem updating object [%s] HTTP status [%s] response [%s]'),
                $this->url(),
                $stat,
                $response->httpBody()
            ));
        }
        // @codeCoverageIgnoreEnd
        
        return $response;
    }

    /**
     * Deletes an object from the Object Store
     *
     * Note that we can delete without retrieving by specifying the name in the
     * parameter array.
     *
     * @api
     * @param array $params an array of parameters
     * @return HttpResponse if successful; FALSE if not
     * @throws DeleteError
     */
    public function delete($params = array())
    {
        $this->setParams($params);

        $response = $this->getService()->request($this->url(), 'DELETE');

        // check the status
        // @codeCoverageIgnoreStart
        if (($stat = $response->httpStatus()) >= 300) {
            throw new Exceptions\DeleteError(sprintf(
                Lang::translate('Problem deleting object [%s] HTTP status [%s] response [%s]'),
                $this->url(),
                $stat,
                $response->httpBody()
            ));
        }
        // @codeCoverageIgnoreEnd
        
        return $response;
    }

    /**
     * Copies the object to another container/object
     *
     * Note that this function, because it operates within the Object Store
     * itself, is much faster than downloading the object and re-uploading it
     * to a new object.
     *
     * @param DataObject $target the target of the COPY command
     */
    public function copy(DataObject $target)
    {
        $uri = sprintf('/%s/%s', $target->container()->name(), $target->name());

        $this->getLogger()->info('Copying object to [{uri}]', array('uri' => $uri));

        $response = $this->getService()->request(
            $this->url(),
            'COPY',
            array('Destination' => $uri)
        );

        // check response code
        // @codeCoverageIgnoreStart
        if ($response->httpStatus() > 202) {
            throw new Exceptions\ObjectCopyError(sprintf(
                Lang::translate('Error copying object [%s], status [%d] response [%s]'),
                $this->url(),
                $response->httpStatus(),
                $response->httpBody()
            ));
        }
        // @codeCoverageIgnoreEnd

        return $response;
    }

    /**
     * Returns the container of the object
     *
     * @return Container
     */
    public function container()
    {
        return $this->container;
    }

    /**
     * returns the TEMP_URL for the object
     *
     * Some notes:
     * * The `$secret` value is arbitrary; it must match the value set for
     *   the `X-Account-Meta-Temp-URL-Key` on the account level. This can be
     *   set by calling `$service->SetTempUrlSecret($secret)`.
     * * The `$expires` value is the number of seconds you want the temporary
     *   URL to be valid for. For example, use `60` to make it valid for a
     *   minute
     * * The `$method` must be either GET or PUT. No other methods are
     *   supported.
     *
     * @param string $secret the shared secret
     * @param integer $expires the expiration time (in seconds)
     * @param string $method either GET or PUT
     * @return string the temporary URL
     */
    public function tempUrl($secret, $expires, $method)
    {
        $method = strtoupper($method);
        $expiry_time = time() + $expires;

        // check for proper method
        if ($method != 'GET' && $method != 'PUT') {
            throw new Exceptions\TempUrlMethodError(sprintf(
                Lang::translate(
                'Bad method [%s] for TempUrl; only GET or PUT supported'),
                $method
            ));
        }

        // construct the URL
        $url  = $this->url();
        $path = urldecode(parse_url($url, PHP_URL_PATH));

        $hmac_body = "$method\n$expiry_time\n$path";
        $hash = hash_hmac('sha1', $hmac_body, $secret);

        $this->getLogger()->info('URL [{url}]; SIG [{sig}]; HASH [{hash}]', array(
            'url'  => $url, 
            'sig'  => $hmac_body, 
            'hash' => $hash
        ));

        $temp_url = sprintf('%s?temp_url_sig=%s&temp_url_expires=%d', $url, $hash, $expiry_time);

        // debug that stuff
        $this->getLogger()->info('TempUrl generated [{url}]', array(
            'url' => $temp_url
        ));

        return $temp_url;
    }

    /**
     * Sets object data from string
     *
     * This is a convenience function to permit the use of other technologies
     * for setting an object's content.
     *
     * @param string $data
     * @return void
     */
    public function setData($data)
    {
        $this->data = (string) $data;
    }

    /**
     * Return object's data as a string
     *
     * @return string the entire object
     */
    public function saveToString()
    {
        return $this->getService()->request($this->url())->httpBody();
    }

    /**
     * Saves the object's data to local filename
     *
     * Given a local filename, the Object's data will be written to the newly
     * created file.
     *
     * Example:
     * <code>
     * # ... authentication/connection/container code excluded
     * # ... see previous examples
     *
     * # Whoops!  I deleted my local README, let me download/save it
     * #
     * $my_docs = $conn->get_container("documents");
     * $doc = $my_docs->get_object("README");
     *
     * $doc->SaveToFilename("/home/ej/cloudfiles/readme.restored");
     * </code>
     *
     * @param string $filename name of local file to write data to
     * @return boolean <kbd>TRUE</kbd> if successful
     * @throws IOException error opening file
     * @throws InvalidResponseException unexpected response
     */
    public function saveToFilename($filename)
    {
        if (!$fp = @fopen($filename, "wb")) {
            throw new Exceptions\IOError(sprintf(
                Lang::translate('Could not open file [%s] for writing'),
                $filename
            ));
        }
        
        $result = $this->getService()->request($this->url(), 'GET', array(), $fp);
        
        fclose($fp);
        
        return $result;
    }

    /**
     * Saves the object's to a stream filename
     *
     * Given a local filename, the Object's data will be written to the stream
     *
     * Example:
     * <code>
     * # ... authentication/connection/container code excluded
     * # ... see previous examples
     *
     * # If I want to write the README to a temporary memory string I
     * # do :
     * #
     * $my_docs = $conn->get_container("documents");
     * $doc = $my_docs->DataObject(array("name"=>"README"));
     *
     * $fp = fopen('php://temp', 'r+');
     * $doc->SaveToStream($fp);
     * fclose($fp);
     * </code>
     *
     * @param string $filename name of local file to write data to
     * @return boolean <kbd>TRUE</kbd> if successful
     * @throws IOException error opening file
     * @throws InvalidResponseException unexpected response
     */
    public function saveToStream($resource)
    {
        if (!is_resource($resource)) {
            throw new Exceptions\ObjectError(
                Lang::translate("Resource argument not a valid PHP resource."
            ));
        }

        return $this->getService()->request($this->url(), 'GET', array(), $resource);
    }


    /**
     * Returns the object's MD5 checksum
     *
     * Accessor method for reading Object's private ETag attribute.
     *
     * @api
     * @return string MD5 checksum hexidecimal string
     */
    public function getETag()
    {
        return $this->etag;
    }

    /**
     * Purges the object from the CDN
     *
     * Note that the object will still be served up to the time of its
     * TTL value.
     *
     * @api
     * @param string $email An email address that will be notified when
     *      the object is purged.
     * @return void
     * @throws CdnError if the container is not CDN-enabled
     * @throws CdnHttpError if there is an HTTP error in the transaction
     */
    public function purgeCDN($email)
    {
        // @codeCoverageIgnoreStart
        if (!$cdn = $this->Container()->CDNURL()) {
            throw new Exceptions\CdnError(Lang::translate('Container is not CDN-enabled'));
        }
        // @codeCoverageIgnoreEnd

        $url = $cdn . '/' . $this->name;
        $headers['X-Purge-Email'] = $email;
        $response = $this->getService()->request($url, 'DELETE', $headers);

        // check the status
        // @codeCoverageIgnoreStart
        if ($response->httpStatus() > 204) {
            throw new Exceptions\CdnHttpError(sprintf(
                Lang::translate('Error purging object, status [%d] response [%s]'),
                $response->httpStatus(),
                $response->httpBody()
            ));
        }
        // @codeCoverageIgnoreEnd
        
        return true;
    }

    /**
     * Returns the CDN URL (for managing the object)
     *
     * Note that the DataObject::PublicURL() method is used to return the
     * publicly-available URL of the object, while the CDNURL() is used
     * to manage the object.
     *
     * @return string
     */
    public function CDNURL()
    {
        return $this->container()->CDNURL() . '/' . $this->name;
    }

    /**
     * Returns the object's Public CDN URL, if available
     *
     * @api
     * @param string $type can be 'streaming', 'ssl', 'ios-streaming', 
     *		or anything else for the
     *      default URL. For example, `$object->PublicURL('ios-streaming')`
     * @return string
     */
    public function publicURL($type = null)
    {
        if (!$prefix = $this->container()->CDNURI()) {
            return null;
        }

        switch(strtoupper($type)) {
            case 'SSL':
                $url = $this->container()->SSLURI().'/'.$this->name;
                break;
            case 'STREAMING':
                $url = $this->container()->streamingURI().'/'.$this->name;
                break;
            case 'IOS':
            case 'IOS-STREAMING':
            	$url = $this->container()->iosStreamingURI().'/'.$this->name;
                break;
            default:
                $url = $prefix.'/'.$this->name;
                break;
        }
        
        return $url;
    }

    /**
     * Sets parameters from an array and validates them.
     *
     * @param  array $params  Associative array of parameters
     * @return void
     */
    private function setParams(array $params = array())
    {
        // Inspect the user's array for any unapproved keys, and unset if necessary
        foreach (array_diff(array_keys($params), $this->allowedProperties) as $key) {
            $this->getLogger()->warning('You cannot use the {keyName} key when creating an object', array(
                'keyName' => $key
            ));
            unset($params[$key]);
        }
        
        $this->populate($params);
    }

    /**
     * Retrieves a single object, parses headers
     *
     * @return void
     * @throws NoNameError, ObjFetchError
     */
    private function fetch()
    {
        if (!$this->name) {
            throw new Exceptions\NoNameError(Lang::translate('Cannot retrieve an unnamed object'));
        }

        $response = $this->getService()->request($this->url(), 'HEAD', array('Accept' => '*/*'));

        // check for errors
        // @codeCoverageIgnoreStart
        if ($response->httpStatus() >= 300) {
            throw new Exceptions\ObjFetchError(sprintf(
                Lang::translate('Problem retrieving object [%s]'),
                $this->url()
            ));
        }
        // @codeCoverageIgnoreEnd

        // set headers as metadata?
        $this->saveResponseHeaders($response);

        // parse the metadata
        $this->getMetadata($response);
    }
    
    /**
     * Extracts the headers from the response, and saves them as object 
     * attributes. Additional name conversions are done where necessary.
     * 
     * @param Http $response
     */
    private function saveResponseHeaders(Http $response, $fillExtraIfNotFound = true)
    {
        foreach ($response->headers() as $header => $value) {
            if (isset($this->headerTranslate[$header])) {
                // This header needs to be translated
                $property = $this->headerTranslate[$header];
                // Are there multiple properties that need to be set?
                if (is_array($property)) {
                    foreach ($property as $subProperty) {
                        $this->$subProperty = $value;
                    }
                } else {
                    $this->$property = $value;
                }
            } elseif ($fillExtraIfNotFound === true) {
                // Otherwise, stock extra headers 
                $this->extra_headers[$header] = $value;
            }
        }
    }

    /**
     * Compatability.
     */
    public function refresh()
    {
        return $this->fetch();
    }
    
    /**
     * Returns the service associated with this object
     *
     * It's actually the object's container's service, so this method will
     * simplify things a bit.
     */
    private function getService()
    {
        return $this->container->getService();
    }

    /**
     * Performs an internal check to get the proper MIME type for an object
     *
     * This function would go over the available PHP methods to get
     * the MIME type.
     *
     * By default it will try to use the PHP fileinfo library which is
     * available from PHP 5.3 or as an PECL extension
     * (http://pecl.php.net/package/Fileinfo).
     *
     * It will get the magic file by default from the system wide file
     * which is usually available in /usr/share/magic on Unix or try
     * to use the file specified in the source directory of the API
     * (share directory).
     *
     * if fileinfo is not available it will try to use the internal
     * mime_content_type function.
     *
     * @param string $handle name of file or buffer to guess the type from
     * @return boolean <kbd>TRUE</kbd> if successful
     * @throws BadContentTypeException
     * @codeCoverageIgnore
     */
    private function inferContentType($handle)
    {
        if ($contentType = $this->getContentType()) {
            return $contentType;
        }
        
        $contentType = false;
        
        $filePath = (is_string($handle)) ? $handle : (string) $handle;
        
        if (function_exists("finfo_open")) {
            
            $magicPath = dirname(__FILE__) . "/share/magic"; 
            $finfo = new FileInfo(FILEINFO_MIME, file_exists($magicPath) ? $magicPath : null);
            
            if ($finfo) {
                
                $contentType = is_file($filePath) 
                    ? $finfo->file($handle) 
                    : $finfo->buffer($handle);

                /**
                 * PHP 5.3 fileinfo display extra information like charset so we 
                 * remove everything after the ; since we are not into that stuff
                 */  
                if (null !== ($extraInfo = strpos($contentType, "; "))) {
                    $contentType = substr($contentType, 0, $extraInfo);
                }
            }
            
            //unset($finfo);
        }

        if (!$contentType) {
            // Try different native function instead
            if (is_file((string) $handle) && function_exists("mime_content_type")) {
                $contentType = mime_content_type($handle);
            } else {
                $this->getLogger()->error('Content-Type cannot be found');
            }
        }

        return $contentType;
    }

}
