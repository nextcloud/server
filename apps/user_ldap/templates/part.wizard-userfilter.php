<fieldset id="ldapWizard2">
	<div>
		<p>
			<?php p($l->t('Listing and searching for users is constrained by these criteria:'));?>
		</p>
		<p>
			<label for="ldap_userfilter_objectclass">
				<?php p($l->t('Only these object classes:'));?>
			</label>

			<select id="ldap_userfilter_objectclass" multiple="multiple"
			 name="ldap_userfilter_objectclass" class="multiSelectPlugin">
			</select>
		</p>
		<p>
			<label></label>
			<span class="ldapInputColElement"><?php p($l->t('The most common object classes for users are organizationalPerson, person, user, and inetOrgPerson. If you are not sure which object class to select, please consult your directory admin.'));?></span>
		</p>
		<p>
			<label for="ldap_userfilter_groups">
				<?php p($l->t('Only from these groups:'));?>
			</label>

			<input type="text" class="ldapManyGroupsSupport ldapManyGroupsSearch hidden" placeholder="<?php p($l->t('Search groups'));?>" />

			<select id="ldap_userfilter_groups" multiple="multiple"
			 name="ldap_userfilter_groups" class="multiSelectPlugin">
			</select>
		</p>
		<p class="ldapManyGroupsSupport hidden">
			<label></label>
			<select class="ldapGroupList ldapGroupListAvailable" multiple="multiple"
					aria-describedby="ldapGroupListAvailable_instructions"
					title="<?php p($l->t('Available groups'));?>"></select>
			<p class="hidden-visually" id="ldapGroupListAvailable_instructions">
				<?php p($l->t('Available groups'));?>
			</p>
			<span class="buttonSpan">
				<button class="ldapGroupListSelect" type="button">&gt;</button><br/>
				<button class="ldapGroupListDeselect" type="button">&lt;</button>
			</span>
			<select class="ldapGroupList ldapGroupListSelected" multiple="multiple"
					aria-describedby="ldapGroupListSelected_instructions"
					title="<?php p($l->t('Selected groups'));?>"></select>
			<p class="hidden-visually" id="ldapGroupListSelected_instructions">
				<?php p($l->t('Selected groups'));?>
			</p>
		</p>
		<p>
			<label><a id='toggleRawUserFilter' class='ldapToggle'>↓ <?php p($l->t('Edit LDAP Query'));?></a></label>
		</p>
		<p id="ldapReadOnlyUserFilterContainer" class="hidden ldapReadOnlyFilterContainer">
			<label><?php p($l->t('LDAP Filter:'));?></label>
			<span class="ldapFilterReadOnlyElement ldapInputColElement"></span>
		</p>
		<p id="rawUserFilterContainer">
			<textarea type="text" id="ldap_userlist_filter" name="ldap_userlist_filter"
				class="ldapFilterInputElement"
				placeholder="<?php p($l->t('Edit LDAP Query'));?>"
				aria-describedby="ldap_userlist_filter_instructions"
				title="<?php p($l->t('The filter specifies which LDAP users shall have access to the %s instance.', [$theme->getName()]));?>">
			</textarea>
		<p class="hidden-visually" id="ldap_userlist_filter_instructions">
			<?php p($l->t('The filter specifies which LDAP users shall have access to the %s instance.', [$theme->getName()]));?>
		</p>
		</p>
		<p>
			<div class="ldapWizardInfo invisible">&nbsp;</div>
		</p>
		<p class="ldap_count">
			<button class="ldapGetEntryCount ldapGetUserCount" name="ldapGetEntryCount" type="button">
				<?php p($l->t('Verify settings and count users'));?>
			</button>
			<span id="ldap_user_count"></span>
		</p>
		<?php print_unescaped($_['wizardControls']); ?>
	</div>
</fieldset>
