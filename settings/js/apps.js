/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

$(document).ready(function(){
	$('#leftcontent li').each(function(index,li){
		var app=$.parseJSON($(this).children('span').text());
		$(li).data('app',app);
	});
	$('#leftcontent li').click(function(){
		var app=$(this).data('app');
		$('#rightcontent p').show();
		$('#rightcontent span.name').text(app.name);
		$('#rightcontent span.version').text(app.version);
		$('#rightcontent p.description').text(app.description);
		$('#rightcontent span.author').text(app.author);
		$('#rightcontent span.licence').text(app.licence);
		
		$('#rightcontent input.enable').show();
		$('#rightcontent input.enable').val((app.active)?t('settings','Disable'):t('settings','Enable'));
		$('#rightcontent input.enable').data('appid',app.id);
		$('#rightcontent input.enable').data('active',app.active);
	});
	$('#rightcontent input.enable').click(function(){
		var app=$(this).data('appid');
		var active=$(this).data('active');
		if(app){
			if(active){
				$.post(OC.filePath('settings','ajax','disableapp.php'),{appid:app});
				$('#leftcontent li[data-id="'+app+'"]').removeClass('active');
			}else{
				$.post(OC.filePath('settings','ajax','enableapp.php'),{appid:app});
				$('#leftcontent li[data-id="'+app+'"]').addClass('active');
			}
			active=!active;
			$(this).data('active',active);
			$(this).val((active)?t('settings','Disable'):t('settings','Enable'));
			var appData=$('#leftcontent li[data-id="'+app+'"]');
			appData.active=active;
		}
	});
});
