<?php

declare(strict_types=1);

namespace Sabre\HTTP\Auth;

use Sabre\HTTP;

/**
 * HTTP AWS Authentication handler.
 *
 * Use this class to leverage amazon's AWS authentication header
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class AWS extends AbstractAuth
{
    /**
     * The signature supplied by the HTTP client.
     *
     * @var string
     */
    private $signature;

    /**
     * The accesskey supplied by the HTTP client.
     *
     * @var string
     */
    private $accessKey;

    /**
     * An error code, if any.
     *
     * This value will be filled with one of the ERR_* constants
     *
     * @var int
     */
    public $errorCode = 0;

    public const ERR_NOAWSHEADER = 1;
    public const ERR_MD5CHECKSUMWRONG = 2;
    public const ERR_INVALIDDATEFORMAT = 3;
    public const ERR_REQUESTTIMESKEWED = 4;
    public const ERR_INVALIDSIGNATURE = 5;

    /**
     * Gathers all information from the headers.
     *
     * This method needs to be called prior to anything else.
     */
    public function init(): bool
    {
        $authHeader = $this->request->getHeader('Authorization');

        if (null === $authHeader) {
            $this->errorCode = self::ERR_NOAWSHEADER;

            return false;
        }
        $authHeader = explode(' ', $authHeader);

        if ('AWS' !== $authHeader[0] || !isset($authHeader[1])) {
            $this->errorCode = self::ERR_NOAWSHEADER;

            return false;
        }

        list($this->accessKey, $this->signature) = explode(':', $authHeader[1]);

        return true;
    }

    /**
     * Returns the username for the request.
     */
    public function getAccessKey(): string
    {
        return $this->accessKey;
    }

    /**
     * Validates the signature based on the secretKey.
     */
    public function validate(string $secretKey): bool
    {
        $contentMD5 = $this->request->getHeader('Content-MD5');

        if ($contentMD5) {
            // We need to validate the integrity of the request
            $body = $this->request->getBody();
            $this->request->setBody($body);

            if ($contentMD5 !== base64_encode(md5((string) $body, true))) {
                // content-md5 header did not match md5 signature of body
                $this->errorCode = self::ERR_MD5CHECKSUMWRONG;

                return false;
            }
        }

        if (!$requestDate = $this->request->getHeader('x-amz-date')) {
            $requestDate = $this->request->getHeader('Date');
        }

        if (!$this->validateRFC2616Date((string) $requestDate)) {
            return false;
        }

        $amzHeaders = $this->getAmzHeaders();

        $signature = base64_encode(
            $this->hmacsha1($secretKey,
                $this->request->getMethod()."\n".
                $contentMD5."\n".
                $this->request->getHeader('Content-type')."\n".
                $requestDate."\n".
                $amzHeaders.
                $this->request->getUrl()
            )
        );

        if ($this->signature !== $signature) {
            $this->errorCode = self::ERR_INVALIDSIGNATURE;

            return false;
        }

        return true;
    }

    /**
     * Returns an HTTP 401 header, forcing login.
     *
     * This should be called when username and password are incorrect, or not supplied at all
     */
    public function requireLogin()
    {
        $this->response->addHeader('WWW-Authenticate', 'AWS');
        $this->response->setStatus(401);
    }

    /**
     * Makes sure the supplied value is a valid RFC2616 date.
     *
     * If we would just use strtotime to get a valid timestamp, we have no way of checking if a
     * user just supplied the word 'now' for the date header.
     *
     * This function also makes sure the Date header is within 15 minutes of the operating
     * system date, to prevent replay attacks.
     */
    protected function validateRFC2616Date(string $dateHeader): bool
    {
        $date = HTTP\parseDate($dateHeader);

        // Unknown format
        if (!$date) {
            $this->errorCode = self::ERR_INVALIDDATEFORMAT;

            return false;
        }

        $min = new \DateTime('-15 minutes');
        $max = new \DateTime('+15 minutes');

        // We allow 15 minutes around the current date/time
        if ($date > $max || $date < $min) {
            $this->errorCode = self::ERR_REQUESTTIMESKEWED;

            return false;
        }

        return true;
    }

    /**
     * Returns a list of AMZ headers.
     */
    protected function getAmzHeaders(): string
    {
        $amzHeaders = [];
        $headers = $this->request->getHeaders();
        foreach ($headers as $headerName => $headerValue) {
            if (0 === strpos(strtolower($headerName), 'x-amz-')) {
                $amzHeaders[strtolower($headerName)] = str_replace(["\r\n"], [' '], $headerValue[0])."\n";
            }
        }
        ksort($amzHeaders);

        $headerStr = '';
        foreach ($amzHeaders as $h => $v) {
            $headerStr .= $h.':'.$v;
        }

        return $headerStr;
    }

    /**
     * Generates an HMAC-SHA1 signature.
     */
    private function hmacsha1(string $key, string $message): string
    {
        if (function_exists('hash_hmac')) {
            return hash_hmac('sha1', $message, $key, true);
        }

        $blocksize = 64;
        if (strlen($key) > $blocksize) {
            $key = pack('H*', sha1($key));
        }
        $key = str_pad($key, $blocksize, chr(0x00));
        $ipad = str_repeat(chr(0x36), $blocksize);
        $opad = str_repeat(chr(0x5C), $blocksize);
        $hmac = pack('H*', sha1(($key ^ $opad).pack('H*', sha1(($key ^ $ipad).$message))));

        return $hmac;
    }
}
