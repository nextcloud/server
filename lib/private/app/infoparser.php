<?php
/**
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OC\App;

use OCP\IURLGenerator;

class InfoParser {
	/**
	 * @var \OC\HTTPHelper
	 */
	private $httpHelper;

	/**
	 * @var IURLGenerator
	 */
	private $urlGenerator;

	/**
	 * @param \OC\HTTPHelper $httpHelper
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(\OC\HTTPHelper $httpHelper, IURLGenerator $urlGenerator) {
		$this->httpHelper = $httpHelper;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @param string $file the xml file to be loaded
	 * @return null|array where null is an indicator for an error
	 */
	public function parse($file) {
		if (!file_exists($file)) {
			return null;
		}

		libxml_use_internal_errors(true);
		$loadEntities = libxml_disable_entity_loader(false);
		$xml = simplexml_load_file($file);
		libxml_disable_entity_loader($loadEntities);
		if ($xml == false) {
			libxml_clear_errors();
			return null;
		}
		$array = $this->xmlToArray($xml);
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
		if (!array_key_exists('types', $array)) {
			$array['types'] = array();
		}

		if (array_key_exists('documentation', $array) && is_array($array['documentation'])) {
			foreach ($array['documentation'] as $key => $url) {
				// If it is not an absolute URL we assume it is a key
				// i.e. admin-ldap will get converted to go.php?to=admin-ldap
				if (!$this->httpHelper->isHTTPURL($url)) {
					$url = $this->urlGenerator->linkToDocs($url);
				}

				$array['documentation'][$key] = $url;
			}
		}
		if (array_key_exists('types', $array)) {
			if (is_array($array['types'])) {
				foreach ($array['types'] as $type => $v) {
					unset($array['types'][$type]);
					if (is_string($type)) {
						$array['types'][] = $type;
					}
				}
			} else {
				$array['types'] = array();
			}
		}

		return $array;
	}

	/**
	 * @param \SimpleXMLElement $xml
	 * @return array
	 */
	function xmlToArray($xml) {
		if (!$xml->children()) {
			return (string)$xml;
		}

		$array = array();
		foreach ($xml->children() as $element => $node) {
			$totalElement = count($xml->{$element});

			if (!isset($array[$element])) {
				$array[$element] = "";
			}
			/**
			 * @var \SimpleXMLElement $node
			 */

			// Has attributes
			if ($attributes = $node->attributes()) {
				$data = array(
					'@attributes' => array(),
				);
				if (!count($node->children())){
					$value = (string)$node;
					if (!empty($value)) {
						$data['@value'] = (string)$node;
					}
				} else {
					$data = array_merge($data, $this->xmlToArray($node));
				}
				foreach ($attributes as $attr => $value) {
					$data['@attributes'][$attr] = (string)$value;
				}

				if ($totalElement > 1) {
					$array[$element][] = $data;
				} else {
					$array[$element] = $data;
				}
				// Just a value
			} else {
				if ($totalElement > 1) {
					$array[$element][] = $this->xmlToArray($node);
				} else {
					$array[$element] = $this->xmlToArray($node);
				}
			}
		}

		return $array;
	}
}
