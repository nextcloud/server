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
		<input type="password" id="pass1" name="oldpassword" placeholder="<?php echo $l->t( 'Old password' );?>" />
		<input type="password" id="pass2" name="password" placeholder="<?php echo $l->t( 'New password' );?>" data-typetoggle="#show" />
		<input type="checkbox" id="show" name="show" /><label for="show"><?php echo $l->t( 'show' );?></label>
		<input id="passwordbutton" type="submit" value="Change password" />
	</fieldset>
</form>

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

<?php foreach($_['forms'] as $form){
	echo $form;
};?>
