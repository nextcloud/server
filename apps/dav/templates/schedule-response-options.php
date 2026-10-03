<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
\OCP\Util::addStyle('dav', 'schedule-response');
$preselect = $_['preselect'] ?? 'ACCEPTED';
$formAction = $_['formAction'];
?>

<div class="guest-box">
	<form action="<?php p($formAction); ?>" method="post">
		<fieldset id="partStat">
			<h2><?php p($l->t('Are you accepting the invitation?')); ?></h2>
			<div id="selectPartStatForm">
				<input type="radio" id="partStatAccept" name="partStat" value="ACCEPTED" <?php if ($preselect === 'ACCEPTED') {
					echo 'checked';
				} ?> />
				<label for="partStatAccept">
					<span><?php p($l->t('Accept')); ?></span>
				</label>

				<input type="radio" id="partStatTentative" name="partStat" value="TENTATIVE" <?php if ($preselect === 'TENTATIVE') {
					echo 'checked';
				} ?> />
				<label for="partStatTentative">
					<span><?php p($l->t('Tentative')); ?></span>
				</label>

				<input type="radio" class="declined" id="partStatDeclined" name="partStat" value="DECLINED" <?php if ($preselect === 'DECLINED') {
					echo 'checked';
				} ?> />
				<label for="partStatDeclined">
					<span><?php p($l->t('Decline')); ?></span>
				</label>
			</div>
		</fieldset>
		<fieldset>
			<input type="submit" value="<?php p($l->t('Save'));?>">
		</fieldset>
	</form>
</div>
