<?php

/**
 * Dropbox API class
 *
 * @package Dropbox
 * @copyright Copyright (C) 2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/dropbox-php/wiki/License MIT
 */
class Dropbox_API {

    /**
     * Sandbox root-path
     */
    const ROOT_SANDBOX = 'sandbox';

    /**
     * Dropbox root-path
     */
    const ROOT_DROPBOX = 'dropbox';

    /**
     * API URl
     */
    protected $api_url = 'https://api.dropbox.com/1/';

    /**
     * Content API URl
     */
    protected $api_content_url = 'https://api-content.dropbox.com/1/';

    /**
     * OAuth object
     *
     * @var Dropbox_OAuth
     */
    protected $oauth;

    /**
     * Default root-path, this will most likely be 'sandbox' or 'dropbox'
     *
     * @var string
     */
    protected $root;
    protected $useSSL;
    
    /**
     * PHP is Safe Mode
     * 
     * @var bool
     */
    private $isSafeMode;
    
    /**
     * Chunked file size limit
     * 
     * @var unknown
     */
    public $chunkSize = 20971520; //20MB

    /**
     * Constructor
     *
     * @param Dropbox_OAuth Dropbox_Auth object
     * @param string $root default root path (sandbox or dropbox)
     */
    public function __construct(Dropbox_OAuth $oauth, $root = self::ROOT_DROPBOX, $useSSL = true) {

        $this->oauth = $oauth;
        $this->root = $root;
        $this->useSSL = $useSSL;
        $this->isSafeMode = (version_compare(PHP_VERSION, '5.4', '<') && get_cfg_var('safe_mode'));
        if (!$this->useSSL)
        {
            throw new Dropbox_Exception('Dropbox REST API now requires that all requests use SSL');
        }

    }

    /**
     * Returns information about the current dropbox account
     *
     * @return stdclass
     */
    public function getAccountInfo() {

        $data = $this->oauth->fetch($this->api_url . 'account/info');
        return json_decode($data['body'],true);

    }

    /**
     * Returns a file's contents
     *
     * @param string $path path
     * @param string $root Use this to override the default root path (sandbox/dropbox)
     * @return string
     */
    public function getFile($path = '', $root = null) {

        if (is_null($root)) $root = $this->root;
        $path = str_replace(array('%2F','~'), array('/','%7E'), rawurlencode($path));
        $result = $this->oauth->fetch($this->api_content_url . 'files/' . $root . '/' . ltrim($path,'/'));
        return $result['body'];

    }

    /**
     * Uploads a new file
     *
     * @param string $path Target path (including filename)
     * @param string $file Either a path to a file or a stream resource
     * @param string $root Use this to override the default root path (sandbox/dropbox)
     * @return bool
     */
    public function putFile($path, $file, $root = null) {

        if (is_resource($file)) {
            $stat = fstat($file);
            $size = $stat['size'];
        } else if (is_string($file) && is_readable($file)) {
            $size = filesize($file);
        } else {
        	throw new Dropbox_Exception('File must be a file-resource or a file path string');
        }
         
        if ($this->oauth->isPutSupport()) {
            if ($size) {
                if ($size > $this->chunkSize) {
                    $res = array('uploadID' => null, 'offset' => 0);
                    $i[$res['offset']] = 0;
                    while($i[$res['offset']] < 5) {
                        $res = $this->chunkedUpload($path, $file, $root, true, $res['offset'], $res['uploadID']);
                        if (isset($res['uploadID'])) {
                            if (!isset($i[$res['offset']])) {
                                $i[$res['offset']] = 0;
                            } else {
                                $i[$res['offset']]++;
                            }
                        } else {
                            break;
                        }
                    }
                    return true;
                } else {
                    return $this->putStream($path, $file, $root);
                }
            }
        }

        if ($size > 157286400) {
            // Dropbox API /files has a maximum file size limit of 150 MB.
            // https://www.dropbox.com/developers/core/docs#files-POST
            throw new Dropbox_Exception("Uploading file to Dropbox failed");
        }

        $directory = dirname($path);
        $filename = basename($path);

        if($directory==='.') $directory = '';
        $directory = str_replace(array('%2F','~'), array('/','%7E'), rawurlencode($directory));
        $filename = str_replace('~', '%7E', rawurlencode($filename));
        if (is_null($root)) $root = $this->root;

        if (is_string($file)) {

            $file = fopen($file,'rb');

        } elseif (!is_resource($file)) {
            throw new Dropbox_Exception('File must be a file-resource or a file path string');
        }
        
       	if (!$this->isSafeMode) {
       		set_time_limit(600);
       	}
        $result=$this->multipartFetch($this->api_content_url . 'files/' .
                $root . '/' . trim($directory,'/'), $file, $filename);

        if(!isset($result["httpStatus"]) || $result["httpStatus"] != 200)
            throw new Dropbox_Exception("Uploading file to Dropbox failed");

        return true;
    }

    /**
     * Uploads large files to Dropbox in mulitple chunks
     * 
     * @param string $file Absolute path to the file to be uploaded
     * @param string|bool $filename The destination filename of the uploaded file
     * @param string $path Path to upload the file to, relative to root
     * @param boolean $overwrite Should the file be overwritten? (Default: true)
     * @return stdClass
     */
    public function chunkedUpload($path, $handle, $root = null, $overwrite = true, $offset = 0, $uploadID = null)
    {
        if (is_string($handle) && is_readable($handle)) {
            $handle = fopen($handle, 'rb');
        }

        if (is_resource($handle)) {
            // Seek to the correct position on the file pointer
            fseek($handle, $offset);

            // Read from the file handle until EOF, uploading each chunk
            while ($data = fread($handle, $this->chunkSize)) {
                if (!$this->isSafeMode) {
                	set_time_limit(600);
                }
                
                // Open a temporary file handle and write a chunk of data to it
                $chunkHandle = fopen('php://temp', 'rwb');
                fwrite($chunkHandle, $data);

                // Set the file, request parameters and send the request
                $this->oauth->setInFile($chunkHandle);
                $params = array('upload_id' => $uploadID, 'offset' => $offset);

                try {
                    // Attempt to upload the current chunk
                    $res = $this->oauth->fetch($this->api_content_url . 'chunked_upload', $params, 'PUT');
                    $response = json_decode($res['body'], true);
                } catch (Exception $e) {
                    $res = $this->oauth->getLastResponse();
                    if ($response['httpStatus'] == 400) {
                        // Incorrect offset supplied, return expected offset and upload ID
                        $response = json_decode($res['body'], true);
                        $uploadID = $response['upload_id'];
                        $offset = $response['offset'];
                        return array('uploadID' => $uploadID, 'offset' => $offset);
                    } else {
                        // Re-throw the caught Exception
                        throw $e;
                    }
                    throw $e;
                }

                // On subsequent chunks, use the upload ID returned by the previous request
                if (isset($response['upload_id'])) {
                    $uploadID = $response['upload_id'];
                }

                // Set the data offset
                if (isset($response['offset'])) {
                    $offset = $response['offset'];
                }

                // Close the file handle for this chunk
                fclose($chunkHandle);
            }

            // Complete the chunked upload
            $path = str_replace(array('%2F','~'), array('/','%7E'), rawurlencode($path));
            if (is_null($root)) {
            	$root = $this->root;
            }
            $params = array('overwrite' => (int) $overwrite, 'upload_id' => $uploadID);
            return $this->oauth->fetch($this->api_content_url . 'commit_chunked_upload/' .
                    $root . '/' . ltrim($path,'/'), $params, 'POST');
        } else {
            throw new Dropbox_Exception('Could not open ' . $handle . ' for reading');
        }
    }

    /**
     * Uploads file data from a stream
     * 
     * Note: This function is experimental and requires further testing
     * @param resource $stream A readable stream created using fopen()
     * @param string $filename The destination filename, including path
     * @param boolean $overwrite Should the file be overwritten? (Default: true)
     * @return array
     */
    public function putStream($path, $file, $root = null, $overwrite = true)
    {
       	if ($this->isSafeMode) {
       		set_time_limit(600);
       	}
        
        $path = str_replace(array('%2F','~'), array('/','%7E'), rawurlencode($path));
        if (is_null($root)) {
        	$root = $this->root;
        }

        $params = array('overwrite' => (int) $overwrite);
        $this->oauth->setInfile($file);
        $result=$this->oauth->fetch($this->api_content_url . 'files_put/' .
                $root . '/' . ltrim($path,'/'), $params, 'PUT');
        
        if (!isset($result["httpStatus"]) || $result["httpStatus"] != 200) {
            throw new Dropbox_Exception("Uploading file to Dropbox failed");
        }
        
        return true;
    }

    /**
     * Copies a file or directory from one location to another
     *
     * This method returns the file information of the newly created file.
     *
     * @param string $from source path
     * @param string $to destination path
     * @param string $root Use this to override the default root path (sandbox/dropbox)
     * @return stdclass
     */
    public function copy($from, $to, $root = null) {

        if (is_null($root)) $root = $this->root;
        $response = $this->oauth->fetch($this->api_url . 'fileops/copy', array('from_path' => $from, 'to_path' => $to, 'root' => $root), 'POST');

        return json_decode($response['body'],true);

    }

    /**
     * Creates a new folder
     *
     * This method returns the information from the newly created directory
     *
     * @param string $path
     * @param string $root Use this to override the default root path (sandbox/dropbox)
     * @return stdclass
     */
    public function createFolder($path, $root = null) {

        if (is_null($root)) $root = $this->root;

        // Making sure the path starts with a /
        $path = '/' . ltrim($path,'/');

        $response = $this->oauth->fetch($this->api_url . 'fileops/create_folder', array('path' => $path, 'root' => $root),'POST');
        return json_decode($response['body'],true);

    }

    /**
     * Deletes a file or folder.
     *
     * This method will return the metadata information from the deleted file or folder, if successful.
     *
     * @param string $path Path to new folder
     * @param string $root Use this to override the default root path (sandbox/dropbox)
     * @return array
     */
    public function delete($path, $root = null) {

        if (is_null($root)) $root = $this->root;
        $response = $this->oauth->fetch($this->api_url . 'fileops/delete', array('path' => $path, 'root' => $root), 'POST');
        return json_decode($response['body']);

    }

    /**
     * Moves a file or directory to a new location
     *
     * This method returns the information from the newly created directory
     *
     * @param mixed $from Source path
     * @param mixed $to destination path
     * @param string $root Use this to override the default root path (sandbox/dropbox)
     * @return stdclass
     */
    public function move($from, $to, $root = null) {

        if (is_null($root)) $root = $this->root;
        $response = $this->oauth->fetch($this->api_url . 'fileops/move', array('from_path' => rawurldecode($from), 'to_path' => rawurldecode($to), 'root' => $root), 'POST');

        return json_decode($response['body'],true);

    }

    /**
     * Returns file and directory information
     *
     * @param string $path Path to receive information from
     * @param bool $list When set to true, this method returns information from all files in a directory. When set to false it will only return infromation from the specified directory.
     * @param string $hash If a hash is supplied, this method simply returns true if nothing has changed since the last request. Good for caching.
     * @param int $fileLimit Maximum number of file-information to receive
     * @param string $root Use this to override the default root path (sandbox/dropbox)
     * @return array|true
     */
    public function getMetaData($path, $list = true, $hash = null, $fileLimit = null, $root = null) {

        if (is_null($root)) $root = $this->root;

        $args = array(
            'list' => $list,
        );

        if (!is_null($hash)) $args['hash'] = $hash;
        if (!is_null($fileLimit)) $args['file_limit'] = $fileLimit;

        $path = str_replace(array('%2F','~'), array('/','%7E'), rawurlencode($path));
        $response = $this->oauth->fetch($this->api_url . 'metadata/' . $root . '/' . ltrim($path,'/'), $args);

        /* 304 is not modified */
        if ($response['httpStatus']==304) {
            return true;
        } else {
            return json_decode($response['body'],true);
        }

    }

    /**
    * A way of letting you keep up with changes to files and folders in a user's Dropbox. You can periodically call /delta to get a list of "delta entries", which are instructions on how to update your local state to match the server's state.
    *
    * This method returns the information from the newly created directory
    *
    * @param string $cursor A string that is used to keep track of your current state. On the next call pass in this value to return delta entries that have been recorded since the cursor was returned.
    * @return stdclass
    */
    public function delta($cursor) {

    	$arg['cursor'] = $cursor;

    	$response = $this->oauth->fetch($this->api_url . 'delta', $arg, 'POST');
    	return json_decode($response['body'],true);

    }

    /**
     * Returns a thumbnail (as a string) for a file path.
     *
     * @param string $path Path to file
     * @param string $size small, medium or large
     * @param string $root Use this to override the default root path (sandbox/dropbox)
     * @return string
     */
    public function getThumbnail($path, $size = 'small', $root = null) {

        if (is_null($root)) $root = $this->root;
        $path = str_replace(array('%2F','~'), array('/','%7E'), rawurlencode($path));
        $response = $this->oauth->fetch($this->api_content_url . 'thumbnails/' . $root . '/' . ltrim($path,'/'),array('size' => $size));

        return $response['body'];

    }

    /**
     * This method is used to generate multipart POST requests for file upload
     *
     * @param string $uri
     * @param array $arguments
     * @return bool
     */
    protected function multipartFetch($uri, $file, $filename) {

        /* random string */
        $boundary = 'R50hrfBj5JYyfR3vF3wR96GPCC9Fd2q2pVMERvEaOE3D8LZTgLLbRpNwXek3';

        $headers = array(
            'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
        );

        $body="--" . $boundary . "\r\n";
        $body.="Content-Disposition: form-data; name=file; filename=".rawurldecode($filename)."\r\n";
        $body.="Content-type: application/octet-stream\r\n";
        $body.="\r\n";
        $body.=stream_get_contents($file);
        $body.="\r\n";
        $body.="--" . $boundary . "--";

        // Dropbox requires the filename to also be part of the regular arguments, so it becomes
        // part of the signature.
        $uri.='?file=' . $filename;

        return $this->oauth->fetch($uri, $body, 'POST', $headers);

    }


	/**
     * Search
     *
     * Returns metadata for all files and folders that match the search query.
     *
	 * @added by: diszo.sasil
	 *
     * @param string $query
     * @param string $root Use this to override the default root path (sandbox/dropbox)
	 * @param string $path
     * @return array
     */
	public function search($query = '', $root = null, $path = ''){
		if (is_null($root)) $root = $this->root;
		if(!empty($path)){
			$path = str_replace(array('%2F','~'), array('/','%7E'), rawurlencode($path));
		}
        $response = $this->oauth->fetch($this->api_url . 'search/' . $root . '/' . ltrim($path,'/'),array('query' => $query));
        return json_decode($response['body'],true);
	}

    /**
     * Creates and returns a shareable link to files or folders.
     *
     * Note: Links created by the /shares API call expire after thirty days.
     *
     * @param string $path
     * @param string $root      Use this to override the default root path (sandbox/dropbox)
     * @param string $short_url When true (default), the URL returned will be shortened using the Dropbox URL shortener
     * @return array
     */
    public function share($path, $root = null, $short_url = true) {
        if (is_null($root)) $root = $this->root;
        $path = str_replace(array('%2F','~'), array('/','%7E'), rawurlencode($path));
        $short_url = ((is_string($short_url) && strtolower($short_url) === 'false') || !$short_url)? 'false' : 'true';
        $response = $this->oauth->fetch($this->api_url.  'shares/'. $root . '/' . ltrim($path, '/'), array('short_url' => $short_url), 'POST');
        return json_decode($response['body'],true);

    }

    /**
    * Returns a link directly to a file.
    * Similar to /shares. The difference is that this bypasses the Dropbox webserver, used to provide a preview of the file, so that you can effectively stream the contents of your media.
    *
    * Note: The /media link expires after four hours, allotting enough time to stream files, but not enough to leave a connection open indefinitely.
    *
    * @param type $path
    * @param type $root
    * @return type
    */
    public function media($path, $root = null) {

    	if (is_null($root)) $root = $this->root;
    	$path = str_replace(array('%2F','~'), array('/','%7E'), rawurlencode($path));
    	$response = $this->oauth->fetch($this->api_url.  'media/'. $root . '/' . ltrim($path, '/'), array(), 'POST');
    	return json_decode($response['body'],true);

    }

    /**
    * Creates and returns a copy_ref to a file. This reference string can be used to copy that file to another user's Dropbox by passing it in as the from_copy_ref parameter on /fileops/copy.
    *
    * @param type $path
    * @param type $root
    * @return type
    */
    public function copy_ref($path, $root = null) {

    	if (is_null($root)) $root = $this->root;
    	$path = str_replace(array('%2F','~'), array('/','%7E'), rawurlencode($path));
    	$response = $this->oauth->fetch($this->api_url.  'copy_ref/'. $root . '/' . ltrim($path, '/'));
    	return json_decode($response['body'],true);

    }


}
