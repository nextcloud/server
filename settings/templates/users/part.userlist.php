<table id="userlist" class="hascontrols grid" data-groups="<?php p($_['allGroups']);?>">
	<thead>
		<tr>
			<?php if ($_['enableAvatars']): ?>
			<th id='headerAvatar'></th>
			<?php endif; ?>
			<th id='headerName'><?php p($l->t('Username'))?></th>
			<th id="headerDisplayName"><?php p($l->t( 'Full Name' )); ?></th>
			<th id="headerPassword"><?php p($l->t( 'Password' )); ?></th>
			<th class="mailAddress"><?php p($l->t( 'Email' )); ?></th>
			<th id="headerGroups"><?php p($l->t( 'Groups' )); ?></th>
			<?php if(is_array($_['subadmins']) || $_['subadmins']): ?>
			<th id="headerSubAdmins"><?php p($l->t('Group Admin for')); ?></th>
			<?php endif;?>
			<th id="headerQuota"><?php p($l->t('Quota')); ?></th>
			<th class="storageLocation"><?php p($l->t('Storage Location')); ?></th>
			<th class="userBackend"><?php p($l->t('User Backend')); ?></th>
			<th class="lastLogin"><?php p($l->t('Last Login')); ?></th>
			<th id="headerRemove">&nbsp;</th>
		</tr>
	</thead>
	<tbody>
		<!-- the following <tr> is used as a template for the JS part -->
		<tr style="display:none">
			<?php if ($_['enableAvatars']): ?>
				<td class="avatar"><div class="avatardiv"></div></td>
			<?php endif; ?>
			<td class="name"></td>
			<td class="displayName"><span></span> <img class="svg action"
				src="<?php p(image_path('core', 'actions/rename.svg'))?>"
				alt="<?php p($l->t("change full name"))?>" title="<?php p($l->t("change full name"))?>"/>
			</td>
			<td class="password"><span>●●●●●●●</span> <img class="svg action"
				src="<?php print_unescaped(image_path('core', 'actions/rename.svg'))?>"
				alt="<?php p($l->t("set new password"))?>" title="<?php p($l->t("set new password"))?>"/>
			</td>
			<td class="mailAddress"><span></span> <img class="svg action"
				src="<?php p(image_path('core', 'actions/rename.svg'))?>"
				alt="<?php p($l->t('change email address'))?>" title="<?php p($l->t('change email address'))?>"/>
			</td>
			<td class="groups"></td>
			<?php if(is_array($_['subadmins']) || $_['subadmins']): ?>
				<td class="subadmins"></td>
			<?php endif;?>
			<td class="quota">
				<select class='quota-user' data-inputtitle="<?php p($l->t('Please enter storage quota (ex: "512 MB" or "12 GB")')) ?>">
					<option	value='default'>
						<?php p($l->t('Default'));?>
					</option>
					<option value='none'>
						<?php p($l->t('Unlimited'));?>
					</option>
					<?php foreach($_['quota_preset'] as $preset):?>
						<option value='<?php p($preset);?>'>
							<?php p($preset);?>
						</option>
					<?php endforeach;?>
					<option value='other' data-new>
						<?php p($l->t('Other'));?> ...
					</option>
				</select>
			</td>
			<td class="storageLocation"></td>
			<td class="userBackend"></td>
			<td class="lastLogin"></td>
			<td class="remove"></td>
		</tr>
	</tbody>
</table>
