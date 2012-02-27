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

class OC_Filestorage_Google {

	private $datadir;
	private $consumer;
	private $oauth_token;
	private $sig_method;

	public function __construct($arguments) {
		$this->datadir = $arguments['datadir'];
		$consumer_key = isset($arguments['consumer_key']) ? $arguments['consumer_key'] : 'anonymous';
		$consumer_secret = isset($arguments['consumer_secret']) ? $arguments['consumer_secret'] : 'anonymous';
		$this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
		$this->oauth_token = new OAuthToken($arguments['token'], $arguments['token_secret']);
		$this->sig_method = new OAuthSignatureMethod_HMAC_SHA1();
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
		// TODO Look up google docs query caching/only send back if changes occured
		// TODO Look inside of collections for specific file
		// TODO Strip extension
		$title = basename($path);
		return $this->sendRequest('https://docs.google.com/feeds/default/private/full?showfolders=true&title='.$title.'&title-exact=true', 'GET');
	}

	public function mkdir($path) {
		$dir = dirname($path);
		// Check if path parent is root directory
		if ($dir == '/' || $dir == '\.' || $dir == '.') {
			$feedUri = 'https://docs.google.com/feeds/default/private/full';
		// Get parent content link
		} else {
			$dom = $this->getResource(basename($dir));
			$feedUri = $dom->getElementsByTagName('content')->item(0)->getAttribute('src');
		}
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
		return false;
	}

	public function rmdir($path) {
		return $this->unlink($path);
	}

	public function opendir($path) {
		if ($path == '' || $path == '/') {
			$resource = 'https://docs.google.com/feeds/default/private/full/folder%3Aroot/contents';
			$dom = $this->sendRequest('https://docs.google.com/feeds/default/private/full/folder%3Aroot/contents', 'GET');
		} else {
			$dom = $this->getResource($path); 
		}
		global $FAKEDIRS;
		$files = array();
// 		while ($next) {
// 			// send request $next link
// 			$links = $dom->getElementsByTagName('link');
// 			foreach ($links as $link) {
// 				if ($link->getAttribute('rel') == 'next') {
// 					$next = $link->getAttribute('src');
// 					break;
// 				} else {
// 					$next = false;
// 				}
// 			}
			$entries = $dom->getElementsByTagName('entry');
			foreach($entries as $entry) {
				$name = $entry->getElementsByTagName('title')->item(0)->nodeValue;
				// Native Google resources don't include extensions in title
				if (!strpos($name, '.')) {
					if ($ext = $this->filetype('', $entry)) {
						$name .= '.'.$ext;
					}
				}
				$files[] = $name;
			}
// 		}
		$FAKEDIRS['google'] = $files;
		return opendir('fakedir://google');
	}

	public function is_dir($path) {
		if ($entry = $this->getResource($path)) {
			$categories = $entry->getElementsByTagName('category');
			foreach ($categories as $category) {
				if ($category->getAttribute('scheme') == 'http://schemas.google.com/g/2005#kind') {
					// Check if label is equal to folder
					$type = $category->getAttribute('label');
					if (strlen(strstr($type, 'folder')) > 0) {
						return true;
					}
				}
			}
		}
		return false; 
	}

	public function is_file($path) {
		if ($this->getResource($path)) {
			return true;
		}
		return false;
	}

	public function stat($path) {
		if ($dom = $this->getResource($path)) {
			// TODO Native resources don't have a file size
			$stat['size'] =  $dom->getElementsByTagNameNS('http://schemas.google.com/g/2005', 'quotaBytesUsed')->item(0)->nodeValue;
			$stat['mtime'] = strtotime($dom->getElementsByTagName('updated')->item(1)->nodeValue);
			$stat['ctime'] = strtotime($dom->getElementsByTagName('published')->item(0)->nodeValue);
			return $stat;
		}
		return false; 
		
	}

	public function filetype($path, $entry = null) {
		if ($entry == null) {
			$entry = $this->getResource($path);
		}
		$categories = $entry->getElementsByTagName('category');
		foreach ($categories as $category) {
			if ($category->getAttribute('scheme') == 'http://schemas.google.com/g/2005#kind') {
				// Guess extension from label, default to ODF extensions
				$type = $category->getAttribute('label');
				if (strlen(strstr($type, 'folder')) > 0) {
					return '';
				} else if (strlen(strstr($type, 'document')) > 0) {
					return 'odt';
				} else if (strlen(strstr($type, 'presentation')) > 0) {
					return 'odp';
				} else if (strlen(strstr($type, 'spreadsheet')) > 0) {
					return 'ods';
				} else {
					return $type;
				}
			}
		}
	}

	public function is_readable($path) {
		return true;
	}

	public function is_writable($path) {
		// Check if edit or edit-media links exist
		if ($entry = $this->getResource($path)) {
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
		if ($this->getResource($path)) {
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

	public function rename($path1,$path2) {

	}

	public function fopen($path,$mode){}

	public function toTmpFile($path) {
		$dom = $this->getResource($path);
		$url = $dom->getElementsByTagName('content')->getAttribute('src');		
	}

	public function fromTmpFile($tmpPath,$path){}
	public function fromUploadedFile($tmpPath,$path){}
	public function getMimeType($path){}
	public function hash($type,$path,$raw){}
	
	public function free_space($path) {
		if ($dom = $this->sendRequest('https://docs.google.com/feeds/metadata/default', 'GET')) {
			$total = $dom->getElementsByTagNameNS('http://schemas.google.com/g/2005', 'quotaBytesTotal')->item(0)->nodeValue;
			$used = $dom->getElementsByTagNameNS('http://schemas.google.com/g/2005', 'quotaBytesUsed')->item(0)->nodeValue;
			return $total - $used;
		}
		return false;
	}
  
	public function search($query){}
	
	public function getLocalFile($path) {
		return false;
	}
}