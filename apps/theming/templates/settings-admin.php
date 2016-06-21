<?php
/** @var array $_ */
/** @var OC_L10N $l */
script('theming', 'settings-admin');
script('theming', '3rdparty/jscolor/jscolor');
style('theming', 'settings-admin');
?>
<div id="theming" class="section">
	<h2 class="inlineblock"><?php p($l->t('Theming')); ?></h2>
		<div id="theming_settings_msg" class="msg success inlineblock" style="display: none;">Saved</div>
	<?php if ($_['themable'] === false) { ?>
	<p>
		<?php p($_['errorMessage']) ?>
	</p>
	<?php } else { ?>
	<p>
		<span class="theming-label"><?php p($l->t('Name:')) ?></span> <input id="theming-name" type="text" placeholder="<?php p($l->t('Name')); ?>" value="<?php p($_['name']) ?>" />
		<span data-setting="name" data-original-title="<?php p($l->t('revert to original value')); ?>" class="theme-undo icon icon-history"></span>
	</p>
	<p>
		<span class="theming-label"><?php p($l->t('URL:')) ?></span> <input id="theming-url" type="text" placeholder="<?php p($l->t('Web address https://…')); ?>" value="<?php p($_['url']) ?>" />
		<span data-setting="url" data-original-title="<?php p($l->t('revert to original value')); ?>" class="theme-undo icon icon-history"></span>
	</p>
	<p>
		<span class="theming-label"><?php p($l->t('Slogan:')) ?></span> <input id="theming-slogan" type="text" placeholder="<?php p($l->t('Slogan')); ?>" value="<?php p($_['slogan']) ?>" />
		<span data-setting="slogan" data-original-title="<?php p($l->t('revert to original value')); ?>" class="theme-undo icon icon-history"></span>
	</p>
	<p>
		<span class="theming-label"><?php p($l->t('Color:')) ?></span> <input id="theming-color" class="jscolor" value="<?php p($_['color']) ?>" />
		<span data-setting="color" data-original-title="<?php p($l->t('revert to original value')); ?>" class="theme-undo icon icon-history"></span>
	</p>
	<p>
		<form class="uploadButton" method="post" action="<?php p($_['uploadLogoRoute']) ?>">
			<span class="theming-label"><?php p($l->t('Logo:')) ?></span>
			<input id="uploadlogo" class="upload-logo-field" name="uploadlogo" type="file">
			<label for="uploadlogo" class="button icon-upload svg" id="uploadlogo" title="<?php p($l->t('Upload new logo')) ?>"></label>
			<span data-setting="logoMime" data-original-title="<?php p($l->t('revert to original value')); ?>" class="theme-undo icon icon-history"></span>
		</form>
	</p>
		<p>
		<form class="uploadButton" method="post" action="<?php p($_['uploadLogoRoute']) ?>">
			<span class="theming-label"><?php p($l->t('Login img.:')) ?></span>
			<input id="upload-login-background" class="upload-logo-field" name="upload-login-background" type="file">
			<label for="upload-login-background" class="button icon-upload svg" id="upload-login-background" title="<?php p($l->t("Upload new login background")) ?>"></label>
			<span data-setting="backgroundMime" data-original-title="<?php p($l->t('revert to original value')); ?>" class="theme-undo icon icon-history"></span>
		</form>
		</p>
	<?php } ?>
</div>
