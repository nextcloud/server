<?php

/**
* ownCloud - gallery application
*
* @author Bartek Przybylski
* @copyright 2012 Bartek Przybylski bartek@alefzero.eu
* 
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*  
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

if (!isset($_GET['token']) || empty($_GET['token'])) {
  exit;
}



OCP\App::checkAppEnabled('gallery');

?>
<!doctype html>
<html>
  <head>
    <link rel="stylesheet" href="css/sharing.css" type="text/css"/>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
    <script src="js/sharing.js" type="text/javascript"></script>
    <script>
      var TOKEN = '<?php echo htmlentities($_GET['token']); ?>';
    </script>
  </head>
  <body>
    <div id="breadcrumb"><span class="breadcrumbelement" onclick="javascript:returnTo(0);return false;">Shared gallery</span></div>
    <div id="gallery_list"></div>
  </body>
</html>
