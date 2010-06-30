<?php
header('Content-Type: text/css');
require_once('../inc/lib_base.php');
?>
html, body {
	background-color: #F9F9F9;
	margin: 0px;
	height: 100%;
	width: 100%;
	position: absolute;
	font-size: 100%;
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
td.error{color:#FF0000; text-align:center}
body,th,td,ul,li,a,div,p,pre {color:#333333; font-family:Verdana,"Bitstream Vera Sans",Arial,Helvetica,Sans,"Bitstream Vera Serif"; font-size: 0.95em;}

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

td.nametext{
	position:relative;
	display:block;
}

.nametext a, .breadcrumb a{color:#333333; font-size: 0.8em; font-weight:bold; text-decoration:none;}
.errortext {color:#CC3333; font-size: 0.95em; font-weight:bold; text-decoration:none;}
.highlighttext {color:#333333; font-size: 0.95em; font-weight:bold; text-decoration:none;}
.datetext {color:#333333; font-size: 0.7em;}
.sizetext{
	color:#333333;
	font-size: 0.7em;
}
.footer {color:#999999; text-align:center; font-size: 0.95em; margin-top:4em;}
.footer a {color:#999999; text-decoration:none;}
.hint {color:#AAAAAA; text-align:center; font-size: 0.8em; margin-top:10px;}
.hint a{color:#AAAAAA; text-align:center; font-size: 0.8em;}

.formstyle {
  font-weight:normal;
  font-size: 0.8em;
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
  font-size: 0.95em; 
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
  font-size: 0.8em;
}

.navigationitemselected a {
  text-decoration:none;
  color: #333333;
  font-size: 0.8em;
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
	z-index:50;
    position:absolute;
    top:20px;
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


input.fileSelector{
	margin-right:17px;
	float:left;
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
<!-- 	border-bottom: 3px solid #CCC; -->
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

#debug{
	position:fixed;
	bottom:20px;
	left:20px;
	border:solid 1px black;
}

.dragClone{
	position:absolute;
}

div.breadcrumb{
	float:left;
	background:transparent;
}

div.moreActionsButton>p{
	padding:0px;
	margin:0px;
	width:100%;
	height:100%;
}

div.moreActionsButton{
	background-color:white;
	display:inline;
	border:1px solid black;
	cursor:pointer;
	padding-right:10px;
	text-align:right;
	width:90px;
	height:19px;
	float:right;
	margin-top:2px !important;
	right:2px;
	position:absolute;
	background:#DDD url(<?php if(isset($WEBROOT)) echo($WEBROOT); ?>/img/arrow_up.png) no-repeat scroll center right;
}

td.moreActionsButtonClicked{
	background:#DDD url(<?php if(isset($WEBROOT)) echo($WEBROOT); ?>/img/arrow_down.png) no-repeat scroll center right !important
}

tr.utilityline{
	height:24px;
}

td.actionsSelected{
	position:absolute;
	width:790px;
}

div.moreActionsList{
	background:#EEE;
	position:absolute;
	bottom:19px;
	right:-2px;
	border:1px solid black;
	min-width:330px;
	text-align:right;
	float:right;
}

div.moreActionsList input{
<!-- 	float:right; -->
}

div.moreActionsList>table{
	width:100%;
}

div.moreActionsList td{
	width:300px;
	text-align:right;
	padding-top:3px !important;
	padding-bottom:3px !important;
}

div.moreActionsList tr:hover{
	background-color:#DDD;
}