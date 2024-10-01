<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2012-2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

require_once __DIR__ . '/print_xml_exception.php';

print_unescaped('<?xml version="1.0" encoding="utf-8"?>' . "\n");
?>
<d:error xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
	<s:exception><?php p($l->t('Internal Server Error')) ?></s:exception>
	<s:message>
		<?php p($l->t('The server was unable to complete your request.')) ?>
		<?php p($l->t('If this happens again, please send the technical details below to the server administrator.')) ?>
		<?php p($l->t('More details can be found in the server log.')) ?>
		<?php if (isset($_['serverLogsDocumentation']) && $_['serverLogsDocumentation'] !== ''): ?>
			<?php p($l->t('For more details see the documentation ↗.'))?>: <?php print_unescaped($_['serverLogsDocumentation']) ?>
		<?php endif; ?>
	</s:message>

	<s:technical-details>
		<s:remote-address><?php p($_['remoteAddr']) ?></s:remote-address>
		<s:request-id><?php p($_['requestID']) ?></s:request-id>

	<?php if (isset($_['debugMode']) && $_['debugMode'] === true): ?>
		<s:type><?php p($_['errorClass']) ?></s:type>
		<s:code><?php p($_['errorCode']) ?></s:code>
		<s:message><?php p($_['errorMsg']) ?></s:message>
		<s:file><?php p($_['file']) ?></s:file>
		<s:line><?php p($_['line']) ?></s:line>

		<s:stacktrace>
			<?php print_exception($_['exception'], $l); ?>
		</s:stacktrace>
	<?php endif; ?>
	</s:technical-details>
</d:error>
