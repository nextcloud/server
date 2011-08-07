<form id="quota">
	<fieldset>
		<legend><?php echo $l->t( 'Account information' );?></legend>
		<div id="quota_indicator"><div style="width:<?php echo $_['usage_relative'] ?>%;">&nbsp;</div></div>
		<p><?php echo $l->t( 'You\'re currently using' );?> <?php echo $_['usage_relative'] ?>% (<?php echo $_['usage'] ?>) <?php echo $l->t( 'of your' );?> <?php echo $_['total_space'] ?> <?php echo $l->t( 'space' );?>.</p>
	</fieldset>
</form>

<form id="passwordform">
	<fieldset>
		<legend><?php echo $l->t( 'Change Password' );?></legend>
		<div id="passwordchanged"><?php echo $l->t( 'Your password got changed');?></div>
		<div id="passworderror"></div>
		<p>
			<label for="pass1"><?php echo $l->t( 'Old password:' );?></label>
			<input type="password" id="pass1" name="oldpassword" />
		</p>
		<p>
			<label for="pass2"><?php echo $l->t( 'New password' );?></label>
			<input type="password" id="pass2" name="password" />
		</p>
		<p>
			<input type="checkbox" id="show" name="show" />
			<label for="show"><?php echo $l->t( 'Show new password' );?></label>
		</p>
		<p class="form_footer">
			<input id="passwordbutton" class="prettybutton" type="submit" value="Save" />
		</p>
	</fieldset>
</form>

<?php if($_['hasopenid']):?>
	<form id="openidform">
		<fieldset>
			<legend><?php echo $l->t( 'OpenID' );?></legend>
			<p>OpenID identity for <b><?php echo OC_User::getUser();?></b></p>
			<p><input name='identity' id='identity' value='<?php echo $_['identity']; ?>'></input></p>
			<p><input type='submit' value='Save'></input></p>
		</fieldset>
	</form>
<?php endif;?>

<form id="languageform">
	<fieldset>
		<legend><?php echo $l->t( 'Language' );?></legend>
		<label for=''></label>
		<select id="languageinput" name='lang'>
			<?php foreach($_['languages'] as $language):?>
				<option value='<?php echo $language;?>'><?php echo $language;?></option>
			<?php endforeach;?>
		</select>
	</fieldset>
</form>
