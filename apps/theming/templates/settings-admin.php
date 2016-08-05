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
		<label><span><?php p($l->t('Name')) ?></span>
			<input id="theming-name" type="text" placeholder="<?php p($l->t('Name')); ?>" value="<?php p($_['name']) ?>" maxlength="250" />
		</label>
		<span data-setting="name" data-toggle="tooltip" data-original-title="<?php p($l->t('reset to default')); ?>" class="theme-undo icon icon-history"></span>
	</p>
	<p>
		<label><span><?php p($l->t('Web address')) ?></span>
			<input id="theming-url" type="text" placeholder="<?php p($l->t('Web address https://…')); ?>" value="<?php p($_['url']) ?>" maxlength="500" />
		</label>
		<span data-setting="url" data-toggle="tooltip" data-original-title="<?php p($l->t('reset to default')); ?>" class="theme-undo icon icon-history"></span>
	</p>
	<p>
		<label><span><?php p($l->t('Slogan')) ?></span>
			<input id="theming-slogan" type="text" placeholder="<?php p($l->t('Slogan')); ?>" value="<?php p($_['slogan']) ?>" maxlength="500" />
		</label>
		<span data-setting="slogan" data-toggle="tooltip" data-original-title="<?php p($l->t('reset to default')); ?>" class="theme-undo icon icon-history"></span>
	</p>
	<p>
		<label><span><?php p($l->t('Color')) ?></span>
			<input id="theming-color" type="text" class="jscolor" maxlength="6" value="<?php p($_['color']) ?>" />
		</label>
		<span data-setting="color" data-toggle="tooltip" data-original-title="<?php p($l->t('reset to default')); ?>" class="theme-undo icon icon-history"></span>
	</p>
	<p>
		<form class="uploadButton" method="post" action="<?php p($_['uploadLogoRoute']) ?>">
			<label for="uploadlogo"><span><?php p($l->t('Logo')) ?></span></label>
			<input id="uploadlogo" class="upload-logo-field" name="uploadlogo" type="file">
			<label for="uploadlogo" class="button icon-upload svg" id="uploadlogo" title="<?php p($l->t('Upload new logo')) ?>"></label>
			<span data-setting="logoMime" data-toggle="tooltip" data-original-title="<?php p($l->t('reset to default')); ?>" class="theme-undo icon icon-history"></span>
		</form>
	</p>
	<p>
		<form class="uploadButton" method="post" action="<?php p($_['uploadLogoRoute']) ?>">
			<label for="upload-login-background"><span><?php p($l->t('Log in image')) ?></span></label>
			<input id="upload-login-background" class="upload-logo-field" name="upload-login-background" type="file">
			<label for="upload-login-background" class="button icon-upload svg" id="upload-login-background" title="<?php p($l->t("Upload new login background")) ?>"></label>
			<span data-setting="backgroundMime" data-toggle="tooltip" data-original-title="<?php p($l->t('reset to default')); ?>" class="theme-undo icon icon-history"></span>
		</form>
		</p>
	<?php } ?>
</div>
