<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
	<title>ownCloud</title>
	<base href="<?php echo($WEBROOT); ?>/"/>
	<link rel="stylesheet" type="text/css" href="<?php
	if(!OC_UTIL::hasSmallScreen()){
		echo($WEBROOT.'/css/default.php');
	}else{
		echo($WEBROOT.'/css/small.php');
	}
	?>"/>
	<script type='text/ecmascript' src='<?php echo($WEBROOT)?>/js/lib_ajax.js'></script>
	<?php
	global $CONFIG_INSTALLED;
	if($CONFIG_INSTALLED){//the javascripts somehow breaks the first run wizzard in konqueror?>
	<script type='text/ecmascript' src='<?php echo($WEBROOT)?>/js/lib_timer.js'></script>
	<script type='text/ecmascript' src='<?php echo($WEBROOT)?>/js/lib_notification.js'></script>
	<script type='text/ecmascript' src='<?php echo($WEBROOT)?>/js/lib_xmlloader.js'></script>
	<script type='text/ecmascript' src='<?php echo($WEBROOT)?>/js/lib_files.js'></script>
	<script type='text/ecmascript' src='<?php echo($WEBROOT)?>/js/lib_event.js'></script>
	<script type='text/ecmascript' src='<?php echo($WEBROOT)?>/js/lib_drag.js'></script>
	<script type='text/ecmascript' src='<?php echo($WEBROOT)?>/js/lib_api.js'></script>
	<script type='text/ecmascript' src='<?php echo($WEBROOT)?>/js/filebrowser.js'></script>
	<?php
	}
	?>
<?php
foreach(OC_UTIL::$scripts as $script){
    echo("<script type='text/ecmascript' src='$WEBROOT/$script'></script>\n");
}
?>
	<script type='text/ecmascript'>
	var WEBROOT='<?php echo($WEBROOT)?>';
	var SMALLSCREEN='<?php echo((OC_UTIL::hasSmallScreen())?'true':'false')?>';
	</script>
    </head>
    <body onload='OC_onload.run()'>
<div id='mainlayout'>
<div class='head'>
<?php
global $CONFIG_ERROR;
if(!OC_UTIL::hasSmallScreen()){
	echo('<h1><a id="owncloud-logo" href="'.$WEBROOT.'"><span>ownCloud</span></a></h1>');
}


  // check if already configured. otherwise start configuration wizard
  $error=OC_CONFIG::writeadminlisener();
  if($e=OC_CONFIG::configlisener()){
	$error.=$e;
  }
  if($e=OC_CONFIG::createuserlisener()){
	$error.=$e;
  }
  if($e=OC_CONFIG::creategrouplisener()){
	$error.=$e;
  }
  $CONFIG_ERROR=$error;
  global $CONFIG_INSTALLED;
  if(!$CONFIG_INSTALLED) {
    global $FIRSTRUN;
    $FIRSTRUN=true;
    echo('</div><div class="body">');
    echo('<div class="center">');
    echo('<p class="errortext">'.$error.'</p>');
    echo('<p class="highlighttext">First Run Wizard</p>');
    OC_CONFIG::showadminform();
    echo('</div>');
    OC_UTIL::showfooter();
    exit();
  }elseif(!isset($_SESSION['username']) or $_SESSION['username']=='') { // show the loginform if not loggedin
    echo('<div class="center">');
    OC_UTIL::showloginform();
    echo('</div>');
    OC_UTIL::showfooter();
    exit();
  }else{
    echo('<div id="nav" class="center">');
    OC_UTIL::shownavigation();
    echo('</div>');
    echo('</div><div class="body">');
  }

?>
