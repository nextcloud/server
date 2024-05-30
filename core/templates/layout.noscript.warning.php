<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
?>
<noscript>
	<div id="nojavascript">
		<div>
			<?php print_unescaped(str_replace(
				['{linkstart}', '{linkend}'],
				['<a href="https://www.enable-javascript.com/" target="_blank" rel="noreferrer noopener">', '</a>'],
				$l->t('This application requires JavaScript for correct operation. Please {linkstart}enable JavaScript{linkend} and reload the page.')
			)); ?>
		</div>
	</div>
</noscript>
