<ul id="usergrouplist">
	<!-- Add new group -->
	<li id="newgroup-init">
		<a href="#">
			<span><?php p($l->t('Add Group'))?></span>
		</a>
	</li>
	<li id="newgroup-form" style="display: none">
		<form>
			<input type="text" id="newgroupname" placeholder="<?php p($l->t('Group')); ?>..." />
			<input type="submit" class="button icon-add svg" value="" />
		</form>
	</li>
	<!-- Everyone -->
	<li id="everyonegroup" data-gid="_everyone" data-usercount="" class="isgroup">
		<a href="#">
			<span class="groupname">
				<?php p($l->t('Everyone')); ?>
			</span>
		</a>
		<span class="utils">
			<span class="usercount" id="everyonecount">

			</span>
		</span>
	</li>

	<!-- The Admin Group -->
	<?php foreach($_["adminGroup"] as $adminGroup): ?>
		<li data-gid="admin" data-usercount="<?php if($adminGroup['usercount'] > 0) { p($adminGroup['usercount']); } ?>" class="isgroup">
			<a href="#"><span class="groupname"><?php p($l->t('Admins')); ?></span></a>
			<span class="utils">
				<span class="usercount"><?php if($adminGroup['usercount'] > 0) { p($adminGroup['usercount']); } ?></span>
			</span>
		</li>
	<?php endforeach; ?>

	<!--List of Groups-->
	<?php foreach($_["groups"] as $group): ?>
		<li data-gid="<?php p($group['name']) ?>" data-usercount="<?php p($group['usercount']) ?>" class="isgroup">
			<a href="#" class="dorename">
				<span class="groupname"><?php p($group['name']); ?></span>
			</a>
			<span class="utils">
				<span class="usercount"><?php if($group['usercount'] > 0) { p($group['usercount']); } ?></span>
				<a href="#" class="action delete" original-title="<?php p($l->t('Delete'))?>">
					<img src="<?php print_unescaped(image_path('core', 'actions/delete.svg')) ?>" class="svg" />
				</a>
			</span>
		</li>
	<?php endforeach; ?>
</ul>
