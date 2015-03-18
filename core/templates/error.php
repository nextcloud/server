<ul class="error-wide">
	<?php foreach($_["errors"] as $error):?>
		<li class='error'>
			<?php p($error['error']) ?><br>
			<?php if(isset($error['hint']) && $error['hint']): ?>
				<p class='hint'><?php print_unescaped($error['hint']) ?></p>
			<?php endif;?>
		</li>
	<?php endforeach ?>
</ul>
