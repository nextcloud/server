	<fieldset class="personalblock">
		<?php
			echo '<img src="../apps/remoteStorage/remoteStorage.png" style="width:16px"> '
				.'<strong>'.$l->t('remoteStorage').'</strong> user address: '
				.OCP\USER::getUser().'@'.$_SERVER['SERVER_NAME']
				.' (<a href="http://unhosted.org/">more info</a>)';
		?>
		<p><em>Apps that currently have access to your ownCloud:</em></p>
		<script>
			function revokeToken(token) {
				var xhr = new XMLHttpRequest();
				xhr.open('POST', '/apps/remoteStorage/ajax/revokeToken.php', true);
				xhr.send(token);
			}
		</script>
		<ul>
		<?php
			foreach(OC_remoteStorage::getAllTokens() as $token => $details) {
				echo '<li onmouseover="'
					.'document.getElementById(\'revoke_'.$token.'\').style.display=\'inline\';"'
					.'onmouseout="document.getElementById(\'revoke_'.$token.'\').style.display=\'none\';"'
					.'> <strong>'.$details['appUrl'].'</strong>: '.$details['categories']
					.' <a href="#" title="Revoke" class="action" style="display:none" id="revoke_'.$token.'" onclick="'
					.'revokeToken(\''.$token.'\');this.parentNode.style.display=\'none\';"'
					.'><img src="/core/img/actions/delete.svg"></a></li>'."\n";
			}
		?></ul>
	</fieldset>
