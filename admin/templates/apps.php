<ul id="leftcontent">
	<?php foreach($_['apps'] as $app):?>
		<li <?php if($app['active']) echo 'class="active"'?> data-id="<?php echo $app['id'] ?>">
			<?php  echo $app['name'] ?>
			<span class="hidden">
				<?php echo json_encode($app) ?>
			</span>
		</li>
	<?php endforeach;?>
</ul>
<div id="rightcontent">
	<h3><span class="name"><?php echo $l->t('Select an App');?></span><span class="version"></span></h3>
	<p class="description"></p>
	<p class="hidden"><?php echo $l->t('By: ');?><span class="author"></span></p>
	<p class="hidden"><?php echo $l->t('Licence: ');?><span class="licence"></span></p>
	<input class="enable hidden" type="submit"></input>
</div>
