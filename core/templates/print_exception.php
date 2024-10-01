<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2012-2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

function print_exception(Throwable $e, \OCP\IL10N $l): void {
	print_unescaped('<pre>');
	p($e->getTraceAsString());
	print_unescaped('</pre>');

	if ($e->getPrevious() !== null) {
		print_unescaped('<br />');
		print_unescaped('<h4>');
		p($l->t('Previous'));
		print_unescaped('</h4>');

		print_exception($e->getPrevious(), $l);
	}
}
