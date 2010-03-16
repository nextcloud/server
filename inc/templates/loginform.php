<?php
if(isset($loginresult) and $loginresult=='error') echo('<p class="errortext">login failed</p>');
?>
<form method="post" enctype="multipart/form-data">
<table cellpadding="5" cellspacing="5" border="0" class="loginform">
<tr><td>login:</td><td><input type="text" name="login" size="30" class="formstyle"></input></td></tr>
<tr><td>password:</td><td><input type="password" name="password" size="30" class="formstyle"></input></td></tr>
<tr><td></td><td><input type="submit" name="loginbutton" alt="login" value="login" class="formstyle" /></td></tr>
</table></form>
