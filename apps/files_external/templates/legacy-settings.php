<?php
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
			<?php if ($is_optional) {
				$classes[] = 'optional';
			} ?>
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
			<?php if ($is_optional) {
				$classes[] = 'optional';
			} ?>
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

<?php
	$canCreateNewLocalStorage = \OC::$server->getConfig()->getSystemValue('files_external_allow_create_new_local', true);
?>
<form data-can-create="<?php echo $canCreateMounts?'true':'false' ?>" data-can-create-local="<?php echo $canCreateNewLocalStorage?'true':'false' ?>" id="files_external" class="section" data-encryption-enabled="<?php echo $_['encryptionEnabled']?'true': 'false'; ?>">
	<table id="externalStorage" class="grid" data-admin='<?php print_unescaped(json_encode($_['visibilityType'] === BackendService::VISIBILITY_ADMIN)); ?>'>
		<thead>
			<tr>
				<th></th>
				<th><?php p($l->t('Folder name')); ?></th>
				<th><?php p($l->t('External storage')); ?></th>
				<th><?php p($l->t('Authentication')); ?></th>
				<th><?php p($l->t('Configuration')); ?></th>
				<?php if ($_['visibilityType'] === BackendService::VISIBILITY_ADMIN) {
					print_unescaped('<th>'.$l->t('Available for').'</th>');
				} ?>
				<th>&nbsp;</th>
				<th>&nbsp;</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<tr class="externalStorageLoading">
				<td colspan="8">
					<span id="externalStorageLoading" class="icon icon-loading"></span>
				</td>
			</tr>
			<tr id="addMountPoint"
			<?php if (!$canCreateMounts): ?>
				style="display: none;"
			<?php endif; ?>
			>
				<td class="status">
					<span data-placement="right" title="<?php p($l->t('Click to recheck the configuration')); ?>"></span>
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
											$sortedBackends = array_filter($_['backends'], function ($backend) use ($_) {
												return $backend->isVisibleFor($_['visibilityType']);
											});
uasort($sortedBackends, function ($a, $b) {
	return strcasecmp($a->getText(), $b->getText());
});
?>
						<?php foreach ($sortedBackends as $backend): ?>
							<?php if ($backend->getDeprecateTo() || (!$canCreateNewLocalStorage && $backend->getIdentifier() == "local")) {
								continue;
							} // ignore deprecated backends?>
							<option value="<?php p($backend->getIdentifier()); ?>"><?php p($backend->getText()); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
				<td class="authentication" data-mechanisms='<?php p(json_encode($_['authMechanisms'])); ?>'></td>
				<td class="configuration"></td>
				<?php if ($_['visibilityType'] === BackendService::VISIBILITY_ADMIN): ?>
					<td class="applicable" align="right">
						<label><input type="checkbox" class="applicableToAllUsers" checked="" /><?php p($l->t('All people')); ?></label>
						<div class="applicableUsersContainer">
							<input type="hidden" class="applicableUsers" style="width:20em;" value="" />
						</div>
					</td>
				<?php endif; ?>
				<td class="mountOptionsToggle hidden">
					<button type="button" class="icon-more" aria-expanded="false" title="<?php p($l->t('Advanced settings')); ?>"></button>
					<input type="hidden" class="mountOptions" value="" />
				</td>
				<td class="save hidden">
					<button type="button" class="icon-checkmark" title="<?php p($l->t('Save')); ?>"></button>
				</td>
			</tr>
		</tbody>
	</table>
</form>

