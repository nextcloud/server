$(document).ready(function(){
	$('.contacts_contacts').find('li').live('click',function(){
		var id = $(this).attr('x-id');
		$.getJSON('details.php',{'id':id},function(jsondata){
			if(jsondata.status == 'success'){
				$('.contacts_details').html(jsondata.data.page);
			}
			else{
				alert(jsondata.data.message);
			}
		});
		return false;
	});

	$('.contacts_addressbooksexpander').click(function(){
		$('.contacts_addressbooksdetails').toggle();
		return false;
	});
});
