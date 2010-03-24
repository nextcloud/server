<?php
header('Content-Type: text/css');
require_once('../inc/lib_base.php');
?>
body {background-color: #F9F9F9;}
body.error {background-color: #F0F0F0;}
body,th,td,ul,li,a,div,p,pre {color:#333333; font-family:Verdana,"Bitstream Vera Sans",Arial,Helvetica,Sans,"Bitstream Vera Serif"; font-size:9.0pt;}

a img {
  border:none;
}

h1 {
  margin-bottom:1.5em;
}

.center {
  text-align:center;
}

.center * {
  margin-left:auto;
  margin-right:auto;
}

td {
  text-align:left;
}

div#nav {
  margin-bottom:2em;
}

a#owncloud-logo {
  margin-left:auto;
  margin-right:auto;
  display:block;
  width:200px;
  height:99px;
  background: transparent url(<?php if(isset($WEBROOT)) echo($WEBROOT); ?>/img/owncloud-logo-small.png) no-repeat scroll 0 0;
}

a#owncloud-logo span {
  display:none;
}

.nametext a {color:#333333; font-size:8pt; font-weight:bold; text-decoration:none;}
.errortext {color:#CC3333; font-size:9pt; font-weight:bold; text-decoration:none;}
.highlighttext {color:#333333; font-size:9pt; font-weight:bold; text-decoration:none;}
.datetext {color:#333333; font-size:7pt;}
.sizetext {color:#333333; font-size:7pt;}
.footer {color:#999999; text-align:center; font-size:9pt; margin-top:4em;}
.footer a {color:#999999; text-decoration:none;}
.hint {color:#AAAAAA; text-align:center; font-size:8pt; margin-top:4em; margin-bottom:2em;}
.hint a{color:#AAAAAA; text-align:center; font-size:8pt;}

.formstyle {
  font-weight:normal;
  font-size: 8.0pt;
  color: #555555;
  background-color: #FFFFFF;
  border: 1px solid #DDDDDD;
  padding:0px;
  margin:0px;
}

.loginform {
  background-color: #EEEEEE;
}

.browser {
  background-color: #EEEEEE;
}

.browserline {
  background-color: #EEEEEE;
}

.browserline:hover {
  background-color: #DDDDDD;
}


.navigationitem1 {
  background-color: #EEEEEE;
  color:#555555; 
  font-size:9pt; 
  font-weight:bold;
}

.navigationitem1 a{
  text-decoration:none;
  padding-right:15px;
  background: transparent url(<?php if(isset($WEBROOT)) echo($WEBROOT); ?>/img/dots.png) no-repeat scroll center right;
}

.navigationitem1 img {
  border:none;
}

.navigationitem1:hover {
  background-color: #EEEEEE;
}

.navigationitem {
  background-color: #EEEEEE;
}

.navigationitem:hover {
  background-color: #DDDDDD;
}

.navigationselected td {
  background-color: #DDDDDD;
}

.navigationitem a {
  text-decoration:none;
  color: #333333;
  font-size: 8.0pt;
}

.navigationitemselected a {
  text-decoration:none;
  color: #333333;
  font-size: 8.0pt;
  font-weight:bold;
}




