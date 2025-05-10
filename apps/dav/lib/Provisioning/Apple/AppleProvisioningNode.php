<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Provisioning\Apple;

use OCP\AppFramework\Utility\ITimeFactory;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\INode;
use Sabre\DAV\IProperties;
use Sabre\DAV\PropPatch;

class AppleProvisioningNode implements INode, IProperties {
	public const FILENAME = 'apple-provisioning.mobileconfig';

	/**
	 * @param ITimeFactory $timeFactory
	 */
	public function __construct(
		protected ITimeFactory $timeFactory,
	) {
	}

	/**
	 * @return string
	 */
	public function getName() {
		return self::FILENAME;
	}


	public function setName($name) {
		throw new Forbidden('Renaming ' . self::FILENAME . ' is forbidden');
	}

	/**
	 * @return null
	 */
	public function getLastModified() {
		return null;
	}

	/**
	 * @throws Forbidden
	 */
	public function delete() {
		throw new Forbidden(self::FILENAME . ' may not be deleted.');
	}

	/**
	 * @param array $properties
	 * @return array
	 */
	public function getProperties($properties) {
		$datetime = $this->timeFactory->getDateTime();

		return [
			'{DAV:}getcontentlength' => 42,
			'{DAV:}getlastmodified' => $datetime->format(\DateTimeInterface::RFC7231),
		];
	}

	/**
	 * @param PropPatch $propPatch
	 * @throws Forbidden
	 */
	public function propPatch(PropPatch $propPatch) {
		throw new Forbidden(self::FILENAME . '\'s properties may not be altered.');
	}
}
