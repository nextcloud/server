<?php

/**
* ownCloud
*
* Original:
* @author Frank Karlitschek
* @copyright 2010 Frank Karlitschek karlitschek@kde.org
* 
* Adapted:
* @author Michiel de Jong, 2012
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.	If not, see <http://www.gnu.org/licenses/>.
*
*/


// Do not load FS ...
$RUNTIME_NOSETUPFS = true;

require_once('../../lib/base.php');
OC_Util::checkAppEnabled('remoteStorage');
require_once('Sabre/autoload.php');
require_once('lib_remoteStorage.php');
require_once('oauth_ro_auth.php');

ini_set('default_charset', 'UTF-8');
#ini_set('error_reporting', '');
@ob_clean();

$path = substr($_SERVER["REQUEST_URI"], strlen($_SERVER["SCRIPT_NAME"]));
$pathParts =	explode('/', $path);

if(count($pathParts) == 2 && $pathParts[0] == '') {
	//TODO: input checking. these explodes may fail to produces the desired arrays:
	$subPathParts = explode('?', $pathParts[1]);
	$ownCloudUser = $subPathParts[0];
	foreach($_GET as $k => $v) {
		if($k=='user_address'){
			$userAddress=$v;
		} else if($k=='redirect_uri'){
			$appUrlParts=explode('/', $v);
		$appUrl = $appUrlParts[2];//bit dodgy i guess
		} else if($k=='scope'){
			$categories=$v;
		}
	}
	$currUser = OC_User::getUser();
	if($currUser == $ownCloudUser) {
		if(isset($_POST['allow'])) {
			//TODO: check if this can be faked by editing the cookie in firebug!
			$token=OC_remoteStorage::createCategories($appUrl, $categories);
			header('Location: '.$_GET['redirect_uri'].'#access_token='.$token.'&token_type=bearer');
		} else {
?>
<!DOCTYPE html>
<html>
	<head>
	<title>ownCloud</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="shortcut icon" href="../../../core/img/favicon.png" /><link rel="apple-touch-icon-precomposed" href="../../../core/img/favicon-touch.png" />
			<link rel="stylesheet" href="../../../core/css/styles.css" type="text/css" media="screen" />
			<link rel="stylesheet" href="../auth.css" type="text/css" media="screen" />
		</head>
	<body id="body-login">
	<div id="login">
		<header>
		<div id="header">
			<img src="../../../core/img/logo.png" alt="ownCloud" />
		</div>
		</header>
		<section id="main">
		<div id="oauth">
      <h2><img src="../remoteStorage-big.png" alt="remoteStorage" /></h2>
      <p><strong><?php $appUrlParts = explode('/', $_GET['redirect_uri']); echo htmlentities($appUrlParts[2]); ?></strong>
      requests read &amp; write access to your 
      <?php
        $categories = explode(',', htmlentities($_GET['scope']));
        if(!count($categories)) {
          echo htmlentities($_GET['scope']);
        } else {
          echo '<em>'.$categories[0].'</em>';
          if(count($categories)==2) {
            echo ' and <em>'.$categories[1].'</em>';
          } else if(count($categories)>2) {
            for($i=1; $i<count($categories)-1; $i++) {
              echo ', <em>'.$categories[$i].'</em>';
            }
            echo ', and <em>'.$categories[$i].'</em>';
          }
        }
      ?>.
			</p>
			<form accept-charset="UTF-8" method="post">
				<input id="allow-auth" name="allow" type="submit" value="Allow" />
				<input id="deny-auth" name="deny" type="submit" value="Deny" />
			</form>
		</div>
		</section>
	</div>
	<footer><p class="info"><a href="http://owncloud.org/">ownCloud</a> &ndash; web services under your control</p></footer>
	</body>
</html>
<?php
		}
	} else {
		if((isset($_SERVER['HTTPS'])) && ($_SERVER['HTTPS'])) {
			$url = "https://";
		} else {
			$url = "http://";
		}
		$url .= $_SERVER['SERVER_NAME'];
		$url .= substr($_SERVER['SCRIPT_NAME'], 0, -strlen('apps/remoteStorage/compat.php'));
		if($currUser) {
			die('You are logged in as '.$currUser.' instead of '.$ownCloudUser);
		} else {
			header('Location: /?redirect_url='.urlencode('/apps/remoteStorage/auth.php'.$_SERVER['PATH_INFO'].'?'.$_SERVER['QUERY_STRING']));
		}
	}
} else {
	//die('please use auth.php/username?params. '.var_export($pathParts, true));
	die('please use auth.php/username?params.');
}
