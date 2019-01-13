<?php
/**
 * @copyright Copyright (c) 2018 Michael Weimann <mail@michael-weimann.eu>
 *
 * @author Michael Weimann <mail@michael-weimann.eu>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * This file contains the server info settings template.
 */

/** @var array $_ */

?>

<div class="section server-info-settings">
	<h2><?php p($l->t('Server info')); ?></h2>
	<p class="settings-hint">
		<?php p($l->t('Enter common info about your Nextcloud instance here. These info are visible to all users.')) ?>
	</p>
	<form id="server-info-form" name="server-info-form">
		<div class="margin-bottom">
			<label class="label" for="location"><?php p($l->t('Server location')); ?></label>
			<input
				class="form-input"
				id="location"
				name="location"
				type="text"
				maxlength="100"
				value="<?php p($_['location']); ?>"
				placeholder="<?php p($l->t('country')); ?>">
		</div>
		<div>
			<label class="label" for="provider"><?php p($l->t('Service provider')); ?></label>
			<input
				class="form-input"
				id="provider"
				name="provider"
				type="text"
				maxlength="100"
				value="<?php p($_['provider']); ?>"
				placeholder="<?php p($l->t('company or person')); ?>">
		</div>
		<div>
			<label class="label" for="providerWebsite"><?php p($l->t('Provider website')); ?></label>
			<input
				class="form-input"
				id="providerWebsite"
				name="providerWebsite"
				type="url"
				maxlength="200"
				value="<?php p($_['providerWebsite']); ?>"
				placeholder="<?php p($l->t('link to website')); ?>">
		</div>
		<div class="margin-bottom">
			<label class="label" for="providerPrivacyLink"><?php p($l->t('Link to privacy policy')); ?></label>
			<input
				class="form-input"
				id="providerPrivacyLink"
				name="providerPrivacyLink"
				type="url"
				maxlength="200"
				value="<?php p($_['providerPrivacyLink']); ?>"
				placeholder="<?php p($l->t('link to privacy policy')); ?>">
		</div>
		<div class="margin-bottom">
			<label class="label" for="adminContact"><?php p($l->t('Admin contact')); ?></label>
			<select class="form-input" name="adminContact" id="adminContact">
				<option value=""><?php p($l->t('choose admin contact')); ?></option>
				<?php foreach($_['adminUsers'] as $adminUser): ?>
					<option
						value="<?php p($adminUser['id']); ?>"
						<?php if ($adminUser['id'] === $_['adminContact']): ?>selected="selected"<?php endif; ?>>
						<?php p($adminUser['displayName']); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="form-actions">
			<button id="server-info-submit-button" class="button">
				<span class="default-label">
					<?php p($l->t('save')); ?>
				</span>
				<span class="working-label">
					<span class="icon-loading-small-dark"></span>
					<?php p($l->t('saving')); ?>
				</span>
				<span class="success-label">
					<span class="icon-checkmark-white"></span>
					<?php p($l->t('saved')); ?>
				</span>
				<span class="error-label">
					<span class="icon-error-white"></span>
					<?php p($l->t('error saving settings')); ?>
				</span>
			</button>
		</div>
	</form>
</div>
