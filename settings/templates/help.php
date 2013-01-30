<div id="controls">
	<?php if($_['admin']) { ?>
		<a class="button newquestion <?php echo($_['style1']); ?>" href="<?php echo($_['url1']); ?>"><?php echo $l->t( 'User Documentation' ); ?></a>
		<a class="button newquestion <?php echo($_['style2']); ?>" href="<?php echo($_['url2']); ?>"><?php echo $l->t( 'Administrator Documentation' ); ?></a>
	<?php } ?>
	<a class="button newquestion" href="http://owncloud.org/support" target="_blank"><?php echo $l->t( 'Online Documentation' ); ?></a>
	<a class="button newquestion" href="http://forum.owncloud.org" target="_blank"><?php echo $l->t( 'Forum' ); ?></a>
	<?php if($_['admin']) { ?>
		<a class="button newquestion" href="https://github.com/owncloud/core/issues" target="_blank"><?php echo $l->t( 'Bugtracker' ); ?></a>
	<?php } ?>
	<a class="button newquestion" href="http://owncloud.com" target="_blank"><?php echo $l->t( 'Commercial Support' ); ?></a>
</div>
<br /><br />
<iframe src="<?php echo($_['url']); ?>" width="100%" id="ifm" ></iframe>