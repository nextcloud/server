/**
 * Copyright (c) 2012, Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

$(document).ready(function(){
	console.log("loaded!");
	$('input[name=encryption_mode]').change(function(){
		console.log("HERE!!!!!!!!!!!");
		var  client=$('input[value="client"]:checked').val()
			 ,server=$('input[value="server"]:checked').val()
			 ,user=$('input[value="user"]:checked').val()
			 ,none=$('input[value="none"]:checked').val()
		if (client)
			$.post(OC.filePath('files_encryption', 'ajax', 'changemode.php'), { mode: 'client' });
		else if (server)
			$.post(OC.filePath('files_encryption', 'ajax', 'changemode.php'), { mode: 'server' });
		else
			$.post(OC.filePath('files_encryption', 'ajax', 'changemode.php'), { mode: 'none' });
	})
})