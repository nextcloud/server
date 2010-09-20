<?php
global $FIRSTRUN;
global $WEBROOT;
global $CONFIG_ERROR;
if(!isset($fillDB)) $fillDB=true;
if(!isset($CONFIG_DBHOST)) $CONFIG_DBHOST='localhost';
if(!isset($CONFIG_DBUSER)) $CONFIG_DBUSER='owncloud';
$newuserpassword=OC_USER::generatepassword();
?>
<script type="text/javascript">
changepassset=function(){
	var change=document.getElementById('changepass').checked;
	if(!change){
		document.getElementById('new_password').style.display='none';
		document.getElementById('new_password_retype').style.display='none';
	}else{
		document.getElementById('new_password').style.display='table-row';
		document.getElementById('new_password_retype').style.display='table-row';
	}
}
</script>
<form method="post" enctype="multipart/form-data" action="<?php echo($WEBROOT);?>/settings/#">
<div><input type='hidden' name='config' value='1' /></div>
<table cellpadding="5" cellspacing="5" border="0" class="loginform">
<?php
	if(!empty($CONFIG_ERROR) and !$FIRSTRUN){
		echo "<tr><td colspan='3' class='error'>$CONFIG_ERROR</td></tr>";
	}
?>
<tr><td>enter password</td><td><input type="password" name="currentpassword" size="30" class="formstyle"></input></td></tr>
<tr><td>change password:</td><td><input onchange='changepassset()' id='changepass' type="checkbox" name="changepass" size="30" class="formstyle" value='1'></input></td></tr>
<tr style='display:none' id='new_password'><td>new password:</td><td><input type="password" name="password" size="30" class="formstyle"></input></td></tr>
<tr style='display:none' id='new_password_retype'><td>retype admin password:</td><td><input type="password" name="password2" size="30" class="formstyle"></input></td></tr>
<tr><td></td><td><input type='submit' value='save' class='formstyle'/></td></tr>
</table>
</form>
