<?php
global $FIRSTRUN;
if(!isset($fillDB)) $fillDB=true;
?>
</script>
<form method="post" enctype="multipart/form-data">
<table cellpadding="5" cellspacing="5" border="0" class="loginform">
<?php
   if(!$FIRSTRUN){?>
      <tr><td>current password</td><td><input type="password" name="currentpassword" size="30" class="formstyle"></input></td></tr>
      <?php
   }
?>
<tr><td>admin login:</td><td><input type="text" name="adminlogin" size="30" class="formstyle" value="<?php echo($CONFIG_ADMINLOGIN);?>"></input></td></tr>
<tr><td>admin password:</td><td><input type="password" name="adminpassword" size="30" class="formstyle"></input></td><td>(leave empty to keep current pass
<tr><td>retype admin password:</td><td><input type="password" name="adminpassword2" size="30" class="formstyle"></input></td></tr>
<tr><td>data directory:</td><td><input type="text" name="datadirectory" size="30" class="formstyle" value="<?php echo($CONFIG_DATADIRECTORY);?>"></input><
<tr><td>force ssl:</td><td><input type="checkbox" name="forcessl" size="30" class="formstyle" value='<?php echo($CONFIG_HTTPFORCESSL);?>'></input></td></t
<tr><td>date format:</td><td><input type="text" name="dateformat" size="30" class="formstyle" value='<?php echo($CONFIG_DATEFORMAT);?>'></input></td></tr>
<tr><td>database name:</td><td><input type="text" name="dbname" size="30" class="formstyle" value='<?php echo($CONFIG_DBNAME);?>'></input></td></tr>
<tr><td>automaticly fill initial database:</td><td><input type="checkbox" name="filldb" size="30" class="formstyle" value='1' <?php if($FIRSTRUN) echo 'ch
<tr><td></td><td><input type="submit" name="set_config" alt="save" value="save" class="formstyle" /></td></tr>
</table></form>
