<ul>
	<?php foreach($_["errors"] as $error):?>
		<li class='error'>
			<?php p($error['error']) ?><br/>
			<p class='hint'><?php if(isset($error['hint']))print_unescaped($error['hint']) ?></p>
		</li>
	<?php endforeach ?>
</ul>
