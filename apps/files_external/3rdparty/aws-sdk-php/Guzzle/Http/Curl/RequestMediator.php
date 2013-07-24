<?php

namespace Guzzle\Http\Curl;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\EntityBody;
use Guzzle\Http\Message\Response;

/**
 * Mediator between curl handles and request objects
 */
class RequestMediator
{
    /** @var RequestInterface */
    protected $request;

    /** @var bool Whether or not to emit read/write events */
    protected $emitIo;

    /**
     * @param RequestInterface $request Request to mediate
     * @param bool             $emitIo  Set to true to dispatch events on input and output
     */
    public function __construct(RequestInterface $request, $emitIo = false)
    {
        $this->request = $request;
        $this->emitIo = $emitIo;
    }

    /**
     * Receive a response header from curl
     *
     * @param resource $curl   Curl handle
     * @param string   $header Received header
     *
     * @return int
     */
    public function receiveResponseHeader($curl, $header)
    {
        static $normalize = array("\r", "\n");
        $length = strlen($header);
        $header = str_replace($normalize, '', $header);

        if (strpos($header, 'HTTP/') === 0) {

            $startLine = explode(' ', $header, 3);
            $code = $startLine[1];
            $status = isset($startLine[2]) ? $startLine[2] : '';

            // Only download the body of the response to the specified response
            // body when a successful response is received.
            if ($code >= 200 && $code < 300) {
                $body = $this->request->getResponseBody();
            } else {
                $body = EntityBody::factory();
            }

            $response = new Response($code, null, $body);
            $response->setStatus($code, $status);
            $this->request->startResponse($response);

            $this->request->dispatch('request.receive.status_line', array(
                'request'       => $this,
                'line'          => $header,
                'status_code'   => $code,
                'reason_phrase' => $status
            ));

        } elseif ($pos = strpos($header, ':')) {
            $this->request->getResponse()->addHeader(
                trim(substr($header, 0, $pos)),
                trim(substr($header, $pos + 1))
            );
        }

        return $length;
    }

    /**
     * Received a progress notification
     *
     * @param int        $downloadSize Total download size
     * @param int        $downloaded   Amount of bytes downloaded
     * @param int        $uploadSize   Total upload size
     * @param int        $uploaded     Amount of bytes uploaded
     * @param resource   $handle       CurlHandle object
     */
    public function progress($downloadSize, $downloaded, $uploadSize, $uploaded, $handle = null)
    {
        $this->request->dispatch('curl.callback.progress', array(
            'request'       => $this->request,
            'handle'        => $handle,
            'download_size' => $downloadSize,
            'downloaded'    => $downloaded,
            'upload_size'   => $uploadSize,
            'uploaded'      => $uploaded
        ));
    }

    /**
     * Write data to the response body of a request
     *
     * @param resource $curl  Curl handle
     * @param string   $write Data that was received
     *
     * @return int
     */
    public function writeResponseBody($curl, $write)
    {
        if ($this->emitIo) {
            $this->request->dispatch('curl.callback.write', array(
                'request' => $this->request,
                'write'   => $write
            ));
        }

        return $this->request->getResponse()->getBody()->write($write);
    }

    /**
     * Read data from the request body and send it to curl
     *
     * @param resource $ch     Curl handle
     * @param resource $fd     File descriptor
     * @param int      $length Amount of data to read
     *
     * @return string
     */
    public function readRequestBody($ch, $fd, $length)
    {
        $read = '';

        if ($this->request->getBody()) {
            $read = $this->request->getBody()->read($length);
            if ($this->emitIo) {
                $this->request->dispatch('curl.callback.read', array(
                    'request' => $this->request,
                    'read'    => $read
                ));
            }
        }

        return !$read ? '' : $read;
    }
}
