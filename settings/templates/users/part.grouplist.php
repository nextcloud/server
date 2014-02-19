<ul>
	<!-- Add new group -->
	<li>
		<form id="newgroup">
			<input type="text" id="newgroupname" placeholder="<?php p($l->t('Group')); ?>..." />
			<input type="submit" class="button" value="<?php p($l->t('Add Group'))?>" />
		</form>
	</li>
	<!-- Everyone -->
	<li>
		<a href="#"><?php p($l->t('Everyone')); ?></a>
	</li>

	<!-- The Admin Group -->
	<?php foreach($_["adminGroup"] as $adminGroup): ?>
		<li>
			<a href="#"><?php p($l->t('Admins')); ?></a>
			<span class="utils">
				<span class="usercount"><?php if(count($adminGroup['useringroup']) > 0) { p(count($adminGroup['useringroup'])); } ?></span>
			</span>
		</li>
	<?php endforeach; ?>

	<!--List of Groups-->
	<?php foreach($_["groups"] as $group): ?>
		<li data-gid="<?php p($group['name']) ?>">
			<a href="#"><?php p($group['name']); ?></a>
			<span class="utils">
				<span class="usercount"><?php if(count($group['useringroup']) > 0) { p(count($group['useringroup'])); } ?></span>
				<img class="svg action rename" src="<?php p(image_path('core', 'actions/rename.svg'))?>"
					alt="<?php p($l->t("change group name"))?>" title="<?php p($l->t("change group name"))?>" />
				<a href="#" class="action delete" original-title="<?php p($l->t('Delete'))?>">
					<img src="<?php print_unescaped(image_path('core', 'actions/delete.svg')) ?>" class="svg" />
				</a>
			</span>
		</li>
	<?php endforeach; ?>
</ul>