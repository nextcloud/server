<?php ?>

<div class="section server-info-settings">
	<h2><?php p($l->t('Server info')); ?></h2>
	<p class="settings-hint">
		<?php p($l->t('Enter common info about your Nextcloud instance here. These info are visible to all users.')) ?>
	</p>
	<form>
		<div class="margin-bottom">
			<label class="label" for="location"><?php p($l->t('Server location')); ?></label>
			<input
				class="form-input"
				id="location"
				name="location"
				type="text"
				maxlength="100"
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
				placeholder="<?php p($l->t('company or person')); ?>">
		</div>
		<div>
			<label class="label" for="providerWebsite"><?php p($l->t('Website')); ?></label>
			<input
				class="form-input"
				id="providerWebsite"
				name="providerWebsite"
				type="url"
				maxlength="200"
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
				placeholder="<?php p($l->t('link to privacy policy')); ?>">
		</div>
		<div class="margin-bottom">
			<label class="label" for="admin"><?php p($l->t('Admin contact')); ?></label>
			<select class="form-input" name="admin">
				<option>Michael Weimann</option>
				<option>Max Mustermann</option>
				<option>Peter Petrowski</option>
			</select>
		</div>
		<div class="form-actions">
			<button id="test123" class="button">
				<span class="default-label">
					<?php p($l->t('Save')); ?>
				</span>
				<span class="working-label">
					<span class="icon-loading-small-dark"></span>
					<?php p($l->t('saving…')); ?>
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
			<script>
				const button = $('#test123');
				button.on('click', (event) => {
					event.stopImmediatePropagation();
					event.preventDefault();
					button.prop('disabled', true);
					button.addClass('button-working');
					setTimeout(() => {
						button.removeClass('button-working');
						button.addClass('button-success');
					}, 1500);
				});
			</script>
		</div>
	</form>
</div>
