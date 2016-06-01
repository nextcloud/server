<div class="quota">
	<!-- Default storage -->
	<span><?php p($l->t('Default Quota'));?></span>
	<?php if((bool) $_['isAdmin']): ?>
		<select id='default_quota' data-inputtitle="<?php p($l->t('Please enter storage quota (ex: "512 MB" or "12 GB")')) ?>" data-tipsy-gravity="s">
			<option <?php if($_['default_quota'] === 'none') print_unescaped('selected="selected"');?> value='none'>
				<?php p($l->t('Unlimited'));?>
			</option>
			<?php foreach($_['quota_preset'] as $preset):?>
				<?php if($preset !== 'default'):?>
					<option <?php if($_['default_quota']==$preset) print_unescaped('selected="selected"');?> value='<?php p($preset);?>'>
						<?php p($preset);?>
					</option>
				<?php endif;?>
			<?php endforeach;?>
			<?php if($_['defaultQuotaIsUserDefined']):?>
				<option selected="selected" value='<?php p($_['default_quota']);?>'>
					<?php p($_['default_quota']);?>
				</option>
			<?php endif;?>
			<option data-new value='other'>
				<?php p($l->t('Other'));?>
				...
			</option>
		</select>
	<?php endif; ?>
	<?php if((bool) !$_['isAdmin']): ?>
		: 
		<?php if( $_['default_quota'] === 'none'): ?>
			<?php p($l->t('Unlimited'));?>
		<?php else: ?>
			<?php p($_['default_quota']);?>
		<?php endif; ?>
	<?php endif; ?>
</div>
