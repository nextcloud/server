<?php /**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */?>
 <script type="text/javascript" src="<?php echo OC_Helper::linkToRoute('apps_custom');?>?appid=<?php echo $_['appid']; ?>"></script>
 <script type="text/javascript" src="<?php echo OC_Helper::linkTo('settings/js', 'apps.js');?>"></script>

<div id="controls">
	<a class="button" target="_blank" href="http://owncloud.org/dev"><?php echo $l->t('Add your App');?></a>
	<a class="button" target="_blank" href="http://apps.owncloud.com"><?php echo $l->t('More Apps');?></a>
</div>
<ul id="leftcontent" class="applist">
	<?php foreach($_['apps'] as $app):?>
	<li <?php if($app['active']) echo 'class="active"'?> data-id="<?php echo $app['id'] ?>" <?php if ( isset( $app['ocs_id'] ) ) { echo "data-id-ocs=\"{$app['ocs_id']}\""; } ?>
		data-type="<?php echo $app['internal'] ? 'internal' : 'external' ?>" data-installed="1">
		<a class="app<?php if(!$app['internal']) echo ' externalapp' ?>" href="?appid=<?php echo $app['id'] ?>"><?php echo htmlentities($app['name']) ?></a>
		<?php  if(!$app['internal']) echo '<small class="'.$app['internalclass'].' list">'.$app['internallabel'].'</small>' ?>
	</li>
	<?php endforeach;?>
</ul>
<div id="rightcontent">
	<div class="appinfo">
	<h3><strong><span class="name"><?php echo $l->t('Select an App');?></span></strong><span class="version"></span><small class="externalapp" style="visibility:hidden;"></small></h3>
	<span class="score"></span>
	<p class="description"></p>
	<img src="" class="preview" />
	<p class="appslink hidden"><a href="#" target="_blank"><?php echo $l->t('See application page at apps.owncloud.com');?></a></p>
	<p class="license hidden"><?php echo $l->t('<span class="licence"></span>-licensed by <span class="author"></span>');?></p>
	<input class="enable hidden" type="submit" />
	<input class="update hidden" type="submit" value="<?php echo($l->t('Update')); ?>" />
	</div>
</div>
