/**
 * Copyright (c) 2012, Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

$(document).ready(function(){
	$('input[name=encryption_mode]').change(function(){
		var  client=$('input[value="client"]:checked').val()
			 ,server=$('input[value="server"]:checked').val()
			 ,user=$('input[value="user"]:checked').val()
			 ,none=$('input[value="none"]:checked').val()
		if (client)
			var encmode= 'client';
		else if (server)
			var encmode = 'server';
		else
			var encmode = 'none';	
		$.post(OC.filePath('files_encryption', 'ajax', 'mode.php'), { mode: encmode });
	})
})