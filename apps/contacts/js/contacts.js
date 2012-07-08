function ucwords (str) {
	return (str + '').replace(/^([a-z])|\s+([a-z])/g, function ($1) {
		return $1.toUpperCase();
	});
}

String.prototype.strip_tags = function(){
	tags = this;
	stripped = tags.replace(/<(.|\n)*?>/g, '');
	return stripped;
};

Contacts={
	UI:{
		notification:function(msg, ndata) {
			$('#notification').text(msg);
			if(data) {
				$('#notification').data(ndata[0],ndata[1]);
			}
			$('#notification').fadeIn();
			setTimeout($('#notification').fadeOut(), 10000);
		},
		notImplemented:function() {
			OC.dialogs.alert(t('contacts', 'Sorry, this functionality has not been implemented yet'), t('contacts', 'Not implemented'));
		},
		searchOSM:function(obj) {
			var adr = Contacts.UI.propertyContainerFor(obj).find('.adr').val();
			if(adr == undefined) {
				OC.dialogs.alert(t('contacts', 'Couldn\'t get a valid address.'), t('contacts', 'Error'));
				return;
			}
			// FIXME: I suck at regexp. /Tanghus
			var adrarr = adr.split(';');
			var adrstr = '';
			if(adrarr[2].trim() != '') {
				adrstr = adrstr + adrarr[2].trim() + ',';
			}
			if(adrarr[3].trim() != '') {
				adrstr = adrstr + adrarr[3].trim() + ',';
			}
			if(adrarr[4].trim() != '') {
				adrstr = adrstr + adrarr[4].trim() + ',';
			}
			if(adrarr[5].trim() != '') {
				adrstr = adrstr + adrarr[5].trim() + ',';
			}
			if(adrarr[6].trim() != '') {
				adrstr = adrstr + adrarr[6].trim();
			}
			adrstr = encodeURIComponent(adrstr);
			var uri = 'http://open.mapquestapi.com/nominatim/v1/search.php?q=' + adrstr + '&limit=10&addressdetails=1&polygon=1&zoom=';
			var newWindow = window.open(uri,'_blank');
			newWindow.focus();
		},
		mailTo:function(obj) {
			var adr = Contacts.UI.propertyContainerFor($(obj)).find('input[type="email"]').val().trim();
			if(adr == '') {
				OC.dialogs.alert(t('contacts', 'Please enter an email address.'), t('contacts', 'Error'));
				return;
			}
			window.location.href='mailto:' + adr;
		},
		propertyContainerFor:function(obj) {
			return $(obj).parents('.propertycontainer').first();
		},
		checksumFor:function(obj) {
			return $(obj).parents('.propertycontainer').first().data('checksum');
		},
		propertyTypeFor:function(obj) {
			return $(obj).parents('.propertycontainer').first().data('element');
		},
		loading:function(obj, state) {
			if(state) {
				$(obj).addClass('loading');
			} else {
				$(obj).removeClass('loading');
			}
		},
		showCardDAVUrl:function(username, bookname){
			$('#carddav_url').val(totalurl + '/' + username + '/' + bookname);
			$('#carddav_url').show();
			$('#carddav_url_close').show();
		},
		loadListHandlers:function() {
			$('.propertylist li a.delete').unbind('click');
			$('.propertylist li a.delete').unbind('keydown');
			var deleteItem = function(obj) {
				obj.tipsy('hide');
				Contacts.UI.Card.deleteProperty(obj, 'list');
			}
			$('.propertylist li a.delete, .addresscard .delete').click(function() { deleteItem($(this)) });
			$('.propertylist li a.delete, .addresscard .delete').keydown(function() { deleteItem($(this)) });
			$('.propertylist li a.mail').click(function() { Contacts.UI.mailTo(this) });
			$('.propertylist li a.mail').keydown(function() { Contacts.UI.mailTo(this) });
			$('.addresscard .globe').click(function() { $(this).tipsy('hide');Contacts.UI.searchOSM(this); });
			$('.addresscard .globe').keydown(function() { $(this).tipsy('hide');Contacts.UI.searchOSM(this); });
			$('.addresscard .edit').click(function() { $(this).tipsy('hide');Contacts.UI.Card.editAddress(this, false); });
			$('.addresscard .edit').keydown(function() { $(this).tipsy('hide');Contacts.UI.Card.editAddress(this, false); });
			$('.addresscard,.propertylist li,.propertycontainer').hover(
				function () {
					$(this).find('.globe,.mail,.delete,.edit').animate({ opacity: 1.0 }, 200, function() {});
				}, 
				function () {
					$(this).find('.globe,.mail,.delete,.edit').animate({ opacity: 0.1 }, 200, function() {});
				}
			);
		},
		loadHandlers:function() {
			var deleteItem = function(obj) {
				obj.tipsy('hide');
				Contacts.UI.Card.deleteProperty(obj, 'single');
			}
			$('#identityprops a.delete').click( function() { deleteItem($(this)) });
			$('#identityprops a.delete').keydown( function() { deleteItem($(this)) });
			$('#categories_value a.edit').click( function() { $(this).tipsy('hide');OCCategories.edit(); } );
			$('#categories_value a.edit').keydown( function() { $(this).tipsy('hide');OCCategories.edit(); } );
			$('#fn_select').combobox({
				'id': 'fn',
				'name': 'value',
				'classes': ['contacts_property', 'nonempty', 'huge', 'tip', 'float'],
				'attributes': {'placeholder': t('contacts', 'Enter name')},
				'title': t('contacts', 'Format custom, Short name, Full name, Reverse or Reverse with comma')});
			$('#bday').datepicker({
						dateFormat : 'dd-mm-yy'
			});
			// Style phone types
			$('#phonelist').find('select.contacts_property').multiselect({
													noneSelectedText: t('contacts', 'Select type'),
													header: false,
													selectedList: 4,
													classes: 'typelist'
												});
			$('#edit_name').click(function(){Contacts.UI.Card.editName()});
			$('#edit_name').keydown(function(){Contacts.UI.Card.editName()});
			
			/* Initialize the photo edit dialog */
			$('#edit_photo_dialog').dialog({ autoOpen: false, modal: true, height: 'auto', width: 'auto' });
			$('#edit_photo_dialog' ).dialog( 'option', 'buttons', [
				{
					text: "Ok",
					click: function() { 
						Contacts.UI.Card.savePhoto(this);
						$(this).dialog('close');
					}
				},
				{
					text: "Cancel",
					click: function() { $(this).dialog('close'); }
				}
			] );
			
			/*$('#fn').blur(function(){
				if($('#fn').val() == '') {
					OC.dialogs.alert(t('contacts','The name field cannot be empty. Please enter a name for this contact.'), t('contacts','Name is empty'), function() { $('#fn').focus(); });
					$('#fn').focus();
					return false;
				}
			});*/
			
			// Name has changed. Update it and reorder.
			$('#fn').change(function(){
				var name = $('#fn').val().strip_tags();
				var item = $('#contacts [data-id="'+Contacts.UI.Card.id+'"]');
				$(item).find('a').html(name);
				Contacts.UI.Card.fn = name;
				var added = false;
				$('#contacts li').each(function(){
					if ($(this).text().toLowerCase() > name.toLowerCase()) {
						$(this).before(item).fadeIn('fast');
						added = true;
						return false;
					}
				});
				if(!added) {
					$('#leftcontent ul').append(item);
				}
				Contacts.UI.Contacts.scrollTo(Contacts.UI.Card.id);
			});

			$('#contacts_deletecard').click( function() { Contacts.UI.Card.doDelete();return false;} );
			$('#contacts_deletecard').keydown( function(event) { 
				if(event.which == 13) {
					Contacts.UI.Card.doDelete();
				}
				return false;
			});

			$('#contacts_downloadcard').click( function() { Contacts.UI.Card.doExport();return false;} );
			$('#contacts_downloadcard').keydown( function(event) { 
				if(event.which == 13) {
					Contacts.UI.Card.doExport();
				}
				return false;
			});

			// Profile picture upload handling
			// New profile picture selected
			$('#file_upload_start').change(function(){
				Contacts.UI.Card.uploadPhoto(this.files);
			});
			$('#contacts_details_photo_wrapper').bind('dragover',function(event){
				$(event.target).addClass('droppable');
				event.stopPropagation();
				event.preventDefault();  
			});
			$('#contacts_details_photo_wrapper').bind('dragleave',function(event){
				$(event.target).removeClass('droppable');
				//event.stopPropagation();
				//event.preventDefault();  
			});
			$('#contacts_details_photo_wrapper').bind('drop',function(event){
				event.stopPropagation();
				event.preventDefault();
				$(event.target).removeClass('droppable');
				$.fileUpload(event.originalEvent.dataTransfer.files);
			});

			$('#categories').multiple_autocomplete({source: categories});
			$('#contacts_deletecard').tipsy({gravity: 'ne'});
			$('#contacts_downloadcard').tipsy({gravity: 'ne'});
			$('#contacts_propertymenu_button').tipsy();
			$('#contacts_newcontact, #chooseaddressbook').tipsy({gravity: 'sw'});
		},
		Card:{
			id:'',
			fn:'',
			fullname:'',
			shortname:'',
			famname:'',
			givname:'',
			addname:'',
			honpre:'',
			honsuf:'',
			data:undefined,
			update:function(id) {
				var newid;
				if(!id) {
					newid = $('#contacts li:first-child').data('id');
				} else {
					newid = id;
				}
				var localLoadContact = function(id) {
					if($('#contacts li').length > 0) {
						$('#leftcontent li[data-id="'+newid+'"]').addClass('active');
						$.getJSON(OC.filePath('contacts', 'ajax', 'contactdetails.php'),{'id':newid},function(jsondata){
							if(jsondata.status == 'success'){
								Contacts.UI.Card.loadContact(jsondata.data);
							} else {
								OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
							}
						});
					}
				}
				
				// Make sure proper DOM is loaded.
				if(!$('#card')[0]) {
					$.getJSON(OC.filePath('contacts', 'ajax', 'loadcard.php'),{},function(jsondata){
						if(jsondata.status == 'success'){
							$('#rightcontent').html(jsondata.data.page).ready(function() {
								Contacts.UI.loadHandlers();
								localLoadContact(newid);
							});
						} else {
							OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
						}
					});
				}
				else if($('#contacts li').length == 0) {
					// load intro page
					$.getJSON(OC.filePath('contacts', 'ajax', 'loadintro.php'),{},function(jsondata){
						if(jsondata.status == 'success'){
							id = '';
							$('#rightcontent').data('id','');
							$('#rightcontent').html(jsondata.data.page);
						} else {
							OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
						}
					});
				}
				else {
					localLoadContact();
				}
			},
			doExport:function() {
				document.location.href = OC.linkTo('contacts', 'export.php') + '?contactid=' + this.id;
			},
			doImport:function(){
				Contacts.UI.notImplemented();
			},
			editNew:function(){ // add a new contact
				this.id = ''; this.bookid = '', this.fn = ''; this.fullname = ''; this.givname = ''; this.famname = ''; this.addname = ''; this.honpre = ''; this.honsuf = '';
				self = this;
				$.getJSON(OC.filePath('contacts', 'ajax', 'selectaddressbook.php'),{},function(jsondata) {
					if(jsondata.status == 'success') {
						if(jsondata.data.type == 'dialog') {
							// Load dialog to select addressbook.
							if($('#selectaddressbook_dialog').dialog('isOpen') == true) {
								$('#selectaddressbook_dialog').dialog('moveToTop');
							} else {
								$('#dialog_holder').html(jsondata.data.page).ready(function($) {
									$('#selectaddressbook_dialog').dialog({
										modal: true, height: 'auto', width: 'auto',
										buttons: {
											'Ok':function() {
												Contacts.UI.Card.add(';;;;;', '',$('#selectaddressbook_dialog').find('input:checked').val(), true);
												$(this).dialog('close');
											},
											'Cancel':function() { $(this).dialog('close'); }
										},
										close: function(event, ui) {
											$(this).dialog('destroy').remove();
										}
									});
								});
							}
						} else {
							Contacts.UI.Card.bookid = jsondata.data.id;
							Contacts.UI.Card.add(';;;;;', '',jsondata.data.id, true);
						}
					} else {
						OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
					}
				});
				return false;
			},
			add:function(n, fn, aid, isnew){ // add a new contact
				var localAddcontact = function(n, fn, aid, isnew) {
					$.post(OC.filePath('contacts', 'ajax', 'addcontact.php'), { n: n, fn: fn, aid: aid, isnew: isnew },
					function(jsondata) {
						if (jsondata.status == 'success'){
							$('#rightcontent').data('id',jsondata.data.id);
							var id = jsondata.data.id;
							$.getJSON(OC.filePath('contacts', 'ajax', 'contactdetails.php'),{'id':id},function(jsondata){
								if(jsondata.status == 'success'){
									Contacts.UI.Card.loadContact(jsondata.data);
									$('#leftcontent .active').removeClass('active');
									var item = $('<li role="button" data-id="'+jsondata.data.id+'" data-book-id="'+aid+'" class="active"><a href="index.php?id='+jsondata.data.id+'" style="background: url('+OC.filePath('contacts', '', 'thumbnail.php')+'?id='+jsondata.data.id+') no-repeat scroll 0% 0% transparent;">'+Contacts.UI.Card.fn+'</a></li>');
									var added = false;
									$('#leftcontent ul li').each(function(){
										if ($(this).text().toLowerCase() > Contacts.UI.Card.fn.toLowerCase()) {
											$(this).before(item).fadeIn('fast');
											added = true;
											return false;
										}
									});
									if(!added) {
										$('#leftcontent ul').append(item);
									}
									if(isnew) { // add some default properties
										Contacts.UI.Card.addProperty('EMAIL');
										Contacts.UI.Card.addProperty('TEL');
										$('#fn').focus();
									}
								}
								else{
									OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
								}
							});
							$('#contact_identity').show();
							$('#actionbar').show();
						}
						else{
							OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
						}
					});
				}
			
				var card = $('#card')[0];
				if(!card) {
					$.getJSON(OC.filePath('contacts', 'ajax', 'loadcard.php'),{},function(jsondata){
						if(jsondata.status == 'success'){
							$('#rightcontent').html(jsondata.data.page).ready(function() {
								Contacts.UI.loadHandlers();
								localAddcontact(n, fn, aid, isnew);
							});
						} else{
							OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
						}
					});
				} else {
					localAddcontact(n, fn, aid, isnew);
				}
			},
			doDelete:function() {
				$('#contacts_deletecard').tipsy('hide');
				OC.dialogs.confirm(t('contacts', 'Are you sure you want to delete this contact?'), t('contacts', 'Warning'), function(answer) {
					if(answer == true) {
						$.post(OC.filePath('contacts', 'ajax', 'deletecard.php'),{'id':Contacts.UI.Card.id},function(jsondata){
							if(jsondata.status == 'success'){
								var newid = '';
								var curlistitem = $('#leftcontent [data-id="'+jsondata.data.id+'"]');
								var newlistitem = curlistitem.prev();
								if(newlistitem == undefined) {
									newlistitem = curlistitem.next();
								}
								curlistitem.remove();
								if(newlistitem != undefined) {
									newid = newlistitem.data('id');
								}
								$('#rightcontent').data('id',newid);
								this.id = this.fn = this.fullname = this.shortname = this.famname = this.givname = this.addname = this.honpre = this.honsuf = '';
								this.data = undefined;
								
								if($('#contacts li').length > 0) { // Load first in list.
									Contacts.UI.Card.update(newid);
								} else {
									// load intro page
									$.getJSON(OC.filePath('contacts', 'ajax', 'loadintro.php'),{},function(jsondata){
										if(jsondata.status == 'success'){
											id = '';
											$('#rightcontent').data('id','');
											$('#rightcontent').html(jsondata.data.page);
										}
										else{
											OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
										}
									});
								}
							}
							else{
								OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
							}
						});
					}
				});
				return false;
			},
			loadContact:function(jsondata){
				this.data = jsondata;
				this.id = this.data.id;
				$('#rightcontent').data('id',this.id);
				this.populateNameFields();
				this.loadPhoto();
				this.loadMails();
				this.loadPhones();
				this.loadAddresses();
				this.loadSingleProperties();
				Contacts.UI.loadListHandlers();
				if(this.data.NOTE) {
					$('#note').data('checksum', this.data.NOTE[0]['checksum']);
					var note = $('#note').find('textarea');
					var txt = this.data.NOTE[0]['value'];
					var nheight = txt.split('\n').length > 4 ? txt.split('\n').length+2 : 5;
					note.css('min-height', nheight+'em');
					note.attr('rows', nheight);
					note.val(txt);
					$('#note').show();
					note.expandingTextarea();
					$('#contacts_propertymenu_dropdown a[data-type="NOTE"]').parent().hide();
				} else {
					$('#note').data('checksum', '');
					$('#note').find('textarea').val('');
					$('#note').hide();
					$('#contacts_propertymenu_dropdown a[data-type="NOTE"]').parent().show();
				}
			},
			loadSingleProperties:function() {
				var props = ['BDAY', 'NICKNAME', 'ORG', 'CATEGORIES'];
				// Clear all elements
				$('#ident .propertycontainer').each(function(){
					if(props.indexOf($(this).data('element')) > -1) {
						$(this).data('checksum', '');
						$(this).find('input').val('');
						$(this).hide();
						$(this).prev().hide();
					}
				});
				for(var prop in props) {
					if(this.data[props[prop]] != undefined) {
						$('#contacts_propertymenu_dropdown a[data-type="'+props[prop]+'"]').parent().hide();
						var property = this.data[props[prop]][0];
						var value = property['value'], checksum = property['checksum'];
						switch(props[prop]) {
							case 'BDAY':
								var val = $.datepicker.parseDate('yy-mm-dd', value.substring(0, 10));
								value = $.datepicker.formatDate('dd-mm-yy', val);
								$('#contact_identity').find('#bday').val(value);
								$('#contact_identity').find('#bday_value').data('checksum', checksum);
								$('#contact_identity').find('#bday_label').show();
								$('#contact_identity').find('#bday_value').show();
								break;
							case 'NICKNAME':
								$('#contact_identity').find('#nickname').val(value);
								$('#contact_identity').find('#nickname_value').data('checksum', checksum);
								$('#contact_identity').find('#nickname_label').show();
								$('#contact_identity').find('#nickname_value').show();
								break;
							case 'ORG':
								$('#contact_identity').find('#org').val(value);
								$('#contact_identity').find('#org_value').data('checksum', checksum);
								$('#contact_identity').find('#org_label').show();
								$('#contact_identity').find('#org_value').show();
								break;
							case 'CATEGORIES':
								$('#contact_identity').find('#categories').val(value);
								$('#contact_identity').find('#categories_value').data('checksum', checksum);
								$('#contact_identity').find('#categories_label').show();
								$('#contact_identity').find('#categories_value').show();
								break;
						}
					} else {
						$('#contacts_propertymenu_dropdown a[data-type="'+props[prop]+'"]').parent().show();
					}
				}
			},
			populateNameFields:function() {
				var props = ['FN', 'N'];
				// Clear all elements
				$('#ident .propertycontainer').each(function(){
					if(props.indexOf($(this).data('element')) > -1) {
						$(this).data('checksum', '');
						$(this).find('input').val('');
					}
				});
				this.fn = ''; this.fullname = ''; this.givname = ''; this.famname = ''; this.addname = ''; this.honpre = ''; this.honsuf = '';
				var narray = undefined;
				if(this.data.FN) {
					this.fn = this.data.FN[0]['value'];
				}
				else {
					this.fn = '';
				}
				if(this.data.N == undefined) {
					narray = [this.fn,'','','','']; // Checking for non-existing 'N' property :-P
				} else {
					narray = this.data.N[0]['value'];
				}
				this.famname = narray[0] || '';
				this.givname = narray[1] || '';
				this.addname = narray[2] || '';
				this.honpre = narray[3] || '';
				this.honsuf = narray[4] || '';
				if(this.honpre.length > 0) {
					this.fullname += this.honpre + ' ';
				}
				if(this.givname.length > 0) {
					this.fullname += ' ' + this.givname;
				}
				if(this.addname.length > 0) {
					this.fullname += ' ' + this.addname;
				}
				if(this.famname.length > 0) {
					this.fullname += ' ' + this.famname;
				}
				if(this.honsuf.length > 0) {
					this.fullname += ', ' + this.honsuf;
				}
				$('#n').val(narray.join(';'));
				$('#fn_select option').remove();
				var names = [this.fn, this.fullname, this.givname + ' ' + this.famname, this.famname + ' ' + this.givname, this.famname + ', ' + this.givname];
				if(this.data.ORG) {
					names[names.length]=this.data.ORG[0].value;
				}
				$.each(names, function(key, value) {
					$('#fn_select')
						.append($('<option></option>')
						.text(value)); 
				});
				$('#fn_select').combobox('value', this.fn);
				$('#contact_identity').find('*[data-element="N"]').data('checksum', this.data.N[0]['checksum']);
				if(this.data.FN) {
					$('#contact_identity').find('*[data-element="FN"]').data('checksum', this.data.FN[0]['checksum']);
				}
				$('#contact_identity').show();
			},
			hasCategory:function(category) {
				if(this.data.CATEGORIES) {
					var categories = this.data.CATEGORIES[0]['value'].split(/,\s*/);
					for(var c in categories) {
						var cat = this.data.CATEGORIES[0]['value'][c];
						if(typeof cat === 'string' && (cat.toUpperCase() === category.toUpperCase())) {
							return true;
						}
					}
				}
				return false;
			},
			categoriesChanged:function(newcategories) { // Categories added/deleted.
				categories = $.map(newcategories, function(v) {return v;});
				$('#categories').multiple_autocomplete('option', 'source', categories);
				var categorylist = $('#categories_value').find('input');
				$.getJSON(OC.filePath('contacts', 'ajax', 'categories/categoriesfor.php'),{'id':Contacts.UI.Card.id},function(jsondata){
					if(jsondata.status == 'success'){
						$('#categories_value').data('checksum', jsondata.data.checksum);
						categorylist.val(jsondata.data.value);
					} else {
						OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
					}
				});
			},
			savePropertyInternal:function(name, fields, oldchecksum, checksum){
				// TODO: Add functionality for new fields.
				//console.log('savePropertyInternal: ' + name + ', fields: ' + fields + 'checksum: ' + checksum);
				//console.log('savePropertyInternal: ' + this.data[name]);
				var multivalue = ['CATEGORIES'];
				var params = {};
				var value = multivalue.indexOf(name) != -1 ? new Array() : undefined;
				jQuery.each(fields, function(i, field){
					//.substring(11,'parameters[TYPE][]'.indexOf(']'))
					if(field.name.substring(0, 5) === 'value') {
						if(multivalue.indexOf(name) != -1) {
							value.push(field.value);
						} else {
							value = field.value;
						}
					} else if(field.name.substring(0, 10) === 'parameters') {
						var p = field.name.substring(11,'parameters[TYPE][]'.indexOf(']'));
						if(!(p in params)) {
							params[p] = [];
						}
						params[p].push(field.value);
					}
				});
				for(var i in this.data[name]) {
					if(this.data[name][i]['checksum'] == oldchecksum) {
						this.data[name][i]['checksum'] = checksum;
						this.data[name][i]['value'] = value;
						this.data[name][i]['parameters'] = params;
					}
				}
			},
			saveProperty:function(obj){
				if(!$(obj).hasClass('contacts_property')) {
					return false;
				}
				if($(obj).hasClass('nonempty') && $(obj).val().trim() == '') {
					OC.dialogs.alert(t('contacts', 'This property has to be non-empty.'), t('contacts', 'Error'));
					return false;
				}
				container = $(obj).parents('.propertycontainer').first(); // get the parent holding the metadata.
				Contacts.UI.loading(obj, true);
				var checksum = container.data('checksum');
				var name = container.data('element');
				var fields = container.find('input.contacts_property,select.contacts_property').serializeArray();
				switch(name) {
					case 'FN':
						var nempty = true;
						for(var i in Contacts.UI.Card.data.N[0]['value']) {
							if(Contacts.UI.Card.data.N[0]['value'][i] != '') {
								nempty = false;
								break;
							}
						}
						if(nempty) {
							$('#n').val(fields[0].value + ';;;;');
							Contacts.UI.Card.data.N[0]['value'] = Array(fields[0].value, '', '', '', '');
							setTimeout(function() {Contacts.UI.Card.saveProperty($('#n'))}, 500);
						}
						break;
				}
				var q = container.find('input.contacts_property,select.contacts_property,textarea.contacts_property').serialize();
				if(q == '' || q == undefined) {
					OC.dialogs.alert(t('contacts', 'Couldn\'t serialize elements.'), t('contacts', 'Error'));
					Contacts.UI.loading(obj, false);
					return false;
				}
				q = q + '&id=' + this.id + '&name=' + name;
				if(checksum != undefined && checksum != '') { // save
					q = q + '&checksum=' + checksum;
					//console.log('Saving: ' + q);
					$(obj).attr('disabled', 'disabled');
					$.post(OC.filePath('contacts', 'ajax', 'saveproperty.php'),q,function(jsondata){
						if(jsondata.status == 'success'){
							container.data('checksum', jsondata.data.checksum);
							Contacts.UI.Card.savePropertyInternal(name, fields, checksum, jsondata.data.checksum);
							Contacts.UI.loading(obj, false);
							$(obj).removeAttr('disabled');
							return true;
						}
						else{
							OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
							Contacts.UI.loading(obj, false);
							$(obj).removeAttr('disabled');
							return false;
						}
					},'json');
				} else { // add
					//console.log('Adding: ' + q);
					$(obj).attr('disabled', 'disabled');
					$.post(OC.filePath('contacts', 'ajax', 'addproperty.php'),q,function(jsondata){
						if(jsondata.status == 'success'){
							container.data('checksum', jsondata.data.checksum);
							// TODO: savePropertyInternal doesn't know about new fields
							//Contacts.UI.Card.savePropertyInternal(name, fields, checksum, jsondata.data.checksum);
							Contacts.UI.loading(obj, false);
							$(obj).removeAttr('disabled');
							return true;
						}
						else{
							OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
							Contacts.UI.loading(obj, false);
							$(obj).removeAttr('disabled');
							return false;
						}
					},'json');
				}
			},
			addProperty:function(type){
				switch (type) {
					case 'PHOTO':
						this.loadPhoto(true);
						$('#file_upload_form').show();
						$('#contacts_propertymenu_dropdown a[data-type="'+type+'"]').parent().hide();
						$('#file_upload_start').trigger('click');
						break;
					case 'NOTE':
						$('#note').show();
						$('#contacts_propertymenu_dropdown a[data-type="'+type+'"]').parent().hide();
						$('#note').find('textarea').expandingTextarea();
						$('#note').find('textarea').focus();
						break;
					case 'EMAIL':
						if($('#emaillist>li').length == 1) {
							$('#emails').show();
						}
						Contacts.UI.Card.addMail();
						break;
					case 'TEL':
						if($('#phonelist>li').length == 1) {
							$('#phones').show();
						}
						Contacts.UI.Card.addPhone();
						break;
					case 'ADR':
						if($('#addressdisplay>dl').length == 1) {
							$('#addresses').show();
						}
						Contacts.UI.Card.editAddress('new', true);
						break;
					case 'NICKNAME':
					case 'ORG':
					case 'BDAY':
					case 'CATEGORIES':
						$('dl dt[data-element="'+type+'"],dd[data-element="'+type+'"]').show();
						$('dd[data-element="'+type+'"]').find('input').focus();
						$('#contacts_propertymenu_dropdown a[data-type="'+type+'"]').parent().hide();
						break;
				}
			},
			deleteProperty:function(obj, type){
				Contacts.UI.loading(obj, true);
				var checksum = Contacts.UI.checksumFor(obj);
				if(checksum) {
					$.post(OC.filePath('contacts', 'ajax', 'deleteproperty.php'),{'id': this.id, 'checksum': checksum },function(jsondata){
						if(jsondata.status == 'success'){
							if(type == 'list') {
								Contacts.UI.propertyContainerFor(obj).remove();
							} else if(type == 'single') {
								var proptype = Contacts.UI.propertyTypeFor(obj);
								Contacts.UI.Card.data[proptype] = null;
								var othertypes = ['NOTE', 'PHOTO'];
								if(othertypes.indexOf(proptype) != -1) {
									Contacts.UI.propertyContainerFor(obj).data('checksum', '');
									if(proptype == 'PHOTO') {
										Contacts.UI.Contacts.refreshThumbnail(Contacts.UI.Card.id);
										Contacts.UI.Card.loadPhoto(true);
									} else if(proptype == 'NOTE') {
										$('#note').find('textarea').val('');
										Contacts.UI.propertyContainerFor(obj).hide();
									}
								} else {
									$('dl dt[data-element="'+proptype+'"],dd[data-element="'+proptype+'"]').hide();
									$('dl dd[data-element="'+proptype+'"]').data('checksum', '');
									$('dl dd[data-element="'+proptype+'"]').find('input').val('');
								}
								$('#contacts_propertymenu_dropdown a[data-type="'+proptype+'"]').parent().show();
								Contacts.UI.loading(obj, false);
							} else {
								OC.dialogs.alert(t('contacts', '\'deleteProperty\' called without type argument. Please report at bugs.owncloud.org'), t('contacts', 'Error'));
								Contacts.UI.loading(obj, false);
							}
						}
						else{
							Contacts.UI.loading(obj, false);
							OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
						}
					});
				} else { // Property hasn't been saved so there's nothing to delete.
					if(type == 'list') {
						Contacts.UI.propertyContainerFor(obj).remove();
					} else if(type == 'single') {
						var proptype = Contacts.UI.propertyTypeFor(obj);
						$('dl dt[data-element="'+proptype+'"],dd[data-element="'+proptype+'"]').hide();
						$('#contacts_propertymenu_dropdown a[data-type="'+proptype+'"]').parent().show();
						Contacts.UI.loading(obj, false);
					} else {
						OC.dialogs.alert(t('contacts', '\'deleteProperty\' called without type argument. Please report at bugs.owncloud.org'), t('contacts', 'Error'));
					}
				}
			},
			editName:function(){
				var isnew = (this.id == '');
				/* Initialize the name edit dialog */
				if($('#edit_name_dialog').dialog('isOpen') == true){
					$('#edit_name_dialog').dialog('moveToTop');
				}else{
					$('#dialog_holder').load(OC.filePath('contacts', 'ajax', 'editname.php')+'?id='+this.id, function(jsondata){
						if(jsondata.status != 'error'){
							$('#edit_name_dialog' ).dialog({
								modal: true,
								closeOnEscape: (isnew == '' && false || true),
								title:  (isnew && t('contacts', 'Add contact') || t('contacts', 'Edit name')),
								height: 'auto', width: 'auto',
								buttons: {
									'Ok':function() { 
										Contacts.UI.Card.saveName(this);
										$(this).dialog('destroy').remove();
									},
									'Cancel':function() { $(this).dialog('destroy').remove(); }
								},
								close: function(event, ui) {
									$(this).dialog('destroy').remove();
									//return event;
								},
								open: function(event, ui) {
									// load 'N' property - maybe :-P
								}
							});
						} else {
							OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
						}
					});
				}
			},
			saveName:function(dlg){
				//console.log('saveName, id: ' + this.id);
				var n = new Array($(dlg).find('#fam').val().strip_tags(),$(dlg).find('#giv').val().strip_tags(),$(dlg).find('#add').val().strip_tags(),$(dlg).find('#pre').val().strip_tags(),$(dlg).find('#suf').val().strip_tags());
				this.famname = n[0];
				this.givname = n[1];
				this.addname = n[2];
				this.honpre = n[3];
				this.honsuf = n[4];
				this.fullname = '';

				$('#n').val(n.join(';'));
				if(n[3].length > 0) {
					this.fullname = n[3] + ' ';
				}
				this.fullname += n[1] + ' ' + n[2] + ' ' + n[0];
				if(n[4].length > 0) {
					this.fullname += ', ' + n[4];
				}

				$('#fn_select option').remove();
				//$('#fn_select').combobox('value', this.fn);
				var tmp = [this.fullname, this.givname + ' ' + this.famname, this.famname + ' ' + this.givname, this.famname + ', ' + this.givname];
				var names = new Array();
				for(var name in tmp) {
					if(names.indexOf(tmp[name]) == -1) {
						names.push(tmp[name]);
					}
				}
				$.each(names, function(key, value) {
					$('#fn_select')
						.append($('<option></option>')
						.text(value)); 
				});
				
				if(this.id == '') {
					var aid = $(dlg).find('#aid').val();
					Contacts.UI.Card.add(n.join(';'), $('#short').text(), aid);
				} else {
					Contacts.UI.Card.saveProperty($('#n'));
				}
			},
			loadAddresses:function(){
				$('#addresses').hide();
				$('#addressdisplay dl.propertycontainer').remove();
				for(var adr in this.data.ADR) {
					$('#addressdisplay dl').first().clone().insertAfter($('#addressdisplay dl').last()).show();
					$('#addressdisplay dl').last().removeClass('template').addClass('propertycontainer');
					$('#addressdisplay dl').last().data('checksum', this.data.ADR[adr]['checksum']);
					var adrarray = this.data.ADR[adr]['value'];
					var adrtxt = '';
					if(adrarray[0] && adrarray[0].length > 0) {
						adrtxt = adrtxt + '<li>' + adrarray[0].strip_tags() + '</li>';
					}
					if(adrarray[1] && adrarray[1].length > 0) {
						adrtxt = adrtxt + '<li>' + adrarray[1].strip_tags() + '</li>';
					}
					if(adrarray[2] && adrarray[2].length > 0) {
						adrtxt = adrtxt + '<li>' + adrarray[2].strip_tags() + '</li>';
					}
					if((adrarray[3] && adrarray[5]) && adrarray[3].length > 0 || adrarray[5].length > 0) {
						adrtxt = adrtxt + '<li>' + adrarray[5].strip_tags() + ' ' + adrarray[3].strip_tags() + '</li>';
					}
					if(adrarray[4] && adrarray[4].length > 0) {
						adrtxt = adrtxt + '<li>' + adrarray[4].strip_tags() + '</li>';
					}
					if(adrarray[6] && adrarray[6].length > 0) {
						adrtxt = adrtxt + '<li>' + adrarray[6].strip_tags() + '</li>';
					}
					$('#addressdisplay dl').last().find('.addresslist').html(adrtxt);
					var types = new Array();
					var ttypes = new Array();
					for(var param in this.data.ADR[adr]['parameters']) {
						if(param.toUpperCase() == 'TYPE') {
							types.push(t('contacts', ucwords(this.data.ADR[adr]['parameters'][param].toLowerCase())));
							ttypes.push(this.data.ADR[adr]['parameters'][param]);
						}
					}
					$('#addressdisplay dl').last().find('.adr_type_label').text(types.join('/'));
					$('#addressdisplay dl').last().find('.adr_type').val(ttypes.join(','));
					$('#addressdisplay dl').last().find('.adr').val(adrarray.join(';'));
					$('#addressdisplay dl').last().data('checksum', this.data.ADR[adr]['checksum']);
				}
				if($('#addressdisplay dl').length > 1) {
					$('#addresses').show();
					$('#contact_communication').show();
				}
				return false;
			},
			editAddress:function(obj, isnew){
				var container = undefined;
				var q = q = '?id=' + this.id;
				if(obj === 'new') {
					isnew = true;
					$('#addressdisplay dl').first().clone(true).insertAfter($('#addressdisplay dl').last()).show();
					container = $('#addressdisplay dl').last();
					container.removeClass('template').addClass('propertycontainer');
				} else {
					q = q + '&checksum='+Contacts.UI.checksumFor(obj);
				}
				/* Initialize the address edit dialog */
				if($('#edit_address_dialog').dialog('isOpen') == true){
					$('#edit_address_dialog').dialog('moveToTop');
				}else{
					$('#dialog_holder').load(OC.filePath('contacts', 'ajax', 'editaddress.php')+q, function(jsondata){
						if(jsondata.status != 'error'){
							$('#edit_address_dialog' ).dialog({
								/*modal: true,*/
								height: 'auto', width: 'auto',
								buttons: {
									'Ok':function() {
										if(isnew) {
											Contacts.UI.Card.saveAddress(this, $('#addressdisplay dl:last-child').find('input').first(), isnew);
										} else {
											Contacts.UI.Card.saveAddress(this, obj, isnew);
										}
										$(this).dialog('destroy').remove();
									},
									'Cancel':function() {
										$(this).dialog('destroy').remove();
										if(isnew) {
											container.remove();
										}
									}
								},
								close : function(event, ui) {
									$(this).dialog('destroy').remove();
									if(isnew) {
										container.remove();
									}
								},
								open : function(event, ui) {
									$( "#adr_city" ).autocomplete({
										source: function( request, response ) {
											$.ajax({
												url: "http://ws.geonames.org/searchJSON",
												dataType: "jsonp",
												data: {
													featureClass: "P",
													style: "full",
													maxRows: 12,
													lang: lang,
													name_startsWith: request.term
												},
												success: function( data ) {
													response( $.map( data.geonames, function( item ) {
														return {
															label: item.name + (item.adminName1 ? ", " + item.adminName1 : "") + ", " + item.countryName,
															value: item.name,
															country: item.countryName
														}
													}));
												}
											});
										},
										minLength: 2,
										select: function( event, ui ) {
											if(ui.item && $('#adr_country').val().trim().length == 0) {
												$('#adr_country').val(ui.item.country);
											}
										},
										open: function() {
											$( this ).removeClass( "ui-corner-all" ).addClass( "ui-corner-top" );
										},
										close: function() {
											$( this ).removeClass( "ui-corner-top" ).addClass( "ui-corner-all" );
										}
									});
									$( "#adr_country" ).autocomplete({
										source: function( request, response ) {
											$.ajax({
												url: "http://ws.geonames.org/searchJSON",
												dataType: "jsonp",
												data: {
													/*featureClass: "A",*/
													featureCode: "PCLI",
													/*countryBias: "true",*/
													/*style: "full",*/
													lang: lang,
													maxRows: 12,
													name_startsWith: request.term
												},
												success: function( data ) {
													response( $.map( data.geonames, function( item ) {
														return {
															label: item.name,
															value: item.name
														}
													}));
												}
											});
										},
										minLength: 2,
										select: function( event, ui ) {
											/*if(ui.item) {
												$('#adr_country').val(ui.item.country);
											}
											log( ui.item ?
												"Selected: " + ui.item.label :
												"Nothing selected, input was " + this.value);*/
										},
										open: function() {
											$( this ).removeClass( "ui-corner-all" ).addClass( "ui-corner-top" );
										},
										close: function() {
											$( this ).removeClass( "ui-corner-top" ).addClass( "ui-corner-all" );
										}
									});
								}
							});
						} else {
							alert(jsondata.data.message);
						}
					});
				}
			},
			saveAddress:function(dlg, obj, isnew){
				if(isnew) {
					container = $('#addressdisplay dl').last();
					obj = $('#addressdisplay dl:last-child').find('input').first();
				} else {
					checksum = Contacts.UI.checksumFor(obj);
					container = Contacts.UI.propertyContainerFor(obj);
				}
				var adr = new Array($(dlg).find('#adr_pobox').val().strip_tags(),$(dlg).find('#adr_extended').val().strip_tags(),$(dlg).find('#adr_street').val().strip_tags(),$(dlg).find('#adr_city').val().strip_tags(),$(dlg).find('#adr_region').val().strip_tags(),$(dlg).find('#adr_zipcode').val().strip_tags(),$(dlg).find('#adr_country').val().strip_tags());
				$(container).find('.adr').val(adr.join(';'));
				$(container).find('.adr_type').val($(dlg).find('#adr_type').val());
				$(container).find('.adr_type_label').html(t('contacts',ucwords($(dlg).find('#adr_type').val().toLowerCase())));
				Contacts.UI.Card.saveProperty($(container).find('input').first());
				var adrtxt = '';
				if(adr[0].length > 0) {
					adrtxt = adrtxt + '<li>' + adr[0] + '</li>';
				}
				if(adr[1].length > 0) {
					adrtxt = adrtxt + '<li>' + adr[1] + '</li>';
				}
				if(adr[2].length > 0) {
					adrtxt = adrtxt + '<li>' + adr[2] + '</li>';
				}
				if(adr[3].length > 0 || adr[5].length > 0) {
					adrtxt = adrtxt + '<li>' + adr[5] + ' ' + adr[3] + '</li>';
				}
				if(adr[4].length > 0) {
					adrtxt = adrtxt + '<li>' + adr[4] + '</li>';
				}
				if(adr[6].length > 0) {
					adrtxt = adrtxt + '<li>' + adr[6] + '</li>';
				}
				$(container).find('.addresslist').html(adrtxt);
			},
			uploadPhoto:function(filelist) {
				if(!filelist) {
					OC.dialogs.alert(t('contacts','No files selected for upload.'), t('contacts', 'Error'));
					return;
				}
				var file = filelist[0];
				var target = $('#file_upload_target');
				var form = $('#file_upload_form');
				var totalSize=0;
				if(file.size > $('#max_upload').val()){
					OC.dialogs.alert(t('contacts','The file you are trying to upload exceed the maximum size for file uploads on this server.'), t('contacts', 'Error'));
					return;
				} else {
					target.load(function(){
						var response=jQuery.parseJSON(target.contents().text());
						if(response != undefined && response.status == 'success'){
							Contacts.UI.Card.editPhoto(response.data.id, response.data.tmp);
							//alert('File: ' + file.tmp + ' ' + file.name + ' ' + file.mime);
						}else{
							OC.dialogs.alert(response.data.message, t('contacts', 'Error'));
						}
					});
					form.submit();
				}
			},
			loadPhotoHandlers:function(){
				$('#phototools li a').tipsy('hide');
				$('#phototools li a').tipsy();
				$('#phototools li a').click(function() {
					$(this).tipsy('hide');
				});
				$('#contacts_details_photo_wrapper').hover(
					function () {
						$('#phototools').slideDown(200);
					},
					function () {
						$('#phototools').slideUp(200);
					}
				);
				$('#phototools').hover(
					function () {
						$(this).removeClass('transparent');
					},
					function () {
						$(this).addClass('transparent');
					}
				);
				if(this.data.PHOTO) {
					$('#phototools .delete').click(function() {
						$(this).tipsy('hide');
						Contacts.UI.Card.deleteProperty($('#contacts_details_photo'), 'single');
						$(this).hide();
					});
					$('#phototools .edit').click(function() {
						$(this).tipsy('hide');
						Contacts.UI.Card.editCurrentPhoto();
					});
				} else {
					$('#phototools .delete').hide();
					$('#phototools .edit').hide();
				}
				$('#phototools .upload').click(function() {
					$('#file_upload_start').trigger('click');
				});
				$('#phototools .cloud').click(function() {
					OC.dialogs.filepicker(t('contacts', 'Select photo'), Contacts.UI.Card.cloudPhotoSelected, false, 'image', true);
				});
			},
			cloudPhotoSelected:function(path){
				$.getJSON(OC.filePath('contacts', 'ajax', 'oc_photo.php'),{'path':path,'id':Contacts.UI.Card.id},function(jsondata){
					if(jsondata.status == 'success'){
						//alert(jsondata.data.page);
						Contacts.UI.Card.editPhoto(jsondata.data.id, jsondata.data.tmp)
						$('#edit_photo_dialog_img').html(jsondata.data.page);
					}
					else{
						OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
					}
				});
			},
			loadPhoto:function(refresh){
				$('#phototools li a').tipsy('hide');
				$.getJSON(OC.filePath('contacts', 'ajax', 'loadphoto.php'),{'id':this.id, 'refresh': refresh},function(jsondata){
					if(jsondata.status == 'success'){
						$('#contacts_details_photo_wrapper').data('checksum', jsondata.data.checksum);
						$('#contacts_details_photo_wrapper').html(jsondata.data.page);
						Contacts.UI.Card.loadPhotoHandlers();
					}
					else{
						OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
					}
				});
				$('#file_upload_form').show();
				$('#contacts_propertymenu_dropdown a[data-type="PHOTO"]').parent().hide();
			},
			editCurrentPhoto:function(){
				$.getJSON(OC.filePath('contacts', 'ajax', 'currentphoto.php'),{'id':this.id},function(jsondata){
					if(jsondata.status == 'success'){
						//alert(jsondata.data.page);
						Contacts.UI.Card.editPhoto(jsondata.data.id, jsondata.data.tmp)
						$('#edit_photo_dialog_img').html(jsondata.data.page);
					}
					else{
						OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
					}
				});
			},
			editPhoto:function(id, tmp_path){
				//alert('editPhoto: ' + tmp_path);
				$.getJSON(OC.filePath('contacts', 'ajax', 'cropphoto.php'),{'tmp_path':tmp_path,'id':this.id,'requesttoken':requesttoken},function(jsondata){
					if(jsondata.status == 'success'){
						//alert(jsondata.data.page);
						$('#edit_photo_dialog_img').html(jsondata.data.page);
					}
					else{
						OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
					}
				});
				if($('#edit_photo_dialog').dialog('isOpen') == true){
					$('#edit_photo_dialog').dialog('moveToTop');
				} else {
					$('#edit_photo_dialog').dialog('open');
				}
			},
			savePhoto:function(){
				var target = $('#crop_target');
				var form = $('#cropform');
				form.submit();
				target.load(function(){
					var response=jQuery.parseJSON(target.contents().text());
					if(response != undefined && response.status == 'success'){
						// load cropped photo.
						$('#contacts_details_photo_wrapper').html(response.data.page);
						Contacts.UI.Card.data.PHOTO = true;
						Contacts.UI.Card.loadPhotoHandlers();
					}else{
						OC.dialogs.alert(response.data.message, t('contacts', 'Error'));
					}
				});
				Contacts.UI.Contacts.refreshThumbnail(this.id);
			},
			addMail:function() {
				//alert('addMail');
				$('#emaillist li.template:first-child').clone(true).appendTo($('#emaillist')).show().find('a .tip').tipsy();
				$('#emaillist li.template:last-child').find('select').addClass('contacts_property');
				$('#emaillist li.template:last-child').removeClass('template').addClass('propertycontainer');
				$('#emaillist li:last-child').find('input[type="email"]').focus();
				return false;
			},
			loadMails:function() {
				$('#emails').hide();
				$('#emaillist li.propertycontainer').remove();
				for(var mail in this.data.EMAIL) {
					this.addMail();
					//$('#emaillist li:first-child').clone().appendTo($('#emaillist')).show();
					$('#emaillist li:last-child').data('checksum', this.data.EMAIL[mail]['checksum'])
					$('#emaillist li:last-child').find('input[type="email"]').val(this.data.EMAIL[mail]['value']);
					for(var param in this.data.EMAIL[mail]['parameters']) {
						if(param.toUpperCase() == 'PREF') {
							$('#emaillist li:last-child').find('input[type="checkbox"]').attr('checked', 'checked')
						}
						else if(param.toUpperCase() == 'TYPE') {
							for(etype in this.data.EMAIL[mail]['parameters'][param]) {
								var et = this.data.EMAIL[mail]['parameters'][param][etype];
								$('#emaillist li:last-child').find('select option').each(function(){
									if($.inArray($(this).val().toUpperCase(), et.toUpperCase().split(',')) > -1) {
										$(this).attr('selected', 'selected');
									}
								});
							}
						}
					}
				}
				if($('#emaillist li').length > 1) {
					$('#emails').show();
					$('#contact_communication').show();
				}

				$('#emaillist li:last-child').find('input[type="text"]').focus();
				return false;
			},
			addPhone:function() {
				$('#phonelist li.template:first-child').clone(true).appendTo($('#phonelist')); //.show();
				$('#phonelist li.template:last-child').find('select').addClass('contacts_property');
				$('#phonelist li.template:last-child').removeClass('template').addClass('propertycontainer');
				$('#phonelist li:last-child').find('input[type="text"]').focus();
				$('#phonelist li:last-child').find('select').multiselect({
														noneSelectedText: t('contacts', 'Select type'),
														header: false,
														selectedList: 4,
														classes: 'typelist'
													});
				$('#phonelist li:last-child').show();
				return false;
			},
			loadPhones:function() {
				$('#phones').hide();
				$('#phonelist li.propertycontainer').remove();
				for(var phone in this.data.TEL) {
					this.addPhone();
					$('#phonelist li:last-child').find('select').multiselect('destroy');
					$('#phonelist li:last-child').data('checksum', this.data.TEL[phone]['checksum'])
					$('#phonelist li:last-child').find('input[type="text"]').val(this.data.TEL[phone]['value']);
					for(var param in this.data.TEL[phone]['parameters']) {
						if(param.toUpperCase() == 'PREF') {
							$('#phonelist li:last-child').find('input[type="checkbox"]').attr('checked', 'checked');
						}
						else if(param.toUpperCase() == 'TYPE') {
							for(ptype in this.data.TEL[phone]['parameters'][param]) {
								var pt = this.data.TEL[phone]['parameters'][param][ptype];
								$('#phonelist li:last-child').find('select option').each(function(){
									//if ($(this).val().toUpperCase() == pt.toUpperCase()) {
									if ($.inArray($(this).val().toUpperCase(), pt.toUpperCase().split(',')) > -1) {
										$(this).attr('selected', 'selected');
									}
								});
							}
						}
					}
					$('#phonelist li:last-child').find('select').multiselect({
														noneSelectedText: t('contacts', 'Select type'),
														header: false,
														selectedList: 4,
														classes: 'typelist'
													});
				}
				if($('#phonelist li').length > 1) {
					$('#phones').show();
					$('#contact_communication').show();
				}
				return false;
			},
		},
		Addressbooks:{
			droptarget:undefined,
			droptext:t('contacts', 'Drop a VCF file to import contacts.'),
			overview:function(){
				if($('#chooseaddressbook_dialog').dialog('isOpen') == true){
					$('#chooseaddressbook_dialog').dialog('moveToTop');
				}else{
					$('#dialog_holder').load(OC.filePath('contacts', 'ajax', 'chooseaddressbook.php'), function(jsondata){
						if(jsondata.status != 'error'){
							$('#chooseaddressbook_dialog').dialog({
								minWidth: 600,
								close: function(event, ui) {
									$(this).dialog('destroy').remove();
								}
							}).css('overflow','visible');
						} else {
							alert(jsondata.data.message);
						}
					});
				}
				return false;
			},
			activation:function(checkbox, bookid)
			{
				$.post(OC.filePath('contacts', 'ajax', 'activation.php'), { bookid: bookid, active: checkbox.checked?1:0 },
				  function(data) {
					if (data.status == 'success'){
						checkbox.checked = data.active == 1;
						Contacts.UI.Contacts.update();
					}
				  });
			},
			newAddressbook:function(object){
				var tr = $(document.createElement('tr'))
					.load(OC.filePath('contacts', 'ajax', 'addbook.php'));
				$(object).closest('tr').after(tr).hide();
			},
			editAddressbook:function(object, bookid){
				var tr = $(document.createElement('tr'))
					.load(OC.filePath('contacts', 'ajax', 'editaddressbook.php') + "?bookid="+bookid);
				$(object).closest('tr').after(tr).hide();
			},
			deleteAddressbook:function(obj, bookid){
				var check = confirm("Do you really want to delete this address book?");
				if(check == false){
					return false;
				}else{
					$.post(OC.filePath('contacts', 'ajax', 'deletebook.php'), { id: bookid},
					  function(jsondata) {
						if (jsondata.status == 'success'){
							$(obj).closest('tr').remove();
							Contacts.UI.Contacts.update();
						} else {
							OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
						}
					  });
				}
			},
			loadImportHandlers:function() {
				$('#import_upload_start').change(function(){
					Contacts.UI.Addressbooks.uploadImport(this.files);
				});
				$('#importaddressbook_dialog').find('.upload').click(function() {
					Contacts.UI.Addressbooks.droptarget.html(t('contacts', 'Uploading...'));
					Contacts.UI.loading(Contacts.UI.Addressbooks.droptarget, true);
					//$('#import_upload_start').trigger('click');
					//return false;
				});
				$('#importaddressbook_dialog').find('.upload').tipsy();
				this.droptarget = $('#import_drop_target');
				$(this.droptarget).bind('dragover',function(event){
					$(event.target).addClass('droppable');
					event.stopPropagation();
					event.preventDefault();  
				});
				$(this.droptarget).bind('dragleave',function(event){
					$(event.target).removeClass('droppable');
				});
				$(this.droptarget).bind('drop',function(event){
					event.stopPropagation();
					event.preventDefault();
					$(event.target).removeClass('droppable');
					$(event.target).html(t('contacts', 'Uploading...'));
					Contacts.UI.loading(event.target, true);
					$.importUpload(event.originalEvent.dataTransfer.files);
				});

				$.importUpload = function(files){
					var file = files[0];
					if(file.size > $('#max_upload').val()){
						OC.dialogs.alert(t('contacts','The file you are trying to upload exceed the maximum size for file uploads on this server.'), t('contacts','Upload too large'));
						$(Contacts.UI.Addressbooks.droptarget).html(Contacts.UI.Addressbooks.droptext);
						Contacts.UI.loading(Contacts.UI.Addressbooks.droptarget, false);
						return;
					}
					if(file.type.indexOf('text') != 0) {
						OC.dialogs.alert(t('contacts','You have dropped a file type that cannot be imported: ') + file.type, t('contacts','Wrong file type'));
						$(Contacts.UI.Addressbooks.droptarget).html(Contacts.UI.Addressbooks.droptext);
						Contacts.UI.loading(Contacts.UI.Addressbooks.droptarget, false);
						return;
					}
					var xhr = new XMLHttpRequest();

					if (!xhr.upload) {
						OC.dialogs.alert(t('contacts', 'Your browser doesn\'t support AJAX upload. Please upload the contacts file to ownCloud and import that way.'), t('contacts', 'Error'))
					}
					importUpload = xhr.upload,
					xhr.onreadystatechange = function() {
						if (xhr.readyState == 4){
							response = $.parseJSON(xhr.responseText);
							if(response.status == 'success') {
								if(xhr.status == 200) {
									Contacts.UI.Addressbooks.doImport(response.data.path, response.data.file);
								} else {
									$(Contacts.UI.Addressbooks.droptarget).html(Contacts.UI.Addressbooks.droptext);
									Contacts.UI.loading(Contacts.UI.Addressbooks.droptarget, false);
									OC.dialogs.alert(xhr.status + ': ' + xhr.responseText, t('contacts', 'Error'));
								}
							} else {
								OC.dialogs.alert(response.data.message, t('contacts', 'Error'));
							}
						}
					};
					xhr.open('POST', OC.filePath('contacts', 'ajax', 'uploadimport.php') + '?file='+encodeURIComponent(file.name)+'&requesttoken='+requesttoken, true);
					xhr.setRequestHeader('Cache-Control', 'no-cache');
					xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
					xhr.setRequestHeader('X_FILE_NAME', encodeURIComponent(file.name));
					xhr.setRequestHeader('X-File-Size', file.size);
					xhr.setRequestHeader('Content-Type', file.type);
					xhr.send(file);
				}
			},
			uploadImport:function(filelist) {
				if(!filelist) {
					OC.dialogs.alert(t('contacts','No files selected for upload.'), t('contacts', 'Error'));
					return;
				}
				//var file = filelist.item(0);
				var file = filelist[0];
				var target = $('#import_upload_target');
				var form = $('#import_upload_form');
				var totalSize=0;
				if(file.size > $('#max_upload').val()){
					OC.dialogs.alert(t('contacts','The file you are trying to upload exceed the maximum size for file uploads on this server.'), t('contacts', 'Error'));
					return;
				} else {
					target.load(function(){
						var response=jQuery.parseJSON(target.contents().text());
						if(response != undefined && response.status == 'success'){
							Contacts.UI.Addressbooks.doImport(response.data.path, response.data.file);
						}else{
							OC.dialogs.alert(response.data.message, t('contacts', 'Error'));
						}
					});
					form.submit();
				}
			},
			importAddressbook:function(object){
				var tr = $(document.createElement('tr'))
					.load(OC.filePath('contacts', 'ajax', 'importaddressbook.php'));
				$(object).closest('tr').after(tr).hide();
			},
			doImport:function(path, file){
				$(Contacts.UI.Addressbooks.droptarget).html(t('contacts', 'Importing...'));
				Contacts.UI.loading(Contacts.UI.Addressbooks.droptarget, true);
				var id = $('#importaddressbook_dialog').find('#book').val();
				$.post(OC.filePath('contacts', '', 'import.php'), { id: id, path: path, file: file, fstype: 'OC_FilesystemView' },
					function(jsondata){
						if(jsondata.status == 'success'){
							Contacts.UI.Addressbooks.droptarget.html(t('contacts', 'Import done. Success/Failure: ')+jsondata.data.imported+'/'+jsondata.data.failed);
							$('#chooseaddressbook_dialog').find('#close_button').val(t('contacts', 'OK'));
							Contacts.UI.Contacts.update();
							setTimeout(
									function() {
										$(Contacts.UI.Addressbooks.droptarget).html(Contacts.UI.Addressbooks.droptext);
									}, 5000);
						} else {
							OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
						}
				});
				Contacts.UI.loading(Contacts.UI.Addressbooks.droptarget, false);
			},
			submit:function(button, bookid){
				var displayname = $("#displayname_"+bookid).val().trim();
				var active = $("#edit_active_"+bookid+":checked").length;
				var description = $("#description_"+bookid).val();
				
				if(displayname.length == 0) {
					OC.dialogs.alert(t('contacts', 'Displayname cannot be empty.'), t('contacts', 'Error'));
					return false;
				}
				var url;
				if (bookid == 'new'){
					url = OC.filePath('contacts', 'ajax', 'createaddressbook.php');
				}else{
					url = OC.filePath('contacts', 'ajax', 'updateaddressbook.php');
				}
				$.post(url, { id: bookid, name: displayname, active: active, description: description },
					function(jsondata){
						if(jsondata.status == 'success'){
							$(button).closest('tr').prev().html(jsondata.page).show().next().remove();
							Contacts.UI.Contacts.update();
						} else {
							OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
						}
				});
			},
			cancel:function(button, bookid){
				$(button).closest('tr').prev().show().next().remove();
			}
		},
		Contacts:{
			// Reload the contacts list.
			update:function(){
				$.getJSON(OC.filePath('contacts', 'ajax', 'contacts.php'),{},function(jsondata){
					if(jsondata.status == 'success'){
						$('#contacts').html(jsondata.data.page);
						Contacts.UI.Card.update();
					}
					else{
						OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
					}
				});
				setTimeout(function() {
					$('#contacts li').unbind('inview');
					$('#contacts li').bind('inview', function(event, isInView, visiblePartX, visiblePartY) {
						if (isInView) {
							if (!$(this).find('a').attr('style')) {
								$(this).find('a').css('background','url('+OC.filePath('contacts', '', 'thumbnail.php')+'?id='+$(this).data('id')+') no-repeat');
							}
						}
					})}, 500);
				setTimeout(Contacts.UI.Contacts.lazyupdate, 500);
			},
			// Add thumbnails to the contact list as they become visible in the viewport.
			lazyupdate:function(){
				$('#contacts li').live('inview', function(){
					if (!$(this).find('a').attr('style')) {
						$(this).find('a').css('background','url('+OC.filePath('contacts', '', 'thumbnail.php')+'?id='+$(this).data('id')+') no-repeat');
					}
				});
			},
			refreshThumbnail:function(id){
				var item = $('#contacts [data-id="'+id+'"]').find('a');
				item.html(Contacts.UI.Card.fn);
				item.css('background','url('+OC.filePath('contacts', '', 'thumbnail.php')+'?id='+id+'&refresh=1'+Math.random()+') no-repeat');
			},
			scrollTo:function(id){
				$('#contacts').animate({
					scrollTop: $('#leftcontent li[data-id="'+id+'"]').offset().top-20}, 'slow','swing');
			}
		}
	}
}
$(document).ready(function(){

	OCCategories.changed = Contacts.UI.Card.categoriesChanged;
	OCCategories.app = 'contacts';

	$('#notification').click(function(){
		$('#notification').fadeOut();
	});
	
	$('#chooseaddressbook').click(Contacts.UI.Addressbooks.overview);
	$('#chooseaddressbook').keydown(Contacts.UI.Addressbooks.overview);

	$('#contacts_newcontact').click(Contacts.UI.Card.editNew);
	$('#contacts_newcontact').keydown(Contacts.UI.Card.editNew);
	
	// Load a contact.
	$('#contacts').keydown(function(event) {
		if(event.which == 13) {
			$('#contacts').click();
		}
	});
	$('#contacts').click(function(event){
		var $tgt = $(event.target);
		if ($tgt.is('li') || $tgt.is('a')) {
			var item = $tgt.is('li')?$($tgt):($tgt).parent();
			var id = item.data('id');
			item.addClass('active');
			var oldid = $('#rightcontent').data('id');
			if(oldid != 0){
				$('#contacts li[data-id="'+oldid+'"]').removeClass('active');
			}
			$.getJSON(OC.filePath('contacts', 'ajax', 'contactdetails.php'),{'id':id},function(jsondata){
				if(jsondata.status == 'success'){
					Contacts.UI.Card.loadContact(jsondata.data);
				}
				else{
					OC.dialogs.alert(jsondata.data.message, t('contacts', 'Error'));
				}
			});
		}
		return false;
	});

	$('#contacts li').bind('inview', function(event, isInView, visiblePartX, visiblePartY) {
		if (isInView) { //NOTE: I've kept all conditions for future reference ;-)
			// element is now visible in the viewport
			if (visiblePartY == 'top') {
				// top part of element is visible
			} else if (visiblePartY == 'bottom') {
				// bottom part of element is visible
			} else {
				// whole part of element is visible
				if (!$(this).find('a').attr('style')) {
					//alert($(this).data('id') + ' has background: ' + $(this).attr('style'));
					$(this).find('a').css('background','url('+OC.filePath('contacts', '', 'thumbnail.php')+'?id='+$(this).data('id')+') no-repeat');
				}/* else {
					alert($(this).data('id') + ' has style ' + $(this).attr('style').match('url'));
				}*/
			}
		} else {
			// element has gone out of viewport
		}
	});
	
	$('.contacts_property').live('change', function(){
		Contacts.UI.Card.saveProperty(this);
	});

	/**
	 * Upload function for dropped files. Should go in the Contacts class/object.
	 */
	$.fileUpload = function(files){
		var file = files[0];
		if(file.size > $('#max_upload').val()){
			OC.dialogs.alert(t('contacts','The file you are trying to upload exceed the maximum size for file uploads on this server.'), t('contacts','Upload too large'));
			return;
		}
		if (file.type.indexOf("image") != 0) {
			OC.dialogs.alert(t('contacts','Only image files can be used as profile picture.'), t('contacts','Wrong file type'));
			return;
		}
		var xhr = new XMLHttpRequest();

		if (!xhr.upload) {
			OC.dialogs.alert(t('contacts', 'Your browser doesn\'t support AJAX upload. Please click on the profile picture to select a photo to upload.'), t('contacts', 'Error'))
		}
		fileUpload = xhr.upload,
		xhr.onreadystatechange = function() {
			if (xhr.readyState == 4){
				response = $.parseJSON(xhr.responseText);
				if(response.status == 'success') {
					if(xhr.status == 200) {
						Contacts.UI.Card.editPhoto(response.data.id, response.data.tmp);
					} else {
						OC.dialogs.alert(xhr.status + ': ' + xhr.responseText, t('contacts', 'Error'));
					}
				} else {
					OC.dialogs.alert(response.data.message, t('contacts', 'Error'));
				}
			}
		};
	
		fileUpload.onprogress = function(e){
			if (e.lengthComputable){
				var _progress = Math.round((e.loaded * 100) / e.total);
				//if (_progress != 100){
				//}
			}
		};
		xhr.open('POST', OC.filePath('contacts', 'ajax', 'uploadphoto.php')+'?id='+Contacts.UI.Card.id+'&requesttoken='+requesttoken+'&imagefile='+encodeURIComponent(file.name), true);
		xhr.setRequestHeader('Cache-Control', 'no-cache');
		xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		xhr.setRequestHeader('X_FILE_NAME', encodeURIComponent(file.name));
		xhr.setRequestHeader('X-File-Size', file.size);
		xhr.setRequestHeader('Content-Type', file.type);
		xhr.send(file);
	}

	$('body').click(function(e){
		if(!$(e.target).is('#contacts_propertymenu_button')) {
			$('#contacts_propertymenu_dropdown').hide();
		}
	});
	function propertyMenu(){
		var menu = $('#contacts_propertymenu_dropdown');
		if(menu.is(':hidden')) {
			menu.show();
			menu.find('li').first().focus();
		} else {
			menu.hide();
		}
	}
	$('#contacts_propertymenu_button').click(propertyMenu);
	$('#contacts_propertymenu_button').keydown(propertyMenu);
	function propertyMenuItem(){
		var type = $(this).data('type');
		Contacts.UI.Card.addProperty(type);
		$('#contacts_propertymenu_dropdown').hide();
	}
	$('#contacts_propertymenu_dropdown a').click(propertyMenuItem);
	$('#contacts_propertymenu_dropdown a').keydown(propertyMenuItem);
});
