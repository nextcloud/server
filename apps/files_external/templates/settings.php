<?php
	use \OCA\Files_External\Lib\Backend\Backend;
	use \OCA\Files_External\Lib\Auth\AuthMechanism;
	use \OCA\Files_External\Lib\DefinitionParameter;
	use \OCA\Files_External\Service\BackendService;

	$l->t("Enable encryption");
	$l->t("Enable previews");
	$l->t("Enable sharing");
	$l->t("Check for changes");
	$l->t("Never");
	$l->t("Once every direct access");

	script('files_external', 'settings');
	style('files_external', 'settings');

	// load custom JS
	foreach ($_['backends'] as $backend) {
		/** @var Backend $backend */
		$scripts = $backend->getCustomJs();
		foreach ($scripts as $script) {
			script('files_external', $script);
		}
	}
	foreach ($_['authMechanisms'] as $authMechanism) {
		/** @var AuthMechanism $authMechanism */
		$scripts = $authMechanism->getCustomJs();
		foreach ($scripts as $script) {
			script('files_external', $script);
		}
	}

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
			<?php $checkboxId = uniqid("checkbox_"); ?>
			<div>
			<label>
			<input type="checkbox"
				id="<?php p($checkboxId); ?>"
				<?php if (!empty($classes)): ?> class="checkbox <?php p(implode(' ', $classes)); ?>"<?php endif; ?>
				data-parameter="<?php p($parameter->getName()); ?>"
				<?php if ($value === true): ?> checked="checked"<?php endif; ?>
			/>
			<?php p($placeholder); ?>
			</label>
			</div>
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
	<table id="externalStorage" class="grid" data-admin='<?php print_unescaped(json_encode($_['visibilityType'] === BackendService::VISIBILITY_ADMIN)); ?>'>
		<thead>
			<tr>
				<th></th>
				<th><?php p($l->t('Folder name')); ?></th>
				<th><?php p($l->t('External storage')); ?></th>
				<th><?php p($l->t('Authentication')); ?></th>
				<th><?php p($l->t('Configuration')); ?></th>
				<?php if ($_['visibilityType'] === BackendService::VISIBILITY_ADMIN) print_unescaped('<th>'.$l->t('Available for').'</th>'); ?>
				<th>&nbsp;</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<tr id="addMountPoint"
			<?php if ($_['visibilityType'] === BackendService::VISIBILITY_PERSONAL && $_['allowUserMounting'] === false): ?>
				style="display: none;"
			<?php endif; ?>
			>
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
							$sortedBackends = array_filter($_['backends'], function($backend) use ($_) {
								return $backend->isVisibleFor($_['visibilityType']);
							});
							uasort($sortedBackends, function($a, $b) {
								return strcasecmp($a->getText(), $b->getText());
							});
						?>
						<?php foreach ($sortedBackends as $backend): ?>
							<?php if ($backend->getDeprecateTo()) continue; // ignore deprecated backends ?>
							<option value="<?php p($backend->getIdentifier()); ?>"><?php p($backend->getText()); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
				<td class="authentication" data-mechanisms='<?php p(json_encode($_['authMechanisms'])); ?>'></td>
				<td class="configuration"></td>
				<?php if ($_['visibilityType'] === BackendService::VISIBILITY_ADMIN): ?>
					<td class="applicable" align="right">
						<input type="hidden" class="applicableUsers" style="width:20em;" value="" />
					</td>
				<?php endif; ?>
				<td class="mountOptionsToggle hidden">
					<img class="svg"
						title="<?php p($l->t('Advanced settings')); ?>"
						alt="<?php p($l->t('Advanced settings')); ?>"
						src="<?php print_unescaped(image_path('core', 'actions/settings.svg')); ?>"
					/>
					<input type="hidden" class="mountOptions" value="" />
				</td>
				<td class="hidden">
					<img class="svg"
						alt="<?php p($l->t('Delete')); ?>"
						title="<?php p($l->t('Delete')); ?>"
						src="<?php print_unescaped(image_path('core', 'actions/delete.svg')); ?>"
					/>
				</td>
			</tr>
		</tbody>
	</table>
	<br />

	<?php if ($_['visibilityType'] === BackendService::VISIBILITY_ADMIN): ?>
		<br />
		<input type="checkbox" name="allowUserMounting" id="allowUserMounting" class="checkbox"
			value="1" <?php if ($_['allowUserMounting'] == 'yes') print_unescaped(' checked="checked"'); ?> />
		<label for="allowUserMounting"><?php p($l->t('Allow users to mount external storage')); ?></label> <span id="userMountingMsg" class="msg"></span>

		<p id="userMountingBackends"<?php if ($_['allowUserMounting'] != 'yes'): ?> class="hidden"<?php endif; ?>>
			<?php p($l->t('Allow users to mount the following external storage')); ?><br />
			<?php
				$userBackends = array_filter($_['backends'], function($backend) {
					return $backend->isAllowedVisibleFor(BackendService::VISIBILITY_PERSONAL);
				});
			?>
			<?php $i = 0; foreach ($userBackends as $backend): ?>
				<?php if ($deprecateTo = $backend->getDeprecateTo()): ?>
					<input type="hidden" id="allowUserMountingBackends<?php p($i); ?>" name="allowUserMountingBackends[]" value="<?php p($backend->getIdentifier()); ?>" data-deprecate-to="<?php p($deprecateTo->getIdentifier()); ?>" />
				<?php else: ?>
					<input type="checkbox" id="allowUserMountingBackends<?php p($i); ?>" class="checkbox" name="allowUserMountingBackends[]" value="<?php p($backend->getIdentifier()); ?>" <?php if ($backend->isVisibleFor(BackendService::VISIBILITY_PERSONAL)) print_unescaped(' checked="checked"'); ?> />
					<label for="allowUserMountingBackends<?php p($i); ?>"><?php p($backend->getText()); ?></label> <br />
				<?php endif; ?>
				<?php $i++; ?>
			<?php endforeach; ?>
		</p>
	<?php endif; ?>
</form>
