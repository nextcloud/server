<div id="controls">
	<form id="newuser" autocomplete="off">
		<input id="newusername" type="text"
			placeholder="<?php p($l->t('Login Name'))?>"
			autocomplete="off" autocapitalize="off" autocorrect="off" />
		<input
			type="password" id="newuserpassword"
			placeholder="<?php p($l->t('Password'))?>"
			autocomplete="off" autocapitalize="off" autocorrect="off" />
		<select
			class="groupsselect" id="newusergroups" data-placeholder="groups"
			title="<?php p($l->t('Groups'))?>" multiple="multiple">
			<?php foreach($_["adminGroup"] as $adminGroup): ?>
				<option value="<?php p($adminGroup['name']);?>"><?php p($adminGroup['name']); ?></option>
			<?php endforeach; ?>
			<?php foreach($_["groups"] as $group): ?>
				<option value="<?php p($group['name']);?>"><?php p($group['name']);?></option>
			<?php endforeach;?>
		</select>
		<input type="submit" class="button" value="<?php p($l->t('Create'))?>" />
	</form>
	<?php if((bool)$_['recoveryAdminEnabled']): ?>
	<div class="recoveryPassword">
	<input id="recoveryPassword"
		   type="password"
		   placeholder="<?php p($l->t('Admin Recovery Password'))?>"
		   title="<?php p($l->t('Enter the recovery password in order to recover the users files during password change'))?>"
		   alt="<?php p($l->t('Enter the recovery password in order to recover the users files during password change'))?>"/>
	</div>
	<?php endif; ?>
	
	<form id="userlistoptions">
		<?php p($l->t('Display')) ?> : 
		<label><input type="checkbox" name="StorageLocation" value="StorageLocation" id="CheckboxStorageLocation">
			<?php p($l->t('Location')) ?>
		</label>
		<label><input type="checkbox" name="LastLogin" value="LastLogin" id="CheckboxLastLogin">
			<?php p($l->t('Last login')) ?>
		</label>
	</form>
	
	<form autocomplete="off" id="usersearchform">
		<input type="text" class="input userFilter" placeholder="<?php p($l->t('Search Users and Groups')); ?>" />
	</form>
</div>
