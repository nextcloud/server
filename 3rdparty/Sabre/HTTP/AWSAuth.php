<?php

/**
 * HTTP AWS Authentication handler
 *
 * Use this class to leverage amazon's AWS authentication header
 *
 * @package Sabre
 * @subpackage HTTP
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_HTTP_AWSAuth extends Sabre_HTTP_AbstractAuth {

    /**
     * The signature supplied by the HTTP client
     *
     * @var string
     */
    private $signature = null;

    /**
     * The accesskey supplied by the HTTP client
     *
     * @var string
     */
    private $accessKey = null;

    /**
     * An error code, if any
     *
     * This value will be filled with one of the ERR_* constants
     *
     * @var int
     */
    public $errorCode = 0;

    const ERR_NOAWSHEADER = 1;
    const ERR_MD5CHECKSUMWRONG = 2;
    const ERR_INVALIDDATEFORMAT = 3;
    const ERR_REQUESTTIMESKEWED = 4;
    const ERR_INVALIDSIGNATURE = 5;

    /**
     * Gathers all information from the headers
     *
     * This method needs to be called prior to anything else.
     *
     * @return bool
     */
    public function init() {

        $authHeader = $this->httpRequest->getHeader('Authorization');
        $authHeader = explode(' ',$authHeader);

        if ($authHeader[0]!='AWS' || !isset($authHeader[1])) {
            $this->errorCode = self::ERR_NOAWSHEADER;
             return false;
        }

        list($this->accessKey,$this->signature) = explode(':',$authHeader[1]);

        return true;

    }

    /**
     * Returns the username for the request
     *
     * @return string
     */
    public function getAccessKey() {

        return $this->accessKey;

    }

    /**
     * Validates the signature based on the secretKey
     *
     * @param string $secretKey
     * @return bool
     */
    public function validate($secretKey) {

        $contentMD5 = $this->httpRequest->getHeader('Content-MD5');

        if ($contentMD5) {
            // We need to validate the integrity of the request
            $body = $this->httpRequest->getBody(true);
            $this->httpRequest->setBody($body,true);

            if ($contentMD5!=base64_encode(md5($body,true))) {
                // content-md5 header did not match md5 signature of body
                $this->errorCode = self::ERR_MD5CHECKSUMWRONG;
                return false;
            }

        }

        if (!$requestDate = $this->httpRequest->getHeader('x-amz-date'))
            $requestDate = $this->httpRequest->getHeader('Date');

        if (!$this->validateRFC2616Date($requestDate))
            return false;

        $amzHeaders = $this->getAmzHeaders();

        $signature = base64_encode(
            $this->hmacsha1($secretKey,
                $this->httpRequest->getMethod() . "\n" .
                $contentMD5 . "\n" .
                $this->httpRequest->getHeader('Content-type') . "\n" .
                $requestDate . "\n" .
                $amzHeaders .
                $this->httpRequest->getURI()
            )
        );

        if ($this->signature != $signature) {

            $this->errorCode = self::ERR_INVALIDSIGNATURE;
            return false;

        }

        return true;

    }


    /**
     * Returns an HTTP 401 header, forcing login
     *
     * This should be called when username and password are incorrect, or not supplied at all
     *
     * @return void
     */
    public function requireLogin() {

        $this->httpResponse->setHeader('WWW-Authenticate','AWS');
        $this->httpResponse->sendStatus(401);

    }

    /**
     * Makes sure the supplied value is a valid RFC2616 date.
     *
     * If we would just use strtotime to get a valid timestamp, we have no way of checking if a
     * user just supplied the word 'now' for the date header.
     *
     * This function also makes sure the Date header is within 15 minutes of the operating
     * system date, to prevent replay attacks.
     *
     * @param string $dateHeader
     * @return bool
     */
    protected function validateRFC2616Date($dateHeader) {

        $date = Sabre_HTTP_Util::parseHTTPDate($dateHeader);

        // Unknown format
        if (!$date) {
            $this->errorCode = self::ERR_INVALIDDATEFORMAT;
            return false;
        }

        $min = new DateTime('-15 minutes');
        $max = new DateTime('+15 minutes');

        // We allow 15 minutes around the current date/time
        if ($date > $max || $date < $min) {
            $this->errorCode = self::ERR_REQUESTTIMESKEWED;
            return false;
        }

        return $date;

    }

    /**
     * Returns a list of AMZ headers
     *
     * @return string
     */
    protected function getAmzHeaders() {

        $amzHeaders = array();
        $headers = $this->httpRequest->getHeaders();
        foreach($headers as $headerName => $headerValue) {
            if (strpos(strtolower($headerName),'x-amz-')===0) {
                $amzHeaders[strtolower($headerName)] = str_replace(array("\r\n"),array(' '),$headerValue) . "\n";
            }
        }
        ksort($amzHeaders);

        $headerStr = '';
        foreach($amzHeaders as $h=>$v) {
            $headerStr.=$h.':'.$v;
        }

        return $headerStr;

    }

    /**
     * Generates an HMAC-SHA1 signature
     *
     * @param string $key
     * @param string $message
     * @return string
     */
    private function hmacsha1($key, $message) {

        $blocksize=64;
        if (strlen($key)>$blocksize)
            $key=pack('H*', sha1($key));
        $key=str_pad($key,$blocksize,chr(0x00));
        $ipad=str_repeat(chr(0x36),$blocksize);
        $opad=str_repeat(chr(0x5c),$blocksize);
        $hmac = pack('H*',sha1(($key^$opad).pack('H*',sha1(($key^$ipad).$message))));
        return $hmac;

    }

}
