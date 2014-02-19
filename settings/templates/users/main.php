<?php
/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */
$allGroups=array();
foreach($_["groups"] as $group) {
	$allGroups[] = $group['name'];
}
$_['subadmingroups'] = $allGroups;
$items = array_flip($_['subadmingroups']);
unset($items['admin']);
$_['subadmingroups'] = array_flip($items);
?>

<script type="text/javascript" src="<?php print_unescaped(OC_Helper::linkToRoute('isadmin'));?>"></script>

<div id="app-navigation">
	<?php print_unescaped($this->inc('users/part.grouplist')); ?>
	<div id="app-settings">
		<?php print_unescaped($this->inc('users/part.setquota')); ?>
	</div>
</div>

<div id="app-content">
	<?php print_unescaped($this->inc('users/part.createuser')); ?>
	<?php print_unescaped($this->inc('users/part.userlist')); ?>
</div>