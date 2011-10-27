<?php /**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */?>

<div id="controls">
	<a class="button" target="_blank" href="http://owncloud.org/contribute/writing-apps/"><?php echo $l->t('Add your application');?></a>
</div>
<ul id="leftcontent">
	<?php foreach($_['apps'] as $app):?>
	<li <?php if($app['active']) echo 'class="active"'?> data-id="<?php echo $app['id'] ?>">
		<?php  echo $app['name'] ?>
		<span class="hidden">
			<?php OC_JSON::encodedPrint($app,false) ?>
		</span>
	</li>
	<?php endforeach;?>
</ul>
<div id="rightcontent">
	<h3><strong><span class="name"><?php echo $l->t('Select an App');?></span></strong><span class="version"></span></h3>
	<p class="description"></p>
	<p class="hidden"><span class="licence"></span><?php echo $l->t('-licensed');?> <?php echo $l->t('by');?> <span class="author"></span></p>
	<input class="enable hidden" type="submit" />
</div>
