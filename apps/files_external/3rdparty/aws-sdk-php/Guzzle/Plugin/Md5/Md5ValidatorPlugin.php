<?php

namespace Guzzle\Plugin\Md5;

use Guzzle\Common\Event;
use Guzzle\Common\Exception\UnexpectedValueException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Ensures that an the MD5 hash of an entity body matches the Content-MD5
 * header (if set) of an HTTP response.  An exception is thrown if the
 * calculated MD5 does not match the expected MD5.
 */
class Md5ValidatorPlugin implements EventSubscriberInterface
{
    /** @var int Maximum Content-Length in bytes to validate */
    protected $contentLengthCutoff;

    /** @var bool Whether or not to compare when a Content-Encoding is present */
    protected $contentEncoded;

    /**
     * @param bool     $contentEncoded      Calculating the MD5 hash of an entity body where a Content-Encoding was
     *                                      applied is a more expensive comparison because the entity body will need to
     *                                      be compressed in order to get the correct hash.  Set to FALSE to not
     *                                      validate the MD5 hash of an entity body with an applied Content-Encoding.
     * @param bool|int $contentLengthCutoff Maximum Content-Length (bytes) in which a MD5 hash will be validated. Any
     *                                      response with a Content-Length greater than this value will not be validated
     *                                      because it will be deemed too memory intensive.
     */
    public function __construct($contentEncoded = true, $contentLengthCutoff = false)
    {
        $this->contentLengthCutoff = $contentLengthCutoff;
        $this->contentEncoded = $contentEncoded;
    }

    public static function getSubscribedEvents()
    {
        return array('request.complete' => array('onRequestComplete', 255));
    }

    /**
     * {@inheritdoc}
     * @throws UnexpectedValueException
     */
    public function onRequestComplete(Event $event)
    {
        $response = $event['response'];

        if (!$contentMd5 = $response->getContentMd5()) {
            return;
        }

        $contentEncoding = $response->getContentEncoding();
        if ($contentEncoding && !$this->contentEncoded) {
            return false;
        }

        // Make sure that the size of the request is under the cutoff size
        if ($this->contentLengthCutoff) {
            $size = $response->getContentLength() ?: $response->getBody()->getSize();
            if (!$size || $size > $this->contentLengthCutoff) {
                return;
            }
        }

        if (!$contentEncoding) {
            $hash = $response->getBody()->getContentMd5();
        } elseif ($contentEncoding == 'gzip') {
            $response->getBody()->compress('zlib.deflate');
            $hash = $response->getBody()->getContentMd5();
            $response->getBody()->uncompress();
        } elseif ($contentEncoding == 'compress') {
            $response->getBody()->compress('bzip2.compress');
            $hash = $response->getBody()->getContentMd5();
            $response->getBody()->uncompress();
        } else {
            return;
        }

        if ($contentMd5 !== $hash) {
            throw new UnexpectedValueException(
                "The response entity body may have been modified over the wire.  The Content-MD5 "
                . "received ({$contentMd5}) did not match the calculated MD5 hash ({$hash})."
            );
        }
    }
}
