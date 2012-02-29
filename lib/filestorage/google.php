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

	private $datadir;
	private $consumer;
	private $oauth_token;
	private $sig_method;
	private $entries;

	public function __construct($arguments) {
		$this->datadir = $arguments['datadir'];
		$consumer_key = isset($arguments['consumer_key']) ? $arguments['consumer_key'] : 'anonymous';
		$consumer_secret = isset($arguments['consumer_secret']) ? $arguments['consumer_secret'] : 'anonymous';
		$this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
		$this->oauth_token = new OAuthToken($arguments['token'], $arguments['token_secret']);
		$this->sig_method = new OAuthSignatureMethod_HMAC_SHA1();
		$this->entries = array();
	}

	private function sendRequest($feedUri, $http_method, $postData = null) {
		$feedUri = trim($feedUri);
		// create an associative array from each key/value url query param pair.
		$params = array();
		$pieces = explode('?', $feedUri);
		if (isset($pieces[1])) {
			$params = explode_assoc('=', '&', $pieces[1]);
		}
		// urlencode each url parameter key/value pair
		$tempStr = $pieces[0];
		foreach ($params as $key => $value) {
			$tempStr .= '&' . urlencode($key) . '=' . urlencode($value);
		}
		$feedUri = preg_replace('/&/', '?', $tempStr, 1);
		$req = OAuthRequest::from_consumer_and_token($this->consumer, $this->oauth_token, $http_method, $feedUri, $params);
		$req->sign_request($this->sig_method, $this->consumer, $this->oauth_token);
		$auth_header = $req->to_header();
		$result = send_signed_request($http_method, $feedUri, array($auth_header, 'Content-Type: application/atom+xml', 'GData-Version: 3.0'), $postData);
		// TODO Return false if error is received
		if (!$result) {
			return false;
		}
		$result = explode('<', $result, 2);
		$result = isset($result[1]) ? '<'.$result[1] : $result[0];
		$dom = new DOMDocument();
		$dom->loadXML($result);
		return $dom;
	}

	private function getResource($path) {
		if (array_key_exists($path, $this->entries)) {
			return $this->entries[$path];
		} else {
			$title = basename($path);
			$dom = $this->sendRequest('https://docs.google.com/feeds/default/private/full?showfolders=true&title='.$title, 'GET');
			// Check if request was successful and entry exists
			if ($dom && $entry = $dom->getElementsByTagName('entry')->item(0)) {
				$this->entries[$path] = $entry;
				return $entry;
			}
			return false;
		}
	}

	private function getExtension($entry) {
		$mimetype = $this->getMimeType('', $entry);
		switch($mimetype) {
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
		$dir = dirname($path);
		// Check if path parent is root directory
		if ($dir == '/' || $dir == '\.' || $dir == '.') {
			$feedUri = 'https://docs.google.com/feeds/default/private/full';
		// Get parent content link
		} else if ($dom = $this->getResource(basename($dir))) {
			$feedUri = $dom->getElementsByTagName('content')->item(0)->getAttribute('src');
		}
		if (isset($feedUri)) {
			$title = basename($path);
			// Construct post data
			$postData = '<?xml version="1.0" encoding="UTF-8"?>';
			$postData .= '<entry xmlns="http://www.w3.org/2005/Atom">';
			$postData .= '<category scheme="http://schemas.google.com/g/2005#kind" term="http://schemas.google.com/docs/2007#folder"/>';
			$postData .= '<title>'.$title.'</title>';
			$postData .= '</entry>';
			if ($dom = $this->sendRequest($feedUri, 'POST', $postData)) {
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
				$collectionId = $entry->getElementsByTagNameNS('http://schemas.google.com/g/2005', 'resourceId')->item(0)->nodeValue;
				$next = 'https://docs.google.com/feeds/default/private/full/folder%3A'.$collectionId.'/contents';
			} else {
				return false;
			}
		}
		$files = array();
		while ($next) {
			$dom = $this->sendRequest($next, 'GET');
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
					$name .= '.'.$this->getExtension($entry);
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
			$stat['atime'] = strtotime($entry->getElementsByTagNameNS('http://schemas.google.com/g/2005', 'lastViewed')->item(0)->nodeValue);
			$stat['mtime'] = strtotime($entry->getElementsByTagName('updated')->item(0)->nodeValue);
			$stat['ctime'] = strtotime($entry->getElementsByTagName('published')->item(0)->nodeValue);
		}
		return $stat;
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
					$feedUri = $link->getAttribute('href');
				}
			}
		}
		if (isset($feedUri)) {
			$this->sendRequest($feedUri, 'DELETE');
			return true;
		}
		return false;
	}

	public function rename($path1, $path2) {
		// TODO Add support for moving to different collections
		// Get resource edit link to rename resource
		if ($entry = $this->getResource($path1)) {
			$etag = $entry->getElementsByTagName('entry')->item(0)->getAttribute('gd:etag');
			$links = $entry->getElementsByTagName('link');
			foreach ($links as $link) {
				if ($link->getAttribute('rel') == 'edit') {
					$feedUri = $link->getAttribute('href');
				}
			}
		}
		if (isset($etag) && isset($feedUri)) {
			$title = basename($path2);
			// Construct post data
			$postData = '<?xml version="1.0" encoding="UTF-8"?>';
			$postData .= '<entry xmlns="http://www.w3.org/2005/Atom" xmlns:docs="http://schemas.google.com/docs/2007" xmlns:gd="http://schemas.google.com/g/2005" gd:etag='.$etag.'>';
			$postData .= '<title>'.$title.'</title>';
			$postData .= '</entry>';
			$this->sendRequest($feedUri, 'PUT', $postData);
			return true;
		}
		return false;
	}

	public function fopen($path, $mode) {
		if ($entry = $this->getResource($path)) {
			$extension = $this->getExtension($path);
			$downloadUri = $entry->getElementsByTagName('content')->item(0)->getAttribute('src');
			// TODO Non-native documents don't need these additional parameters
			$downloadUri .= '&exportFormat='.$extension.'&format='.$extension;
		}
	}

	public function getMimeType($path, $entry = null) {
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
			if ($mimetype == 'text/html' || $mimetype == 'application/atom+xml') {
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
		if ($dom = $this->sendRequest('https://docs.google.com/feeds/metadata/default', 'GET')) {
			// NOTE: Native Google Docs resources don't count towards quota
			$total = $dom->getElementsByTagNameNS('http://schemas.google.com/g/2005', 'quotaBytesTotal')->item(0)->nodeValue;
			$used = $dom->getElementsByTagNameNS('http://schemas.google.com/g/2005', 'quotaBytesUsed')->item(0)->nodeValue;
			return $total - $used;
		}
		return false;
	}
  
	public function search($query) {
		
	}

}