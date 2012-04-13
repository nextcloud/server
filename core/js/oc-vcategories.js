OCCategories={
	edit:function(){
		console.log('OCCategories.edit');
		if(OCCategories.app == undefined) {
			OC.dialogs.alert('OCCategories.app is not set!');
			return;
		}
		$('body').append('<div id="category_dialog"></div>');
		$('#category_dialog').load(OC.filePath('core', 'ajax', 'vcategories/edit.php')+'?app='+OCCategories.app, function(response){
			try {
				var jsondata = jQuery.parseJSON(response);
				if(response.status == 'error'){
					OC.dialogs.alert(response.data.message, 'Error');
					return;
				}
			} catch(e) {
				$('#edit_categories_dialog').dialog({
						modal: true,
						height: 350, minHeight:200, width: 250, minWidth: 200,
						buttons: {
							'Delete':function() {
								OCCategories.doDelete();
							},
							'Rescan':function() {
								OCCategories.rescan();
							}
						},
						close : function(event, ui) {
							$(this).dialog('destroy').remove();
							$('#category_dialog').remove();
						},
						open : function(event, ui) {
							$('#category_addinput').live('input',function(){
								if($(this).val().length > 0) {
									$('#category_addbutton').removeAttr('disabled');
								}
							});
							$('#categoryform').submit(function() {
								OCCategories.add($('#category_addinput').val());
								$('#category_addinput').val('');
								$('#category_addbutton').attr('disabled', 'disabled');
								return false;
							});
							$('#category_addbutton').live('click',function(e){
								e.preventDefault();
								if($('#category_addinput').val().length > 0) {
									OCCategories.add($('#category_addinput').val());
									$('#category_addinput').val('');
								}
							});
						}
				});
			}
		});
	},
	_processDeleteResult:function(jsondata, status, xhr){
		if(jsondata.status == 'success'){
			OCCategories._update(jsondata.data.categories);
		} else {
			OC.dialogs.alert(jsondata.data.message, 'Error');
		}
	},
	doDelete:function(){
		var categories = $('#categorylist').find('input[type="checkbox"]').serialize();
		categories += '&app=' + OCCategories.app;
		console.log('OCCategories.delete: ' + categories);
		$.post(OC.filePath(OCCategories.app, 'ajax', 'categories/delete.php'), categories, OCCategories._processDeleteResult)
		.error(function(xhr){
			if (xhr.status == 404) {
				$.post(OC.filePath('core', 'ajax', 'vcategories/delete.php'), categories, OCCategories._processDeleteResult);
			}
		});
	},
	add:function(category){
		console.log('OCCategories.add ' + category);
		$.getJSON(OC.filePath('core', 'ajax', 'vcategories/add.php'),{'category':category, 'app':OCCategories.app},function(jsondata){
			if(jsondata.status == 'success'){
				OCCategories._update(jsondata.data.categories);
			} else {
				OC.dialogs.alert(jsondata.data.message, 'Error');
			}
		});
		return false;
	},
	rescan:function(){
		console.log('Categories.rescan');
		$.getJSON(OC.filePath(OCCategories.app, 'ajax', 'categories/rescan.php'),function(jsondata, status, xhr){
			if(jsondata.status == 'success'){
				OCCategories._update(jsondata.data.categories);
			} else {
				OC.dialogs.alert(jsondata.data.message, 'Error');
			}
		}).error(function(xhr){
			if (xhr.status == 404) {
				OC.dialogs.alert('The required file ' + OC.filePath(Categories.app, 'ajax', 'categories/rescan.php') + ' is not installed!', 'Error');
			}
		});
	},
	_update:function(categories){
		var categorylist = $('#categorylist');
		categorylist.find('li').remove();
		for(var category in categories) {
			var item = '<li><input type="checkbox" name="categories" value="' + categories[category] + '" />' + categories[category] + '</li>';
			$(item).appendTo(categorylist);
		}
		if(OCCategories.changed != undefined) {
			OCCategories.changed(categories);
		}
	}
}

