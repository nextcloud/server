<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>ownCloud</title>
<link rel="stylesheet" type="text/css" href="<?php echo($WEBROOT); ?>/css/default.php" />
</head>
<body>
<?php
echo('<h1><a id="owncloud-logo" href="'.$WEBROOT.'/"><span>ownCloud</span></a></h1>');


  // check if already configured. otherwise start configuration wizard
  $error=OC_CONFIG::writeconfiglisener();
  if(empty($CONFIG_ADMINLOGIN)) {
    echo('<div class="center">');
    echo('<p class="errortext">'.$error.'</p>');
    echo('<p class="highlighttext">First Run Wizard</p>');
    OC_CONFIG::showconfigform();
    echo('</div>');
    OC_UTIL::showfooter();
    exit();
  }


  // show the loginform if not loggedin
  if(!isset($_SESSION['username']) or $_SESSION['username']=='') {
    echo('<div class="center">');
    OC_UTIL::showloginform();
    echo('</div>');
    OC_UTIL::showfooter();
    exit();
  }else{
    echo('<div id="nav" class="center">');
    OC_UTIL::shownavigation();
    echo('</div>');
  }

?>
