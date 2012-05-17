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

OCP\App::checkAppEnabled('remoteStorage');
require_once('Sabre/autoload.php');
require_once('lib_remoteStorage.php');
require_once('oauth_ro_auth.php');

ini_set('default_charset', 'UTF-8');
#ini_set('error_reporting', '');
@ob_clean();

foreach($_GET as $k => $v) {
  if($k=='userid'){
    $userId=$v;
  } else if($k=='redirect_uri'){
    $appUrlParts=explode('/', $v);
    $appUrl = $appUrlParts[2];//bit dodgy i guess
  } else if($k=='scope'){
    $categories=$v;
  }
}
$currUser = OCP\USER::getUser();
if($userId && $appUrl && $categories) {
  if($currUser == $userId) {
    if(isset($_POST['allow'])) {
      //TODO: check if this can be faked by editing the cookie in firebug!
      $token=OC_remoteStorage::createCategories($appUrl, $categories);
      header('Location: '.$_GET['redirect_uri'].'#access_token='.$token.'&token_type=bearer');
    } else if($existingToken = OC_remoteStorage::getTokenFor($appUrl, $categories)) {
      header('Location: '.$_GET['redirect_uri'].'#access_token='.$existingToken.'&token_type=bearer');
    } else {
      //params ok, logged in ok, but need to click Allow still:
?>
<!DOCTYPE html>
<html>
	<head>
	<title>ownCloud</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="shortcut icon" href="../../../core/img/favicon.png" /><link rel="apple-touch-icon-precomposed" href="../../../core/img/favicon-touch.png" />
			<link rel="stylesheet" href="../../../core/css/styles.css" type="text/css" media="screen" />
			<link rel="stylesheet" href="../../../core/css/auth.css" type="text/css" media="screen" />
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
			<h2><img src="../../../core/img/remoteStorage-big.png" alt="remoteStorage" /></h2>
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
		}//end 'need to click Allow still'
	} else {//login not ok
		if($currUser) {
			die('You are logged in as '.$currUser.' instead of '.$userId);
		} else {
			header('Location: /?redirect_url='.urlencode('/apps/remoteStorage/auth.php'.$_SERVER['PATH_INFO'].'?'.$_SERVER['QUERY_STRING']));
		}
	}
} else {//params not ok
	die('please use e.g. /?app=remoteStorage&getfile=auth.php&userid=admin&redirect_uri=http://host/path&scope=...');
}
