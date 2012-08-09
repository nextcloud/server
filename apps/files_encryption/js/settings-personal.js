/**
 * Copyright (c) 2012, Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

$(document).ready(function(){
	$('input[name=encryption_mode]').change(function(){
		var prevmode = document.getElementById('prev_encryption_mode').value
		var  client=$('input[value="client"]:checked').val()
			 ,server=$('input[value="server"]:checked').val()
			 ,user=$('input[value="user"]:checked').val()
			 ,none=$('input[value="none"]:checked').val()
		if (client) {
			$.post(OC.filePath('files_encryption', 'ajax', 'mode.php'), { mode: 'client' });
			if (prevmode == 'server') {
				OC.dialogs.info(t('encryption', 'Please switch to your ownCloud client and change your encryption password to complete the conversion.'), t('encryption', 'switched to client side encryption'));
			}
		} else if (server) {
			if (prevmode == 'client') {
				OC.dialogs.form([{text:'Login password', name:'newpasswd', type:'password'},{text:'Encryption password used on the client', name:'oldpasswd', type:'password'}],t('encryption', 'Change encryption password to login password'), function(data) {
					$.post(OC.filePath('files_encryption', 'ajax', 'mode.php'), { mode: 'server', newpasswd: data[0].value, oldpasswd: data[1].value }, function(result) {
						if (result.status != 'success') {
							document.getElementById(prevmode+'_encryption').checked = true;
							OC.dialogs.alert(t('encryption', 'Please check your passwords and try again.'), t('encryption', 'Could not change your file encryption password to your login password'))
						} else {
							console.log("alles super");
						}
					}, true);
				});
			} else {
				$.post(OC.filePath('files_encryption', 'ajax', 'mode.php'), { mode: 'server' });
			}
		} else {
			$.post(OC.filePath('files_encryption', 'ajax', 'mode.php'), { mode: 'none' });
		}
	})
})