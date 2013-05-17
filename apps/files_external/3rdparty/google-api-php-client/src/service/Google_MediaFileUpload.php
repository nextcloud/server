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

/**
 * @author Chirag Shah <chirags@google.com>
 *
 */
class Google_MediaFileUpload {
  const UPLOAD_MEDIA_TYPE = 'media';
  const UPLOAD_MULTIPART_TYPE = 'multipart';
  const UPLOAD_RESUMABLE_TYPE = 'resumable';

  /** @var string $mimeType */
  public $mimeType;

  /** @var string $data */
  public $data;

  /** @var bool $resumable */
  public $resumable;

  /** @var int $chunkSize */
  public $chunkSize;

  /** @var int $size */
  public $size;

  /** @var string $resumeUri */
  public $resumeUri;

  /** @var int $progress */
  public $progress;

  /**
   * @param $mimeType string
   * @param $data string The bytes you want to upload.
   * @param $resumable bool
   * @param bool $chunkSize File will be uploaded in chunks of this many bytes.
   * only used if resumable=True
   */
  public function __construct($mimeType, $data, $resumable=false, $chunkSize=false) {
    $this->mimeType = $mimeType;
    $this->data = $data;
    $this->size = strlen($this->data);
    $this->resumable = $resumable;
    if(!$chunkSize) {
      $chunkSize = 256 * 1024;
    }
    $this->chunkSize = $chunkSize;
    $this->progress = 0;
  }

  public function setFileSize($size) {
    $this->size = $size;
  }

  /**
   * @static
   * @param $meta
   * @param $params
   * @return array|bool
   */
  public static function process($meta, &$params) {
    $payload = array();
    $meta = is_string($meta) ? json_decode($meta, true) : $meta;
    $uploadType = self::getUploadType($meta, $payload, $params);
    if (!$uploadType) {
      // Process as a normal API request.
      return false;
    }

    // Process as a media upload request.
    $params['uploadType'] = array(
        'type' => 'string',
        'location' => 'query',
        'value' => $uploadType,
    );

    $mimeType = isset($params['mimeType'])
        ? $params['mimeType']['value']
        : false;
    unset($params['mimeType']);

    if (!$mimeType) {
      $mimeType = $payload['content-type'];
    }

    if (isset($params['file'])) {
      // This is a standard file upload with curl.
      $file = $params['file']['value'];
      unset($params['file']);
      return self::processFileUpload($file, $mimeType);
    }

    $data = isset($params['data'])
        ? $params['data']['value']
        : false;
    unset($params['data']);

    if (self::UPLOAD_RESUMABLE_TYPE == $uploadType) {
      $payload['content-type'] = $mimeType;
      $payload['postBody'] = is_string($meta) ? $meta : json_encode($meta);

    } elseif (self::UPLOAD_MEDIA_TYPE == $uploadType) {
      // This is a simple media upload.
      $payload['content-type'] = $mimeType;
      $payload['postBody'] = $data;
    }

    elseif (self::UPLOAD_MULTIPART_TYPE == $uploadType) {
      // This is a multipart/related upload.
      $boundary = isset($params['boundary']['value']) ? $params['boundary']['value'] : mt_rand();
      $boundary = str_replace('"', '', $boundary);
      $payload['content-type'] = 'multipart/related; boundary=' . $boundary;
      $related = "--$boundary\r\n";
      $related .= "Content-Type: application/json; charset=UTF-8\r\n";
      $related .= "\r\n" . json_encode($meta) . "\r\n";
      $related .= "--$boundary\r\n";
      $related .= "Content-Type: $mimeType\r\n";
      $related .= "Content-Transfer-Encoding: base64\r\n";
      $related .= "\r\n" . base64_encode($data) . "\r\n";
      $related .= "--$boundary--";
      $payload['postBody'] = $related;
    }

    return $payload;
  }

  /**
   * Prepares a standard file upload via cURL.
   * @param $file
   * @param $mime
   * @return array Includes the processed file name.
   * @visible For testing.
   */
  public static function processFileUpload($file, $mime) {
    if (!$file) return array();
    if (substr($file, 0, 1) != '@') {
      $file = '@' . $file;
    }

    // This is a standard file upload with curl.
    $params = array('postBody' => array('file' => $file));
    if ($mime) {
      $params['content-type'] = $mime;
    }

    return $params;
  }

  /**
   * Valid upload types:
   * - resumable (UPLOAD_RESUMABLE_TYPE)
   * - media (UPLOAD_MEDIA_TYPE)
   * - multipart (UPLOAD_MULTIPART_TYPE)
   * - none (false)
   * @param $meta
   * @param $payload
   * @param $params
   * @return bool|string
   */
  public static function getUploadType($meta, &$payload, &$params) {
    if (isset($params['mediaUpload'])
        && get_class($params['mediaUpload']['value']) == 'Google_MediaFileUpload') {
      $upload = $params['mediaUpload']['value'];
      unset($params['mediaUpload']);
      $payload['content-type'] = $upload->mimeType;
      if (isset($upload->resumable) && $upload->resumable) {
        return self::UPLOAD_RESUMABLE_TYPE;
      }
    }

    // Allow the developer to override the upload type.
    if (isset($params['uploadType'])) {
      return $params['uploadType']['value'];
    }

    $data = isset($params['data']['value'])
        ? $params['data']['value'] : false;

    if (false == $data && false == isset($params['file'])) {
      // No upload data available.
      return false;
    }

    if (isset($params['file'])) {
      return self::UPLOAD_MEDIA_TYPE;
    }

    if (false == $meta) {
      return self::UPLOAD_MEDIA_TYPE;
    }

    return self::UPLOAD_MULTIPART_TYPE;
  }


  public function nextChunk(Google_HttpRequest $req, $chunk=false) {
    if (false == $this->resumeUri) {
      $this->resumeUri = $this->getResumeUri($req);
    }

    if (false == $chunk) {
      $chunk = substr($this->data, $this->progress, $this->chunkSize);
    }

    $lastBytePos = $this->progress + strlen($chunk) - 1;
    $headers = array(
      'content-range' => "bytes $this->progress-$lastBytePos/$this->size",
      'content-type' => $req->getRequestHeader('content-type'),
      'content-length' => $this->chunkSize,
      'expect' => '',
    );

    $httpRequest = new Google_HttpRequest($this->resumeUri, 'PUT', $headers, $chunk);
    $response = Google_Client::$io->authenticatedRequest($httpRequest);
    $code = $response->getResponseHttpCode();
    if (308 == $code) {
      $range = explode('-', $response->getResponseHeader('range'));
      $this->progress = $range[1] + 1;
      return false;
    } else {
      return Google_REST::decodeHttpResponse($response);
    }
  }

  private function getResumeUri(Google_HttpRequest $httpRequest) {
    $result = null;
    $body = $httpRequest->getPostBody();
    if ($body) {
      $httpRequest->setRequestHeaders(array(
        'content-type' => 'application/json; charset=UTF-8',
        'content-length' => Google_Utils::getStrLen($body),
        'x-upload-content-type' => $this->mimeType,
        'x-upload-content-length' => $this->size,
        'expect' => '',
      ));
    }

    $response = Google_Client::$io->makeRequest($httpRequest);
    $location = $response->getResponseHeader('location');
    $code = $response->getResponseHttpCode();
    if (200 == $code && true == $location) {
      return $location;
    }
    throw new Google_Exception("Failed to start the resumable upload");
  }
}