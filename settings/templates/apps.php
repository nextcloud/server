<?php /**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */?>
 <script type="text/javascript"
	src="<?php print_unescaped(OC_Helper::linkToRoute('apps_custom'));?>?appid=<?php p($_['appid']); ?>"></script>
 <script type="text/javascript" src="<?php print_unescaped(OC_Helper::linkTo('settings/js', 'apps.js'));?>"></script>

<div id="controls">
	<a class="button" target="_blank" href="http://owncloud.org/dev"><?php p($l->t('Add your App'));?></a>
	<a class="button" target="_blank" href="http://apps.owncloud.com"><?php p($l->t('More Apps'));?></a>
</div>
<ul id="leftcontent" class="applist hascontrols">
	<?php foreach($_['apps'] as $app):?>
	<li <?php if($app['active']) print_unescaped('class="active"')?> data-id="<?php p($app['id']) ?>"
		<?php if ( isset( $app['ocs_id'] ) ) { print_unescaped("data-id-ocs=\"{".OC_Util::sanitizeHTML($app['ocs_id'])."}\""); } ?>
			data-type="<?php p($app['internal'] ? 'internal' : 'external') ?>" data-installed="1">
		<a class="app<?php if(!$app['internal']) p(' externalapp') ?>"
			href="?appid=<?php p($app['id']) ?>"><?php p($app['name']) ?></a>
		<?php  if(!$app['internal'])
			print_unescaped('<small class="'.OC_Util::sanitizeHTML($app['internalclass']).' list">'.OC_Util::sanitizeHTML($app['internallabel']).'</small>') ?>
	</li>
	<?php endforeach;?>
</ul>
<div id="rightcontent">
	<div class="appinfo">
	<h3><strong><span class="name"><?php p($l->t('Select an App'));?></span></strong><span
		class="version"></span><small class="externalapp" style="visibility:hidden;"></small></h3>
	<span class="score"></span>
	<p class="description"></p>
	<img src="" class="preview" />
	<p class="appslink hidden"><a href="#" target="_blank"><?php
		p($l->t('See application page at apps.owncloud.com'));?></a></p>
	<p class="license hidden"><?php
		print_unescaped($l->t('<span class="licence"></span>-licensed by <span class="author"></span>'));?></p>
	<input class="enable hidden" type="submit" />
	<input class="update hidden" type="submit" value="<?php p($l->t('Update')); ?>" />
	</div>
</div>
