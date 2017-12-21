<div class="app-navigation-new">
	<button type="button" class="icon-add"><?php p($l->t('New user'))?></button>
	<form class="newUserMenu" id="newuser" autocomplete="off" style="display: none;">
		<div>
			<input id="newusername" type="text" required
				placeholder="<?php p($l->t('Username'))?>" name="username"
				autocomplete="off" autocapitalize="none" autocorrect="off" />
		</div>
		<div>
			<input id="newuserpassword" type="password" required
				   placeholder="<?php p($l->t('Password'))?>" name="password"
				   autocomplete="new-password" autocapitalize="none" autocorrect="off" />
		</div>
		<div>
			<input id="newemail" type="text" style="display:none"
				   placeholder="<?php p($l->t('E-Mail'))?>" name="email"
				   autocomplete="off" autocapitalize="none" autocorrect="off" />
		</div>
		<div>
			<label class="groups" for="newgroup">
				<div class="groupsListContainer multiselect button" data-placeholder="<?php p($l->t('Groups'))?>"><span class="title groupsList"></span>
					<span class="icon-triangle-s"></span>
				</div>
			</label>
			<input type="submit" id="newsubmit" class="button icon-confirm has-tooltip" value="" title="<?php p($l->t('Create'))?>" />
		</div>
		<?php if((bool)$_['recoveryAdminEnabled']): ?>
		<div class="recoveryPassword">
			<input id="recoveryPassword"
				   type="password"
				   placeholder="<?php p($l->t('Admin Recovery Password'))?>"
				   title="<?php p($l->t('Enter the recovery password in order to recover the users files during password change'))?>"
				   alt="<?php p($l->t('Enter the recovery password in order to recover the users files during password change'))?>"/>
		</div>
		<?php endif; ?>
	</form>
</div>
