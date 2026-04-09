<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\SystemTag;

/**
 * Exception when a tag was not found.
 *
 * @since 9.0.0
 */
class TagNotFoundException extends \RuntimeException {
	/** @var string[] */
	protected $tags;

	/**
	 * TagNotFoundException constructor.
	 *
	 * @param string $message
	 * @param int $code
	 * @param \Exception|null $previous
	 * @param string[] $tags
	 * @since 9.0.0
	 */
	public function __construct(string $message = '', int $code = 0, ?\Exception $previous = null, array $tags = []) {
		parent::__construct($message, $code, $previous);
		$this->tags = $tags;
	}

	/**
	 * @return string[]
	 * @since 9.0.0
	 */
	public function getMissingTags(): array {
		return $this->tags;
	}
}
