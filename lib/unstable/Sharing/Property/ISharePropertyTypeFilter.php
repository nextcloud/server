<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing\Property;

use NCU\Sharing\Share;
use NCU\Sharing\ShareAccessContext;
use OCP\AppFramework\Attribute\Implementable;

/**
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface ISharePropertyTypeFilter extends ISharePropertyType {
	/**
	 * Evaluates if a share should be filtered out.
	 *
	 * The method is called for every share, regardless if the property itself is present or not.
	 *
	 * @experimental 35.0.0
	 */
	public function isFiltered(ShareAccessContext $accessContext, Share $share): bool;
}
