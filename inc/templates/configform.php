<?php
global $createDB;
global $fillDB;
if(!isset($createDB)) $createDB=true;
if(!isset($fillDB)) $fillDB=true;
?>
<script type="text/javascript">
function showDBAdmin(){
var show=document.getElementById('dbCreate').checked;
document.getElementById('dbAdminUser').style.display=(show)?'table-row':'none';
document.getElementById('dbAdminPwd').style.display=(show)?'table-row':'none';
}
</script>
<form method="post" enctype="multipart/form-data">
<table cellpadding="5" cellspacing="5" border="0" class="loginform">
<tr><td>admin login:</td><td><input type="text" name="adminlogin" size="30" class="formstyle" value="<?php echo($CONFIG_ADMINLOGIN);?>"></input></td></tr>
<tr><td>admin password:</td><td><input type="password" name="adminpassword" size="30" class="formstyle" value="<?php echo($CONFIG_ADMINPASSWORD);?>"></input></td></tr>
<tr><td>retype admin password:</td><td><input type="password" name="adminpassword2" size="30" class="formstyle" value="<?php echo($CONFIG_ADMINPASSWORD);?>"></input></td></tr>
<tr><td>data directory:</td><td><input type="text" name="datadirectory" size="30" class="formstyle" value="<?php echo($CONFIG_DATADIRECTORY);?>"></input></td></tr>
<tr><td>force ssl:</td><td><input type="checkbox" name="forcessl" size="30" class="formstyle" value='<?php echo($CONFIG_HTTPFORCESSL);?>'></input></td></tr>
<tr><td>date format:</td><td><input type="text" name="dateformat" size="30" class="formstyle" value='<?php echo($CONFIG_DATEFORMAT);?>'></input></td></tr>
<tr><td>database host:</td><td><input type="text" name="dbhost" size="30" class="formstyle" value='<?php echo($CONFIG_DBHOST);?>'></input></td></tr>
<tr><td>database name:</td><td><input type="text" name="dbname" size="30" class="formstyle" value='<?php echo($CONFIG_DBNAME);?>'></input></td></tr>
<tr><td>database user:</td><td><input type="text" name="dbuser" size="30" class="formstyle" value='<?php echo($CONFIG_DBUSER);?>'></input></td></tr>
<tr><td>database password:</td><td><input type="password" name="dbpassword" size="30" class="formstyle" value='<?php echo($CONFIG_DBPASSWORD);?>'></input></td></tr>
<tr><td>retype database password:</td><td><input type="password" name="dbpassword2" size="30" class="formstyle" value='<?php echo($CONFIG_DBPASSWORD);?>'></input></td></tr>
<tr><td>create database and user:</td><td><input id='dbCreate' type="checkbox" name="createdatabase" size="30" class="formstyle" value='1' <?php if($createDB) echo 'checked'; ?> onchange='showDBAdmin()'></input></td></tr>
<tr id='dbAdminUser'><td>database administrative user:</td><td><input type="text" name="dbadminuser" size="30" class="formstyle" value='root'></input></td></tr>
<tr id='dbAdminPwd'><td>database administrative password:</td><td><input type="password" name="dbadminpwd" size="30" class="formstyle" value=''></input></td></tr>
<tr><td>automaticly fill initial database:</td><td><input type="checkbox" name="filldb" size="30" class="formstyle" value='1' <?php if($fillDB) echo 'checked'; ?>></input></td></tr>
<tr><td></td><td><input type="submit" name="set_config" alt="save" value="save" class="formstyle" /></td></tr>
</table></form>
<script type="text/javascript">showDBAdmin()</script>