<?php
declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

style('core', 'login/authpicker');

/** @var array $_ */
?>

<div class="picker-window">
	<h2><?php p($l->t('Account connected')) ?></h2>
	<p class="info">
		<?php p($l->t('Your client should now be connected!')) ?><br/>
		<?php p($l->t('You can close this window.')) ?>
	</p>

	<br/>
</div>
