<?php

declare(strict_types=1);

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JsonSchema\Uri\Retrievers;

use JsonSchema\Exception\ResourceNotFoundException;

/**
 * Tries to retrieve JSON schemas from a URI using file_get_contents()
 *
 * @author Sander Coolen <sander@jibber.nl>
 */
class FileGetContents extends AbstractRetriever
{
    protected $messageBody;

    /**
     * {@inheritdoc}
     *
     * @see \JsonSchema\Uri\Retrievers\UriRetrieverInterface::retrieve()
     */
    public function retrieve($uri)
    {
        $errorMessage = null;
        set_error_handler(function ($errno, $errstr) use (&$errorMessage) {
            $errorMessage = $errstr;
        });
        $response = file_get_contents($uri);
        restore_error_handler();

        if ($errorMessage) {
            throw new ResourceNotFoundException($errorMessage);
        }

        if (false === $response) {
            throw new ResourceNotFoundException('JSON schema not found at ' . $uri);
        }

        if ($response == ''
            && substr($uri, 0, 7) == 'file://' && substr($uri, -1) == '/'
        ) {
            throw new ResourceNotFoundException('JSON schema not found at ' . $uri);
        }

        $this->messageBody = $response;
        if (!empty($http_response_header)) {
            // $http_response_header cannot be tested, because it's defined in the method's local scope
            // See http://php.net/manual/en/reserved.variables.httpresponseheader.php for more info.
            $this->fetchContentType($http_response_header); // @codeCoverageIgnore
        } else {                                            // @codeCoverageIgnore
            // Could be a "file://" url or something else - fake up the response
            $this->contentType = null;
        }

        return $this->messageBody;
    }

    /**
     * @param array $headers HTTP Response Headers
     *
     * @return bool Whether the Content-Type header was found or not
     */
    private function fetchContentType(array $headers)
    {
        foreach (array_reverse($headers) as $header) {
            if ($this->contentType = self::getContentTypeMatchInHeader($header)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $header
     *
     * @return string|null
     */
    protected static function getContentTypeMatchInHeader($header)
    {
        if (0 < preg_match("/Content-Type:(\V*)/ims", $header, $match)) {
            return trim($match[1]);
        }

        return null;
    }
}
