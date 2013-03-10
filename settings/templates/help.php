<div id="controls">
	<?php if($_['admin']) { ?>
		<a class="button newquestion <?php p($_['style1']); ?>"
			href="<?php print_unescaped($_['url1']); ?>"><?php p($l->t( 'User Documentation' )); ?></a>
		<a class="button newquestion <?php p($_['style2']); ?>"
			href="<?php print_unescaped($_['url2']); ?>"><?php p($l->t( 'Administrator Documentation' )); ?></a>
	<?php } ?>
	<a class="button newquestion" href="http://owncloud.org/support" target="_blank"><?php
		p($l->t( 'Online Documentation' )); ?></a>
	<a class="button newquestion" href="http://forum.owncloud.org" target="_blank"><?php
		p($l->t( 'Forum' )); ?></a>
	<?php if($_['admin']) { ?>
		<a class="button newquestion" href="https://github.com/owncloud/core/blob/master/CONTRIBUTING.md" target="_blank"><?php
			p($l->t( 'Bugtracker' )); ?></a>
	<?php } ?>
	<a class="button newquestion" href="http://owncloud.com" target="_blank"><?php
		p($l->t( 'Commercial Support' )); ?></a>
</div>
<div class="help-includes">
	<iframe src="<?php print_unescaped($_['url']); ?>" class="help-iframe">abc</iframe>
</div>
