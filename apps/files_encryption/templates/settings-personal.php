<form id="encryption">
<fieldset class="personalblock">

<strong>Choose encryption mode:</strong>

<p>
	<input type="radio" name="encryption_mode" value="client" style="width:20px;" <?php if ($_['encryption_mode'] == 'client') echo "checked='checked'"?>/> Client side encryption (most secure but makes it impossible to access your data from the web interface)<br />
	<input type="radio" name="encryption_mode" value="server" style="width:20px;" <?php if ($_['encryption_mode'] == 'server') echo "checked='checked'"?> /> Server side encryption (allows you to access your files from the web interface and the desktop client)<br />
	<input type="radio" name="encryption_mode" value="none" style="width:20px;" <?php if ($_['encryption_mode'] == 'none') echo "checked='checked'"?>/> None (no encryption at all)<br/>
	</p>	
	</fieldset>
</form>

