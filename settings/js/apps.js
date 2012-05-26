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
	$('#leftcontent li').keydown(function(event) {
		if (event.which == 13 || event.which == 32) {
			$(event.target).click()
		}
		return false;
	});
	$('#leftcontent li').click(function(){
		var app=$(this).data('app');
		$('#rightcontent p').show();
		$('#rightcontent span.name').text(app.name);
		$('#rightcontent small.externalapp').text(app.internallabel);
		$('#rightcontent span.version').text(app.version);
		$('#rightcontent p.description').text(app.description);
		$('#rightcontent img.preview').attr('src',app.preview);
		$('#rightcontent small.externalapp').attr('style','visibility:visible');
		$('#rightcontent span.author').text(app.author);
		$('#rightcontent span.licence').text(app.licence);
		
		$('#rightcontent input.enable').show();
		$('#rightcontent input.enable').val((app.active)?t('settings','Disable'):t('settings','Enable'));
		$('#rightcontent input.enable').data('appid',app.id);
		$('#rightcontent input.enable').data('active',app.active);
		return false;
	});
	$('#rightcontent input.enable').click(function(){
		var element = $(this);
		var app=$(this).data('appid');
		var active=$(this).data('active');
		if(app){
			if(active){
				$.post(OC.filePath('settings','ajax','disableapp.php'),{appid:app},function(result){
					if(!result || result.status!='success'){
						OC.dialogs.alert('Error while disabling app','Error');
					}
					else {
						element.data('active',false);
						element.val(t('settings','Enable'));
						var appData=$('#leftcontent li[data-id="'+app+'"]');
						appData.active=false;
					}
				},'json');
				$('#leftcontent li[data-id="'+app+'"]').removeClass('active');
			}else{
				$.post(OC.filePath('settings','ajax','enableapp.php'),{appid:app},function(result){
					if(!result || result.status!='success'){
						OC.dialogs.alert('Error while enabling app','Error');
					}
					else {
						element.data('active',true);
						element.val(t('settings','Disable'));
						var appData=$('#leftcontent li[data-id="'+app+'"]');
						appData.active=true;
					}
				},'json');
				$('#leftcontent li[data-id="'+app+'"]').addClass('active');
			}
		}
	});
});
