<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

class OC_Theme {

	/**
	 * Returns the base URL
	 * @return string URL
	 */
	public function getBaseUrl(): string {
		return 'https://nextcloud.com';
	}

	/**
	 * Returns the documentation URL
	 * @return string URL
	 */
	public function getDocBaseUrl(): string {
		return 'https://docs.nextcloud.com';
	}

	/**
	 * Returns the title
	 * @return string title
	 */
	public function getTitle(): string {
		return 'Custom Cloud';
	}

	/**
	 * Returns the short name of the software
	 * @return string title
	 */
	public function getName(): string {
		return 'Custom Cloud';
	}

	/**
	 * Returns the short name of the software containing HTML strings
	 * @return string title
	 */
	public function getHTMLName(): string {
		return 'Custom Cloud';
	}

	/**
	 * Returns entity (e.g. company name) - used for footer, copyright
	 * @return string entity name
	 */
	public function getEntity(): string {
		return 'Custom Cloud Co.';
	}

	/**
	 * Returns slogan
	 * @return string slogan
	 */
	public function getSlogan(): string {
		return 'Your custom cloud, personalized for you!';
	}

	/**
	 * Returns short version of the footer
	 * @return string short footer
	 */
	public function getShortFooter(): string {
		$entity = $this->getEntity();

		$footer = '© ' . date('Y');

		// Add link if entity name is not empty
		if ($entity !== '') {
			$footer .= ' <a href="' . $this->getBaseUrl() . '" target="_blank">' . $entity . '</a>' . '<br/>';
		}

		$footer .= $this->getSlogan();

		return $footer;
	}

	/**
	 * Returns long version of the footer
	 * @return string long footer
	 */
	public function getLongFooter(): string {
		$footer = '© ' . date('Y') . ' <a href="' . $this->getBaseUrl() . '" target="_blank">' . $this->getEntity() . '</a>' .
			'<br/>' . $this->getSlogan();

		return $footer;
	}

	/**
	 * Generate a documentation link for a given key
	 * @return string documentation link
	 */
	public function buildDocLinkToKey($key): string {
		return $this->getDocBaseUrl() . '/server/15/go.php?to=' . $key;
	}


	/**
	 * Returns mail header color
	 * @return string
	 */
	public function getColorPrimary(): string {
		return '#745bca';
	}

	/**
	 * Returns background color to be used
	 * @return string
	 */
	public function getColorBackground(): string {
		return '#3d85c6';
	}

	/**
	 * Returns variables to overload defaults from core/css/variables.scss
	 * @return array
	 */
	public function getScssVariables(): array {
		return [
			'color-primary' => '#745bca'
		];
	}
}
