<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Files\NodeAttributes;

use OCP\IUser;
use OCP\Share\IShare;

/**
 * Who the attributes are provided for: a logged in user,
 * or a visitor of a public share who only has the share.
 *
 * @experimental 36.0.0
 */
final class NodeAttributeContext {
	private function __construct(
		private readonly ?IUser $user,
		private readonly ?IShare $share,
	) {
	}

	/**
	 * @experimental 36.0.0
	 */
	public static function forUser(IUser $user): self {
		return new self($user, null);
	}

	/**
	 * @experimental 36.0.0
	 */
	public static function forShare(IShare $share): self {
		return new self(null, $share);
	}

	/**
	 * The logged in user, null on public shares
	 *
	 * @experimental 36.0.0
	 */
	public function getUser(): ?IUser {
		return $this->user;
	}

	/**
	 * The public share, null for logged in users
	 *
	 * @experimental 36.0.0
	 */
	public function getShare(): ?IShare {
		return $this->share;
	}
}
