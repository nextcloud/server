<form id="quota">
	<fieldset>
		<legend>Account information</legend>
		<div id="quota_indicator"><div style="width:<?php echo $_['usage_relative'] ?>%;">&nbsp;</div></div>
		<p>You're currently using <?php echo $_['usage_relative'] ?>% (<?php echo $_['usage'] ?>) of your <?php echo $_['total_space'] ?> space.</p>
	</fieldset>
</form>

<form id="user_settings">
	<fieldset>
	<legend>User settings</legend>
	<p>
		<label for="email">Email :</label>
		<input type="text" id="email" name="email" value="user@example.net" />
	</p>
	<p>
		<label for="pass1">New password :</label>
		<input type="password" id="pass1" name="pass1" /> 
	</p>
	<p>
		<label for="pass2">Confirm new password :</label>
		<input type="password" id="pass2" name="pass2" /> 
	</p>
	<p class="form_footer">
		<input type="submit" value="Save" />
	</p>
	</fieldset>
</form>
