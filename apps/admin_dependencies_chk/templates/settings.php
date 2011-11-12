<fieldset id="status_list" class="personalblock">
	<legend><?php echo $l->t('Dependencies status');?></legend>
	<ul class="state">
		<?php foreach($_['items'] as $item):?>
			<li class="state_<?php echo $item['status'];?>">
				<span class="state_module" title="<?php echo $item['message'];?>"><?php echo $item['part'];?></span>
				<div class="state_used"><?php echo $l->t('Used by :');?>
					<ul>
						<?php foreach($item['modules'] as $module):?>
							<li><?php echo $module;?></li>
						<?php endforeach;?>
					</ul>
			</li>
		<?php endforeach;?>
	</ul>
</fieldset>