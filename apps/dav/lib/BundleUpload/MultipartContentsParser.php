<?php
/**
 * @author Piotr Mrowczynski <Piotr.Mrowczynski@owncloud.com>
 * @author Louis Chemineau <louis@chmn.me>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\BundleUpload;

use Exception;
use Sabre\HTTP\RequestInterface;
use Sabre\DAV\Exception\BadRequest;

/**
 * This class is used to parse multipart/related HTTP message according to RFC http://www.rfc-archive.org/getrfc.php?rfc=2387
 * This class requires a message to contain Content-length parameters, which is used in high performance reading of file contents.
 */

class MultipartContentsParser {
    /**
     * @var \Sabre\HTTP\RequestInterface
     */
    // private $request;

    /** @var resource */
    private $stream = null;

	/** @var string */
	private $boundary = "";
	private $lastBoundary = "";

    /**
     * @var Bool
     */
    // private $endDelimiterReached = false;

    /**
     * Constructor.
     */
    public function __construct(RequestInterface $request) {
        $this->stream = $request->getBody();
        if (gettype($this->stream) !== 'resource') {
            throw new BadRequest('Wrong body type');
        }

		$this->boundary = '--'.$this->getBoundary($request->getHeader('Content-Type'))."\r\n";
		$this->lastBoundary = '--'.$this->getBoundary($request->getHeader('Content-Type'))."--\r\n";
    }

    /**
	 * Parse the boundary from a Content-Type header
	 *
     * @throws \Sabre\DAV\Exception\BadRequest
	 */
	private function getBoundary(string $contentType) {
		// Making sure the end node exists
		//TODO: add support for user creation if that is first sync. Currently user has to be created.
		// $this->userFilesHome = $this->request->getPath();
		// $userFilesHomeNode = $this->server->tree->getNodeForPath($this->userFilesHome);
		// if (!($userFilesHomeNode instanceof FilesHome)){
		// 	throw new Forbidden('URL endpoint has to be instance of \OCA\DAV\Files\FilesHome');
		// }

		// $headers = array('Content-Type');
		// foreach ($headers as $header) {
		// 	$value = $this->request->getHeader($header);
		// 	if ($value === null) {
		// 		throw new Forbidden(sprintf('%s header is needed', $header));
		// 	} elseif (!is_int($value) && empty($value)) {
		// 		throw new Forbidden(sprintf('%s header must not be empty', $header));
		// 	}
		// }

		// Validate content-type
		// Ex: Content-Type: "multipart/related; boundary=boundary_bf38b9b4b10a303a28ed075624db3978"
		[$mimeType, $boundary] = explode(';', $contentType);

		if (trim($mimeType) !== 'multipart/related') {
			throw new BadRequest('Content-Type must be multipart/related');
		}

		// Validate boundary
		[$key, $value] = explode('=', $boundary);
		if (trim($key) !== 'boundary') {
			throw new BadRequest('Boundary is invalid');
		}

		$value=trim($value);

		// Remove potential quotes around boundary value
		if (substr($value, 0, 1) == '"' && substr($value, -1) == '"') {
			$value = substr($value, 1, -1);
		}

		return $value;
	}

    /**
     * Get a line.
     *
     * If false is return, it's the end of file.
     *
     * @throws \Sabre\DAV\Exception\BadRequest
     */
    // public function gets() {
    //     $content = $this->getContent();
    //     if (!is_resource($content)) {
    //         throw new BadRequest('Unable to get request content');
    //     }

    //     return fgets($content);
    // }

    /**
     */
    // public function getCursor() {
    //     return ftell($this->getContent());
    // }

    /**
     */
    // public function getEndDelimiterReached() {
    //     return $this->endDelimiterReached;
    // }

    /**
     * Return if end of file.
     */
    public function eof() {
        return feof($this->stream);
    }

    /**
     * Seeks to offset of some file contentLength from the current cursor position in the
     * multipartContent.
     *
     * Return true on success and false on failure
     */
    // public function multipartContentSeekToContentLength(int $contentLength) {
    //     return (fseek($this->getContent(), $contentLength, SEEK_CUR) === 0 ? true : false);
    // }

    /**
     * Get request content.
     *
     * @throws \Sabre\DAV\Exception\BadRequest
     *
     * @return resource
     */
    // public function getContent() {
    //     if ($this->stream === null) {
    //         // Pass body by reference, so other objects can have global access
    //         $content = $this->request->getBody();

    //         if (!$this->stream) {
    //             throw new BadRequest('Unable to get request content');
    //         }

    //         if (gettype($this->stream) !== 'resource') {
    //             throw new BadRequest('Wrong body type');
    //         }

    //         $this->stream = $content;
    //     }

    //     return $this->stream;
    // }

    // public function getBoundary(string $boundary) {
    //     return "\r\n--$boundary\r\n";
    // }

    public function checkBoundary(string $boundary, string $line) {
        if ($line !== $boundary) {
            throw new Exception("Invalid boundary, is '$line', should be '$this->boundary'.");
        }

        return true;
    }

    public function lastBoundary() {
        $content = fread($this->stream, strlen($this->lastBoundary));
        $result = fseek($this->stream, -strlen($this->lastBoundary), SEEK_CUR);

        if ($result === -1) {
            throw new Exception("Unknown error while seeking content");
        }

        return $content === $this->lastBoundary;
    }

    /**
     * Return the next part of the request.
     *
     * @throws Exception
     */
    public function readNextPart(int $length = 0) {
        $this->checkBoundary($this->boundary, fread($this->stream, strlen($this->boundary)));

        $headers = $this->readPartHeaders();

        if ($length === 0 && isset($headers["content-length"])) {
            $length = $headers["content-length"];
        }

        if ($length === 0) {
                throw new Exception("Part cannot be of length 0.");
        }

        $content = $this->readPartContent2($length);

        return [$headers, $content];
    }

    /**
     * Return the next part of the request.
     *
     * @throws Exception
     */
    public function readNextStream() {
        $this->checkBoundary($this->boundary, fread($this->stream, strlen($this->boundary)));

        $headers = $this->readPartHeaders();

        return [$headers, $this->stream];
    }

    /**
     * Return the headers of a part of the request.
     *
     * @throws \Sabre\DAV\Exception\BadRequest
     * @throws Exception
     */
    public function readPartHeaders() {
        $headers = [];
        $blankLineCount = 0;

        while($blankLineCount < 1) {
            $line = fgets($this->stream);

            if ($line === false) {
                throw new Exception('An error appears while reading headers of a part');
            }

            if ($line === "\r\n") {
                break;
            }

            try {
                [$key, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($key))] = trim($value);
            } catch (Exception $e) {
                throw new BadRequest('An error appears while parsing headers of a part', $e);
            }
        }

        return $headers;
    }

    /**
     * Return the content of the current part of the stream.
     *
     * @throws \Sabre\DAV\Exception\BadRequest
     * @throws Exception
     */
    public function readPartContent() {
        $line = '';
        $content = '';

        do {
            $content .= $line;

            if (feof($this->stream)) {
                throw new BadRequest("Unexpected EOF while reading stream.");
            }

            $line = fgets($this->stream);

            if ($line === false) {
                throw new Exception("Fail to read part's content.");
            }
        } while ($line !== $this->boundary);

        // We need to be before $boundary for the next parsing.
        $result = fseek($this->stream, -strlen($this->boundary), SEEK_CUR);

        if ($result === -1) {
            throw new Exception("Fail to seek upstream.");
        }

        // Remove the extra new line "\r\n" that is not part of the content
        return substr($content, 0, -2);
    }

    public function readPartContent2(int $length) {
        // Read stream until file's $length, EOF or $boundary is reached
        $content = stream_get_line($this->stream, $length);

        if ($content === false) {
            throw new Exception("Fail to read part's content.");
        }

        if (feof($this->stream)) {
            throw new Exception("Unexpected EOF while reading stream.");
        }

        stream_get_contents($this->stream, 2);

        return $content;
    }

    public function getContentPosition() {
        return ftell($this->stream);
    }

    public function getMetadata() {
        fseek($this->stream, 0);
        return $this->readNextPart();
    }

    public function getContent(int $pos, int $length) {
        $previousPos = ftell($this->stream);

        $content = stream_get_contents($this->stream, $length, $pos);

        fseek($this->stream, $previousPos);

        return $content;
    }

    /**
     * Get a part of request separated by boundary $boundary.
     *
     * If this method returns an exception, it means whole request has to be abandoned,
     * Request part without correct headers might corrupt the message and parsing is impossible
     *
     * @throws \Exception
     */
    // public function getPartHeaders(string $boundary) {
    //     $delimiter = '--'.$boundary."\r\n";
    //     $endDelimiter = '--'.$boundary.'--';
    //     $boundaryCount = 0;
    //     $content = '';
    //     $headers = null;

    //     while (!$this->eof()) {
    //         $line = $this->gets();
    //         if ($line === false) {
    //             if ($boundaryCount == 0) {
    //                 // Empty part, ignore
    //                 break;
    //             }
    //             else{
    //                 throw new \Exception('An error appears while reading and parsing header of content part using fgets');
    //             }
    //         }

    //         if ($boundaryCount == 0) {
    //             if ($line != $delimiter) {
    //                 if ($this->getCursor() == strlen($line)) {
    //                     throw new \Exception('Expected boundary delimiter in content part - this is not a multipart request');
    //                 }
    //                 elseif ($line == $endDelimiter || $line == $endDelimiter."\r\n") {
    //                     $this->endDelimiterReached = true;
    //                     break;
    //                 }
    //                 elseif ($line == "\r\n") {
    //                     continue;
    //                 }
    //             } else {
    //                 continue;
    //             }
    //             // At this point we know, that first line was boundary
    //             $boundaryCount++;
    //         }
    //         elseif ($boundaryCount == 1 && $line == "\r\n"){
    //             //header-end according to RFC
    //             $content .= $line;
    //             $headers = $this->readHeaders($content);
    //             break;
    //         }
    //         elseif ($line == $endDelimiter || $line == $endDelimiter."\r\n") {
    //             $this->endDelimiterReached = true;
    //             break;
    //         }

    //         $content .= $line;
    //     }

    //     if ($this->eof()){
    //         $this->endDelimiterReached = true;
    //     }

    //     return $headers;
    // }

    /**
     * Read the contents from the current file pointer to the specified length
     *
     * @throws \Sabre\DAV\Exception\BadRequest
     */
    // public function streamReadToString(int $length) {
    //     if ($length<0) {
    //         throw new BadRequest('Method streamRead cannot read contents with negative length');
    //     }
    //     $source = $this->getContent();
    //     $bufChunkSize = 8192;
    //     $count = $length;
    //     $buf = '';

    //     while ($count!=0) {
    //         $bufSize = (($count - $bufChunkSize)<0) ? $count : $bufChunkSize;
    //         $buf .= fread($source, $bufSize);
    //         $count -= $bufSize;
    //     }

    //     $bytesWritten = strlen($buf);
    //     if ($length != $bytesWritten){
    //         throw new BadRequest('Method streamRead read '.$bytesWritten.' expected '.$length);
    //     }
    //     return $buf;
    // }

    /**
     * Read the contents from the current file pointer to the specified length and pass
     *
     * @param resource $target
     *
     * @throws \Sabre\DAV\Exception\BadRequest
     */
    // public function streamReadToStream($target, int $length) {
    //     if ($length<0) {
    //         throw new BadRequest('Method streamRead cannot read contents with negative length');
    //     }
    //     $source = $this->getContent();
    //     $bufChunkSize = 8192;
    //     $count = $length;
    //     $returnStatus = true;

    //     while ($count!=0) {
    //         $bufSize = (($count - $bufChunkSize)<0) ? $count : $bufChunkSize;
    //         $buf = fread($source, $bufSize);
    //         $bytesWritten = fwrite($target, $buf);

    //         // note: strlen is expensive so only use it when necessary,
    //         // on the last block
    //         if ($bytesWritten === false
    //             || ($bytesWritten < $bufSize)
    //         ) {
    //             // write error, could be disk full ?
    //             $returnStatus = false;
    //             break;
    //         }
    //         $count -= $bufSize;
    //     }

    //     return $returnStatus;
    // }


    /**
     * Get headers from content
     */
    // public function readHeaders($content) {
    //     $headers = null;
    //     $headerLimitation = strpos($content, "\r\n\r\n");
    //     if ($headerLimitation === false) {
    //         return null;
    //     }
    //     $headersContent = substr($content, 0, $headerLimitation);
    //     $headersContent = trim($headersContent);
    //     foreach (explode("\r\n", $headersContent) as $header) {
    //         $parts = explode(':', $header, 2);
    //         if (count($parts) != 2) {
    //             //has incorrect header, abort
    //             return null;
    //         }
    //         $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
    //     }

    //     return $headers;
    // }
}