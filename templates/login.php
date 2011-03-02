<?php
/*
 * Template for login page
 */
?>
<div id="login">
	<img src="<? echo image_path( "", "owncloud-logo-medium-white.png" ) ?>" alt="ownCloud" />
	<form action="index.php" method="post">
		<!-- <h1>Sign in :</h1> -->
		<fieldset>
			<? if( $_["error"] ){ ?>
				Login failed!
			<? } ?>
			<p><input type="text" name="user" value="" /></p>
			<p><input type="password" name="password" /></p>
			<p><input type="submit" value="Sign in" /></p>
		</fieldset>
	</form>
</div>

