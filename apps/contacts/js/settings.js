OC.Contacts = OC.Contacts || {};
OC.Contacts.Settings = OC.Contacts.Settings || {
	init:function() {
		this.Addressbook.adrsettings = $('.addressbooks-settings').first();
		this.Addressbook.adractions = $('#contacts-settings').find('div.actions');
		console.log('actions: ' + this.Addressbook.adractions.length);
	},
	Addressbook:{
		showActions:function(act) {
			this.adractions.children().hide();
			this.adractions.children('.'+act.join(',.')).show();
		},
		doActivate:function(id, tgt) {
			var active = tgt.is(':checked');
			console.log('doActivate: ', id, active);
			$.post(OC.filePath('contacts', 'ajax', 'addressbook/activate.php'), {id: id, active: Number(active)}, function(jsondata) {
				if (jsondata.status == 'success'){
					if(!active) {
						$('#contacts h3[data-id="'+id+'"],#contacts ul[data-id="'+id+'"]').remove();
					} else {
						OC.Contacts.Contacts.update();
					}
				} else {
					console.log('Error:', jsondata.data.message);
					OC.Contacts.notify(t('contacts', 'Error') + ': ' + jsondata.data.message);
					tgt.checked = !active;
				}
			});
		},
		doDelete:function(id) {
			console.log('doDelete: ', id);
			var check = confirm('Do you really want to delete this address book?');
			if(check == false){
				return false;
			} else {
				$.post(OC.filePath('contacts', 'ajax', 'addressbook/delete.php'), { id: id}, function(jsondata) {
					if (jsondata.status == 'success'){
						$('#contacts h3[data-id="'+id+'"],#contacts ul[data-id="'+id+'"]').remove();
						$('.addressbooks-settings tr[data-id="'+id+'"]').remove()
						OC.Contacts.Contacts.update();
					} else {
						OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
					}
				});
			}
		},
		doEdit:function(id) {
			console.log('doEdit: ', id);
			this.showActions(['active', 'name', 'description', 'save', 'cancel']);
			var name = this.adrsettings.find('[data-id="'+id+'"]').find('.name').text();
			var description = this.adrsettings.find('[data-id="'+id+'"]').find('.description').text();
			var active = this.adrsettings.find('[data-id="'+id+'"]').find(':checkbox').is(':checked');
			console.log('name, desc', name, description);
			this.adractions.find('.active').prop('checked', active);
			this.adractions.find('.name').val(name);
			this.adractions.find('.description').val(description);
			this.adractions.data('id', id);
		},
		doSave:function() {
			var name = this.adractions.find('.name').val();
			var description = this.adractions.find('.description').val();
			var active = this.adractions.find('.active').is(':checked');
			var id = this.adractions.data('id');
			console.log('doSave:', id, name, description, active);

			if(name.length == 0) {
				OC.dialogs.alert(t('contacts', 'Displayname cannot be empty.'), t('contacts', 'Error'));
				return false;
			}
			var url;
			if (id == 'new'){
				url = OC.filePath('contacts', 'ajax', 'addressbook/add.php');
			}else{
				url = OC.filePath('contacts', 'ajax', 'addressbook/update.php');
			}
			self = this;
			$.post(url, { id: id, name: name, active: Number(active), description: description },
				function(jsondata){
					if(jsondata.status == 'success'){
						self.showActions(['new',]);
						self.adractions.removeData('id');
						active = Boolean(Number(jsondata.addressbook.active));
						if(id == 'new') {
							self.adrsettings.find('table')
								.append('<tr class="addressbook" data-id="'+jsondata.addressbook.id+'" data-uri="'+jsondata.addressbook.uri+'">'
									+ '<td class="active"><input type="checkbox" '+(active ? 'checked="checked"' : '')+' /></td>'
									+ '<td class="name">'+jsondata.addressbook.displayname+'</td>'
									+ '<td class="description">'+jsondata.addressbook.description+'</td>'
									+ '<td class="action"><a class="svg action globe" title="'+t('contacts', 'Show CardDav link')+'"></a></td>'
									+ '<td class="action"><a class="svg action cloud" title="'+t('contacts', 'Show read-only VCF link')+'"></a></td>'
									+ '<td class="action"><a class="svg action download" title="'+t('contacts', 'Download')+'" '
									+ 'href="'+totalurl+'/'+encodeURIComponent(oc_current_user)+'/'
									+ encodeURIComponent(jsondata.addressbook.uri)+'?export"></a></td>'
									+ '<td class="action"><a class="svg action edit" title="'+t('contacts', 'Edit')+'"></a></td>'
									+ '<td class="action"><a class="svg action delete" title="'+t('contacts', 'Delete')+'"></a></td>'
									+ '</tr>');
						} else {
						var row = self.adrsettings.find('tr[data-id="'+id+'"]');
							row.find('td.active').find('input:checkbox').prop('checked', active);
							row.find('td.name').text(jsondata.addressbook.displayname);
							row.find('td.description').text(jsondata.addressbook.description);
						}
						OC.Contacts.Contacts.update();
					} else {
						OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
					}
			});
		},
		showCardDAV:function(id) {
			console.log('showCardDAV: ', id);
			var row = this.adrsettings.find('tr[data-id="'+id+'"]');
			this.adractions.find('.link').val(totalurl+'/'+encodeURIComponent(oc_current_user)+'/');
			this.showActions(['link','cancel']);
		},
		showVCF:function(id) {
			console.log('showVCF: ', id);
			var row = this.adrsettings.find('tr[data-id="'+id+'"]');
			this.adractions.find('.link').val(totalurl+'/'+encodeURIComponent(oc_current_user)+'/'+encodeURIComponent(row.data('uri'))+'?export');
			this.showActions(['link','cancel']);
		}
	}
};


$(document).ready(function() {
	OC.Contacts.Settings.init();

	var moreless = $('#contacts-settings').find('.moreless').first();
	moreless.keydown(function(event) {
		if(event.which == 13 || event.which == 32) {
			moreless.click();
		}
	});
	moreless.on('click', function(event) {
		event.preventDefault();
		if(OC.Contacts.Settings.Addressbook.adrsettings.is(':visible')) {
			OC.Contacts.Settings.Addressbook.adrsettings.slideUp();
			OC.Contacts.Settings.Addressbook.adrsettings.prev('dt').hide();
			moreless.text(t('contacts', 'More...'));
		} else {
			OC.Contacts.Settings.Addressbook.adrsettings.slideDown();
			OC.Contacts.Settings.Addressbook.adrsettings.prev('dt').show();
			moreless.text(t('contacts', 'Less...'));
		}
	});

	OC.Contacts.Settings.Addressbook.adrsettings.keydown(function(event) {
		if(event.which == 13 || event.which == 32) {
			OC.Contacts.Settings.Addressbook.adrsettings.click();
		}
	});


	OC.Contacts.Settings.Addressbook.adrsettings.on('click', function(event){
		$('.tipsy').remove();
		var tgt = $(event.target);
		if(tgt.is('a') || tgt.is(':checkbox')) {
			var id = tgt.parents('tr').first().data('id');
			if(!id) {
				return;
			}
			if(tgt.is(':checkbox')) {
				OC.Contacts.Settings.Addressbook.doActivate(id, tgt);
			} else if(tgt.is('a')) {
				if(tgt.hasClass('edit')) {
					OC.Contacts.Settings.Addressbook.doEdit(id);
				} else if(tgt.hasClass('delete')) {
					OC.Contacts.Settings.Addressbook.doDelete(id);
				} else if(tgt.hasClass('globe')) {
					OC.Contacts.Settings.Addressbook.showCardDAV(id);
				} else if(tgt.hasClass('cloud')) {
					OC.Contacts.Settings.Addressbook.showVCF(id);
				}
			}
		} else if(tgt.is('button')) {
			event.preventDefault();
			if(tgt.hasClass('save')) {
				OC.Contacts.Settings.Addressbook.doSave();
			} else if(tgt.hasClass('cancel')) {
				OC.Contacts.Settings.Addressbook.showActions(['new']);
			} else if(tgt.hasClass('new')) {
				OC.Contacts.Settings.Addressbook.doEdit('new');
			}
		}
	});
});
