<?php
global $WEBROOT;
?>
</div>
<div class='foot'>
<div class='bar'><p class="hint">
Hint: Mount it via webdav like this: <a href="webdav://<?php echo($_SERVER["HTTP_HOST"].$WEBROOT.'/webdav/owncloud.php');?>">webdav://<?php echo($_SERVER["HTTP_HOST"].$WEBROOT);?>/webdav/owncloud.php</a>
</p></div>
<p class="footer">
<?php
  echo('<a href="http://ownCloud.org">ownCloud</a> - 1.0 beta 2');
?>
</p>
</div>
</div>
<!--<p id="debug">debug</p>-->
</body></html>
