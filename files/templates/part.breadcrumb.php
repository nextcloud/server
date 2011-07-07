	<div class='crumb' data-dir='/'>
		<a href="<?php echo link_to("files", "index.php?dir=/"); ?>"><img src="<?php echo image_path("", "actions/go-home.png"); ?>" alt="Root"/></a>
	</div>
	<?php foreach($_["breadcrumb"] as $crumb): ?>
		<div class='crumb' data-dir='<?php echo $crumb["dir"];?>'>
			<a href="<?php echo link_to("files", "index.php?dir=".$crumb["dir"]); ?>"><?php echo htmlspecialchars($crumb["name"]); ?></a>
		</div>
	<?php endforeach; ?>