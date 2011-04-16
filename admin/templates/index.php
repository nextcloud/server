<?php
/*
 * Template for admin pages
 */
?>
<h1>Administration</h1>

<h2>System</h2>
<ul>
	<?php foreach($_["syspages"] as $i): ?>
		<li><a href="<?php echo $i["href"]; ?>"><?php echo $i["name"]; ?></a></li>
	<?php endforeach; ?>
</ul>
<h2>Applications</h2>
<ul>
	<?php foreach($_["apppages"] as $i): ?>
		<li><a href="<?php echo $i["href"]; ?>"><?php echo $i["name"]; ?></a></li>
	<?php endforeach; ?>
</ul>
