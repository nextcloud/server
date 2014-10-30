<form id="ldap" class="section" action="#" method="post">
	<h2><?php p($l->t('LDAP')); ?></h2>

	<div id="ldapSettings">
	<ul>
		<?php foreach($_['toc'] as $id => $title) { ?>
			<li id="<?php p($id); ?>"><a href="<?php p($id); ?>"><?php p($title); ?></a></li>
		<?php } ?>
		<li class="ldapSettingsTabs"><a href="#ldapSettings-2"><?php p($l->t('Expert'));?></a></li>
		<li class="ldapSettingsTabs"><a href="#ldapSettings-1"><?php p($l->t('Advanced'));?></a></li>
	</ul>
		<?php if(OCP\App::isEnabled('user_webdavauth')) {
			print_unescaped('<p class="ldapwarning">'.$l->t('<b>Warning:</b> Apps user_ldap and user_webdavauth are incompatible. You may experience unexpected behavior. Please ask your system administrator to disable one of them.').'</p>');
		}
		if(!function_exists('ldap_connect')) {
			print_unescaped('<p class="ldapwarning">'.$l->t('<b>Warning:</b> The PHP LDAP module is not installed, the backend will not work. Please ask your system administrator to install it.').'</p>');
		}
		?>
	<?php print_unescaped($_['tabs']); ?>
	<fieldset id="ldapSettings-1">
		<div id="ldapAdvancedAccordion">
			<h3><?php p($l->t('Connection Settings'));?></h3>
			<div>
				<p><label for="ldap_configuration_active"><?php p($l->t('Configuration Active'));?></label><input type="checkbox" id="ldap_configuration_active" name="ldap_configuration_active" value="1" data-default="<?php p($_['ldap_configuration_active_default']); ?>"  title="<?php p($l->t('When unchecked, this configuration will be skipped.'));?>" /></p>
				<p><label for="ldap_backup_host"><?php p($l->t('Backup (Replica) Host'));?></label><input type="text" id="ldap_backup_host" name="ldap_backup_host" data-default="<?php p($_['ldap_backup_host_default']); ?>" title="<?php p($l->t('Give an optional backup host. It must be a replica of the main LDAP/AD server.'));?>"></p>
				<p><label for="ldap_backup_port"><?php p($l->t('Backup (Replica) Port'));?></label><input type="number" id="ldap_backup_port" name="ldap_backup_port" data-default="<?php p($_['ldap_backup_port_default']); ?>"  /></p>
				<p><label for="ldap_override_main_server"><?php p($l->t('Disable Main Server'));?></label><input type="checkbox" id="ldap_override_main_server" name="ldap_override_main_server" value="1" data-default="<?php p($_['ldap_override_main_server_default']); ?>"  title="<?php p($l->t('Only connect to the replica server.'));?>" /></p>
				<p><label for="ldap_nocase"><?php p($l->t('Case insensitive LDAP server (Windows)'));?></label><input type="checkbox" id="ldap_nocase" name="ldap_nocase" data-default="<?php p($_['ldap_nocase_default']); ?>"  value="1"<?php if (isset($_['ldap_nocase']) && ($_['ldap_nocase'])) p(' checked'); ?>></p>
				<p><label for="ldap_turn_off_cert_check"><?php p($l->t('Turn off SSL certificate validation.'));?></label><input type="checkbox" id="ldap_turn_off_cert_check" name="ldap_turn_off_cert_check" title="<?php p($l->t('Not recommended, use it for testing only! If connection only works with this option, import the LDAP server\'s SSL certificate in your %s server.', $theme->getName() ));?>" data-default="<?php p($_['ldap_turn_off_cert_check_default']); ?>" value="1"><br/></p>
				<p><label for="ldap_cache_ttl"><?php p($l->t('Cache Time-To-Live'));?></label><input type="number" id="ldap_cache_ttl" name="ldap_cache_ttl" title="<?php p($l->t('in seconds. A change empties the cache.'));?>" data-default="<?php p($_['ldap_cache_ttl_default']); ?>" /></p>
			</div>
			<h3><?php p($l->t('Directory Settings'));?></h3>
			<div>
				<p><label for="ldap_display_name"><?php p($l->t('User Display Name Field'));?></label><input type="text" id="ldap_display_name" name="ldap_display_name" data-default="<?php p($_['ldap_display_name_default']); ?>" title="<?php p($l->t('The LDAP attribute to use to generate the user\'s display name.'));?>" /></p>
				<p><label for="ldap_base_users"><?php p($l->t('Base User Tree'));?></label><textarea id="ldap_base_users" name="ldap_base_users" placeholder="<?php p($l->t('One User Base DN per line'));?>" data-default="<?php p($_['ldap_base_users_default']); ?>" title="<?php p($l->t('Base User Tree'));?>"></textarea></p>
				<p><label for="ldap_attributes_for_user_search"><?php p($l->t('User Search Attributes'));?></label><textarea id="ldap_attributes_for_user_search" name="ldap_attributes_for_user_search" placeholder="<?php p($l->t('Optional; one attribute per line'));?>" data-default="<?php p($_['ldap_attributes_for_user_search_default']); ?>" title="<?php p($l->t('User Search Attributes'));?>"></textarea></p>
				<p><label for="ldap_group_display_name"><?php p($l->t('Group Display Name Field'));?></label><input type="text" id="ldap_group_display_name" name="ldap_group_display_name" data-default="<?php p($_['ldap_group_display_name_default']); ?>" title="<?php p($l->t('The LDAP attribute to use to generate the groups\'s display name.'));?>" /></p>
				<p><label for="ldap_base_groups"><?php p($l->t('Base Group Tree'));?></label><textarea id="ldap_base_groups" name="ldap_base_groups" placeholder="<?php p($l->t('One Group Base DN per line'));?>" data-default="<?php p($_['ldap_base_groups_default']); ?>" title="<?php p($l->t('Base Group Tree'));?>"></textarea></p>
				<p><label for="ldap_attributes_for_group_search"><?php p($l->t('Group Search Attributes'));?></label><textarea id="ldap_attributes_for_group_search" name="ldap_attributes_for_group_search" placeholder="<?php p($l->t('Optional; one attribute per line'));?>" data-default="<?php p($_['ldap_attributes_for_group_search_default']); ?>" title="<?php p($l->t('Group Search Attributes'));?>"></textarea></p>
				<p><label for="ldap_group_member_assoc_attribute"><?php p($l->t('Group-Member association'));?></label><select id="ldap_group_member_assoc_attribute" name="ldap_group_member_assoc_attribute" data-default="<?php p($_['ldap_group_member_assoc_attribute_default']); ?>" ><option value="uniqueMember"<?php if (isset($_['ldap_group_member_assoc_attribute']) && ($_['ldap_group_member_assoc_attribute'] === 'uniqueMember')) p(' selected'); ?>>uniqueMember</option><option value="memberUid"<?php if (isset($_['ldap_group_member_assoc_attribute']) && ($_['ldap_group_member_assoc_attribute'] === 'memberUid')) p(' selected'); ?>>memberUid</option><option value="member"<?php if (isset($_['ldap_group_member_assoc_attribute']) && ($_['ldap_group_member_assoc_attribute'] === 'member')) p(' selected'); ?>>member (AD)</option></select></p>
				<p><label for="ldap_nested_groups"><?php p($l->t('Nested Groups'));?></label><input type="checkbox" id="ldap_nested_groups" name="ldap_nested_groups" value="1" data-default="<?php p($_['ldap_nested_groups_default']); ?>"  title="<?php p($l->t('When switched on, groups that contain groups are supported. (Only works if the group member attribute contains DNs.)'));?>" /></p>
				<p><label for="ldap_paging_size"><?php p($l->t('Paging chunksize'));?></label><input type="number" id="ldap_paging_size" name="ldap_paging_size" title="<?php p($l->t('Chunksize used for paged LDAP searches that may return bulky results like user or group enumeration. (Setting it 0 disables paged LDAP searches in those situations.)'));?>" data-default="<?php p($_['ldap_paging_size_default']); ?>" /></p>
			</div>
			<h3><?php p($l->t('Special Attributes'));?></h3>
			<div>
				<p><label for="ldap_quota_attr"><?php p($l->t('Quota Field'));?></label><input type="text" id="ldap_quota_attr" name="ldap_quota_attr" data-default="<?php p($_['ldap_quota_attr_default']); ?>"/></p>
				<p><label for="ldap_quota_def"><?php p($l->t('Quota Default'));?></label><input type="text" id="ldap_quota_def" name="ldap_quota_def" data-default="<?php p($_['ldap_quota_def_default']); ?>" title="<?php p($l->t('in bytes'));?>" /></p>
				<p><label for="ldap_email_attr"><?php p($l->t('Email Field'));?></label><input type="text" id="ldap_email_attr" name="ldap_email_attr" data-default="<?php p($_['ldap_email_attr_default']); ?>" /></p>
				<p><label for="home_folder_naming_rule"><?php p($l->t('User Home Folder Naming Rule'));?></label><input type="text" id="home_folder_naming_rule" name="home_folder_naming_rule" title="<?php p($l->t('Leave empty for user name (default). Otherwise, specify an LDAP/AD attribute.'));?>" data-default="<?php p($_['home_folder_naming_rule_default']); ?>" /></p>
			</div>
		</div>
		<?php print_unescaped($_['settingControls']); ?>
	</fieldset>
	<fieldset id="ldapSettings-2">
		<p><strong><?php p($l->t('Internal Username'));?></strong></p>
		<p class="ldapIndent"><?php p($l->t('By default the internal username will be created from the UUID attribute. It makes sure that the username is unique and characters do not need to be converted. The internal username has the restriction that only these characters are allowed: [ a-zA-Z0-9_.@- ].  Other characters are replaced with their ASCII correspondence or simply omitted. On collisions a number will be added/increased. The internal username is used to identify a user internally. It is also the default name for the user home folder. It is also a part of remote URLs, for instance for all *DAV services. With this setting, the default behavior can be overridden. To achieve a similar behavior as before ownCloud 5 enter the user display name attribute in the following field. Leave it empty for default behavior. Changes will have effect only on newly mapped (added) LDAP users.'));?></p>
		<p class="ldapIndent"><label for="ldap_expert_username_attr"><?php p($l->t('Internal Username Attribute:'));?></label><input type="text" id="ldap_expert_username_attr" name="ldap_expert_username_attr" data-default="<?php p($_['ldap_expert_username_attr_default']); ?>" /></p>
		<p><strong><?php p($l->t('Override UUID detection'));?></strong></p>
		<p class="ldapIndent"><?php p($l->t('By default, the UUID attribute is automatically detected. The UUID attribute is used to doubtlessly identify LDAP users and groups. Also, the internal username will be created based on the UUID, if not specified otherwise above. You can override the setting and pass an attribute of your choice. You must make sure that the attribute of your choice can be fetched for both users and groups and it is unique. Leave it empty for default behavior. Changes will have effect only on newly mapped (added) LDAP users and groups.'));?></p>
		<p class="ldapIndent"><label for="ldap_expert_uuid_user_attr"><?php p($l->t('UUID Attribute for Users:'));?></label><input type="text" id="ldap_expert_uuid_user_attr" name="ldap_expert_uuid_user_attr" data-default="<?php p($_['ldap_expert_uuid_user_attr_default']); ?>" /></p>
		<p class="ldapIndent"><label for="ldap_expert_uuid_group_attr"><?php p($l->t('UUID Attribute for Groups:'));?></label><input type="text" id="ldap_expert_uuid_group_attr" name="ldap_expert_uuid_group_attr" data-default="<?php p($_['ldap_expert_uuid_group_attr_default']); ?>" /></p>
		<p><strong><?php p($l->t('Username-LDAP User Mapping'));?></strong></p>
		<p class="ldapIndent"><?php p($l->t('Usernames are used to store and assign (meta) data. In order to precisely identify and recognize users, each LDAP user will have a internal username. This requires a mapping from username to LDAP user. The created username is mapped to the UUID of the LDAP user. Additionally the DN is cached as well to reduce LDAP interaction, but it is not used for identification. If the DN changes, the changes will be found. The internal username is used all over. Clearing the mappings will have leftovers everywhere. Clearing the mappings is not configuration sensitive, it affects all LDAP configurations! Never clear the mappings in a production environment, only in a testing or experimental stage.'));?></p>
		<p class="ldapIndent"><button type="button" id="ldap_action_clear_user_mappings" name="ldap_action_clear_user_mappings"><?php p($l->t('Clear Username-LDAP User Mapping'));?></button><br/><button type="button" id="ldap_action_clear_group_mappings" name="ldap_action_clear_group_mappings"><?php p($l->t('Clear Groupname-LDAP Group Mapping'));?></button></p>
		<?php print_unescaped($_['settingControls']); ?>
	</fieldset>
	</div>

</form>
