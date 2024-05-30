<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\WorkflowEngine\EntityContext;

/**
 * Interface IUrl
 *
 *
 * @since 18.0.0
 */
interface IUrl {
	/**
	 * returns a URL that is related to the entity, e.g. the link to a share
	 *
	 * @since 18.0.0
	 */
	public function getUrl(): string;
}
