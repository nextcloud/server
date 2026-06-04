<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\RichObjectStrings;

/**
 * Class Validator
 *
 * @psalm-type RichObjectParameter = array{
 *     type: string,
 *     id: string,
 *     name: string,
 *     server?: string,
 *     link?: string,
 *     'call-type'?: 'one2one'|'group'|'public',
 *     'icon-url'?: string,
 *     'message-id'?: string,
 *     boardname?: string,
 *     stackname?: string,
 *     size?: string,
 *     path?: string,
 *     mimetype?: string,
 *     'preview-available'?: 'yes'|'no',
 *     'hide-download'?: 'yes'|'no',
 *     mtime?: string,
 *     latitude?: string,
 *     longitude?: string,
 *     description?: string,
 *     thumb?: string,
 *     website?: string,
 *     visibility?: '0'|'1',
 *     assignable?: '0'|'1',
 *     conversation?: string,
 *     etag?: string,
 *     permissions?: string,
 *     width?: string,
 *     height?: string,
 *     blurhash?: string,
 * }
 *
 * @since 11.0.0
 */
interface IValidator {
	/**
	 * Only alphanumeric, dash, underscore and dot are allowed, starting with a character
	 * @since 31.0.0
	 */
	public const PLACEHOLDER_REGEX = '[A-Za-z][A-Za-z0-9\-_.]+';

	/**
	 * @param string $subject
	 * @param array<non-empty-string, RichObjectParameter> $parameters
	 * @throws InvalidObjectExeption
	 * @since 11.0.0
	 */
	public function validate(string $subject, array $parameters): void;
}
