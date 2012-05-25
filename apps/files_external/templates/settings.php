<form id="files_external">
	<fieldset class="personalblock">
	<legend><strong><?php echo $l->t('External Storage'); ?></strong></legend>
		<?php if (isset($_['storage'])): ?>
		<table id="externalStorage">
			<thead>
				<tr>
					<th><?php echo $l->t('Type'); ?></th>
					<th><?php echo $l->t('Configuration'); ?></th>
					<th><?php echo $l->t('Mount Location'); ?></th>
					<th><?php echo $l->t('Options'); ?></th>
					<?php if ($_['isAdminPage'] == true) echo '<th>'.$l->t('Applicable').'</th>'; ?>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
			<?php $_['storage'] = array_merge($_['storage'], array(array('id' => 'addStorage', 'mount' => ''))); ?>
			<?php foreach($_['storage'] as $storage): ?>
				<tr <?php if ($storage['id'] == 'addStorage') echo 'id="addStorage"'; ?> data-storage-id="<?php echo $storage['id']; ?>">
					<?php if ($storage['id'] == 'addStorage'): ?>
						<td class="selectStorage">
							<select id="selectStorage" data-configurations="<?php echo $_['configurations']; ?>">
								<option value="" disabled selected style="display:none;"><?php echo $l->t('Add storage'); ?></option>
								<?php foreach($_['backends'] as $backend): ?>
									<option value="<?php echo $backend; ?>"><?php echo $backend; ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					<?php else: ?>
						<td class="type" <?php if ($storage['status'] == 'error') echo 'class="error"'; ?>><?php echo $storage['type']; ?></td>
					<?php endif; ?>
					<td class ="configuration">
					<?php if (isset($storage['configuration'])): ?>
						<?php foreach($storage['configuration'] as $parameter => $value): ?>
							<?php if (strpos($parameter, '*') !== false): ?>
								<input type="password" value="<?php echo $value; ?>" placeholder="<?php echo $l->t(substr($parameter, 1)); ?>" />
							<?php else: ?>
								<input type="text" value="<?php echo $value; ?>" placeholder="<?php echo $l->t($parameter); ?>" />
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
					</td>
					<td class="mount"><input type="text" name="storageMountLocation" value="<?php echo $storage['mount']; ?>" placeholder="<?php echo $l->t('Mount Location'); ?>" /></td>
					<td class="options">
						<select class="selectOptions" title="<?php echo $l->t('None set')?>" multiple="multiple">
							<?php if (OCP\App::isEnabled('files_encryption')) echo '<option value="Encrypt">Encrypt</option>'; ?>
							<?php if (OCP\App::isEnabled('files_versions')) echo '<option value="Version control">Version control</option>'; ?>
							<?php if (OCP\App::isEnabled('files_sharing')) echo '<option value="Allow sharing">Allow sharing</option>'; ?>
						</select>
					</td>
					<?php if ($_['isAdminPage'] == true): ?>
						<td class="applicable">
							<select class="selectApplicable" data-storage-applicable="<?php echo $storage['applicable']; ?>" title="<?php echo $l->t('None set'); ?>" multiple="multiple">
								<option value="Global"><?php echo $l->t('Global'); ?></option>
								<?php foreach($_['groups'] as $group): ?>
									<option value="<?php echo $group; ?>"><?php echo $group; ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					<?php endif; ?>
					<td <?php if ($storage['id'] != 'addStorage') echo 'class="remove"'; ?>><img alt="<?php echo $l->t('Delete'); ?>" title="<?php echo $l->t('Delete'); ?>" class="svg action" src="<?php echo image_path('core', 'actions/delete.svg'); ?>" /></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
		<?php if ($_['isAdminPage'] == true): ?>
			<br />
			<input type="checkbox" name="allowUserMounting" id="allowUserMounting" value="1" <?php if ($_['allowUserMounting'] == 'yes') echo ' checked="checked"'; ?> />
			<label for="allowUserMounting"><?php echo $l->t('Enable User External Storage'); ?></label><br/>
			<em><?php echo $l->t('Allow users to mount their own external storage'); ?></em>
		<?php endif; ?>
	</fieldset>
</form>