<?php
$name = isset($_['name'])?$_['name']:'';
//print_r($name);
$id = isset($_['id'])?$_['id']:'';
$addressbooks = isset($_['addressbooks'])?$_['addressbooks']:null;
?>
<div id="edit_name_dialog" title="Edit name">
	<form>
	<fieldset>
	<dl class="form">
		<?php if(!is_null($addressbooks)) { 
			if(count($_['addressbooks'])==1) {
		?>
			<input type="hidden" id="aid" name="aid" value="<?php echo $_['addressbooks'][0]['id']; ?>">
		<?php } else { ?>
		<dt><label for="addressbook"><?php echo $l->t('Addressbook'); ?></label></dt>
		<dd>
			<select id="aid" name="aid" size="1">
				<?php echo OCP\html_select_options($_['addressbooks'], null, array('value'=>'id', 'label'=>'displayname')); ?>
			</select>
		</dd>
		<?php }} ?>
		<dt><label for="pre"><?php echo $l->t('Hon. prefixes'); ?></label></dt>
		<dd>
			<input name="pre" id="pre" value="<?php echo isset($name['value'][3]) ? $name['value'][3] : ''; ?>" type="text" list="prefixes" />
			<datalist id="prefixes">
				<option value="<?php echo $l->t('Miss'); ?>">
				<option value="<?php echo $l->t('Ms'); ?>">
				<option value="<?php echo $l->t('Mr'); ?>">
				<option value="<?php echo $l->t('Sir'); ?>">
				<option value="<?php echo $l->t('Mrs'); ?>">
				<option value="<?php echo $l->t('Dr'); ?>">
			</datalist>
		</dd>
		<dt><label for="giv"><?php echo $l->t('Given name'); ?></label></dt>
		<dd><input name="giv" id="giv" value="<?php echo isset($name['value'][1]) ? $name['value'][1] : ''; ?>" type="text" /></dd>
		<dt><label for="add"><?php echo $l->t('Additional names'); ?></label></dt>
		<dd><input name="add" id="add" value="<?php echo isset($name['value'][2]) ? $name['value'][2] : ''; ?>" type="text" /></dd>
		<dt><label for="fam"><?php echo $l->t('Family name'); ?></label></dt>
		<dd><input name="fam" id="fam" value="<?php echo isset($name['value'][0]) ? $name['value'][0] : ''; ?>" type="text" /></dd>
		<dt><label for="suf"><?php echo $l->t('Hon. suffixes'); ?></label></dt>
		<dd>
			<input name="suf" id="suf" value="<?php echo isset($name['value'][4]) ? $name['value'][4] : ''; ?>" type="text" list="suffixes" />
			<datalist id="suffixes">
				<option value="<?php echo $l->t('J.D.'); ?>">
				<option value="<?php echo $l->t('M.D.'); ?>">
				<option value="<?php echo $l->t('D.O.'); ?>">
				<option value="<?php echo $l->t('D.C.'); ?>">
				<option value="<?php echo $l->t('Ph.D.'); ?>">
				<option value="<?php echo $l->t('Esq.'); ?>">
				<option value="<?php echo $l->t('Jr.'); ?>">
				<option value="<?php echo $l->t('Sn.'); ?>">
			</datalist>
		</dd>
	</dl>
	</fieldset>
	</form>
</div>
