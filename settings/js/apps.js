/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * Copyright (c) 2012, Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC.Settings = OC.Settings || {};
OC.Settings.Apps = OC.Settings.Apps || {
	loadApp:function(app) {
		var page = $('#rightcontent');
		page.find('p.license').show();
		page.find('span.name').text(app.name);
		page.find('small.externalapp').text(app.internallabel);
		if (app.version) {
			page.find('span.version').text(app.version);
		} else {
			page.find('span.version').text('');
		}
		page.find('span.score').html(app.score);
		page.find('p.description').text(app.description);
		page.find('img.preview').attr('src', app.preview);
		page.find('small.externalapp').attr('style', 'visibility:visible');
		page.find('span.author').text(app.author);
		page.find('span.licence').text(app.licence);

		if (app.update != false) {
			page.find('input.update').show();
			page.find('input.update').data('appid', app.id);
			page.find('input.update').attr('value',t('settings', 'Update to {appversion}', {appversion:app.update}));
		} else {
			page.find('input.update').hide();
		}

		page.find('input.enable').show();
		page.find('input.enable').val((app.active) ? t('settings', 'Disable') : t('settings', 'Enable'));
		page.find('input.enable').data('appid', app.id);
		page.find('input.enable').data('active', app.active);
		if (app.internal == false) {
			page.find('span.score').show();
			page.find('p.appslink').show();
			page.find('a').attr('href', 'http://apps.owncloud.com/content/show.php?content=' + app.id);
			page.find('small.externalapp').hide();
		} else {
			page.find('p.appslink').hide();
			page.find('span.score').hide();
		}
	},
	enableApp:function(appid, active, element) {
		console.log('enableApp:', appid, active, element);
		var appitem=$('#leftcontent li[data-id="'+appid+'"]');
		appData = appitem.data('app');
		appData.active = !active;
		appitem.data('app', appData);
		element.val(t('settings','Please wait....'));
		if(active) {
			$.post(OC.filePath('settings','ajax','disableapp.php'),{appid:appid},function(result) {
				if(!result || result.status!='success') {
					OC.dialogs.alert('Error while disabling app','Error');
				}
				else {
					element.data('active',false);
					OC.Settings.Apps.removeNavigation(appid);
					element.val(t('settings','Enable'));
				}
			},'json');
			$('#leftcontent li[data-id="'+appid+'"]').removeClass('active');
		} else {
			$.post(OC.filePath('settings','ajax','enableapp.php'),{appid:appid},function(result) {
				if(!result || result.status!='success') {
					OC.dialogs.alert('Error while enabling app','Error');
				}
				else {
					OC.Settings.Apps.addNavigation(appid);
					element.data('active',true);
					element.val(t('settings','Disable'));
				}
			},'json')
			.fail(function() { 
				OC.dialogs.alert('Error while enabling app','Error');
				element.data('active',false);
				OC.Settings.Apps.removeNavigation(appid);
				element.val(t('settings','Enable'));
			});
			$('#leftcontent li[data-id="'+appid+'"]').addClass('active');
		}
	},
	updateApp:function(appid, element) {
		console.log('updateApp:', appid, element);
		element.val(t('settings','Updating....'));
		$.post(OC.filePath('settings','ajax','updateapp.php'),{appid:appid},function(result) {
			if(!result || result.status!='success') {
				OC.dialogs.alert(t('settings','Error while updating app'),t('settings','Error'));
			}
			else {
				element.val(t('settings','Updated'));
				element.hide();
			}
		},'json');
	},

	insertApp:function(appdata) {
		var applist = $('#leftcontent li');
		var app =
				$('<li data-id="' + appdata.id + '" data-type="external" data-installed="0">'
				+ '<a class="app externalapp" href="' + OC.filePath('settings', 'apps', 'index.php') + '&appid=' + appdata.id+'">'
				+ appdata.name+'</a><small class="externalapp list">3rd party</small></li>');
		app.data('app', appdata);
		var added = false;
		applist.each(function() {
			if(!parseInt($(this).data('installed')) && $(this).find('a').text().toLowerCase() > appdata.name.toLowerCase()) {
				$(this).before(app);
				added = true;
				return false; // dang, remember this to get out of loop
			}
		});
		if(!added) {
			applist.last().after(app);
		}
		return app;
	},
	removeNavigation: function(appid){
		$.getJSON(OC.filePath('settings', 'ajax', 'navigationdetect.php'), {app: appid}).done(function(response){
			if(response.status === 'success'){
				var navIds=response.nav_ids;
				for(var i=0; i< navIds.length; i++){
					$('#apps').children('li[data-id="'+navIds[i]+'"]').remove();
				}
			}
		});
	},
	addNavigation: function(appid){
		$.getJSON(OC.filePath('settings', 'ajax', 'navigationdetect.php'), {app: appid}).done(function(response){
			if(response.status === 'success'){
				var navEntries=response.nav_entries;
				for(var i=0; i< navEntries.length; i++){
					var entry = navEntries[i];
					var container = $('#apps');

					if(container.children('li[data-id="'+entry.id+'"]').length === 0){
						var li=$('<li></li>');
						li.attr('data-id', entry.id);
						var img= $('<img class="icon"/>').attr({ src: entry.icon});
						var a=$('<a></a>').attr('href', entry.href);
						var filename=$('<span></span>')
						filename.text(entry.name);
						a.prepend(filename);
						a.prepend(img);
						li.append(a);
						container.append(li);
					}
				}
			}
		});
	}
};

$(document).ready(function(){
	$('#leftcontent li').each(function(index,li){
		var app = OC.get('appData_'+$(li).data('id'));
		$(li).data('app',app);
		$(this).find('span.hidden').remove();
	});
	$('#leftcontent li').keydown(function(event) {
		if (event.which == 13 || event.which == 32) {
			$(event.target).click();
		}
		return false;
	});

	$(document).on('click', '#leftcontent', function(event){
		var tgt = $(event.target);
		if (tgt.is('li') || tgt.is('a')) {
			var item = tgt.is('li') ? $(tgt) : $(tgt).parent();
			var app = item.data('app');
			OC.Settings.Apps.loadApp(app);
		}
		return false;
	});
	$('#rightcontent input.enable').click(function(){
		var element = $(this);
		var appid=$(this).data('appid');
		var active=$(this).data('active');
		if(appid) {
			OC.Settings.Apps.enableApp(appid, active, element);
		}
	});
	$('#rightcontent input.update').click(function(){
		var element = $(this);
		var appid=$(this).data('appid');
		if(appid) {
			OC.Settings.Apps.updateApp(appid, element);
		}
	});

	if(appid) {
		var item = $('#leftcontent li[data-id="'+appid+'"]');
		if(item) {
			item.trigger('click');
			item.addClass('active');
			$('#leftcontent').animate({scrollTop: $(item).offset().top-70}, 'slow','swing');
		}
	}
});
