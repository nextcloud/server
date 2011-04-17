	<a href="<?php echo link_to("files", "index.php?dir=/"); ?>"><img src="<?php echo image_path("", "actions/go-home.png"); ?>" alt="Root" /></a>
	<?php foreach($_["breadcrumb"] as $crumb): ?>
		<a href="<?php echo link_to("files", "index.php?dir=".$crumb["dir"]); ?>"><?php echo $crumb["name"]; ?></a>
	<?php endforeach; ?>