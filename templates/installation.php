<div id="login">
	<img src="<?php echo image_path("", "owncloud-logo-medium-white.png"); ?>" alt="ownCloud" />
	<form action="#" method="post">
		<input type='hidden' name='install' value='true'/>
		<fieldset>
			<input type="text" name="login" value="your email" />
			<input type="password" name="pass" value="password" />
        </fieldset>
		<fieldset>
			<?php if(!$_['hasSQLite']): ?>
				<legend><abbr title="to use SQLite instead, install it on your server">MySQL</abbr> Database</legend>
				<input type="text" name="dbuser" value="MySQL user" />
				<input type="password" name="dbpass" value="password" />
				<input type="text" name="dbname" value="database name" />
			<?php endif;?>
		</fieldset>
		<fieldset id="advanced">
			<legend><a id="advanced_options_link" href="">Advanced ▾</a></legend>
			<div id="advanced_options">
				<label class="left">Data directory</label><input type="text" name="directory" value="<?php echo($_['datadir']);?>" />
				<?php if($_['hasMySQL'] and $_['hasSQLite']): ?>
					<input type="radio" name="dbtype" value='sqlite' id="sqlite" checked="checked" /><label for="sqlite">SQLite</label>
					<input type="radio" name="dbtype" value='mysql' id="mysql"><label for="mysql">MySQL</label>
					<div id="use_mysql">
						<input type="text" name="dbuser" value="MySQL user" />
						<input type="password" name="dbpass" value="password" />
						<input type="text" name="dbname" value="database name" />
				<?php endif;?>
				<?php if($_['hasMySQL'] and !$_['hasSQLite']): ?>
						<input type='hidden' name='dbtype' value='mysql'/>
				<?php endif;?>
				<?php if(!$_['hasMySQL'] and $_['hasSQLite']): ?>
						<input type='hidden' name='dbtype' value='sqlite'/>
				<?php endif;?>
				<?php if($_['hasMySQL'] and $_['hasSQLite']): ?>
						<label class="left">Host</label><input type="text" name="dbhost" value="localhost" />
						<label class="left">Table prefix</label><input type="text" name="dbtableprefix" value="oc_" />
					</div>
				<?php endif;?>
				<?php if($_['hasMySQL'] and !$_['hasSQLite']): ?>
					<label class="left">Host</label><input type="text" name="dbhost" value="localhost" />
					<label class="left">Table prefix</label><input type="text" name="dbtableprefix" value="oc_" />
				<?php endif;?>
			</div>
		</fieldset>
		<fieldset>
			<p class="submit"><input type="submit" value="Create" /></p>
		</fieldset>
	</form>
</div>
