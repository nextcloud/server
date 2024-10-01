<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\DB\QueryBuilder;

/**
 * @since 8.2.0
 */
interface ILiteral {
	/**
	 * @return string
	 * @since 8.2.0
	 */
	public function __toString();
}
