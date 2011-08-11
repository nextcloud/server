<form action="index.php" method="post">

<input type="hidden" name="install" value="true" />
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
		<legend><?php echo $l->t( 'Create an <strong>admin account</strong>' ); ?></legend>
		<input type="text" name="adminlogin" id="adminlogin" value="<?php print OC_Helper::init_var('adminlogin'); ?>" placeholder="<?php echo $l->t( 'Username' ); ?>" autocomplete="off" autofocus required />
		<input type="password" name="adminpass" id="adminpass" value="<?php print OC_Helper::init_var('adminpass'); ?>" placeholder="<?php echo $l->t( 'Password' ); ?>" required />
	</fieldset>
	
	<fieldset id='databaseField'>
		<?php if($_['hasMySQL'] or $_['hasPostgreSQL']) $hasOtherDB = true; //other than SQLite ?>
		<legend><?php echo $l->t( 'Configure the database' ); ?></legend>
		<div id="selectDbType">
		<?php if($_['hasSQLite']): ?>
		<input type='hidden' id='hasSQLite' value='true' />
		<?php if(!$hasOtherDB): ?>
		<p><?php echo $l->t( 'SQLite will be used.' ); ?></p>
		<input type="hidden" id="dbtype" name="dbtype" value="sqlite" />
		<?php else: ?>
		<input type="radio" name="dbtype" value='sqlite' id="sqlite" <?php OC_Helper::init_radio('dbtype', 'sqlite', 'sqlite'); ?>/>
		<label class="sqlite" for="sqlite"><?php echo $l->t( 'SQLite' ); ?></label>
		<?php endif; ?>
		<?php endif; ?>

		<?php if($_['hasMySQL']): ?>
		<input type='hidden' id='hasMySQL' value='true'/>
		<?php if(!$_['hasSQLite'] and !$_['hasPostgreSQL']): ?>
		<p><?php echo $l->t( 'MySQL will be used.' ); ?></p>
		<input type="hidden" id="dbtype" name="dbtype" value="mysql" />
		<?php else: ?>
		<input type="radio" name="dbtype" value='mysql' id="mysql" <?php OC_Helper::init_radio('dbtype','pgsql', 'mysql', 'sqlite'); ?>/>
		<label class="mysql" for="mysql">MySQL</label>
		<?php endif; ?>
		<?php endif; ?>

		<?php if($_['hasPostgreSQL']): ?>
		<?php if(!$_['hasSQLite'] and !$_['hasMySQL']): ?>
		<p><?php echo $l->t( 'PostgreSQL will be used.' ); ?></p>
		<input type="hidden" id="dbtype" name="dbtype" value="pgsql" />
		<?php else: ?>
		<p><label class="pgsql" for="pgsql">PostgreSQL</label><input type="radio" name="dbtype" value='pgsql' id="pgsql" <?php OC_Helper::init_radio('dbtype','pgsql', 'mysql', 'sqlite'); ?>/></p>
		<?php endif; ?>
		<?php endif; ?>
		</div>

		<?php if($hasOtherDB): ?>
		<div id="use_other_db">
			<input type="text" name="dbuser" id="dbuser" value="<?php print OC_Helper::init_var('dbuser'); ?>" placeholder="<?php echo $l->t( 'Database user' ); ?>" autocomplete="off" />
			<input type="password" name="dbpass" id="dbpass" value="<?php print OC_Helper::init_var('dbpass'); ?>" placeholder="<?php echo $l->t( 'Database password' ); ?>" />
			<input type="text" name="dbname" id="dbname" value="<?php print OC_Helper::init_var('dbname'); ?>" placeholder="<?php echo $l->t( 'Database name' ); ?>" autocomplete="off" />
			
			
		</div>
		<?php endif; ?>

	</fieldset>
	
	<a id='showAdvanced'><strong><?php echo $l->t( 'Advanced' ); ?> ▾</strong></a>

	<fieldset id='datadirField'>
		<input type="text" name="dbhost" id="dbhost" value="<?php print OC_Helper::init_var('dbhost', 'localhost'); ?>" placeholder="<?php echo $l->t( 'Host' ); ?>" />
		<input type="text" name="dbtableprefix" id="dbtableprefix" value="<?php print OC_Helper::init_var('dbtableprefix', 'oc_'); ?>" placeholder="<?php echo $l->t( 'Table prefix' ); ?>" />

		<label id="directorylabel" for="directory"><?php echo $l->t( 'Data folder' ); ?></label><input type="text" name="directory" id="directory" value="<?php print OC_Helper::init_var('directory', $_['directory']); ?>" placeholder="<?php echo $l->t( 'Data folder' ); ?>" />
	</fieldset>

	<input type="submit" value="<?php echo $l->t( 'Finish setup' ); ?>" />
</form>
