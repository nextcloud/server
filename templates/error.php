<?php
/*
 * Template for error page
 */
?>
<div id="login">
	<img src="<?php echo image_path("", "owncloud-logo-medium-white.png"); ?>" alt="ownCloud" />
	<br/><br/><br/><br/>
	<ul>
		<?php foreach($_["errors"] as $error):?>
			<li><?php echo $error ?></li>
		<?php endforeach ?>
	</ul>
</div>

