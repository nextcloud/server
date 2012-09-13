<ul>
	<li class='error'>
		<details>
		<summary class="error">We're sorry, but something went terribly wrong.<br></summary>
		<p class="exception">
		<?php
		if($_['showsysinfo'] == true) {
			echo 'If you would like to support ownCloud\'s developers and report this error in our <a href="http://bugs.owncloud.org">Bugtracker</a>, please copy the following informations into the description. <br><br><textarea readonly>';
			echo 'Message: '  . $_['message'] . "\n";
			echo 'Error Code: '  . $_['code'] . "\n";
			echo 'File: '  . $_['file'] . "\n";
			echo 'Line: '  . $_['line'] . "\n\n";
			echo 'PHP: '  . $_['sysinfo']['phpversion'] . "\n";
			echo 'OS: '  . $_['sysinfo']['os'] . "\n";
			echo 'OS Release: '  . $_['sysinfo']['osrelease'] . "\n";
			echo 'OS Arch.: '  . $_['sysinfo']['osarchitecture'] . "\n";
			echo 'PHP-Server-Interface: '  . $_['sysinfo']['phpserverinterface'] . "\n";
			echo 'Protocol: '  . $_['sysinfo']['serverprotocol'] . "\n";
			echo 'HTTPS: '  . $_['sysinfo']['https'] . "\n";
			echo 'Request Method: '  . $_['sysinfo']['requestmethod'] . "\n";
			echo 'Database: '  . $_['sysinfo']['database'] . "\n";
			echo '</textarea>';
		}else{
			echo 'Your administrator has disabled systeminformations.';
		}
		?>
		</p>
		</details>
	</li>
</ul>