<?php
/**

 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
 *
 */
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
	<div>
		<label>
			<span><?php p($l->t('Name')) ?></span>
			<input id="theming-name" type="text" placeholder="<?php p($l->t('Name')); ?>" value="<?php p($_['name']) ?>" maxlength="250" />
			<div data-setting="name" data-toggle="tooltip" data-original-title="<?php p($l->t('reset to default')); ?>" class="theme-undo icon icon-history"></div>
		</label>
	</div>
	<div>
		<label>
			<span><?php p($l->t('Web address')) ?></span>
			<input id="theming-url" type="text" placeholder="<?php p($l->t('Web address https://…')); ?>" value="<?php p($_['url']) ?>" maxlength="500" />
			<div data-setting="url" data-toggle="tooltip" data-original-title="<?php p($l->t('reset to default')); ?>" class="theme-undo icon icon-history"></div>
		</label>
	</div>
	<div>
		<label>
			<span><?php p($l->t('Slogan')) ?></span>
			<input id="theming-slogan" type="text" placeholder="<?php p($l->t('Slogan')); ?>" value="<?php p($_['slogan']) ?>" maxlength="500" />
			<div data-setting="slogan" data-toggle="tooltip" data-original-title="<?php p($l->t('reset to default')); ?>" class="theme-undo icon icon-history"></div>
		</label>
	</div>
	<div>
		<label>
			<span><?php p($l->t('Color')) ?></span>
			<input id="theming-color" type="text" class="jscolor" maxlength="6" value="<?php p($_['color']) ?>" />
			<div data-setting="color" data-toggle="tooltip" data-original-title="<?php p($l->t('reset to default')); ?>" class="theme-undo icon icon-history"></div>
		</label>
	</div>
	<div>
		<form class="uploadButton inlineblock" method="post" action="<?php p($_['uploadLogoRoute']) ?>">
			<input type="hidden" id="current-logoMime" name="current-logoMime" value="<?php p($_['logoMime']); ?>" />
			<label for="uploadlogo"><span><?php p($l->t('Logo')) ?></span></label>
			<input id="uploadlogo" class="upload-logo-field" name="uploadlogo" type="file" />
			<label for="uploadlogo" class="button icon-upload svg" id="uploadlogo" title="<?php p($l->t('Upload new logo')) ?>"></label>
			<div data-setting="logoMime" data-toggle="tooltip" data-original-title="<?php p($l->t('reset to default')); ?>" class="theme-undo icon icon-history"></div>
		</form>
	</div>
	<div>
		<form class="uploadButton inlineblock" method="post" action="<?php p($_['uploadLogoRoute']) ?>">
			<input type="hidden" id="current-backgroundMime" name="current-backgroundMime" value="<?php p($_['backgroundMime']); ?>" />
			<label for="upload-login-background"><span><?php p($l->t('Log in image')) ?></span></label>
			<input id="upload-login-background" class="upload-logo-field" name="upload-login-background" type="file">
			<label for="upload-login-background" class="button icon-upload svg" id="upload-login-background" title="<?php p($l->t("Upload new login background")) ?>"></label>
			<div data-setting="backgroundMime" data-toggle="tooltip" data-original-title="<?php p($l->t('reset to default')); ?>" class="theme-undo icon icon-history"></div>
		</form>
	</div>

	<div id="theming-preview" style="background-color:<?php p($_['color']);?>; background-image:url(<?php p($_['background']); ?>);">
		<img src="<?php p($_['logo']); ?>" id="theming-preview-logo" />
	</div>
	<?php } ?>
</div>
