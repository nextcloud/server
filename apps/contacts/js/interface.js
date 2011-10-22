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
				$('#contacts_details_list').append(jsondata.data.page);
				$('#contacts_addpropertyform').remove();
				$('#contacts_addcontactsparts').remove();
				$('#contacts_addproperty').show();
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
		var checksum = $(this).parents('li').first().data('checksum');
		$.getJSON('ajax/showsetproperty.php',{'id': id, 'checksum': checksum },function(jsondata){
			if(jsondata.status == 'success'){
				$('.contacts_property[data-checksum="'+checksum+'"]').html(jsondata.data.page);
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
		$(this).find('span').show();
	});

	$('.contacts_property').live('mouseleave',function(){
		$(this).find('span').hide();
	});
});
