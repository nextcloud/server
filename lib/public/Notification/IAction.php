<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Notification;

/**
 * Interface IAction
 *
 * @since 9.0.0
 */
interface IAction {
	/**
	 * @since 17.0.0
	 */
	public const TYPE_GET = 'GET';
	/**
	 * @since 17.0.0
	 */
	public const TYPE_POST = 'POST';
	/**
	 * @since 17.0.0
	 */
	public const TYPE_PUT = 'PUT';
	/**
	 * @since 17.0.0
	 */
	public const TYPE_DELETE = 'DELETE';
	/**
	 * @since 17.0.0
	 */
	public const TYPE_WEB = 'WEB';

	/**
	 * @param string $label
	 * @return $this
	 * @throws InvalidValueException if the label is invalid
	 * @since 9.0.0
	 * @since 30.0.0 throws {@see InvalidValueException} instead of \InvalidArgumentException
	 */
	public function setLabel(string $label): IAction;

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getLabel(): string;

	/**
	 * @param string $label
	 * @return $this
	 * @throws InvalidValueException if the label is invalid
	 * @since 9.0.0
	 * @since 30.0.0 throws {@see InvalidValueException} instead of \InvalidArgumentException
	 */
	public function setParsedLabel(string $label): IAction;

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getParsedLabel(): string;

	/**
	 * @param bool $primary
	 * @return $this
	 * @since 9.0.0
	 */
	public function setPrimary(bool $primary): IAction;

	/**
	 * @return bool
	 * @since 9.0.0
	 */
	public function isPrimary(): bool;

	/**
	 * @param string $link
	 * @param string $requestType
	 * @return $this
	 * @throws InvalidValueException if the link is invalid
	 * @since 9.0.0
	 * @since 30.0.0 throws {@see InvalidValueException} instead of \InvalidArgumentException
	 */
	public function setLink(string $link, string $requestType): IAction;

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getLink(): string;

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getRequestType(): string;

	/**
	 * @return bool
	 * @since 9.0.0
	 */
	public function isValid(): bool;

	/**
	 * @return bool
	 * @since 9.0.0
	 */
	public function isValidParsed(): bool;
}
