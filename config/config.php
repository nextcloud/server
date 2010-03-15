<?php

function getConfig(){
?>
<form method="post" enctype="multipart/form-data" action="index.php" >
<table cellpadding="5" cellspacing="5" border="0" class="loginform">
<tr><td>owner name:</td><td><input type="text" name="CONFIG_FOOTEROWNERNAME" size="30" class="formstyle"></input></td></tr>
<tr><td>owner email:</td><td><input type="text" name="CONFIG_FOOTEROWNEREMAIL" size="30" class="formstyle"></input></td></tr>
<tr><td>admin name:</td><td><input type="text" name="CONFIG_ADMINLOGIN" size="30" class="formstyle"></input></td></tr>
<tr><td>admin password:</td><td><input type="password" name="CONFIG_ADMINPASSWORD" size="30" class="formstyle"></input></td></tr>
<tr><td>retype admin password:</td><td><input type="password" name="CONFIG_ADMINPASSWORD_RETYPE" size="30" class="formstyle"></input></td></tr>
<tr><td>document root:</td><td><input type="text" name="CONFIG_DOCUMENTROOT" size="30" class="formstyle" value="<?php echo realpath(dirname(__FILE__).'/../'); ?>"></input></td></tr>
<tr><td>data directory:</td><td><input type="text" name="CONFIG_DATADIRECTORY" size="30" class="formstyle" value="<?php echo realpath(dirname(__FILE__).'/../'); ?>/data/"></input></td></tr>
<tr><td>site root:</td><td><input type="text" name="CONFIG_SITEROOT" size="30" class="formstyle" value="<?php echo $_SERVER['SERVER_NAME'].dirname($_SERVER['PHP_SELF']); ?>"></input></td></tr>
<tr><td>force ssl:</td><td><input type="checkbox" name="CONFIG_HTTPFORCESSL" size="30" class="formstyle" value='0'></input></td></tr>
<tr><td>date format:</td><td><input type="text" name="CONFIG_DATEFORMAT" size="30" class="formstyle" value='j M Y G:i'></input></td></tr>
<tr><td>database host:</td><td><input type="text" name="CONFIG_DBHOST" size="30" class="formstyle" value='localhost'></input></td></tr>
<tr><td>database name:</td><td><input type="text" name="CONFIG_DBNAME" size="30" class="formstyle" value='owncloud'></input></td></tr>
<tr><td>database user:</td><td><input type="text" name="CONFIG_DBUSER" size="30" class="formstyle" value='owncloud'></input></td></tr>
<tr><td>database password:</td><td><input type="password" name="CONFIG_DBPWD" size="30" class="formstyle" value=''></input></td></tr>
<tr><td>retype database password:</td><td><input type="password" name="CONFIG_DBPWD_RETYPE" size="30" class="formstyle" value=''></input></td></tr>
<tr><td></td><td><input type="submit" name="savebutton" alt="save" value="save" class="formstyle" /></td></tr>
</table></form>
<?php
	die();
}

function writeConfig($config){
	$allowed=array('CONFIG_FOOTEROWNERNAME','CONFIG_FOOTEROWNEREMAIL','CONFIG_ADMINLOGIN','CONFIG_ADMINPASSWORD','CONFIG_DBHOST','CONFIG_DBNAME','CONFIG_DBUSER','CONFIG_DBPWD','CONFIG_DOCUMENTROOT','CONFIG_DATADIRECTORY','CONFIG_HTTPFORCESSL','CONFIG_DATEFORMAT');
	$requireRetype=array('CONFIG_ADMINPASSWORD','CONFIG_DBPWD');
	foreach($requireRetype as $name){
		if($config[$name]!=$config[$name.'_RETYPE']){
			echo "error: passwords don't match";
			getConfig();
		}
	}
	$configString="//config\n";
	foreach($allowed as $name){
		if($config[$name]===''){
			echo "error: empty field not allowed";
			getConfig();
		}
		$GLOBALS[$name]=$config[$name];
		if(is_string($config[$name])){
			$value="'{$config[$name]}'";
		}else{
			$value=(integer)$config[$name];
		}
		$configString.="\$$name = $value;\n";
	}
	
	$configFile=file_get_contents(__FILE__);
	$configFile=str_replace('//config'.'_placeholder',$configString,$configFile);
	file_put_contents(__FILE__,$configFile);
}

//config_placeholder


if(!isset($CONFIG_ADMINLOGIN)){
	if(!isset($_POST['CONFIG_FOOTEROWNERNAME'])){
		getConfig();
	}else{
		writeConfig($_POST);
	}
}

$protocol=strtolower($_SERVER['SERVER_PROTOCOL']);
$CONFIG_PROTOCOL=substr($protocol,0,strpos($protocol,"/"))."://";
$CONFIG_WEBROOT=$CONFIG_PROTOCOL.$CONFIG_SITEROOT;

$CONFIG_LOADPLUGINS='';


// set the right include path
// don´t change unless you know what you are doing
set_include_path(get_include_path().PATH_SEPARATOR.$CONFIG_DOCUMENTROOT.PATH_SEPARATOR.$CONFIG_DOCUMENTROOT.'/inc');


require_once('lib_base.php');

?>
