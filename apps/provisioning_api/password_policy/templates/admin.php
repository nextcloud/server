<?php
/**

 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

style('password_policy', 'styles');

?>

<div class="section" id="password-policy">
	<form id="password_policy" action="#" method="post">
		<h2><?php p($l->t('Share link password policy'));?></h2>

		<br>
		<p><?php p($l->t('Passwords should have at least:'));?></p>

		<ul>
			<li><label><input type="checkbox" name="spv_min_chars_checked"
						<?php if ($_['spv_min_chars_checked']): ?> checked="checked"<?php endif; ?>>
					<input type="number" name="spv_min_chars_value" min="0" max="255" value="<?php p($_['spv_min_chars_value']) ?>"> <?php p($l->t('minimum characters'));?></label></li>
			<li><label><input type="checkbox" name="spv_uppercase_checked"
						<?php if ($_['spv_uppercase_checked']): ?> checked="checked"<?php endif; ?>>
					<input type="number" name="spv_uppercase_value" min="0" max="255" value="<?php p($_['spv_uppercase_value']) ?>"> <?php p($l->t('uppercase letters'));?></label></li>
			<li><label><input type="checkbox" name="spv_numbers_checked"
						<?php if ($_['spv_numbers_checked']): ?> checked="checked"<?php endif; ?>>
					<input type="number" name="spv_numbers_value" min="0" max="255" value="<?php p($_['spv_numbers_value']) ?>"> <?php p($l->t('numbers'));?></label></li>
			<li><label><input type="checkbox" name="spv_special_chars_checked"
						<?php if ($_['spv_special_chars_checked']): ?> checked="checked"<?php endif; ?>>
					<input type="number" name="spv_special_chars_value" min="0" max="255" value="<?php p($_['spv_special_chars_value']) ?>"> <?php p($l->t('special characters'));?></label></li>

			<li class="indented"><label><input type="checkbox" name="spv_def_special_chars_checked"
						<?php if ($_['spv_def_special_chars_checked']): ?> checked="checked"<?php endif; ?>> <?php p($l->t('Define special characters'));?></label>
				<input type="text" name="spv_def_special_chars_value" value="<?php p($_['spv_def_special_chars_value']) ?>" placeholder="Separated by space or comma"></li>
		</ul>
		<input type="hidden" name="app" value="oca-password-policy" />

		<br>
		<p><?php p($l->t('Link expiration:'));?></p>

		<ul>
			<li><label><input type="checkbox" name="spv_expiration_password_checked"
					<?php if ($_['spv_expiration_password_checked']): ?> checked="checked"<?php endif; ?>>
				<input type="number" name="spv_expiration_password_value"  min="0" max="255" value="<?php p($_['spv_expiration_password_value']) ?>" placeholder="7">
				<?php p($l->t('days to expire link if password is set'));?></label>
			</li>
			<li><label><input type="checkbox" name="spv_expiration_nopassword_checked"
					<?php if ($_['spv_expiration_nopassword_checked']): ?> checked="checked"<?php endif; ?>>
				<input type="number" name="spv_expiration_nopassword_value"  min="0" max="255" value="<?php p($_['spv_expiration_nopassword_value']) ?>" placeholder="7">
				<?php p($l->t('days to expire link if password is not set'));?></label>
			</li>
		</ul>
		<br>

		<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>" id="requesttoken">
		<input type="submit" value="Save" />
	</form>
</div>
