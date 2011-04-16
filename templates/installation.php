<?php
/*
 * Template for installation page
 */
?>
<div id="login">
	<img src="<?php echo image_path("", "owncloud-logo-medium-white.png"); ?>" alt="ownCloud" />
	<form action="" method="post">
		<input type='hidden' name='install' value='true'/>
		<fieldset>
			<p><input type="text" name="login" value="username" /></p>
			<p><input type="password" name="pass" value="password" /></p>
        </fieldset>
		<fieldset>
			<?php if(!$_['hasSQLite']): ?>
				<legend><abbr title="to use SQLite instead, install it on your server">MySQL</abbr> Database</legend>
				<p><input type="text" name="dbuser" value="admin / username" /></p>
				<p><input type="password" name="dbpass" value="password" /></p>
				<p><input type="text" name="dbname" value="database name" /></p>
			<?php endif;?>
		</fieldset>
		<fieldset id="advanced">
			<legend><a id="advanced_options_link" href="">Advanced ▾</a></legend>
			<div id="advanced_options">
				<p><label class="left">Data directory</label></p><p><input type="text" name="directory" value="<?php echo($_['datadir']);?>" /></p>
				<?php if($_['hasMySQL'] and $_['hasSQLite']): ?>
					<p><label class="left">Database</label></p>
					<p><input type="radio" name="dbtype" value='sqlite' id="sqlite" checked="checked" /><label for="sqlite">SQLite</label>
					<input type="radio" name="dbtype" value='mysql' id="mysql"><label for="mysql">MySQL</label></p>
					<div id="use_mysql">
						<p><input type="text" name="dbuser" value="admin / username" /></p>
						<p><input type="password" name="dbpass" value="password" /></p>
						<p><input type="text" name="dbname" value="database name" /></p>
				<?php endif;?>
				<?php if($_['hasMySQL'] and !$_['hasSQLite']): ?>
					<input type='hidden' name='dbtype' value='mysql'/>
				<?php endif;?>
				<?php if(!$_['hasMySQL'] and $_['hasSQLite']): ?>
					<input type='hidden' name='dbtype' value='sqlite'/>
				<?php endif;?>
				<?php if($_['hasMySQL']): ?>
						<p><label class="left">Host</label></p><p><input type="text" name="dbhost" value="localhost" /></p>
						<p><label class="left">Table prefix</label></p><p><input type="text" name="dbtableprefix" value="oc_" /></p>
					</div>
				<?php endif;?>
			</div>
		</fieldset>
		<fieldset>
			<p class="submit"><input type="submit" value="Create" /></p>
		</fieldset>
	</form>
</div>