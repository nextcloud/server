/**
 * ownCloud - Addressbook
 *
 * @author Jakob Sack
 * @copyright 2011 Jakob Sack mail@jakobsack.de
 * @copyright 2011-2012 Thomas Tanghus <thomas@tanghus.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 * TODO:  
 * If you add a contact, its thumbnail doesnt show in the list. But when you add another one it shows up, but not for the second contact added.
 *  Place a new contact in correct alphabetic order
 */


Contacts={
	UI:{
		showCardDAVUrl:function(username, bookname){
			$('#carddav_url').val(totalurl + '/' + username + '/' + bookname);
			$('#carddav_url').show();
			$('#carddav_url_close').show();
		},
		Addressbooks:{
			overview:function(){
				if($('#chooseaddressbook_dialog').dialog('isOpen') == true){
					$('#chooseaddressbook_dialog').dialog('moveToTop');
				}else{
					$('#dialog_holder').load(OC.filePath('contacts', 'ajax', 'chooseaddressbook.php'), function(){
						$('#chooseaddressbook_dialog').dialog({
							width : 600,
							close : function(event, ui) {
								$(this).dialog('destroy').remove();
							}
						});
					});
				}
			},
			activation:function(checkbox, bookid)
			{
				$.post(OC.filePath('contacts', 'ajax', 'activation.php'), { bookid: bookid, active: checkbox.checked?1:0 },
				  function(data) {
					/*
					 * Arguments:
					 * data.status
					 * data.bookid
					 * data.active
					 */
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
				/* TODO: Shouldn't there be some kinda error checking here? */
			},
			editAddressbook:function(object, bookid){
				var tr = $(document.createElement('tr'))
					.load(OC.filePath('contacts', 'ajax', 'editaddressbook.php') + "?bookid="+bookid);
				$(object).closest('tr').after(tr).hide();
			},
			deleteAddressbook:function(bookid){
				var check = confirm("Do you really want to delete this address book?");
				if(check == false){
					return false;
				}else{
					$.post(OC.filePath('contacts', 'ajax', 'deletebook.php'), { id: bookid},
					  function(data) {
						if (data.status == 'success'){
							$('#chooseaddressbook_dialog').dialog('destroy').remove();
							Contacts.UI.Contacts.update();
							Contacts.UI.Addressbooks.overview();
						} else {
							alert('Error: ' + data.message);
						}
					  });
				}
			},
			submit:function(button, bookid){
				var displayname = $("#displayname_"+bookid).val();
				var active = $("#edit_active_"+bookid+":checked").length;
				var description = $("#description_"+bookid).val();

				var url;
				if (bookid == 'new'){
					url = OC.filePath('contacts', 'ajax', 'createaddressbook.php');
				}else{
					url = OC.filePath('contacts', 'ajax', 'updateaddressbook.php');
				}
				$.post(url, { id: bookid, name: displayname, active: active, description: description },
					function(data){
						if(data.status == 'success'){
							$(button).closest('tr').prev().html(data.page).show().next().remove();
						}
					});
				Contacts.UI.Contacts.update();
			},
			cancel:function(button, bookid){
				$(button).closest('tr').prev().show().next().remove();
			}
		},
		Contacts:{
			update:function(){
				$.getJSON('ajax/contacts.php',{},function(jsondata){
					if(jsondata.status == 'success'){
						$('#contacts').html(jsondata.data.page);
					}
					else{
						alert(jsondata.data.message);
					}
				});
				/*
				var contactlist = $('#contacts');
				var contacts = contactlist.children('li').get();
				//alert(contacts);
				contacts.sort(function(a, b) {
					var compA = $(a).text().toUpperCase();
					var compB = $(b).text().toUpperCase();
					return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
				})
				$.each(contacts, function(idx, itm) { contactlist.append(itm); });
				*/
				setTimeout(Contacts.UI.Contacts.lazyupdate(), 500);
			},
			lazyupdate:function(){
				//alert('lazyupdate');
				$('#contacts li').live('inview', function(){
					if (!$(this).find('a').attr('style')) {
						//alert($(this).data('id') + ' has background: ' + $(this).attr('style'));
						$(this).find('a').css('background','url(thumbnail.php?id='+$(this).data('id')+') no-repeat');
					}/* else {
						alert($(this).data('id') + ' has style ' + $(this).attr('style').match('url'));
					}*/
				});
			}
		}
	}
}

$(document).ready(function(){
	/*-------------------------------------------------------------------------
	 * Event handlers
	 *-----------------------------------------------------------------------*/
	$('#leftcontent li').live('click',function(){
		var id = $(this).data('id');
		var oldid = $('#rightcontent').data('id');
		if(oldid != 0){
			$('#leftcontent li[data-id="'+oldid+'"]').removeClass('active');
		}
		$.getJSON('ajax/getdetails.php',{'id':id},function(jsondata){
			if(jsondata.status == 'success'){
				$('#rightcontent').data('id',jsondata.data.id);
				$('#rightcontent').html(jsondata.data.page);
				$('#leftcontent li[data-id="'+jsondata.data.id+'"]').addClass('active');
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('#contacts_deletecard').live('click',function(){
		var id = $('#rightcontent').data('id');
		$.getJSON('ajax/deletecard.php',{'id':id},function(jsondata){
			if(jsondata.status == 'success'){
				$('#leftcontent [data-id="'+jsondata.data.id+'"]').remove();
				$('#rightcontent').data('id','');
				$('#rightcontent').empty();
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('#contacts_addproperty').live('click',function(){
		var id = $('#rightcontent').data('id');
		$.getJSON('ajax/showaddproperty.php',{'id':id},function(jsondata){
			if(jsondata.status == 'success'){
				$('#contacts_details_list').append(jsondata.data.page);
				$('#contacts_addproperty').hide();
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('#contacts_addpropertyform [name="name"]').live('change',function(){
		$('#contacts_addpropertyform #contacts_addresspart').remove();
		$('#contacts_addpropertyform #contacts_phonepart').remove();
		$('#contacts_addpropertyform #contacts_fieldpart').remove();
		$('#contacts_addpropertyform #contacts_generic').remove();
		if($(this).val() == 'ADR'){
			$('#contacts_addresspart').clone().insertAfter($('#contacts_addpropertyform .contacts_property_name'));
		}
		else if($(this).val() == 'TEL'){
			$('#contacts_phonepart').clone().insertAfter($('#contacts_addpropertyform .contacts_property_name'));
		}
		else{
			$('#contacts_generic').clone().insertAfter($('#contacts_addpropertyform .contacts_property_name'));
		}
		$('#contacts_addpropertyform .contacts_property_data select').chosen();
	});

	$('#contacts_addpropertyform input[type="submit"]').live('click',function(){
		$.post('ajax/addproperty.php',$('#contacts_addpropertyform').serialize(),function(jsondata){
			if(jsondata.status == 'success'){
				$('#contacts_addpropertyform').before(jsondata.data.page);
			}
			else{
				alert(jsondata.data.message);
			}
		}, 'json');
		return false;
	});

	$('#chooseaddressbook').click(function(){
		Contacts.UI.Addressbooks.overview();
		return false;
	});
	
	$('#contacts_newcontact').click(function(){
		$.getJSON('ajax/showaddcard.php',{},function(jsondata){
			if(jsondata.status == 'success'){
				$('#rightcontent').data('id','');
				$('#rightcontent').html(jsondata.data.page)
					.find('select').chosen();
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('#contacts_addcardform input[type="submit"]').live('click',function(){
		$.post('ajax/addcard.php',$('#contacts_addcardform').serialize(),function(jsondata){
			if(jsondata.status == 'success'){
				$('#rightcontent').data('id',jsondata.data.id);
				$('#rightcontent').html(jsondata.data.page);
				$('#leftcontent .active').removeClass('active');
				$('#leftcontent ul').append('<li data-id="'+jsondata.data.id+'" class="active"><a href="index.php?id='+jsondata.data.id+'">'+jsondata.data.name+'</a></li>');
			}
			else{
				alert(jsondata.data.message);
			}
		}, 'json');
		return false;
	});

	$('.contacts_property [data-use="edit"]').live('click',function(){
		var id = $('#rightcontent').data('id');
		var checksum = $(this).parents('.contacts_property').first().data('checksum');
		$.getJSON('ajax/showsetproperty.php',{'id': id, 'checksum': checksum },function(jsondata){
			if(jsondata.status == 'success'){
				$('.contacts_property[data-checksum="'+checksum+'"]').html(jsondata.data.page)
					.find('select').chosen();
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('#contacts_setpropertyform input[type="submit"]').live('click',function(){
		$.post('ajax/setproperty.php',$(this).parents('form').first().serialize(),function(jsondata){
			if(jsondata.status == 'success'){
				$('.contacts_property[data-checksum="'+jsondata.data.oldchecksum+'"]').replaceWith(jsondata.data.page);
			}
			else{
				alert(jsondata.data.message);
			}
		},'json');
		return false;
	});

	$('.contacts_property [data-use="delete"]').live('click',function(){
		var id = $('#rightcontent').data('id');
		var checksum = $(this).parents('li').first().data('checksum');
		$.getJSON('ajax/deleteproperty.php',{'id': id, 'checksum': checksum },function(jsondata){
			if(jsondata.status == 'success'){
				$('.contacts_property[data-checksum="'+checksum+'"]').remove();
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});


	$('.contacts_property').live('mouseenter',function(){
		$(this).find('span[data-use]').show();
	});

	$('.contacts_property').live('mouseleave',function(){
		$(this).find('span[data-use]').hide();
	});

	$('#contacts_addcardform select').chosen();

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
					$(this).find('a').css('background','url(thumbnail.php?id='+$(this).data('id')+') no-repeat');
				}/* else {
					alert($(this).data('id') + ' has style ' + $(this).attr('style').match('url'));
				}*/
			}
		} else {
			// element has gone out of viewport
		}
	});
});
