<table>
	<thead>
		<tr>
			<th id="headerSize"><?php echo $l->t( 'Size' ); ?></th>
			<th id="headerDate"><span id="modified"><?php echo $l->t( 'Modified' ); ?></span><span class="selectedActions"><a href="" class="delete"><?php echo $l->t('Delete all')?> <img class="svg" alt="<?php echo $l->t('Delete')?>" src="<?php echo OCP\image_path("core", "actions/delete.svg"); ?>" /></a></span></th>
		</tr>
	</thead>
	<tbody id="fileList" data-readonly="<?php echo $_['readonly'];?>">
		<?php echo($_['fileList']); ?>
	</tbody>
</table>