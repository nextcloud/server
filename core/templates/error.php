<ul>
	<?php foreach($_["errors"] as $error):?>
		<li class='error'>
			<?php echo $error['error'] ?><br/>
			<p class='hint'><?php if(isset($error['hint']))echo $error['hint'] ?></p>
		</li>
	<?php endforeach ?>
</ul>
