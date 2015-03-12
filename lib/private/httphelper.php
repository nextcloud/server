<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC;

use OCP\IConfig;
use OCP\ICertificateManager;

class HTTPHelper {
	const USER_AGENT = 'ownCloud Server Crawler';

	/** @var \OCP\IConfig */
	private $config;

	/** @var \OC\Security\CertificateManager */
	private $certificateManager;

	/**
	 * @param \OCP\IConfig $config
	 */
	public function __construct(IConfig $config, ICertificateManager $certificateManager) {
		$this->config = $config;
		$this->certificateManager = $certificateManager;
	}

	/**
	 * Returns the default context array
	 * @return array
	 */
	public function getDefaultContextArray() {
		return array(
			'http' => array(
				'header' => 'User-Agent: ' . self::USER_AGENT . "\r\n",
				'timeout' => 10,
				'follow_location' => false, // Do not follow the location since we can't limit the protocol
			),
			'ssl' => array(
				'disable_compression' => true
			)
		);
	}

	/**
	 * Get URL content
	 * @param string $url Url to get content
	 * @throws \Exception If the URL does not start with http:// or https://
	 * @return string of the response or false on error
	 * This function get the content of a page via curl, if curl is enabled.
	 * If not, file_get_contents is used.
	 */
	public function getUrlContent($url) {
		if (!$this->isHTTPURL($url)) {
			throw new \Exception('$url must start with https:// or http://', 1);
		}

		$proxy = $this->config->getSystemValue('proxy', null);
		$proxyUserPwd = $this->config->getSystemValue('proxyuserpwd', null);
		$curl = curl_init();
		$max_redirects = 10;

		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_PROTOCOLS,  CURLPROTO_HTTP | CURLPROTO_HTTPS);
		curl_setopt($curl, CURLOPT_REDIR_PROTOCOLS,  CURLPROTO_HTTP | CURLPROTO_HTTPS);

		curl_setopt($curl, CURLOPT_USERAGENT, self::USER_AGENT);
		if ($proxy !== null) {
			curl_setopt($curl, CURLOPT_PROXY, $proxy);
		}
		if ($proxyUserPwd !== null) {
			curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxyUserPwd);
		}

		if (ini_get('open_basedir') === '') {
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_MAXREDIRS, $max_redirects);
			$data = curl_exec($curl);
		} else {
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
			$mr = $max_redirects;
			if ($mr > 0) {
				$newURL = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
				$rCurl = curl_copy_handle($curl);
				curl_setopt($rCurl, CURLOPT_HEADER, true);
				curl_setopt($rCurl, CURLOPT_NOBODY, true);
				curl_setopt($rCurl, CURLOPT_FORBID_REUSE, false);
				curl_setopt($rCurl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($rCurl, CURLOPT_USERAGENT, self::USER_AGENT);
				do {
					curl_setopt($rCurl, CURLOPT_URL, $newURL);
					$header = curl_exec($rCurl);
					if (curl_errno($rCurl)) {
						$code = 0;
					} else {
						$code = curl_getinfo($rCurl, CURLINFO_HTTP_CODE);
						if ($code == 301 || $code == 302) {
							preg_match('/Location:(.*?)\n/', $header, $matches);
							$newURL = trim(array_pop($matches));
						} else {
							$code = 0;
						}
					}
				} while ($code && --$mr);
				curl_close($rCurl);
				if ($mr > 0) {
					curl_setopt($curl, CURLOPT_URL, $newURL);
				}
			}

			if ($mr == 0 && $max_redirects > 0) {
				$data = false;
			} else {
				$data = curl_exec($curl);
			}
		}
		curl_close($curl);

		return $data;
	}

	/**
	 * Returns the response headers of a HTTP URL without following redirects
	 * @param string $location Needs to be a HTTPS or HTTP URL
	 * @return array
	 */
	public function getHeaders($location) {
		stream_context_set_default($this->getDefaultContextArray());
		return get_headers($location, 1);
	}

	/**
	 * Checks whether the supplied URL begins with HTTPS:// or HTTP:// (case insensitive)
	 * @param string $url
	 * @return bool
	 */
	public function isHTTPURL($url) {
		return stripos($url, 'https://') === 0 || stripos($url, 'http://') === 0;
	}

	/**
	 * create string of parameters for post request
	 *
	 * @param array $parameters
	 * @return string
	 */
	private function assemblePostParameters(array $parameters) {
		$parameterString = '';
		foreach ($parameters as $key => $value) {
			$parameterString .= $key . '=' . urlencode($value) . '&';
		}

		return rtrim($parameterString, '&');
	}

	/**
	 * send http post request
	 *
	 * @param string $url
	 * @param array $fields data send by the request
	 * @return bool
	 */
	public function post($url, array $fields) {

		$fieldsString = $this->assemblePostParameters($fields);

		$certBundle = $this->certificateManager->getCertificateBundle();

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS, (string)$fieldsString);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		if (is_readable($certBundle)) {
			curl_setopt($ch, CURLOPT_CAINFO, $certBundle);
		}

		$result = curl_exec($ch);
		$success = $result ? true : false;

		curl_close($ch);

		return array('success' => $success, 'result' => $result);
	}

}
