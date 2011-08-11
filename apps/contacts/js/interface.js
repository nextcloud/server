$(document).ready(function(){
	/* $('.contacts_addressbooksexpander').click(function(){
		$('.contacts_addressbooksdetails').toggle();
		return false;
	});*/

	$('#leftcontent li').live('click',function(){
		var id = $(this).data('id');
		$.getJSON('ajax/getdetails.php',{'id':id},function(jsondata){
			if(jsondata.status == 'success'){
				$('#rightcontent').data('id',jsondata.data.id);
				$('#rightcontent').html(jsondata.data.page);
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
				$('#rightcontent').html('');
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
				$('#rightcontent').append(jsondata.data.page);
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
			$('#contacts_addresspart').clone().insertBefore($('#contacts_addpropertyform input[type="submit"]'));
		}
		else if($(this).val() == 'TEL'){
			$('#contacts_phonepart').clone().insertBefore($('#contacts_addpropertyform input[type="submit"]'));
		}
		else{
			$('#contacts_generic').clone().insertBefore($('#contacts_addpropertyform input[type="submit"]'));
		}
	});

	$('#contacts_addpropertyform input[type="submit"]').live('click',function(){
		$.post('ajax/addproperty.php',$('#contacts_addpropertyform').serialize(),function(jsondata){
			if(jsondata.status == 'success'){
				$('#contacts_cardoptions').before(jsondata.data.page);
				$('#contacts_addpropertyform').remove();
				$('#contacts_addcontactsparts').remove();
			}
			else{
				alert(jsondata.data.message);
			}
		}, 'json');
		return false;
	});
	
	$('#contacts_newcontact').click(function(){
		$.getJSON('ajax/showaddcard.php',{},function(jsondata){
			if(jsondata.status == 'success'){
				$('#rightcontent').data('id','');
				$('#rightcontent').html(jsondata.data.page);
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
			}
			else{
				alert(jsondata.data.message);
			}
		}, 'json');
		return false;
	});

	$('.contacts_property [data-use="edit"]').live('click',function(){
		var id = $('#rightcontent').data('id');
		var checksum = $(this).parent().parent().data('checksum');
		$.getJSON('ajax/showsetproperty.php',{'id': id, 'checksum': checksum },function(jsondata){
			if(jsondata.status == 'success'){
				$('.contacts_property[data-checksum="'+checksum+'"] .contacts_propertyvalue').html(jsondata.data.page);
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('#contacts_setpropertyform input[type="submit"]').live('click',function(){
		$.post('ajax/setproperty.php',$('#contacts_setpropertyform').serialize(),function(jsondata){
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
		var checksum = $(this).parent().parent().data('checksum');
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
		$(this).find('span').show();
	});
	
	$('.contacts_property').live('mouseleave',function(){
		$(this).find('span').hide();
	});
});
