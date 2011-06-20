<form id="quota">
	<fieldset>
		<legend>Account information</legend>
		<div id="quota_indicator"><div style="width:<?php echo $_['usage_relative'] ?>%;">&nbsp;</div></div>
		<p>You're currently using <?php echo $_['usage_relative'] ?>% (<?php echo $_['usage'] ?>) of your <?php echo $_['total_space'] ?> space.</p>
	</fieldset>
</form>

<form id="passwordform">
	<fieldset>
		<legend>Change Password</legend>
		<div id="passwordchanged">You're password got changed</div>
		<div id="passworderror"></div>
		<p>
			<label for="pass1">Old password:</label>
			<input type="password" id="pass1" name="oldpassword" />
		</p>
		<p>
			<label for="pass2">New password :</label>
			<input type="password" id="pass2" name="password" />
		</p>
		<p>
			<input type="checkbox" id="show" name="show" />
			<label for="show">Show new password</label>
		</p>
		<p class="form_footer">
			<input id="passwordbutton" class="prettybutton" type="submit" value="Save" />
		</p>
	</fieldset>
</form>

<form id="languageform">
	<fieldset>
		<legend>Language</legend>
		<label for=''></label>
		<select id="languageinput" name='lang'>
			<?php foreach($_['languages'] as $language):?>
				<option value='<?php echo $language;?>'><?php echo $language;?></option>
			<?php endforeach;?>
		</select>
	</fieldset>
</form>
