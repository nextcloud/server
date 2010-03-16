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
<tr><td></td><td><input type="submit" name="set_config" alt="save" value="save" class="formstyle" /></td></tr>
</table></form>
