/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */


$(document).ready(function(){
	$('#encryption_blacklist').multiSelect({
		oncheck:blackListChange,
		onuncheck:blackListChange,
		createText:'...',
	});
	
	function blackListChange(){
		var blackList=$('#encryption_blacklist').val().join(',');
		OC.AppConfig.setValue('files_encryption','type_blacklist',blackList);
	}

	$('#enable_encryption').change(function(){
		var checked=$('#enable_encryption').is(':checked');
		OC.AppConfig.setValue('files_encryption','enable_encryption',(checked)?'true':'false');
	})
	$('input[name=encryption_mode]').change(function(){
		var  client=$('input[value="client"]:checked').val()
			 ,server=$('input[value="server"]:checked').val()
			 ,none=$('input[value="none"]:checked').val()
		if (client)
			OC.AppConfig.setValue('files_encryption','mode','client');
		if (server)
			OC.AppConfig.setValue('files_encryption','mode','server');
		if (none)
			OC.AppConfig.setValue('files_encryption','mode','none');
	})
})