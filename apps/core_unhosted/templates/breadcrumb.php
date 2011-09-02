	<a href="<?php echo link_to("files_publiclink", "get.php?token=".$_['token']); ?>"><img src="<?php echo image_path("", "actions/go-home.png"); ?>" alt="Root" /></a>
	<?php foreach($_["breadcrumb"] as $crumb): ?>
		<a href="<?php echo link_to("files_publiclink", "get.php?token=".$_['token']."&path=".$crumb["dir"]); ?>"><?php echo htmlspecialchars($crumb["name"]); ?></a>
	<?php endforeach; ?>