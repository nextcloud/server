<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\FullTextSearch\Model;

/**
 * Body portion to be retrieved from the index.
 *
 * @since 35.0.0
 */
enum BodyPortion: string {
	/**
	 * @since 35.0.0
	 */
	case TITLE = 'title';

	/**
	 * @since 35.0.0
	 */
	case LINKS = 'links';

	/**
	 * @since 35.0.0
	 */
	case TAGS = 'tags';

	/**
	 * @since 35.0.0
	 */
	case SUBTAGS = 'subtags';

	/**
	 * @since 35.0.0
	 */
	case METATAGS = 'metatags';

	/**
	 * @since 35.0.0
	 */
	case PARTS = 'parts';

	/**
	 * @since 35.0.0
	 */
	case MORE = 'more';
}
