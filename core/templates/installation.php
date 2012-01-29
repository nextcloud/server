<input type='hidden' id='hasMySQL' value='<?php echo $_['hasMySQL'] ?>'></input>
<input type='hidden' id='hasSQLite' value='<?php echo $_['hasSQLite'] ?>'></input>
<input type='hidden' id='hasPostgreSQL' value='<?php echo $_['hasPostgreSQL'] ?>'></input>
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
		<p class="infield">
			<label for="adminlogin" class="infield"><?php echo $l->t( 'Username' ); ?></label>
			<input type="text" name="adminlogin" id="adminlogin" value="<?php print OC_Helper::init_var('adminlogin'); ?>" autocomplete="off" autofocus required />
		</p>
		<p class="infield">
			<label for="adminpass" class="infield"><?php echo $l->t( 'Password' ); ?></label>
			<input type="password" name="adminpass" id="adminpass" value="<?php print OC_Helper::init_var('adminpass'); ?>" required />
		</p>
	</fieldset>

	<fieldset id="datadirField">
		<legend><a id="showAdvanced"><?php echo $l->t( 'Advanced' ); ?> ▾</a></legend>
		<div id="datadirContent">
			<label for="directory"><?php echo $l->t( 'Data folder' ); ?>:</label><br/>
			<input type="text" name="directory" id="directory" value="<?php print OC_Helper::init_var('directory', $_['directory']); ?>" />
		</div>
	</fieldset>

	<fieldset id='databaseField'>
		<?php if($_['hasMySQL'] or $_['hasPostgreSQL']) $hasOtherDB = true; else $hasOtherDB =false; //other than SQLite ?>
		<legend><?php echo $l->t( 'Configure the database' ); ?></legend>
		<div id="selectDbType">
		<?php if($_['hasSQLite']): ?>
		<input type='hidden' id='hasSQLite' value="true" />
		<?php if(!$hasOtherDB): ?>
		<p>SQLite <?php echo $l->t( 'will be used' ); ?>.</p>
		<input type="hidden" id="dbtype" name="dbtype" value="sqlite" />
		<?php else: ?>
		<input type="radio" name="dbtype" value="sqlite" id="sqlite" <?php OC_Helper::init_radio('dbtype', 'sqlite', 'sqlite'); ?>/>
		<label class="sqlite" for="sqlite">SQLite</label>
		<?php endif; ?>
		<?php endif; ?>

		<?php if($_['hasMySQL']): ?>
		<input type='hidden' id='hasMySQL' value='true'/>
		<?php if(!$_['hasSQLite'] and !$_['hasPostgreSQL']): ?>
		<p>MySQL <?php echo $l->t( 'will be used' ); ?>.</p>
		<input type="hidden" id="dbtype" name="dbtype" value="mysql" />
		<?php else: ?>
		<input type="radio" name="dbtype" value="mysql" id="mysql" <?php OC_Helper::init_radio('dbtype','mysql', 'sqlite'); ?>/>
		<label class="mysql" for="mysql">MySQL</label>
		<?php endif; ?>
		<?php endif; ?>

		<?php if($_['hasPostgreSQL']): ?>
		<?php if(!$_['hasSQLite'] and !$_['hasMySQL']): ?>
		<p>PostgreSQL <?php echo $l->t( 'will be used' ); ?>.</p>
		<input type="hidden" id="dbtype" name="dbtype" value="pgsql" />
		<?php else: ?>
		<label class="pgsql" for="pgsql">PostgreSQL</label>
		<input type="radio" name="dbtype" value='pgsql' id="pgsql" <?php OC_Helper::init_radio('dbtype','pgsql', 'sqlite'); ?>/>
		<?php endif; ?>
		<?php endif; ?>
		</div>

		<?php if($hasOtherDB): ?>
		<div id="use_other_db">
			<p class="infield">
				<label for="dbuser" class="infield"><?php echo $l->t( 'Database user' ); ?></label>
				<input type="text" name="dbuser" id="dbuser" value="<?php print OC_Helper::init_var('dbuser'); ?>" autocomplete="off" />
			</p>
			<p class="infield">
				<label for="dbpass" class="infield"><?php echo $l->t( 'Database password' ); ?></label>
				<input type="password" name="dbpass" id="dbpass" value="<?php print OC_Helper::init_var('dbpass'); ?>" />
			</p>
			<p class="infield">
				<label for="dbname" class="infield"><?php echo $l->t( 'Database name' ); ?></label>
				<input type="text" name="dbname" id="dbname" value="<?php print OC_Helper::init_var('dbname'); ?>" autocomplete="off" />
			</p>
		</div>
		<?php endif; ?>
		<p class="infield">
			<label for="dbhost" class="infield"><?php echo $l->t( 'Database host' ); ?></label>
			<input type="text" name="dbhost" id="dbhost" value="<?php print OC_Helper::init_var('dbhost', 'localhost'); ?>" />
		</p>
	</fieldset>

	<div class="buttons"><input type="submit" value="<?php echo $l->t( 'Finish setup' ); ?>" /></div>
</form>
