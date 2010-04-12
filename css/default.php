<?php
header('Content-Type: text/css');
require_once('../inc/lib_base.php');
?>
html,body {
    background-color: #F9F9F9;
    margin:0px;
    height:100%;
    width:100%;
    position:absolute;
}
#mainlayout{
	width:100%;
	height:100%;
}

#mainlayout>div{
	position:absolute;
	width:100%;
	left:0px;
}

#mainlayout>.head{
	height:175px;
	top:0px;
}

#mainlayout>.body{
	vertical-align:top;
	top:175px;
	bottom:75px;
	overflow:auto;
}

#mainlayout>.foot{
	height:75px;
	bottom:0px;
}

#mainlayout>.foot>.bar{
	background-color:#EEE;
	position:absolute;
	top:0px;
	height:24px;
	width:100%;
}

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
  width:100%;
  background-color: #EEEEEE;
  padding:0px;
  margin:0px;
  position:absolute;
  bottom:0px;
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

.nametext a, .breadcrumb a{color:#333333; font-size:8pt; font-weight:bold; text-decoration:none;}
.errortext {color:#CC3333; font-size:9pt; font-weight:bold; text-decoration:none;}
.highlighttext {color:#333333; font-size:9pt; font-weight:bold; text-decoration:none;}
.datetext {color:#333333; font-size:7pt;}
.sizetext{
	color:#333333;
	font-size:7pt;
}
.footer {color:#999999; text-align:center; font-size:9pt; margin-top:4em;}
.footer a {color:#999999; text-decoration:none;}
.hint {color:#AAAAAA; text-align:center; font-size:8pt; margin-top:10px;}
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

.hidden{
    height:0px;
    width:0px;
    margin:0px;
    padding:0px;
    border:0px;
    position:absolute;
    top:0px;
    left:0px;
    overflow:hidden;
    /*do not use display:none here, it breaks iframes in some browsers*/
}

div.OCNotification{
    background:#0c285a;
    color:white;
    border:white solid 1px;
    padding:1px;
    margin:4px;
    min-width:200px;
}
div.OCNotificationHolder{
    right:20px;
    bottom:0px;
    position:fixed;
    color:white;
}

td img.file_actions{
    cursor:pointer;
    height:0px;
    width:9px;
}

td.nametext:hover img.file_actions{
    height:auto;
}

div.breadcrumb{
   background-color: #F0F0F0; 
}

div.fileactionlist{
    position:absolute;
    background-color: #DDDDDD;
    margin-top:5px;
    border:1px black solid;
}

div.fileactionlist td{
    cursor:pointer;
}

div.fileactionlist td:hover{
    background-color: #CCCCCC;
}

tr.breadcrumb{
	background-color: #CCCCCC;
}

#content, div.browser{
	vertical-align:top;
	/*min-height:200px;*/
	height:100%;
}

table.browser{
	border: solid 3px #CCC;
	height:100%;
	border-spacing:0px;
}

table.browser thead, table.browser tfoot{
	background-color:#CCC;
	width:100%;
}

td.sizetext{
	width:110px;
	text-align:right;
}

td.fileSelector, td.fileicon{
	width:16px;
}

span.upload{
	float:right;
	text-align:right;
	margin:0px;
	padding:0px;
}

table.browser>tbody{
	vertical-align:top;
}

table.browser>tbody>tr>td, table.browser>tbody>tr{
	padding:0px;
	/*height:100%;*/
}

div.fileList{
	width:800px;
	overflow:auto;
	vertical-align:top;
	height:100%;
	min-height:200px;
	top:0px;
}

div.fileList table{
	width:100%;
	vertical-align:top;
}

table.browser thead td,table.browser tfoot td{
	padding-left:6px;
	padding-top:0px;
	padding-bottom:0px;
}

#imageframe{
	position:absolute;
	top:0px;
	left:0px;
	height:100%;
	width:100%;
	background:rgb(20,20,20);
	background:rgba(20,20,20,0.9);
	text-align:center;
}

#imageframe img{
	vertical-align:middle;
	max-height:90%;
	max-width:90%;
	margin:10px;
	border: black solid 3px;
}

tr.hint, tr.hint td{
	background:transparent;
}