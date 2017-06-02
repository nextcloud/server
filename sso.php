<!DOCTYPE html>
<html>
<body onload="document.ssoForm.submit()">
<form id="ssoForm" name="ssoForm" action="index.php/loginpost" method="post">
  <input id="accessToken" name="accessToken" type="hidden" value="<?php echo htmlspecialchars($_POST["accessToken"]) ?>">
</form>
</body>
</html> 
