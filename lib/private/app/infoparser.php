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

		$content = @file_get_contents($file);
		if (!$content) {
			return null;
		}
		$xml = new SimpleXMLElement($content);
		$data['info'] = array();
		$data['remote'] = array();
		$data['public'] = array();
		foreach ($xml->children() as $child) {
			/**
			 * @var $child SimpleXMLElement
			 */
			if ($child->getName() == 'remote') {
				foreach ($child->children() as $remote) {
					/**
					 * @var $remote SimpleXMLElement
					 */
					$data['remote'][$remote->getName()] = (string)$remote;
				}
			} elseif ($child->getName() == 'public') {
				foreach ($child->children() as $public) {
					/**
					 * @var $public SimpleXMLElement
					 */
					$data['public'][$public->getName()] = (string)$public;
				}
			} elseif ($child->getName() == 'types') {
				$data['types'] = array();
				foreach ($child->children() as $type) {
					/**
					 * @var $type SimpleXMLElement
					 */
					$data['types'][] = $type->getName();
				}
			} elseif ($child->getName() == 'description') {
				$xml = (string)$child->asXML();
				$data[$child->getName()] = substr($xml, 13, -14); //script <description> tags
			} elseif ($child->getName() == 'documentation') {
				foreach ($child as $subChild) {
					$url = (string)$subChild;

					// If it is not an absolute URL we assume it is a key
					// i.e. admin-ldap will get converted to go.php?to=admin-ldap
					if (!$this->httpHelper->isHTTPURL($url)) {
						$url = $this->urlGenerator->linkToDocs($url);
					}

					$data["documentation"][$subChild->getName()] = $url;
				}
			} else {
				$data[$child->getName()] = (string)$child;
			}
		}

		return $data;
	}
}
