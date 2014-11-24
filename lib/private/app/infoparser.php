<?php
 /**
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller deepdiver@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\App;

use OCP\IURLGenerator;
use SimpleXMLElement;

class InfoParser {

	/**
	 * @param \OC\HTTPHelper $httpHelper
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(\OC\HTTPHelper $httpHelper, IURLGenerator $urlGenerator) {
		$this->httpHelper = $httpHelper;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @param string $file
	 * @return null|string
	 */
	public function parse($file) {
		if (!file_exists($file)) {
			return null;
		}

		$xml = simplexml_load_file($file);
		$array = json_decode(json_encode((array)$xml), TRUE);
		if (is_null($array)) {
			return null;
		}
		if (!array_key_exists('info', $array)) {
			$array['info'] = array();
		}
		if (!array_key_exists('remote', $array)) {
			$array['remote'] = array();
		}
		if (!array_key_exists('public', $array)) {
			$array['public'] = array();
		}

		if (array_key_exists('documentation', $array)) {
			foreach ($array['documentation'] as $key => $url) {
				// If it is not an absolute URL we assume it is a key
				// i.e. admin-ldap will get converted to go.php?to=admin-ldap
				if (!$this->httpHelper->isHTTPURL($url)) {
					$url = $this->urlGenerator->linkToDocs($url);
				}

				$array["documentation"][$key] = $url;

			}
		}
		if (array_key_exists('types', $array)) {
			foreach ($array['types'] as $type => $v) {
				unset($array['types'][$type]);
				$array['types'][] = $type;

			}
		}

		return $array;
	}
}
