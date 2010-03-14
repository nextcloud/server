<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>ownCloud</title>
<link rel="stylesheet" type="text/css" href="/css/default.css" />
</head>
<body>
<h1><a id="owncloud-logo" href="/"><span>OwnCloud</span></a></h1>
<?php

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
