<?php
global $WEBROOT;
?>
</div>
<div class='foot'>
<?php
if(!OC_UTIL::hasSmallScreen()){
   if (isset($_SERVER['HTTPS'])) {
      $PROTO="webdavs://";
   } else {
      $PROTO="webdav://";
   }
?>
<div class='bar'><p class="hint">
Hint: Mount it via webdav like this: <a href="<?php echo($PROTO.$_SERVER["HTTP_HOST"].$WEBROOT.'/webdav/owncloud.php');?>"><?php echo($PROTO.$_SERVER["HTTP_HOST"].$WEBROOT);?>/webdav/owncloud.php</a>
</p></div>
<?php }?>
<p class="footer">
<?php
	$version=implode('.',OC_UTIL::getVersion());
  echo('<a href="http://ownCloud.org">ownCloud</a> - '.$version);
?>
</p>
</div>
</div>
</body></html>
