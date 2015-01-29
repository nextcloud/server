<?php
/** @var OC_L10N $l */
/** @var array $_ */
?>
<div class="section" id="fileSharingSettings" >

	<h2><?php p($l->t('Federated Cloud Sharing'));?></h2>

	<input type="checkbox" name="outgoing_server2server_share_enabled" id="outgoingServer2serverShareEnabled"
		   value="1" <?php if ($_['outgoingServer2serverShareEnabled']) print_unescaped('checked="checked"'); ?> />
	<label for="outgoingServer2serverShareEnabled">
		<?php p($l->t('Allow users on this server to send shares to other servers'));?>
	</label><br/>

	<input type="checkbox" name="incoming_server2server_share_enabled" id="incomingServer2serverShareEnabled"
		   value="1" <?php if ($_['incomingServer2serverShareEnabled']) print_unescaped('checked="checked"'); ?> />
	<label for="incomingServer2serverShareEnabled">
		<?php p($l->t('Allow users on this server to receive shares from other servers'));?>
	</label><br/>

</div>
