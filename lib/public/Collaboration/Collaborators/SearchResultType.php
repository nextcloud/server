<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Collaboration\Collaborators;

/**
 * Class SearchResultType
 *
 * @since 13.0.0
 */
class SearchResultType {
	/** @var string */
	protected $label;

	/**
	 * SearchResultType constructor.
	 *
	 * @param string $label
	 * @since 13.0.0
	 */
	public function __construct($label) {
		$this->label = $this->getValidatedType($label);
	}

	/**
	 * @return string
	 * @since 13.0.0
	 */
	public function getLabel() {
		return $this->label;
	}

	/**
	 * @param $type
	 * @return string
	 * @throws \InvalidArgumentException
	 * @since 13.0.0
	 */
	protected function getValidatedType($type) {
		$type = trim((string)$type);

		if ($type === '') {
			throw new \InvalidArgumentException('Type must not be empty');
		}

		if ($type === 'exact') {
			throw new \InvalidArgumentException('Provided type is a reserved word');
		}

		return $type;
	}
}
