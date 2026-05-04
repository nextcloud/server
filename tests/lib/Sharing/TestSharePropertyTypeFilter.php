<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

use OCP\Sharing\Property\ISharePropertyTypeFilter;
use OCP\Sharing\Share;
use OCP\Sharing\ShareAccessContext;

final class TestSharePropertyTypeFilter extends TestSharePropertyType1 implements ISharePropertyTypeFilter {
	#[\Override]
	public function isFiltered(ShareAccessContext $accessContext, Share $share): bool {
		if (($accessContext->arguments[self::class] ?? null) === 'filtered') {
			return true;
		}

		return ($property = $share->properties[self::class] ?? null) !== null && $property->value === 'filtered';
	}
}
