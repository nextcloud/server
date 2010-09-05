<?php
global $WEBROOT;
global $FIRSTRUN;
global $CONFIG_ENABLEBACKUP;
global $CONFIG_DATADIRECTORY_ROOT;
global $CONFIG_BACKUPDIRECTORY;
global $CONFIG_ERROR;
$f=@fopen($SERVERROOT.'/config/config.php','a+');
if(!$f) die('Error: Config file (config/config.php) is not writable for the webserver.<br/>');
@fclose($f);
if(!isset($fillDB)) $fillDB=true;
if(!isset($CONFIG_DBHOST)) $CONFIG_DBHOST='localhost';
if(!isset($CONFIG_DBUSER)) $CONFIG_DBUSER='owncloud';
if(!isset($CONFIG_DBTABLEPREFIX)) $CONFIG_DBTABLEPREFIX='oc_';
$newuserpassword=OC_USER::generatepassword();
?>
<script type="text/javascript">
function showDBAdmin(){
	var show=document.getElementById('dbcreate').checked;
	document.getElementById('dbAdminUser').style.display=(show)?'table-row':'none';
	document.getElementById('dbAdminPwd').style.display=(show)?'table-row':'none';
}

function showBackupPath(){
	var show=document.getElementById('enablebackup').checked;
	document.getElementById('backupdir').style.display=(show)?'table-row':'none';
}

function dbtypechange(){
	var dropdown=action=document.getElementById('dbtype');
	if (dropdown){
		var type=dropdown.options[dropdown.selectedIndex].value;
		var inputs=Array('dbhost','dbuser','dbpass','dbpass_retype','dbcreaterow','dbAdminPwd','dbAdminUser','dbname','dbfill','dbtableprefix');
		var id,element;
		if(type=='sqlite'){
			for(i in inputs){
				id=inputs[i];
				element=document.getElementById(id);
				if(element){
					element.style.display='none';
				}
			}
		}else if(type=='mysql' || type=='pgsql'){
			for(i in inputs){
				id=inputs[i];
				element=document.getElementById(id);
				if(element){
					element.style.display='table-row';
				}
			}
			showDBAdmin();
		}
	}
}

function datetypechange(){
	var dropdown=action=document.getElementById('datetype');
	var type=dropdown.options[dropdown.selectedIndex].value;

	var id,element;
	if(type=='custom'){
		element=document.getElementById('trdateformat');
		if(element){
			element.style.display='table-row';
		}
	}else{
		element=document.getElementById('trdateformat');
		if(element){
			element.style.display='none';
		}
		element=document.getElementById('inputdateformat');
		if(element){
			element.value = type;
		}
	}
}
</script>
<?php
if(!$FIRSTRUN){
	$action=$WEBROOT.'/settings';
}else{
	$action='#';
}
echo('<form method="post" enctype="multipart/form-data" action="'.$action.'">')
?>
<table cellpadding="5" cellspacing="5" border="0" class="loginform">
<?php
	if(!empty($CONFIG_ERROR) and !$FIRSTRUN){
		echo "<tr><td colspan='3' class='error'>$CONFIG_ERROR</td></tr>";
	}
if($FIRSTRUN){?>
<tr><th colspan="2">Administartor User</th></tr>
<tr title="Name used to log in."><td>user name:</td><td><input type="text" name="adminlogin" size="30" class="formstyle" value=""></input></td></tr>
<tr title="Make a secure password, use at least 9 characters. Use letters and numbers."><td>password:</td><td><input type="password" name="adminpassword" size="30" class="formstyle"></input></td></tr>
<tr title="Retype password to avoid typing errors."><td>retype password:</td><td><input type="password" name="adminpassword2" size="30" class="formstyle"></input></td></tr>
<?php
}
?>
<tr><th colspan="2">Advanced Configurations</th></tr>
<?php if($FIRSTRUN){?>
<tr title="This directory is used to store user-uploaded files."><td>data directory:</td><td><input type="text" name="datadirectory" size="30" class="formstyle" value="<?php echo($CONFIG_DATADIRECTORY_ROOT);?>"></input></td></tr>
<?php } ?>
<tr title="Using SSL is more secure but requires specific configurations. Click the link to test SSL on your server."><td>force ssl: (<a href="https://<?php echo($_SERVER["HTTP_HOST"].$WEBROOT);?>" target="_blank">test SLL</a>)</td><td><input type="checkbox" name="forcessl" size="30" class="formstyle" value='1' <?php if($CONFIG_HTTPFORCESSL) echo 'checked="checked"'?>></input></td></tr>
<tr title="Backups are used to save your data."><td>automatic backup:</td><td><input type="checkbox" name="enablebackup" id="enablebackup" onchange='showBackupPath()' size="30" class="formstyle" value='1' <?php if($CONFIG_ENABLEBACKUP) echo 'checked'?>></input></td></tr>
<tr title="This directory is used to store backups." id='backupdir'><td>&nbsp; - backup directory:</td><td><input type="text" name="backupdirectory" size="30" class="formstyle" value="<?php echo($CONFIG_BACKUPDIRECTORY);?>"></input></td></tr>
<tr><td>date format:</td><td><select id='datetype' name="datetype" onchange='datetypechange()'>
	<option value='<?php echo($CONFIG_DATEFORMAT);?>'><?php echo(date($CONFIG_DATEFORMAT));?></option>
	<!-- dd-mm-yyyy yyyy-mm-dd mm-dd-yyyy -->
	<optgroup label="DD-MM-YYYY">
		<option value='j M Y G:i'><?php echo(date('j M Y G:i'));?></option>
		<option value='j M Y'><?php echo(date('j M Y'));?></option>
		<option value='D j M Y G:i:s'><?php echo(date('D j M Y G:i:s'));?></option>
		<option value='l j F'><?php echo(date('l j F'));?></option>
		<option value='d-m-Y G:i'><?php echo(date('d-m-Y H:i'));?></option>
	</optgroup>
	<optgroup label="MM-DD-YYYY">
		<option value='M j Y G:i'><?php echo(date('M j Y G:i'));?></option>
		<option value='M j Y'><?php echo(date('M j Y'));?></option>
	</optgroup>
	<optgroup label="YYYY-MM-DD">
		<option value='Y M j G:i'><?php echo(date('Y M j G:i'));?></option>
		<option value='Y M j'><?php echo(date('Y M j'));?></option>
	</optgroup>
	<option value='custom'>Custom Date</option>
</select></td></tr>
<tr id='trdateformat'><td>&nbsp; - custom date:</td><td><input type="text" id="inputdateformat" name="dateformat" size="30" class="formstyle" value='<?php echo($CONFIG_DATEFORMAT);?>'></input></td></tr>
<?php if($FIRSTRUN){
	if(!is_callable('sqlite_open')){
		echo '<tr><td colspan="2">No SQLite support detected, make sure you have both sqlite and the sqlite php module installed (sqlite and php5-sqlite for Debian/Ubuntu)</td></tr>';
	}
?>
<tr title="SQLite is usually the easiest database to work with."><td>database type:</td><td>
<select id='dbtype' name="dbtype" onchange='dbtypechange()'>
<?php
global $CONFIG_DBTYPE;
if($CONFIG_DBTYPE=='sqlite'){
	if(is_callable('sqlite_open')){
		echo "<option value='sqlite'>SQLite</option>";
	}
	if(is_callable('mysql_connect')){
		echo "<option value='mysql'>MySQL</option>";
	}
	if(is_callable('pg_connect')){
		echo "<option value='pgsql'>PostgreSQL</option>";
	}
}elseif($CONFIG_DBTYPE=='mysql'){
	if(is_callable('mysql_connect')){
		echo "<option value='mysql'>MySQL</option>";
	}
	if(is_callable('sqlite_open')){
		echo "<option value='sqlite'>SQLite</option>";
	}
	if(is_callable('pg_connect')){
		echo "<option value='pgsql'>PostgreSQL</option>";
	}
}elseif($CONFIG_DBTYPE=='pgsql'){
	if(is_callable('pg_connect')){
		echo "<option value='pgsql'>PostgreSQL</option>";
	}
	if(is_callable('mysql_connect')){
		echo "<option value='mysql'>MySQL</option>";
	}
	if(is_callable('sqlite_open')){
		echo "<option value='sqlite'>SQLite</option>";
	}
}
?>
</select>
</td></tr>
<tr title="The database server. In most cases, localhost works fine." id='dbhost'><td>&nbsp; - database host:</td><td><input type="text" name="dbhost" size="30" class="formstyle" value='<?php echo($CONFIG_DBHOST);?>'></input></td></tr>
<tr title="The name of the database." id='dbname'><td>&nbsp; - database name:</td><td><input type="text" name="dbname" size="30" class="formstyle" value='<?php echo($CONFIG_DBNAME);?>'></input></td></tr>
<tr title="Used to avoid conflict between web-applications. Don't use the same prefix for two web-applications." id='dbtableprefix'><td>&nbsp; - table prefix:</td><td><input type="text" name="dbtableprefix" size="30" class="formstyle" value='<?php echo($CONFIG_DBTABLEPREFIX);?>'></input></td></tr>
<tr title="The user of the database. If possible avoid the root user." id='dbuser'><td>&nbsp; - database user:</td><td><input type="text" name="dbuser" size="30" class="formstyle" value='<?php echo($CONFIG_DBUSER);?>'></input></td></tr>
<tr title="The password of the database." id='dbpass'><td>&nbsp; &nbsp; &nbsp; - password:</td><td><input type="password" name="dbpassword" size="30" class="formstyle" value=''></input></td></tr>
<tr title="Retype password to avoid typing errors." id='dbpass_retype'><td>&nbsp; &nbsp; &nbsp; - retype password:</td><td><input type="password" name="dbpassword2" size="30" class="formstyle" value=''></input></td></tr>
<tr title="Automatically create a database and user for ownCloud." id='dbcreaterow'><td>&nbsp; - create database and user:</td><td><input id='dbcreate' type="checkbox" name="createdatabase" size="30" class="formstyle" value='1' checked="checked" onchange='showDBAdmin()'></input></td></tr>
<tr title="This is often 'root'. If in doubt, contact your web-host" id='dbAdminUser'><td>&nbsp; &nbsp;  &nbsp; - administrative user:</td><td><input type="text" name="dbadminuser" size="30" class="formstyle" value='root'></input></td></tr>
<tr title="The password of the database user. If in doubt, contact your web-host." id='dbAdminPwd'><td>&nbsp; &nbsp; &nbsp; - administrative password:</td><td><input type="password" name="dbadminpwd" size="30" class="formstyle" value=''></input></td></tr>
<tr title="Fill database with default data so you can start right away." id='dbfill'><td>&nbsp; - fill initial database:</td><td><input type="checkbox" name="filldb" size="30" class="formstyle" value='1' checked="checked"></input></td></tr>
<?php }?>
<tr><th colspan="2">Conformation</th></tr>
<?php
	if(!$FIRSTRUN){?>
		<tr title="This is to avoid abuse while you are away and have not logged out decently."><td>your password:</td><td><input type="password" name="currentpassword" size="30" class="formstyle"></input></td></tr>
		<?php
	}
?>
<tr><td></td><td><input type="submit" name="set_config" alt="save" value="save" class="formstyle" /></td></tr>
</table></form><br/>
<?php
if(!$FIRSTRUN ){//disabled for now?>
<br/>
<form method="post" enctype="multipart/form-data" action="#">
<table cellpadding="5" cellspacing="5" border="0" class="loginform">
<tr><th colspan='2'>Create new user:</td></tr>
<tr title="Name used to log in."><td>user name</td><td><input type='text' name='new_username' class="formstyle"></input></td></tr>
<tr title="Make a secure password, use at least 9 characters. Use letters and numbers."><td>password</td><td><input type='text' name='new_password' class="formstyle" autocomplete="off" value='<?php echo($newuserpassword);?>'></input></td></tr>
<tr><td></td><td><input type='submit' value='create' class="formstyle"></input></td></tr>
</table>
</form>
<?php
}
?>
<script type="text/javascript">
	dbtypechange();
	datetypechange();
	showBackupPath();
</script>
