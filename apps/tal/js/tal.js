 
$(document).ready(function(){
	$('#manual nav').keydown(function(event) {
		if(event.which == 13) {
			$('#manual ol').click();
		}
	});
	$('#manual nav').click(function(event){
		var $tgt = $(event.target);
		if ($tgt.is('li') || $tgt.is('a')) {
			var item = $tgt.is('li')?$($tgt):($tgt).parent();
			var section = $('#manual section');
			var id = item.data('id');
			item.addClass('active');
			var oldpage = section.data('id');
			if(oldpage){
				$('#manual li[data-id="'+oldpage+'"]').removeClass('active');
			}
			$.getJSON(OC.filePath('tal', 'ajax', 'loadpage.php'),{'id':id},function(jsondata){
				if(jsondata.status == 'success'){
					$('#manual li[data-id="'+id+'"]').addClass('active');
					section.replaceWith(jsondata.data.page);
					section.data('id', id);
				}
				else{
					OC.dialogs.alert(jsondata.data.message, t('core', 'Error'));
				}
			});
		}
		return false;
	});
});