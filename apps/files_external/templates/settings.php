<?php
	use \OCA\Files_External\Lib\Backend\Backend;
	use \OCA\Files_External\Lib\DefinitionParameter;
	use \OCA\Files_External\Service\BackendService;

	function writeParameterInput($parameter, $options, $classes = []) {
		$value = '';
		if (isset($options[$parameter->getName()])) {
			$value = $options[$parameter->getName()];
		}
		$placeholder = $parameter->getText();
		$is_optional = $parameter->isFlagSet(DefinitionParameter::FLAG_OPTIONAL);

		switch ($parameter->getType()) {
		case DefinitionParameter::VALUE_PASSWORD: ?>
			<?php if ($is_optional) { $classes[] = 'optional'; } ?>
			<input type="password"
				<?php if (!empty($classes)): ?> class="<?php p(implode(' ', $classes)); ?>"<?php endif; ?>
				data-parameter="<?php p($parameter->getName()); ?>"
				value="<?php p($value); ?>"
				placeholder="<?php p($placeholder); ?>"
			/>
			<?php
			break;
		case DefinitionParameter::VALUE_BOOLEAN: ?>
			<label>
				<input type="checkbox"
					<?php if (!empty($classes)): ?> class="<?php p(implode(' ', $classes)); ?>"<?php endif; ?>
					data-parameter="<?php p($parameter->getName()); ?>"
				 	<?php if ($value == 'true'): ?> checked="checked"<?php endif; ?>
				/>
				<?php p($placeholder); ?>
			</label>
			<?php
			break;
		case DefinitionParameter::VALUE_HIDDEN: ?>
			<input type="hidden"
				<?php if (!empty($classes)): ?> class="<?php p(implode(' ', $classes)); ?>"<?php endif; ?>
				data-parameter="<?php p($parameter->getName()); ?>"
				value="<?php p($value); ?>"
			/>
			<?php
			break;
		default: ?>
			<?php if ($is_optional) { $classes[] = 'optional'; } ?>
			<input type="text"
				<?php if (!empty($classes)): ?> class="<?php p(implode(' ', $classes)); ?>"<?php endif; ?>
				data-parameter="<?php p($parameter->getName()); ?>"
				value="<?php p($value); ?>"
				placeholder="<?php p($placeholder); ?>"
			/>
			<?php
		}
	}
?>
<form id="files_external" class="section" data-encryption-enabled="<?php echo $_['encryptionEnabled']?'true': 'false'; ?>">
	<h2><?php p($l->t('External Storage')); ?></h2>
	<?php if (isset($_['dependencies']) and ($_['dependencies']<>'')) print_unescaped(''.$_['dependencies'].''); ?>
	<table id="externalStorage" class="grid" data-admin='<?php print_unescaped(json_encode($_['isAdminPage'])); ?>'>
		<thead>
			<tr>
				<th></th>
				<th><?php p($l->t('Folder name')); ?></th>
				<th><?php p($l->t('External storage')); ?></th>
				<th><?php p($l->t('Authentication')); ?></th>
				<th><?php p($l->t('Configuration')); ?></th>
				<?php if ($_['isAdminPage']) print_unescaped('<th>'.$l->t('Available for').'</th>'); ?>
				<th>&nbsp;</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($_['storages'] as $storage): ?>
			<tr class="<?php p($storage->getBackend()->getIdentifier()); ?>" data-id="<?php p($storage->getId()); ?>">
				<td class="status">
					<span></span>
				</td>
				<td class="mountPoint"><input type="text" name="mountPoint"
											  value="<?php p(ltrim($storage->getMountPoint(), '/')); ?>"
											  data-mountpoint="<?php p(ltrim($storage->getMountPoint(), '/')); ?>"
											  placeholder="<?php p($l->t('Folder name')); ?>" />
				</td>
				<td class="backend" data-class="<?php p($storage->getBackend()->getIdentifier()); ?>"><?php p($storage->getBackend()->getText()); ?>
				</td>
				<td class="authentication">
					<select class="selectAuthMechanism">
						<?php
							$authSchemes = $storage->getBackend()->getAuthSchemes();
							$authMechanisms = array_filter($_['authMechanisms'], function($mech) use ($authSchemes) {
								return isset($authSchemes[$mech->getScheme()]);
							});
						?>
						<?php foreach ($authMechanisms as $mech): ?>
							<option value="<?php p($mech->getIdentifier()); ?>" data-scheme="<?php p($mech->getScheme());?>"
								<?php if ($mech->getIdentifier() === $storage->getAuthMechanism()->getIdentifier()): ?>selected<?php endif; ?>
							><?php p($mech->getText()); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
				<td class="configuration">
					<?php
						$options = $storage->getBackendOptions();
						foreach ($storage->getBackend()->getParameters() as $parameter) {
							writeParameterInput($parameter, $options);
						}
						foreach ($storage->getAuthMechanism()->getParameters() as $parameter) {
							writeParameterInput($parameter, $options, ['auth-param']);
						}
					?>
				</td>
				<?php if ($_['isAdminPage']): ?>
					<td class="applicable"
						align="right"
						data-applicable-groups='<?php print_unescaped(json_encode($storage->getApplicableGroups())); ?>'
						data-applicable-users='<?php print_unescaped(json_encode($storage->getApplicableUsers())); ?>'>
						<input type="hidden" class="applicableUsers" style="width:20em;" value=""/>
					</td>
				<?php endif; ?>
				<td class="mountOptionsToggle">
					<img
						class="svg action"
						title="<?php p($l->t('Advanced settings')); ?>"
						alt="<?php p($l->t('Advanced settings')); ?>"
						src="<?php print_unescaped(image_path('core', 'actions/settings.svg')); ?>"
					/>
					<input type="hidden" class="mountOptions" value="<?php p(json_encode($storage->getMountOptions())); ?>" />
					<?php if ($_['isAdminPage']): ?>
						<input type="hidden" class="priority" value="<?php p($storage->getPriority()); ?>" />
					<?php endif; ?>
				</td>
				<td class="remove">
					<img alt="<?php p($l->t('Delete')); ?>"
						title="<?php p($l->t('Delete')); ?>"
						class="svg action"
						src="<?php print_unescaped(image_path('core', 'actions/delete.svg')); ?>"
					/>
				</td>
			</tr>
		<?php endforeach; ?>
			<tr id="addMountPoint">
				<td class="status">
					<span></span>
				</td>
				<td class="mountPoint"><input type="text" name="mountPoint" value=""
					placeholder="<?php p($l->t('Folder name')); ?>">
				</td>
				<td class="backend">
					<select id="selectBackend" class="selectBackend" data-configurations='<?php p(json_encode($_['backends'])); ?>'>
						<option value="" disabled selected
							style="display:none;">
							<?php p($l->t('Add storage')); ?>
						</option>
						<?php
							$sortedBackends = $_['backends'];
							uasort($sortedBackends, function($a, $b) {
								return strcasecmp($a->getText(), $b->getText());
							});
						?>
						<?php foreach ($sortedBackends as $backend): ?>
							<option value="<?php p($backend->getIdentifier()); ?>"><?php p($backend->getText()); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
				<td class="authentication" data-mechanisms='<?php p(json_encode($_['authMechanisms'])); ?>'></td>
				<td class="configuration"></td>
				<?php if ($_['isAdminPage']): ?>
					<td class="applicable" align="right">
						<input type="hidden" class="applicableUsers" style="width:20em;" value="" />
					</td>
				<?php endif; ?>
				<td class="mountOptionsToggle hidden">
					<img class="svg action"
						title="<?php p($l->t('Advanced settings')); ?>"
						alt="<?php p($l->t('Advanced settings')); ?>"
						src="<?php print_unescaped(image_path('core', 'actions/settings.svg')); ?>"
					/>
					<input type="hidden" class="mountOptions" value="" />
				</td>
				<td class="hidden">
					<img class="svg action"
						alt="<?php p($l->t('Delete')); ?>"
						title="<?php p($l->t('Delete')); ?>"
						src="<?php print_unescaped(image_path('core', 'actions/delete.svg')); ?>"
					/>
				</td>
			</tr>
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
			<?php $i = 0; foreach ($_['userBackends'] as $backend): ?>
				<input type="checkbox" id="allowUserMountingBackends<?php p($i); ?>" name="allowUserMountingBackends[]" value="<?php p($backend->getIdentifier()); ?>" <?php if ($backend->isVisibleFor(BackendService::VISIBILITY_PERSONAL)) print_unescaped(' checked="checked"'); ?> />
				<label for="allowUserMountingBackends<?php p($i); ?>"><?php p($backend->getText()); ?></label> <br />
				<?php $i++; ?>
			<?php endforeach; ?>
		</p>
	<?php endif; ?>
</form>
