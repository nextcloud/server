<div id="quota" class="personalblock"><div style="width:<?php echo $_['usage_relative'] ?>%;">
	<p><?php echo $l->t('You use');?> <strong><?php echo $_['usage'] ?></strong> <?php echo $l->t('of the available');?> <?php echo $_['total_space'] ?></p>
</div></div>

<form id="passwordform">
	<fieldset class="personalblock">
		<div id="passwordchanged"><?php echo $l->t('Your password got changed');?></div>
		<div id="passworderror"></div>
		<input type="password" id="pass1" name="oldpassword" placeholder="<?php echo $l->t('Current password');?>" />
		<input type="password" id="pass2" name="password" placeholder="<?php echo $l->t('New password');?>" data-typetoggle="#show" />
		<input type="checkbox" id="show" name="show" /><label for="show"><?php echo $l->t('show');?></label>
		<input id="passwordbutton" type="submit" value="<?php echo $l->t('Change password');?>" />
	</fieldset>
</form>

<form>
	<fieldset class="personalblock">
		<strong><label for="languageinput"><?php echo $l->t('Language');?></label></strong>
		<select id="languageinput" name='lang'>
		<?php foreach($_['languages'] as $language):?>
			<option value="<?php echo $language;?>"><?php echo $language;?></option>
		<?php endforeach;?>
		</select>
		<a href="https://www.transifex.net/projects/p/owncloud/"><?php echo $l->t('Help translating');?></a>
	</fieldset>
</form>

<p class="personalblock">
	<strong>WebDAV</strong> <?php echo ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on') ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$WEBROOT.'/files/webdav.php'; ?>
</p>

<?php foreach($_['forms'] as $form){
	echo $form;
};?>
