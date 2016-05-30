<?php
/**
 * Copyright 2012 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

if (!class_exists('Google_Client')) {
  require_once dirname(__FILE__) . '/../autoload.php';
}

/**
 * Manage large file uploads, which may be media but can be any type
 * of sizable data.
 */
class Google_Http_MediaFileUpload
{
  const UPLOAD_MEDIA_TYPE = 'media';
  const UPLOAD_MULTIPART_TYPE = 'multipart';
  const UPLOAD_RESUMABLE_TYPE = 'resumable';

  /** @var string $mimeType */
  private $mimeType;

  /** @var string $data */
  private $data;

  /** @var bool $resumable */
  private $resumable;

  /** @var int $chunkSize */
  private $chunkSize;

  /** @var int $size */
  private $size;

  /** @var string $resumeUri */
  private $resumeUri;

  /** @var int $progress */
  private $progress;

  /** @var Google_Client */
  private $client;

  /** @var Google_Http_Request */
  private $request;

  /** @var string */
  private $boundary;

  /**
   * Result code from last HTTP call
   * @var int
   */
  private $httpResultCode;

  /**
   * @param $mimeType string
   * @param $data string The bytes you want to upload.
   * @param $resumable bool
   * @param bool $chunkSize File will be uploaded in chunks of this many bytes.
   * only used if resumable=True
   */
  public function __construct(
      Google_Client $client,
      Google_Http_Request $request,
      $mimeType,
      $data,
      $resumable = false,
      $chunkSize = false,
      $boundary = false
  ) {
    $this->client = $client;
    $this->request = $request;
    $this->mimeType = $mimeType;
    $this->data = $data;
    $this->size = strlen($this->data);
    $this->resumable = $resumable;
    if (!$chunkSize) {
      $chunkSize = 256 * 1024;
    }
    $this->chunkSize = $chunkSize;
    $this->progress = 0;
    $this->boundary = $boundary;

    // Process Media Request
    $this->process();
  }

  /**
   * Set the size of the file that is being uploaded.
   * @param $size - int file size in bytes
   */
  public function setFileSize($size)
  {
    $this->size = $size;
  }

  /**
   * Return the progress on the upload
   * @return int progress in bytes uploaded.
   */
  public function getProgress()
  {
    return $this->progress;
  }

  /**
   * Return the HTTP result code from the last call made.
   * @return int code
   */
  public function getHttpResultCode()
  {
    return $this->httpResultCode;
  }

  /**
  * Sends a PUT-Request to google drive and parses the response,
  * setting the appropiate variables from the response()
  *
  * @param Google_Http_Request $httpRequest the Reuqest which will be send
  *
  * @return false|mixed false when the upload is unfinished or the decoded http response
  *
  */
  private function makePutRequest(Google_Http_Request $httpRequest)
  {
    if ($this->client->getClassConfig("Google_Http_Request", "enable_gzip_for_uploads")) {
      $httpRequest->enableGzip();
    } else {
      $httpRequest->disableGzip();
    }

    $response = $this->client->getIo()->makeRequest($httpRequest);
    $response->setExpectedClass($this->request->getExpectedClass());
    $code = $response->getResponseHttpCode();
    $this->httpResultCode = $code;

    if (308 == $code) {
      // Track the amount uploaded.
      $range = explode('-', $response->getResponseHeader('range'));
      $this->progress = $range[1] + 1;

      // Allow for changing upload URLs.
      $location = $response->getResponseHeader('location');
      if ($location) {
        $this->resumeUri = $location;
      }

      // No problems, but upload not complete.
      return false;
    } else {
      return Google_Http_REST::decodeHttpResponse($response, $this->client);
    }
  }

  /**
   * Send the next part of the file to upload.
   * @param [$chunk] the next set of bytes to send. If false will used $data passed
   * at construct time.
   */
  public function nextChunk($chunk = false)
  {
    if (false == $this->resumeUri) {
      $this->resumeUri = $this->fetchResumeUri();
    }

    if (false == $chunk) {
      $chunk = substr($this->data, $this->progress, $this->chunkSize);
    }
    $lastBytePos = $this->progress + strlen($chunk) - 1;
    $headers = array(
      'content-range' => "bytes $this->progress-$lastBytePos/$this->size",
      'content-type' => $this->request->getRequestHeader('content-type'),
      'content-length' => $this->chunkSize,
      'expect' => '',
    );

    $httpRequest = new Google_Http_Request(
        $this->resumeUri,
        'PUT',
        $headers,
        $chunk
    );
    return $this->makePutRequest($httpRequest);
  }

  /**
   * Resume a previously unfinished upload
   * @param $resumeUri the resume-URI of the unfinished, resumable upload.
   */
  public function resume($resumeUri)
  {
     $this->resumeUri = $resumeUri;
     $headers = array(
       'content-range' => "bytes */$this->size",
       'content-length' => 0,
     );
     $httpRequest = new Google_Http_Request(
         $this->resumeUri,
         'PUT',
         $headers
     );
     return $this->makePutRequest($httpRequest);
  }

  /**
   * @return array|bool
   * @visible for testing
   */
  private function process()
  {
    $postBody = false;
    $contentType = false;

    $meta = $this->request->getPostBody();
    $meta = is_string($meta) ? json_decode($meta, true) : $meta;

    $uploadType = $this->getUploadType($meta);
    $this->request->setQueryParam('uploadType', $uploadType);
    $this->transformToUploadUrl();
    $mimeType = $this->mimeType ?
        $this->mimeType :
        $this->request->getRequestHeader('content-type');

    if (self::UPLOAD_RESUMABLE_TYPE == $uploadType) {
      $contentType = $mimeType;
      $postBody = is_string($meta) ? $meta : json_encode($meta);
    } else if (self::UPLOAD_MEDIA_TYPE == $uploadType) {
      $contentType = $mimeType;
      $postBody = $this->data;
    } else if (self::UPLOAD_MULTIPART_TYPE == $uploadType) {
      // This is a multipart/related upload.
      $boundary = $this->boundary ? $this->boundary : mt_rand();
      $boundary = str_replace('"', '', $boundary);
      $contentType = 'multipart/related; boundary=' . $boundary;
      $related = "--$boundary\r\n";
      $related .= "Content-Type: application/json; charset=UTF-8\r\n";
      $related .= "\r\n" . json_encode($meta) . "\r\n";
      $related .= "--$boundary\r\n";
      $related .= "Content-Type: $mimeType\r\n";
      $related .= "Content-Transfer-Encoding: base64\r\n";
      $related .= "\r\n" . base64_encode($this->data) . "\r\n";
      $related .= "--$boundary--";
      $postBody = $related;
    }

    $this->request->setPostBody($postBody);

    if (isset($contentType) && $contentType) {
      $contentTypeHeader['content-type'] = $contentType;
      $this->request->setRequestHeaders($contentTypeHeader);
    }
  }

  private function transformToUploadUrl()
  {
    $base = $this->request->getBaseComponent();
    $this->request->setBaseComponent($base . '/upload');
  }

  /**
   * Valid upload types:
   * - resumable (UPLOAD_RESUMABLE_TYPE)
   * - media (UPLOAD_MEDIA_TYPE)
   * - multipart (UPLOAD_MULTIPART_TYPE)
   * @param $meta
   * @return string
   * @visible for testing
   */
  public function getUploadType($meta)
  {
    if ($this->resumable) {
      return self::UPLOAD_RESUMABLE_TYPE;
    }

    if (false == $meta && $this->data) {
      return self::UPLOAD_MEDIA_TYPE;
    }

    return self::UPLOAD_MULTIPART_TYPE;
  }

  public function getResumeUri()
  {
    return ( $this->resumeUri !== null ? $this->resumeUri : $this->fetchResumeUri() );
  }

  private function fetchResumeUri()
  {
    $result = null;
    $body = $this->request->getPostBody();
    if ($body) {
      $headers = array(
        'content-type' => 'application/json; charset=UTF-8',
        'content-length' => Google_Utils::getStrLen($body),
        'x-upload-content-type' => $this->mimeType,
        'x-upload-content-length' => $this->size,
        'expect' => '',
      );
      $this->request->setRequestHeaders($headers);
    }

    $response = $this->client->getIo()->makeRequest($this->request);
    $location = $response->getResponseHeader('location');
    $code = $response->getResponseHttpCode();

    if (200 == $code && true == $location) {
      return $location;
    }
    $message = $code;
    $body = @json_decode($response->getResponseBody());
    if (!empty($body->error->errors) ) {
      $message .= ': ';
      foreach ($body->error->errors as $error) {
        $message .= "{$error->domain}, {$error->message};";
      }
      $message = rtrim($message, ';');
    }

    $error = "Failed to start the resumable upload (HTTP {$message})";
    $this->client->getLogger()->error($error);
    throw new Google_Exception($error);
  }

  public function setChunkSize($chunkSize)
  {
    $this->chunkSize = $chunkSize;
  }
}
