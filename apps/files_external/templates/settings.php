<form id="files_external">
	<fieldset class="personalblock">
	<legend><strong><?php echo $l->t('External Storage'); ?></strong></legend>
		<table id="externalStorage" data-admin="<?php echo json_encode($_['isAdminPage']); ?>">
			<thead>
				<tr>
					<th><?php echo $l->t('Mount point'); ?></th>
					<th><?php echo $l->t('Backend'); ?></th>
					<th><?php echo $l->t('Configuration'); ?></th>
					<!--<th><?php echo $l->t('Options'); ?></th> -->
					<?php if ($_['isAdminPage']) echo '<th>'.$l->t('Applicable').'</th>'; ?>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody width="100%">
			<?php $_['mounts'] = array_merge($_['mounts'], array('' => array())); ?>
			<?php foreach ($_['mounts'] as $mountPoint => $mount): ?>
				<tr <?php if ($mountPoint == '') echo 'id="addMountPoint"'; ?>>
					<td class="mountPoint"><input type="text" name="mountPoint" value="<?php echo $mountPoint; ?>" placeholder="<?php echo $l->t('Mount point'); ?>" /></td>
					<?php if ($mountPoint == ''): ?>
						<td class="backend">
							<select id="selectBackend" data-configurations='<?php echo json_encode($_['backends']); ?>'>
								<option value="" disabled selected style="display:none;"><?php echo $l->t('Add mount point'); ?></option>
								<?php foreach ($_['backends'] as $class => $backend): ?>
									<option value="<?php echo $class; ?>"><?php echo $backend['backend']; ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					<?php else: ?>
						<td class="backend" data-class="<?php echo $mount['class']; ?>"><?php echo $mount['backend']; ?></td>
					<?php endif; ?>
					<td class ="configuration" width="100%">
						<?php if (isset($mount['configuration'])): ?>
							<?php foreach ($mount['configuration'] as $parameter => $value): ?>
								<?php if (isset($_['backends'][$mount['class']]['configuration'][$parameter])): ?>
									<?php $placeholder = $_['backends'][$mount['class']]['configuration'][$parameter]; ?>
									<?php if (strpos($placeholder, '*') !== false): ?>
										<input type="password" data-parameter="<?php echo $parameter; ?>" value="<?php echo $value; ?>" placeholder="<?php echo substr($placeholder, 1); ?>" />
									<?php elseif(strpos($placeholder, '!') !== false): ?>
										<label><input type="checkbox" data-parameter="<?php echo $parameter; ?>" <?php if ($value == 'true') echo ' checked="checked"'; ?>  /><?php echo substr($placeholder, 1); ?></label>
									<?php elseif (strpos($placeholder, '&') !== false): ?>
										<input type="text" class="optional" data-parameter="<?php echo $parameter; ?>" value="<?php echo $value; ?>" placeholder="<?php echo substr($placeholder, 1); ?>" />
									<?php else: ?>
										<input type="text" data-parameter="<?php echo $parameter; ?>" value="<?php echo $value; ?>" placeholder="<?php echo $placeholder; ?>" />
									<?php endif; ?>
								<?php endif; ?>
							<?php endforeach; ?>
						<?php endif; ?>
					</td>
					<!--<td class="options">
						<select class="selectOptions" title="<?php echo $l->t('None set')?>" multiple="multiple" disabled>
							<?php if (OCP\App::isEnabled('files_encryption')) echo '<option value="Encrypt">Encrypt</option>'; ?>
							<?php if (OCP\App::isEnabled('files_versions')) echo '<option value="Version control">Version control</option>'; ?>
							<?php if (OCP\App::isEnabled('files_sharing')) echo '<option value="Allow sharing">Allow sharing</option>'; ?>
						</select>
					</td>-->
					<?php if ($_['isAdminPage']): ?>
					<td class="applicable" align="right" data-applicable-groups='<?php if (isset($mount['applicable']['groups'])) echo json_encode($mount['applicable']['groups']); ?>' data-applicable-users='<?php if (isset($mount['applicable']['users'])) echo json_encode($mount['applicable']['users']); ?>'>
							<select class="chzn-select" multiple style="width:20em;" data-placeholder="<?php echo $l->t('None set'); ?>">
								<option value="all"><?php echo $l->t('All Users'); ?></option>
								<optgroup label="<?php echo $l->t('Groups'); ?>">
									<?php foreach ($_['groups'] as $group): ?>
										<option value="<?php echo $group; ?>(group)" <?php if (isset($mount['applicable']['groups']) && in_array($group, $mount['applicable']['groups'])) echo 'selected="selected"'; ?>><?php echo $group; ?></option>
									<?php endforeach; ?>
								</optgroup>
								<optgroup label="<?php echo $l->t('Users'); ?>">
									<?php foreach ($_['users'] as $user): ?>
										<option value="<?php echo $user; ?>" <?php if (isset($mount['applicable']['users']) && in_array($user, $mount['applicable']['users'])) echo 'selected="selected"'; ?>"><?php echo $user; ?></option>
									<?php endforeach; ?>
								</optgroup>
							</select>
						</td>
					<?php endif; ?>
					<td <?php if ($mountPoint != '') echo 'class="remove"'; ?>><img alt="<?php echo $l->t('Delete'); ?>" title="<?php echo $l->t('Delete'); ?>" class="svg action" src="<?php echo image_path('core', 'actions/delete.svg'); ?>" /></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php if ($_['isAdminPage']): ?>
			<br />
			<input type="checkbox" name="allowUserMounting" id="allowUserMounting" value="1" <?php if ($_['allowUserMounting'] == 'yes') echo ' checked="checked"'; ?> />
			<label for="allowUserMounting"><?php echo $l->t('Enable User External Storage'); ?></label><br/>
			<em><?php echo $l->t('Allow users to mount their own external storage'); ?></em>
		<?php endif; ?>
	</fieldset>
</form>