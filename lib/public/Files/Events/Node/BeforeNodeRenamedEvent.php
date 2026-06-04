<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Events\Node;

use OCP\Exceptions\AbortedEventException;

/**
 * @since 20.0.0
 */
class BeforeNodeRenamedEvent extends AbstractNodesEvent {
	/**
	 * @since 28.0.0
	 * @deprecated 29.0.0 - use OCP\Exceptions\AbortedEventException instead
	 */
	public function abortOperation(?\Throwable $ex = null) {
		throw new AbortedEventException($ex?->getMessage() ?? 'Operation aborted');
	}
}
