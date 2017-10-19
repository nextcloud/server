<ul id="usergrouplist" data-sort-groups="<?php p($_['sortGroups']); ?>">
	<!-- Add new group -->
	<?php if ($_['isAdmin']) { ?>
	<li id="newgroup-entry">
		<a href="#" class="icon-add" id="newgroup-init"><?php p($l->t('Add group'))?></a>
		<div class="app-navigation-entry-edit" id="newgroup-form">
			<form>
				<input type="text" id="newgroupname" placeholder="<?php p($l->t('Add group'))?>">
				<input type="submit" value="" class="icon-checkmark">
			</form>
		</div>
	</li>
	<?php } ?>
	<!-- Everyone -->
	<li id="everyonegroup" data-gid="_everyone" data-usercount="" class="isgroup">
		<a href="#">
			<span class="groupname">
				<?php p($l->t('Everyone')); ?>
			</span>
		</a>
		<div class="app-navigation-entry-utils">
			<ul>
				<li class="usercount app-navigation-entry-utils-counter" id="everyonecount"></li>
			</ul>
		</div>
	</li>

	<!-- The Admin Group -->
	<?php foreach($_["adminGroup"] as $adminGroup): ?>
		<li data-gid="admin" data-usercount="<?php if($adminGroup['usercount'] > 0) { p($adminGroup['usercount']); } ?>" class="isgroup">
			<a href="#"><span class="groupname"><?php p($l->t('Admins')); ?></span></a>
			<div class="app-navigation-entry-utils">
				<ul>
					<li class="app-navigation-entry-utils-counter"><?php if($adminGroup['usercount'] > 0) { p($adminGroup['usercount']); } ?></li>
				</ul>
			</div>
		</li>
	<?php endforeach; ?>

	<!-- Disabled Users -->
	<?php $disabledUsersGroup = $_["disabledUsersGroup"] ?>
	<li data-gid="_disabledUsers" data-usercount="<?php if($disabledUsersGroup['usercount'] > 0) { p($disabledUsersGroup['usercount']); } ?>" class="isgroup">
		<a href="#"><span class="groupname"><?php p($l->t('Disabled')); ?></span></a>
		<div class="app-navigation-entry-utils">
			<ul>
				<li class="app-navigation-entry-utils-counter"><?php if($disabledUsersGroup['usercount'] > 0) { p($disabledUsersGroup['usercount']); } ?></li>
			</ul>
		</div>
	</li>

	<!--List of Groups-->
	<?php foreach($_["groups"] as $group): ?>
		<li data-gid="<?php p($group['name']) ?>" data-usercount="<?php p($group['usercount']) ?>" class="isgroup">
			<a href="#" class="dorename">
				<span class="groupname"><?php p($group['name']); ?></span>
			</a>
			<div class="app-navigation-entry-utils">
				<ul>
					<li class="app-navigation-entry-utils-counter"><?php if($group['usercount'] > 0) { p($group['usercount']); } ?></li>
				 	<?php if($_['isAdmin']): ?>
				 		<li class="app-navigation-entry-utils-menu-button delete">
							<button class="icon-delete"></button>
						</li>
					<?php endif; ?>
				</ul>
			</div>
		</li>
	<?php endforeach; ?>
</ul>
