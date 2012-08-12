	<fieldset class="personalblock">
		<img src="<?php echo image_path('remoteStorage', 'remoteStorage.png') ?>" style="width:16px">
		<strong><?php echo $l->t('remoteStorage') ?></strong> user address: <?php echo OCP\USER::getUser().'@'.$_SERVER['SERVER_NAME'] ?> (<a href="http://unhosted.org/">more info</a>)
		<p><em>Apps that currently have access to your ownCloud:</em></p>
		<script>
			function revokeToken(token) {
				var xhr = new XMLHttpRequest();
				xhr.open('POST', '/apps/remoteStorage/ajax/revokeToken.php', true);
				xhr.send(token);
			}
		</script>
		<ul>
		<?php foreach(OC_remoteStorage::getAllTokens() as $token => $details) { ?>
			<li onmouseover="$('#revoke_<?php echo $token ?>').show();" onmouseout="$('#revoke_<?php echo $token ?>').hide();">
				<strong><?php echo $details['appUrl'] ?></strong>: <?php echo $details['categories'] ?>
				<a href="#" title="Revoke" class="action" style="display:none" id="revoke_<?php echo $token ?>" onclick="revokeToken('<?php echo $token ?>');$(this).hide();">
					<img src="<?php echo OCP\Util::imagePath('core', 'actions/delete.svg') ?>">
				</a>
			</li>
		<?php } ?>
		</ul>
	</fieldset>
