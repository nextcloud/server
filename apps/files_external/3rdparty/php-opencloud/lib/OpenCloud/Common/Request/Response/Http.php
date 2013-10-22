<?php

namespace OpenCloud\Common\Request\Response;

use OpenCloud\Common\Base;

/**
 * The HttpResponse returns an object with status information, separated
 * headers, and any response body necessary.
 *
 * @api
 * @author Glen Campbell <glen.campbell@rackspace.com>
 */
 
class Http extends Base 
{

    private $errno;
    private $error;
    private $info = array();
    protected $body;
    protected $headers = array();

    /**
     * The constructor parses everything necessary
     */
    public function __construct($request, $data) 
    {
        // save the raw data (who knows? we might need it)
        $this->setBody($data);

        // and split each line into name: value pairs
        foreach($request->returnHeaders() as $line) {
            if (preg_match('/^([^:]+):\s+(.+?)\s*$/', $line, $matches)) {
                $this->headers[$matches[1]] = $matches[2];
            } else {
                $this->headers[$line] = trim($line);
            }
        }

        // @codeCoverageIgnoreStart
        if (isset($this->headers['Cache-Control'])) {
            $this->getLogger()->info('Cache-Control: {header}', array(
                'headers' => $this->headers['Cache-Control']
            ));
        }
        if (isset($this->headers['Expires'])) {
            $this->getLogger()->info('Expires: {header}', array(
                'headers' => $this->headers['Expires']
            ));
        }
        // @codeCoverageIgnoreEnd

        // set some other data
        $this->info = $request->info();
        $this->errno = $request->errno();
        $this->error = $request->error();
    }

    /**
     * Returns the full body of the request
     *
     * @return string
     */
    public function httpBody() 
    {
        return $this->body;
    }
    
    /**
     * Sets the body.
     * 
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * Returns an array of headers
     *
     * @return associative array('header'=>value)
     */
    public function headers() 
    {
        return $this->headers;
    }

    /**
     * Returns a single header
     *
     * @return string with the value of the requested header, or NULL
     */
    public function header($name) 
    {
        return isset($this->headers[$name]) ? $this->headers[$name] : null;
    }

    /**
     * Returns an array of information
     *
     * @return array
     */
    public function info() 
    {
        return $this->info;
    }

    /**
     * Returns the most recent error number
     *
     * @return integer
     */
    public function errno()
    {
        return $this->errno;
    }

    /**
     * Returns the most recent error message
     *
     * @return string
     */
    public function error() 
    {
        return $this->error;
    }

    /**
     * Returns the HTTP status code
     *
     * @return integer
     */
    public function httpStatus() 
    {
        return $this->info['http_code'];
    }

}
