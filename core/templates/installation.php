<div id="login">
	<header><img src="<?php echo image_path('', 'owncloud-logo-medium-white.png'); ?>" alt="ownCloud" /></header>
	<form action="index.php" method="post" id="setup_form">
		<input type="hidden" name="install" value="true" />
		<p class="intro">
			<?php echo $l->t( '<strong>ownCloud</strong> is your personal web storage.' ); ?><br />
			<?php echo $l->t( 'Finish the setup by following the steps below.' ); ?>
		</p>

		<?php if(count($_['errors']) > 0): ?>
		<ul class="errors">
			<?php foreach($_['errors'] as $err): ?>
			<li>
				<?php if(is_array($err)):?>
					<?php print $err['error']; ?>
					<p class='hint'><?php print $err['hint']; ?></p>
				<?php else: ?>
					<?php print $err; ?>
				<?php endif; ?>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>

		<fieldset>
			<legend><?php echo $l->t( 'Create an <strong>admin account</strong>.' ); ?></legend>
			<p><label for="adminlogin"><?php echo $l->t( 'Username' ); ?></label><input type="text" name="adminlogin" id="adminlogin" value="<?php print OC_Helper::init_var('adminlogin'); ?>" autofocus /></p>
			<p><label for="adminpass"><?php echo $l->t( 'Password' ); ?></label><input type="password" name="adminpass" id="adminpass" value="<?php print OC_Helper::init_var('adminpass'); ?>" /></p>
        </fieldset>
        
        <a id='showAdvanced'><strong><?php echo $l->t( 'Advanced' ); ?></strong> <img src='<?php echo OC_Helper::imagePath('','drop-arrow.png'); ?>'></img></a>
        
        <fieldset id='datadirField'>
			<legend><?php echo $l->t( 'Set where to store the data.' ); ?></legend>
			<p><label for="directory"><?php echo $l->t( 'Data directory:' ); ?></label><input type="text" name="directory" id="directory" value="<?php print OC_Helper::init_var('directory', $_['directory']); ?>" /></p>
		</fieldset>
		
		<fieldset id='databaseField'>
			<legend><?php echo $l->t( 'Configure the database.' ); ?></legend>
			<?php if($_['hasSQLite']): ?>
			<input type='hidden' id='hasSQLite' value='true'/>
			<?php if(!$_['hasMySQL']): ?>
			<p><?php echo $l->t( 'SQLite will be used for the database. You have nothing to do.' ); ?></p>
			<input type="hidden" id="dbtype" name="dbtype" value="sqlite" />
			<?php else: ?>
			<p><label class="sqlite" for="sqlite"><?php echo $l->t( 'SQLite' ); ?></label><input type="radio" name="dbtype" value='sqlite' id="sqlite" <?php OC_Helper::init_radio('dbtype', 'sqlite', 'sqlite'); ?>/></p>
			<?php endif; ?>
			<?php endif; ?>

			<?php if($_['hasMySQL']): ?>
			<input type='hidden' id='hasMySQL' value='true'/>
			<?php if(!$_['hasSQLite']): ?>
			<p><?php echo $l->t( 'MySQL will be used for the database.' ); ?></p>
			<input type="hidden" id="dbtype" name="dbtype" value="mysql" />
			<?php else: ?>
			<p><label class="mysql" for="mysql">MySQL </label><input type="radio" name="dbtype" value='mysql' id="mysql" <?php OC_Helper::init_radio('dbtype', 'mysql', 'sqlite'); ?>/></p>
			<?php endif; ?>
			<div id="use_mysql">
				<p><label for="dbuser"><?php echo $l->t( 'MySQL username:' ); ?></label><input type="text" name="dbuser" id="dbuser" value="<?php print OC_Helper::init_var('dbuser'); ?>" /></p>
				<p><label for="dbpass"><?php echo $l->t( 'MySQL password:' ); ?></label><input type="password" name="dbpass" id="dbpass" value="<?php print OC_Helper::init_var('dbpass'); ?>" /></p>
				<p><label for="dbname"><?php echo $l->t( 'Database name:' ); ?></label><input type="text" name="dbname" id="dbname" value="<?php print OC_Helper::init_var('dbname'); ?>" /></p>
				<p><label for="dbhost"><?php echo $l->t( 'Host:' ); ?></label><input type="text" name="dbhost" id="dbhost" value="<?php print OC_Helper::init_var('dbhost', 'localhost'); ?>" /></p>
				<p><label for="dbtableprefix"><?php echo $l->t( 'Table prefix:' ); ?></label><input type="text" name="dbtableprefix" id="dbtableprefix" value="<?php print OC_Helper::init_var('dbtableprefix', 'oc_'); ?>" /></p>
				
			</div>
			<?php endif; ?>
		</fieldset>

		<p class="submit"><input type="submit" value="<?php echo $l->t( 'Finish setup' ); ?>" /></p>
	</form>
</div>
