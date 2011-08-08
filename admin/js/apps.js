$("input[x-use='appenablebutton']").live( "click", function(){
	appid = $(this).parent().data("uid");

	//alert("dsfsdfsdf");
	if($(this).val() == "enabled"){
		$(this).attr("value","disabled");
		$(this).removeClass( "enabled" );
		$(this).addClass( "disabled" );
		$.post( "ajax/disableapp.php", 'appid='+appid);
	}
	else if($(this).val() == "disabled"){
		$(this).attr("value","enabled");
		$(this).removeClass( "disabled" );
		$(this).addClass( "enabled" );
		$.post( "ajax/enableapp.php", 'appid='+appid);
	}
});