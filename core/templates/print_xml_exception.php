<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2012-2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

function print_exception(Throwable $e, \OCP\IL10N $l): void {
	p($e->getTraceAsString());

	if ($e->getPrevious() !== null) {
		print_unescaped('<s:previous-exception>');
		print_exception($e->getPrevious(), $l);
		print_unescaped('</s:previous-exception>');
	}
}
