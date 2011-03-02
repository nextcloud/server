<?php
/*
 * Template for admin pages
 */
?>
<h1>Administration</h1>

<ul>
	<? foreach( $_["adminpages"] as $i ){ ?>
		<li><a href="<? echo link_to( $i["app"], $i["file"] ) ?>"><? echo $i["name"] ?></a></li>
	<? } ?>
</ul>
