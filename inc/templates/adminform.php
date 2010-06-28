<?php
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
    var type=dropdown.options[dropdown.selectedIndex].value;
    var inputs=Array('dbhost','dbuser','dbpass','dbpass_retype','dbcreaterow','dbAdminPwd','dbAdminUser','dbname','dbfill');
    var id,element;
    if(type=='sqlite'){
        for(i in inputs){
            id=inputs[i];
            element=document.getElementById(id);
            if(element){
                element.style.display='none';
            }
        }
    }else if(type=='mysql'){
        for(i in inputs){
            id=inputs[i];
            element=document.getElementById(id);
            if(element){
                element.style.display='table-row';
            }
        }
        showDBAdmin()
    }
}
</script>
<form method="post" enctype="multipart/form-data">
<table cellpadding="5" cellspacing="5" border="0" class="loginform">
<?php
	if(!empty($CONFIG_ERROR) and !$FIRSTRUN){
		echo "<tr><td colspan='3' class='error'>$CONFIG_ERROR</td></tr>";
	}
	if(!$FIRSTRUN){?>
		<tr><td>current password</td><td><input type="password" name="currentpassword" size="30" class="formstyle"></input></td></tr>
		<?php
	}
if($FIRSTRUN){?>
<tr><td>admin login:</td><td><input type="text" name="adminlogin" size="30" class="formstyle" value=""></input></td></tr>
<tr><td>admin password:</td><td><input type="password" name="adminpassword" size="30" class="formstyle"></input></td><td>(leave empty to keep current password)</td></tr>
<tr><td>retype admin password:</td><td><input type="password" name="adminpassword2" size="30" class="formstyle"></input></td></tr>
<tr><td>data directory:</td><td><input type="text" name="datadirectory" size="30" class="formstyle" value="<?php echo($CONFIG_DATADIRECTORY_ROOT);?>"></input></td></tr>
<?php
}
?>
<tr><td>force ssl:</td><td><input type="checkbox" name="forcessl" size="30" class="formstyle" value='1' <?php if($CONFIG_HTTPFORCESSL) echo 'checked'?>></input></td></tr>
<tr><td>enable automatic backup:</td><td><input type="checkbox" name="enablebackup" id="enablebackup" onchange='showBackupPath()' size="30" class="formstyle" value='1' <?php if($CONFIG_ENABLEBACKUP) echo 'checked'?>></input></td></tr>
<tr id='backupdir'><td>backup directory:</td><td><input type="text" name="backupdirectory" size="30" class="formstyle" value="<?php echo($CONFIG_BACKUPDIRECTORY);?>"></input></td></tr>
<tr><td>date format:</td><td><input type="text" name="dateformat" size="30" class="formstyle" value='<?php echo($CONFIG_DATEFORMAT);?>'></input></td></tr>
<?php if($FIRSTRUN){
	if(!is_callable('sqlite_open')){
		echo '<tr><td colspan="2">No SQLite support detected, make sure you have both sqlite and the sqlite php module installed (sqlite and php5-sqlite for Debian/Ubuntu)</td></tr>';
	}
?>
<tr><td>database type:</td><td>
<select id='dbtype' name="dbtype" onchange='dbtypechange()'>
<?php
global $CONFIG_DBTYPE;
$dbtypes=array();
if($CONFIG_DBTYPE=='sqlite'){
	if(is_callable('sqlite_open')){
		$dbtypes[]='SQLite';
	}
	if(is_callable('mysql_connect')){
		$dbtypes[]='MySQL';
	}
}else{
	if(is_callable('mysql_connect')){
		$dbtypes[]='MySQL';
	}
	if(is_callable('sqlite_open')){
		$dbtypes[]='SQLite';
	}
}
foreach($dbtypes as $dbtype){
	echo "<option value='".strtolower($dbtype)."'>$dbtype</option>";
}
?>
</select>
</td></tr>
<tr id='dbhost'><td>database host:</td><td><input type="text" name="dbhost" size="30" class="formstyle" value='<?php echo($CONFIG_DBHOST);?>'></input></td></tr>
<tr id='dbname'><td>database name:</td><td><input type="text" name="dbname" size="30" class="formstyle" value='<?php echo($CONFIG_DBNAME);?>'></input></td></tr>
<tr id='dbuser'><td>database user:</td><td><input type="text" name="dbuser" size="30" class="formstyle" value='<?php echo($CONFIG_DBUSER);?>'></input></td></tr>
<tr id='dbpass'><td>database password:</td><td><input type="password" name="dbpassword" size="30" class="formstyle" value=''></input></td><td>(leave empty to keep current password)</td></tr>
<tr id='dbpass_retype'><td>retype database password:</td><td><input type="password" name="dbpassword2" size="30" class="formstyle" value=''></input></td></tr>
<tr id='dbcreaterow'><td>create database and user:</td><td><input id='dbcreate' type="checkbox" name="createdatabase" size="30" class="formstyle" value='1' <?php if($FIRSTRUN) echo 'checked'; ?> onchange='showDBAdmin()'></input></td></tr>
<tr id='dbAdminUser'><td>database administrative user:</td><td><input type="text" name="dbadminuser" size="30" class="formstyle" value='root'></input></td></tr>
<tr id='dbAdminPwd'><td>database administrative password:</td><td><input type="password" name="dbadminpwd" size="30" class="formstyle" value=''></input></td></tr>
<tr id='dbfill'><td>automaticly fill initial database:</td><td><input type="checkbox" name="filldb" size="30" class="formstyle" value='1' <?php if($FIRSTRUN) echo 'checked'; ?>></input></td></tr>
<?php }?>
<tr><td></td><td><input type="submit" name="set_config" alt="save" value="save" class="formstyle" /></td></tr>
</table></form><br/>
<?php
if(!$FIRSTRUN ){//disabled for now?>
<br/>
<form method="post" enctype="multipart/form-data">
<table cellpadding="5" cellspacing="5" border="0" class="loginform">
<tr><td colspan='2'>Create new user:</td></tr>
<tr><td>user name</td><td><input type='text' name='new_username' class="formstyle"></input></td></tr>
<tr><td>password</td><td><input type='text' name='new_password' class="formstyle" autocomplete="off" value='<?php echo($newuserpassword);?>'></input></td></tr>
<tr><td></td><td><input type='submit' value='create' class="formstyle"></input></td></tr>
</table>
</form>
<?php
}
?>
<script type="text/javascript">
    dbtypechange()
    showBackupPath()
</script>
