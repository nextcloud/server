<?php

/**
* ownCloud
*
* @author Michael Gapczynski
* @copyright 2012 Michael Gapczynski mtgap@owncloud.com
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once 'common.inc.php';

class OC_Filestorage_Google extends OC_Filestorage_Common {

	private $consumer;
	private $oauth_token;
	private $sig_method;
	private $entries;

	private static $tempFiles = array();

	public function __construct($arguments) {
		$consumer_key = isset($arguments['consumer_key']) ? $arguments['consumer_key'] : 'anonymous';
		$consumer_secret = isset($arguments['consumer_secret']) ? $arguments['consumer_secret'] : 'anonymous';
		$this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
		$this->oauth_token = new OAuthToken($arguments['token'], $arguments['token_secret']);
		$this->sig_method = new OAuthSignatureMethod_HMAC_SHA1();
		$this->entries = array();
	}

	private function sendRequest($uri, $httpMethod, $postData = null, $extraHeaders = null, $isDownload = false, $returnHeaders = false, $isContentXML = true) {
		$uri = trim($uri);
		// create an associative array from each key/value url query param pair.
		$params = array();
		$pieces = explode('?', $uri);
		if (isset($pieces[1])) {
			$params = explode_assoc('=', '&', $pieces[1]);
		}
		// urlencode each url parameter key/value pair
		$tempStr = $pieces[0];
		foreach ($params as $key => $value) {
			$tempStr .= '&' . urlencode($key) . '=' . urlencode($value);
		}
		$uri = preg_replace('/&/', '?', $tempStr, 1);
		$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->oauth_token, $httpMethod, $uri, $params);
		$request->sign_request($this->sig_method, $this->consumer, $this->oauth_token);
		$auth_header = $request->to_header();
		$headers = array($auth_header, 'GData-Version: 3.0');
		if ($isContentXML) {
			$headers = array_merge($headers, array('Content-Type: application/atom+xml'));
		}
		if (is_array($extraHeaders)) {
			$headers = array_merge($headers, $extraHeaders);
		}
		$curl = curl_init($uri);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FAILONERROR, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		switch ($httpMethod) {
			case 'GET':
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
				break;
			case 'POST':
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
				break;
			case 'PUT':
				$headers[] = 'If-Match: *';
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $httpMethod);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
				break;
			case 'DELETE':
				$headers[] = 'If-Match: *';
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $httpMethod);
				break;
			default:
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		}
		if ($isDownload) {
			$tmpFile = OC_Helper::tmpFile();
			$handle = fopen($tmpFile, 'w');
			curl_setopt($curl, CURLOPT_FILE, $handle);
		}
		if ($returnHeaders) {
			curl_setopt($curl, CURLOPT_HEADER, true);
		}
		$result = curl_exec($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		if ($result) {
			// TODO https://developers.google.com/google-apps/documents-list/#handling_api_errors
			// TODO Log error messages
			if ($httpCode <= 308) {
				if ($isDownload) {
					return $tmpFile;
				} else {
					return $result;
				}
			}
		}
		return false; 
	}

	private function getFeed($feedUri, $httpMethod, $postData = null) {
		$result = $this->sendRequest($feedUri, $httpMethod, $postData);
		if ($result) {
			$dom = new DOMDocument();
			$dom->loadXML($result);
			return $dom;
		}
		return false;
	}

	private function getResource($path) {
		$file = basename($path);
		if (array_key_exists($file, $this->entries)) {
			return $this->entries[$file];
		} else {
			// Strip the file extension; file could be a native Google Docs resource
			if ($pos = strpos($file, '.')) {
				$title = substr($file, 0, $pos);
				$dom = $this->getFeed('https://docs.google.com/feeds/default/private/full?showfolders=true&title='.$title, 'GET');
				// Check if request was successful and entry exists
				if ($dom && $entry = $dom->getElementsByTagName('entry')->item(0)) {
					$this->entries[$file] = $entry;
					return $entry;
				}
			}
			$dom = $this->getFeed('https://docs.google.com/feeds/default/private/full?showfolders=true&title='.$file, 'GET');
			// Check if request was successful and entry exists
			if ($dom && $entry = $dom->getElementsByTagName('entry')->item(0)) {
				$this->entries[$file] = $entry;
				return $entry;
			}
			return false;
		}
	}

	private function getExtension($entry) {
		$mimetype = $this->getMimeType('', $entry);
		switch ($mimetype) {
			case 'httpd/unix-directory':
				return '';
			case 'application/vnd.oasis.opendocument.text':
				return 'odt';
			case 'application/vnd.oasis.opendocument.spreadsheet':
				return 'ods';
			case 'application/vnd.oasis.opendocument.presentation':
				return 'pptx';
			case 'text/html':
				return 'html';
			default:
				return 'html';
		}
	}
	

	public function mkdir($path) {
		$collection = dirname($path);
		// Check if path parent is root directory
		if ($collection == '/' || $collection == '\.' || $collection == '.') {
			$uri = 'https://docs.google.com/feeds/default/private/full';
		// Get parent content link
		} else if ($dom = $this->getResource(basename($dir))) {
			$uri = $dom->getElementsByTagName('content')->item(0)->getAttribute('src');
		}
		if (isset($uri)) {
			$title = basename($path);
			// Construct post data
			$postData = '<?xml version="1.0" encoding="UTF-8"?>';
			$postData .= '<entry xmlns="http://www.w3.org/2005/Atom">';
			$postData .= '<category scheme="http://schemas.google.com/g/2005#kind" term="http://schemas.google.com/docs/2007#folder"/>';
			$postData .= '<title>'.$title.'</title>';
			$postData .= '</entry>';
			if ($dom = $this->sendRequest($uri, 'POST', $postData)) {
				return true;
			}
		}
		return false;
	}

	public function rmdir($path) {
		return $this->unlink($path);
	}

	public function opendir($path) {
		if ($path == '' || $path == '/') {
			$next = 'https://docs.google.com/feeds/default/private/full/folder%3Aroot/contents';
		} else {
			if ($entry = $this->getResource($path)) {
				$next = $entry->getElementsByTagName('content')->item(0)->getAttribute('src');
			} else {
				return false;
			}
		}
		$files = array();
		while ($next) {
			$dom = $this->getFeed($next, 'GET');
			$links = $dom->getElementsByTagName('link');
			foreach ($links as $link) {
				if ($link->getAttribute('rel') == 'next') {
					$next = $link->getAttribute('src');
					break;
				} else {
					$next = false;
				}
			}
			$entries = $dom->getElementsByTagName('entry');
			foreach ($entries as $entry) {
				$name = $entry->getElementsByTagName('title')->item(0)->nodeValue;
				// Google Docs resources don't always include extensions in title
				if (!strpos($name, '.')) {
					$extension = $this->getExtension($entry);
					if ($extension != '') {
						$name .= '.'.$extension;
					}
				}
				$files[] = $name;
				// Cache entry for future use
				$this->entries[$name] = $entry;
			}
		}
		OC_FakeDirStream::$dirs['google'] = $files;
		return opendir('fakedir://google');
	}

	public function stat($path) {
		if ($path == '' || $path == '/') {
			$stat['size'] = $this->free_space($path);
			$stat['atime'] = time();
			$stat['mtime'] = time();
			$stat['ctime'] = time();
		} else if ($entry = $this->getResource($path)) {
			// NOTE: Native resources don't have a file size
			$stat['size'] = $entry->getElementsByTagNameNS('http://schemas.google.com/g/2005', 'quotaBytesUsed')->item(0)->nodeValue;
// 			if (isset($atime = $entry->getElementsByTagNameNS('http://schemas.google.com/g/2005', 'lastViewed')->item(0)->nodeValue)) 
// 			$stat['atime'] = strtotime($entry->getElementsByTagNameNS('http://schemas.google.com/g/2005', 'lastViewed')->item(0)->nodeValue);
			$stat['mtime'] = strtotime($entry->getElementsByTagName('updated')->item(0)->nodeValue);
			$stat['ctime'] = strtotime($entry->getElementsByTagName('published')->item(0)->nodeValue);
		}
		if (isset($stat)) {
			return $stat;
		}
		return false;
	}

	public function filetype($path) {
		if ($path == '' || $path == '/') {
			return 'dir';
		} else if ($entry = $this->getResource($path)) {
			$categories = $entry->getElementsByTagName('category');
			foreach ($categories as $category) {
				if ($category->getAttribute('scheme') == 'http://schemas.google.com/g/2005#kind') {
					$type = $category->getAttribute('label');
					if (strlen(strstr($type, 'folder')) > 0) {
						return 'dir';
					} else {
						return 'file';
					}
				}
			}
		}
		return false;
	}

	public function is_readable($path) {
		return true;
	}

	public function is_writable($path) {
		if ($path == '' || $path == '/') {
			return true;
		} else if ($entry = $this->getResource($path)) {
			// Check if edit or edit-media links exist
			$links = $entry->getElementsByTagName('link');
			foreach ($links as $link) {
				if ($link->getAttribute('rel') == 'edit') {
					return true;
				} else if ($link->getAttribute('rel') == 'edit-media') {
					return true;
				}
			}
		}
		return false;
	}
	
	public function file_exists($path) {
		if ($path == '' || $path == '/') {
			return true;
		} else if ($this->getResource($path)) {
			return true;
		}
		return false;
	}
	
	public function unlink($path) {
		// Get resource self link to trash resource
		if ($entry = $this->getResource($path)) {
			$links = $entry->getElementsByTagName('link');
			foreach ($links as $link) {
				if ($link->getAttribute('rel') == 'self') {
					$uri = $link->getAttribute('href');
					break;
				}
			}
		}
		if (isset($uri)) {
			$this->sendRequest($uri, 'DELETE');
			return true;
		}
		return false;
	}

	public function rename($path1, $path2) {
		if ($entry = $this->getResource($path1)) {
			$collection = dirname($path2);
			if (dirname($path1) == $collection) {
				// Get resource edit link to rename resource
				$etag = $entry->getAttribute('gd:etag');
				$links = $entry->getElementsByTagName('link');
				foreach ($links as $link) {
					if ($link->getAttribute('rel') == 'edit') {
						$uri = $link->getAttribute('href');
						break;
					}
				}
				$title = basename($path);
				// Construct post data
				$postData = '<?xml version="1.0" encoding="UTF-8"?>';
				$postData .= '<entry xmlns="http://www.w3.org/2005/Atom" xmlns:docs="http://schemas.google.com/docs/2007" xmlns:gd="http://schemas.google.com/g/2005" gd:etag='.$etag.'>';
				$postData .= '<title>'.$title.'</title>';
				$postData .= '</entry>';
				$this->sendRequest($uri, 'PUT', $postData);
				return true;
			} else {
				// Move to different collection
				if ($collectionEntry = $this->getResource($collection)) {
					$feedUri = $colelctionEntry->getElementsByTagName('content')->item(0)->getAttribute('src');
					// Construct post data
					$postData = '<?xml version="1.0" encoding="UTF-8"?>';
					$postData .= '<entry xmlns="http://www.w3.org/2005/Atom">';
					$postData .= '<id>'.$entry->getElementsByTagName('id')->item(0).'</id>';
					$postData .= '</entry>';
					$this->sendRequest($uri, 'POST', $postData);
					return true;
				}
			}
		}
		return false;
	}

	public function fopen($path, $mode) {
		switch ($mode) {
			case 'r':
			case 'rb':
				if ($entry = $this->getResource($path)) {
					$extension = $this->getExtension($entry);
					$downloadUri = $entry->getElementsByTagName('content')->item(0)->getAttribute('src');
					// TODO Non-native documents don't need these additional parameters
					$downloadUri .= '&exportFormat='.$extension.'&format='.$extension;
					$tmpFile = $this->sendRequest($downloadUri, 'GET', null, null, true);
					return fopen($tmpFile, 'r');
				}
			case 'w':
			case 'wb':
			case 'a':
			case 'ab':
			case 'r+':
			case 'w+':
			case 'wb+':
			case 'a+':
			case 'x':
			case 'x+':
			case 'c':
			case 'c+':
				if (strrpos($path,'.') !== false) {
					$ext = substr($path,strrpos($path,'.'));
				} else {
					$ext = '';
				}
				$tmpFile = OC_Helper::tmpFile($ext);
				OC_CloseStreamWrapper::$callBacks[$tmpFile] = array($this, 'writeBack');
				if ($this->file_exists($path)) {
					$source = $this->fopen($path, 'r');
					file_put_contents($tmpFile, $source);
				}
				self::$tempFiles[$tmpFile] = $path;
				return fopen('close://'.$tmpFile, $mode);
		}
		return false;
	}

	public function writeBack($tmpFile) {
		if (isset(self::$tempFiles[$tmpFile])) {
			$this->uploadFile($tmpFile, self::$tempFiles[$tmpFile]);
			unlink($tmpFile);
		}
	}

	private function uploadFile($path, $target) {
		$entry = $this->getResource($target);
		if (!$entry) {
			if (dirname($target) == '.' || dirname($target) == '/') {
				$uploadUri = 'https://docs.google.com/feeds/upload/create-session/default/private/full/folder%3Aroot/contents';
			} else {
				$entry = $this->getResource(dirname($target));
			}
		}
		if (!isset($uploadUri) && $entry) {
			$etag = $entry->getAttribute('gd:etag');
			$links = $entry->getElementsByTagName('link');
			foreach ($links as $link) {
				if ($link->getAttribute('rel') == 'http://schemas.google.com/g/2005#resumable-edit-media') {
					$uploadUri = $link->getAttribute('href');
					break;
				}
			}
		}
		if (isset($uploadUri) && $handle = fopen($path, 'r')) {
			$uploadUri .= '?convert=false';
			$mimetype = OC_Helper::getMimeType($path);
			$size = filesize($path);
			$headers = array('X-Upload-Content-Type: ' => $mimetype, 'X-Upload-Content-Length: ' => $size);
			$postData = '<?xml version="1.0" encoding="UTF-8"?>';
			$postData .= '<entry xmlns="http://www.w3.org/2005/Atom" xmlns:docs="http://schemas.google.com/docs/2007">';
			$postData .= '<title>'.basename($target).'</title>';
			$postData .= '</entry>';
			$result = $this->sendRequest($uploadUri, 'POST', $postData, $headers, false, true);
			if ($result) {
				// Get location to upload file
				if (preg_match('@^Location: (.*)$@m', $result, $matches)) {
					$uploadUri = trim($matches[1]);
				}
			} else {
				return false;
			}
			// 512 kB chunks
			$chunkSize = 524288;
			$i = 0;
			while (!feof($handle)) {
				if ($i + $chunkSize > $size) {
					if ($i == 0) {
						$chunkSize = $size;
					} else {
						$chunkSize = $size % $i;
					}
				}
				$end = $i + $chunkSize - 1;
				$headers = array('Content-Length: '.$chunkSize, 'Content-Type: '.$mimetype, 'Content Range: bytes '.$i.'-'.$end.'/'.$size);
				$postData = fread($handle, $chunkSize);
				$result = $this->sendRequest($uploadUri, 'PUT', $postData, $headers, false, true, false);
				if ($result) {
					// Get next location to upload file chunk
					if (preg_match('@^Location: (.*)$@m', $result, $matches)) {
						$uploadUri = trim($matches[1]);
					}
					$i += $chunkSize;
				} else {
					return false;
				}
			}
			// TODO Wait for resource entry
		}
	}
	
	public function getMimeType($path, $entry = null) {
		// Entry can be passed, because extension is required for opendir and the entry can't be cached without the extension
		if ($entry == null) {
			if ($path == '' || $path == '/') {
				return 'httpd/unix-directory';
			} else {
				$entry = $this->getResource($path);
			}
		}
		if ($entry) {
			$mimetype = $entry->getElementsByTagName('content')->item(0)->getAttribute('type');
			// Native Google Docs resources often default to text/html, but it may be more useful to default to a corresponding ODF mimetype
			// Collections get reported as application/atom+xml, make sure it actually is a folder and fix the mimetype
			if ($mimetype == 'text/html' || $mimetype == 'application/atom+xml;type=feed') {
				$categories = $entry->getElementsByTagName('category');
				foreach ($categories as $category) {
					if ($category->getAttribute('scheme') == 'http://schemas.google.com/g/2005#kind') {
						$type = $category->getAttribute('label');
						if (strlen(strstr($type, 'folder')) > 0) {
							return 'httpd/unix-directory';
						} else if (strlen(strstr($type, 'document')) > 0) {
							return 'application/vnd.oasis.opendocument.text';
						} else if (strlen(strstr($type, 'spreadsheet')) > 0) {
							return 'application/vnd.oasis.opendocument.spreadsheet';
						} else if (strlen(strstr($type, 'presentation')) > 0) {
							return 'application/vnd.oasis.opendocument.presentation';
						} else if (strlen(strstr($type, 'drawing')) > 0) {
							return 'application/vnd.oasis.opendocument.graphics';
						} else {
							// If nothing matches return text/html, all native Google Docs resources can be exported as text/html
							return 'text/html';
						}
					}
				}
			}
			return $mimetype;
		}
		return false;
	}
	
	public function free_space($path) {
		if ($dom = $this->getFeed('https://docs.google.com/feeds/metadata/default', 'GET')) {
			// NOTE: Native Google Docs resources don't count towards quota
			$total = $dom->getElementsByTagNameNS('http://schemas.google.com/g/2005', 'quotaBytesTotal')->item(0)->nodeValue;
			$used = $dom->getElementsByTagNameNS('http://schemas.google.com/g/2005', 'quotaBytesUsed')->item(0)->nodeValue;
			return $total - $used;
		}
		return false;
	}
  
	public function touch($path, $mtime = null) {
	  
	}

}