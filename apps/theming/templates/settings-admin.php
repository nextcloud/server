<?php
/** @var array $_ */
/** @var OC_L10N $l */
script('theming', 'settings-admin');
script('theming', '3rdparty/jscolor/jscolor');
style('theming', 'settings-admin');
?>
<div id="theming" class="section">
	<h2><?php p($l->t('Theming')); ?></h2>
	<?php if ($_['themable'] === false) { ?>
	<p>
		<?php p($_['errorMessage']) ?>
	</p>
	<?php } else { ?>
	<p>
		<span class="theming-label">Name:</span> <input id="theming-name" type="text" placeholder="<?php p($l->t('Name')); ?>" value="<?php p($_['name']) ?>"></input>
		<span data-setting="name" data-original-title="<?php p($l->t('revert to original value')); ?>" class="theme-undo icon icon-history"></span>
	</p>
	<p>
		<span class="theming-label">URL:</span> <input id="theming-url"type="text" placeholder="<?php p($l->t('Web address https://…')); ?>" value="<?php p($_['url']) ?>"></input>
		<span data-setting="url" data-original-title="<?php p($l->t('revert to original value')); ?>" class="theme-undo icon icon-history"></span>
	</p>
	<p>
		<span class="theming-label">Slogan:</span> <input id="theming-slogan" type="text" placeholder="<?php p($l->t('Slogan')); ?>" value="<?php p($_['slogan']) ?>"></input>
		<span data-setting="slogan" data-original-title="<?php p($l->t('revert to original value')); ?>" class="theme-undo icon icon-history"></span>
	</p>
	<p>
		<span class="theming-label">Color:</span> <input id="theming-color" class="jscolor" value="<?php p($_['color']) ?>"></input>
		<span data-setting="color" data-original-title="<?php p($l->t('revert to original value')); ?>" class="theme-undo icon icon-history"></span>
	</p>
		<p>
		<form class="uploadButton" method="post" action="<?php p($_['uploadLogoRoute']) ?>">
			<span class="theming-label">Logo:</span>
			<input id="uploadlogo" class="upload-logo-field" name="uploadlogo" type="file">
			<label for="uploadlogo" class="button icon-upload svg" id="uploadlogo" title="Upload new logo"></label>
			<span data-setting="logoName" data-original-title="<?php p($l->t('revert to original value')); ?>" class="theme-undo icon icon-history"></span>
		</form>
		</p>
	<?php } ?>
</div>
