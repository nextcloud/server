<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Search;

use JsonSerializable;

/**
 * Represents an entry in a list of results an app returns for a unified search
 * query.
 *
 * The app providing the results has to extend this class for customization. In
 * most cases apps do not have to add any additional code.
 *
 * @example ``class MailResultEntry extends SearchResultEntry {}`
 *
 * This approach was chosen over a final class as it allows Nextcloud to later
 * add new optional properties of an entry without having to break the usage of
 * this class in apps.
 *
 * @since 20.0.0
 */
class SearchResultEntry implements JsonSerializable {
	/**
	 * @var string
	 * @since 20.0.0
	 */
	protected $thumbnailUrl;

	/**
	 * @var string
	 * @since 20.0.0
	 */
	protected $title;

	/**
	 * @var string
	 * @since 20.0.0
	 */
	protected $subline;

	/**
	 * @var string
	 * @since 20.0.0
	 */
	protected $resourceUrl;

	/**
	 * @var string
	 * @since 20.0.0
	 */
	protected $icon;

	/**
	 * @var boolean
	 * @since 20.0.0
	 */
	protected $rounded;

	/**
	 * @var string[]
	 * @psalm-var array<string, string>
	 * @since 20.0.0
	 */
	protected $attributes = [];

	/**
	 * @param string $thumbnailUrl a relative or absolute URL to the thumbnail or icon of the entry
	 * @param string $title a main title of the entry
	 * @param string $subline the secondary line of the entry
	 * @param string $resourceUrl the URL where the user can find the detail, like a deep link inside the app
	 * @param string $icon the icon class or url to the icon
	 * @param boolean $rounded is the thumbnail rounded
	 *
	 * @since 20.0.0
	 */
	public function __construct(string $thumbnailUrl,
		string $title,
		string $subline,
		string $resourceUrl,
		string $icon = '',
		bool $rounded = false) {
		$this->thumbnailUrl = $thumbnailUrl;
		$this->title = $title;
		$this->subline = $subline;
		$this->resourceUrl = $resourceUrl;
		$this->icon = $icon;
		$this->rounded = $rounded;
	}

	/**
	 * Add optional attributes to the result entry, e.g. an ID or some other
	 * context information that can be read by the client application
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @since 20.0.0
	 */
	public function addAttribute(string $key, string $value): void {
		$this->attributes[$key] = $value;
	}

	/**
	 * @return array
	 *
	 * @since 20.0.0
	 */
	public function jsonSerialize(): array {
		return [
			'thumbnailUrl' => $this->thumbnailUrl,
			'title' => $this->title,
			'subline' => $this->subline,
			'resourceUrl' => $this->resourceUrl,
			'icon' => $this->icon,
			'rounded' => $this->rounded,
			'attributes' => $this->attributes,
		];
	}
}
