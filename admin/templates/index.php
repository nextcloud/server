<?php
/*
 * Template for admin pages
 */
?>
<h1>Administration</h1>

<ul>
	<?php foreach($_["adminpages"] as $i): ?>
		<li><a href="<?php echo link_to($i["app"], $i["file"]) ?>"><?php echo $i["name"] ?></a></li>
	<?php endforeach; ?>
</ul>
