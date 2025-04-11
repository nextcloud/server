<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\DirectEditing;

use OCP\AppFramework\Http\Response;

/**
 * @since 18.0.0
 */
interface IEditor {
	/**
	 * Return a unique identifier for the editor
	 *
	 * e.g. richdocuments
	 *
	 * @since 18.0.0
	 * @return string
	 */
	public function getId(): string;

	/**
	 * Return a readable name for the editor
	 *
	 * e.g. Collabora Online
	 *
	 * @since 18.0.0
	 * @return string
	 */
	public function getName(): string;

	/**
	 * A list of mimetypes that should open the editor by default
	 *
	 * @since 18.0.0
	 * @return string[]
	 */
	public function getMimetypes(): array;

	/**
	 * A list of mimetypes that can be opened in the editor optionally
	 *
	 * @since 18.0.0
	 * @return string[]
	 */
	public function getMimetypesOptional(): array;

	/**
	 * Return a list of file creation options to be presented to the user
	 *
	 * @since 18.0.0
	 * @return ACreateFromTemplate[]|ACreateEmpty[]
	 */
	public function getCreators(): array;

	/**
	 * Return if the view is able to securely view a file without downloading it to the browser
	 *
	 * @since 18.0.0
	 * @return bool
	 */
	public function isSecure(): bool;

	/**
	 * Return a template response for displaying the editor
	 *
	 * open can only be called once when the client requests the editor with a one-time-use token
	 * For handling editing and later requests, editors need to implement their own token handling and take care of invalidation
	 *
	 * This behavior is similar to the current direct editing implementation in collabora where we generate a one-time token and switch over to the regular wopi token for the actual editing/saving process
	 *
	 * @since 18.0.0
	 * @return Response
	 */
	public function open(IToken $token): Response;
}
