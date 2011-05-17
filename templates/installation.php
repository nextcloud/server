<div id="login">
	<img src="<?php echo image_path('', 'owncloud-logo-medium-white.png'); ?>" alt="ownCloud" />
	<form action="index.php" method="post" id="setup_form">
		<input type="hidden" name="install" value="true" />
		<p class="intro">
			Welcome to <strong>ownCloud</strong>, your personnal cloud.<br />
			To finish the installation, please follow the 3 remaining steps below.
		</p>

		<?php if(count($_['errors']) > 0): ?>
		<ul class="errors">
			<?php foreach($_['errors'] as $err): ?>
			<li><?php print $err; ?></li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>

		<fieldset>
			<legend><strong>STEP 1</strong> : Create an <strong>admin account.</strong></legend>
			<p><label for="adminlogin">Login :</label><input type="text" name="adminlogin" id="adminlogin" value="<?php print OC_HELPER::init_var('adminlogin'); ?>" /></p>
			<p><label for="adminpass">Password :</label><input type="password" name="adminpass" id="adminpass" value="<?php print OC_HELPER::init_var('adminpass'); ?>" /></p>
        </fieldset>
        
        <fieldset>
			<legend><strong>STEP 2</strong> : Set where to store the data.</legend>
			<p><label for="directory">Data directory :</label><input type="text" name="directory" id="directory" value="<?php print OC_HELPER::init_var('directory', $_['directory']); ?>" /></p>
		</fieldset>
		
		<fieldset>
			<legend><strong>STEP 3</strong> : Configure your database.</legend>
			<?php if($_['hasSQLite']): ?>
			<?php if(!$_['hasMySQL']): ?>
			<p>I will use a SQLite database. You have nothing to do !</p>
			<input type="hidden" id="dbtype" name="dbtype" value="sqlite" />
			<?php else: ?>
			<p><label class="sqlite" for="sqlite">SQLite </label><input type="radio" name="dbtype" value='sqlite' id="sqlite" <?php OC_HELPER::init_radio('dbtype', 'sqlite', 'sqlite'); ?>/></p>
			<?php endif; ?>
			<?php endif; ?>

			<?php if($_['hasMySQL']): ?>
			<?php if(!$_['hasSQLite']): ?>
			<p>I will use a MySQL database.</p>
			<input type="hidden" id="dbtype" name="dbtype" value="mysql" />
			<?php else: ?>
			<p><label class="mysql" for="mysql">MySQL </label><input type="radio" name="dbtype" value='mysql' id="mysql" <?php OC_HELPER::init_radio('dbtype', 'mysql', 'sqlite'); ?>/></p>
			<?php endif; ?>
			<div id="use_mysql">
				<p><label for="dbhost">Host :</label><input type="text" name="dbhost" id="dbhost" value="<?php print OC_HELPER::init_var('dbhost', 'localhost'); ?>" /></p>
				<p><label for="dbname">Database name :</label><input type="text" name="dbname" id="dbname" value="<?php print OC_HELPER::init_var('dbname'); ?>" /></p>
				<p><label for="dbtableprefix">Table prefix :</label><input type="text" name="dbtableprefix" id="dbtableprefix" value="<?php print OC_HELPER::init_var('dbtableprefix', 'oc_'); ?>" /></p>
				<p><label for="dbuser">MySQL user login :</label><input type="text" name="dbuser" id="dbuser" value="<?php print OC_HELPER::init_var('dbuser'); ?>" /></p>
				<p><label for="dbpass">MySQL user password :</label><input type="password" name="dbpass" id="dbpass" value="<?php print OC_HELPER::init_var('dbpass'); ?>" /></p>
			</div>
			<?php endif; ?>
		</fieldset>

		<p class="submit"><input type="submit" value="Finish setup" /></p>
	</form>
</div>
