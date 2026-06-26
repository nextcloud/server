<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Event;

use OCP\EventDispatcher\Event;
use OCP\Share\IShare;

/**
 * Emitted before the rendering step of the public share page happens. The event
 * holds a flag that specifies if it is the authentication page of a public share.
 *
 * @since 20.0.0
 */
class BeforeTemplateRenderedEvent extends Event {
	/**
	 * @since 20.0.0
	 */
	public const SCOPE_PUBLIC_SHARE_AUTH = 'publicShareAuth';

	/**
	 * @since 20.0.0
	 */
	public function __construct(
		private IShare $share,
		private ?string $scope = null,
	) {
		parent::__construct();
	}

	/**
	 * @since 20.0.0
	 */
	public function getShare(): IShare {
		return $this->share;
	}

	/**
	 * @since 20.0.0
	 */
	public function getScope(): ?string {
		return $this->scope;
	}
}
