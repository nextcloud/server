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

	$('#enbale_encryption').change(function(){
		var checked=$('#enbale_encryption').is(':checked');
		OC.AppConfig.setValue('files_encryption','enable_encryption',(checked)?'true':'false');
	})
})