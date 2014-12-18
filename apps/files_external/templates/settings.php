<form id="files_external" class="section">
	<h2><?php p($l->t('External Storage')); ?></h2>
	<?php if (isset($_['dependencies']) and ($_['dependencies']<>'')) print_unescaped(''.$_['dependencies'].''); ?>
	<table id="externalStorage" class="grid" data-admin='<?php print_unescaped(json_encode($_['isAdminPage'])); ?>'>
		<thead>
			<tr>
				<th></th>
				<th><?php p($l->t('Folder name')); ?></th>
				<th><?php p($l->t('External storage')); ?></th>
				<th><?php p($l->t('Configuration')); ?></th>
				<?php if ($_['isAdminPage']) print_unescaped('<th>'.$l->t('Available for').'</th>'); ?>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
		<?php $_['mounts'] = array_merge($_['mounts'], array('' => array())); ?>
		<?php foreach ($_['mounts'] as $mount): ?>
			<tr <?php print_unescaped(isset($mount['mountpoint']) ? 'class="'.OC_Util::sanitizeHTML($mount['class']).'"' : 'id="addMountPoint"'); ?>>
				<td class="status">
				<?php if (isset($mount['status'])): ?>
					<span class="<?php p(($mount['status']) ? 'success' : 'error'); ?>"></span>
				<?php endif; ?>
				</td>
				<td class="mountPoint"><input type="text" name="mountPoint"
											  value="<?php p(isset($mount['mountpoint']) ? $mount['mountpoint'] : ''); ?>"
											  data-mountpoint="<?php p(isset($mount['mountpoint']) ? $mount['mountpoint'] : ''); ?>"
											  placeholder="<?php p($l->t('Folder name')); ?>" />
				</td>
				<?php if (!isset($mount['mountpoint'])): ?>
					<td class="backend">
						<select id="selectBackend" data-configurations='<?php p(json_encode($_['backends'])); ?>'>
							<option value="" disabled selected
									style="display:none;"><?php p($l->t('Add storage')); ?></option>
							<?php foreach ($_['backends'] as $class => $backend): ?>
								<option value="<?php p($class); ?>"><?php p($backend['backend']); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				<?php else: ?>
					<td class="backend" data-class="<?php p($mount['class']); ?>"><?php p($mount['backend']); ?>
					</td>
				<?php endif; ?>
				<td class ="configuration">
					<?php if (isset($mount['options'])): ?>
						<?php foreach ($mount['options'] as $parameter => $value): ?>
							<?php if (isset($_['backends'][$mount['class']]['configuration'][$parameter])): ?>
								<?php
									$placeholder = $_['backends'][$mount['class']]['configuration'][$parameter];
									$is_optional = FALSE;
									if (strpos($placeholder, '&') === 0) {
										$is_optional = TRUE;
										$placeholder = substr($placeholder, 1);
									}
								?>
								<?php if (strpos($placeholder, '*') === 0): ?>
									<input type="password"
										   <?php if ($is_optional): ?> class="optional"<?php endif; ?>
										   data-parameter="<?php p($parameter); ?>"
										   value="<?php p($value); ?>"
										   placeholder="<?php p(substr($placeholder, 1)); ?>" />
								<?php elseif (strpos($placeholder, '!') === 0): ?>
									<label><input type="checkbox"
												  data-parameter="<?php p($parameter); ?>"
												  <?php if ($value == 'true'): ?> checked="checked"<?php endif; ?>
												  /><?php p(substr($placeholder, 1)); ?></label>
								<?php elseif (strpos($placeholder, '#') === 0): ?>
									<input type="hidden"
										   data-parameter="<?php p($parameter); ?>"
										   value="<?php p($value); ?>" />
								<?php else: ?>
									<input type="text"
										   <?php if ($is_optional): ?> class="optional"<?php endif; ?>
										   data-parameter="<?php p($parameter); ?>"
										   value="<?php p($value); ?>"
										   placeholder="<?php p($placeholder); ?>" />
								<?php endif; ?>
							<?php endif; ?>
						<?php endforeach; ?>
						<?php if (isset($_['backends'][$mount['class']]['custom'])): ?>
							<?php OCP\Util::addScript('files_external', $_['backends'][$mount['class']]['custom']); ?>
						<?php endif; ?>
					<?php endif; ?>
				</td>
				<?php if ($_['isAdminPage']): ?>
				<td class="applicable"
					align="right"
					data-applicable-groups='<?php if (isset($mount['applicable']['groups']))
													print_unescaped(json_encode($mount['applicable']['groups'])); ?>'
					data-applicable-users='<?php if (isset($mount['applicable']['users']))
													print_unescaped(json_encode($mount['applicable']['users'])); ?>'>
					<input type="hidden" class="applicableUsers" style="width:20em;" value=""/>
				</td>
				<?php endif; ?>
				<td <?php if (isset($mount['mountpoint'])): ?>class="remove"
					<?php else: ?>style="visibility:hidden;"
					<?php endif ?>><img alt="<?php p($l->t('Delete')); ?>"
										title="<?php p($l->t('Delete')); ?>"
										class="svg action"
										src="<?php print_unescaped(image_path('core', 'actions/delete.svg')); ?>" /></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<br />

	<?php if ($_['isAdminPage']): ?>
		<br />
		<input type="checkbox" name="allowUserMounting" id="allowUserMounting"
			value="1" <?php if ($_['allowUserMounting'] == 'yes') print_unescaped(' checked="checked"'); ?> />
		<label for="allowUserMounting"><?php p($l->t('Enable User External Storage')); ?></label> <span id="userMountingMsg" class="msg"></span>

		<p id="userMountingBackends"<?php if ($_['allowUserMounting'] != 'yes'): ?> class="hidden"<?php endif; ?>>
			<?php p($l->t('Allow users to mount the following external storage')); ?><br />
			<?php $i = 0; foreach ($_['personal_backends'] as $class => $backend): ?>
				<input type="checkbox" id="allowUserMountingBackends<?php p($i); ?>" name="allowUserMountingBackends[]" value="<?php p($class); ?>" <?php if ($backend['enabled']) print_unescaped(' checked="checked"'); ?> />
				<label for="allowUserMountingBackends<?php p($i); ?>"><?php p($backend['backend']); ?></label> <br />
				<?php $i++; ?>
			<?php endforeach; ?>
		</p>
	<?php endif; ?>
</form>
