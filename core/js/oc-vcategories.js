var OCCategories= {
	edit:function() {
		if(OCCategories.type == undefined) {
			OC.dialogs.alert('OCCategories.type is not set!');
			return;
		}
		$('body').append('<div id="category_dialog"></div>');
		$('#category_dialog').load(
			OC.filePath('core', 'ajax', 'vcategories/edit.php') + '?type=' + OCCategories.type, function(response) {
			try {
				var jsondata = jQuery.parseJSON(response);
				if(response.status == 'error') {
					OC.dialogs.alert(response.data.message, 'Error');
					return;
				}
			} catch(e) {
				$('#edit_categories_dialog').dialog({
						modal: true,
						height: 350, minHeight:200, width: 250, minWidth: 200,
						buttons: {
							'Close': function() { 
								$(this).dialog("close"); 
							},
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
							$('#category_addinput').live('input',function() {
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
							$('#category_addbutton').live('click',function(e) {
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
	_processDeleteResult:function(jsondata, status, xhr) {
		if(jsondata.status == 'success') {
			OCCategories._update(jsondata.data.categories);
		} else {
			OC.dialogs.alert(jsondata.data.message, 'Error');
		}
	},
	favorites:function(type, cb) {
		$.getJSON(OC.filePath('core', 'ajax', 'categories/favorites.php'), {type: type},function(jsondata) {
			if(jsondata.status === 'success') {
				OCCategories._update(jsondata.data.categories);
			} else {
				OC.dialogs.alert(jsondata.data.message, t('core', 'Error'));
			}
		});
	},
	addToFavorites:function(id, type) {
		$.post(OC.filePath('core', 'ajax', 'vcategories/addToFavorites.php'), {id:id, type:type}, function(jsondata) {
			if(jsondata.status !== 'success') {
				OC.dialogs.alert(jsondata.data.message, 'Error');
			}
		});
	},
	removeFromFavorites:function(id, type) {
		$.post(OC.filePath('core', 'ajax', 'vcategories/removeFromFavorites.php'), {id:id, type:type}, function(jsondata) {
			if(jsondata.status !== 'success') {
				OC.dialogs.alert(jsondata.data.message, t('core', 'Error'));
			}
		});
	},
	doDelete:function() {
		var categories = $('#categorylist').find('input:checkbox').serialize();
		if(categories == '' || categories == undefined) {
			OC.dialogs.alert(t('core', 'No categories selected for deletion.'), t('core', 'Error'));
			return false;
		}
		var q = categories + '&type=' + OCCategories.type;
		if(OCCategories.app) {
			q += '&app=' + OCCategories.app;
			$.post(OC.filePath(OCCategories.app, 'ajax', 'categories/delete.php'), q, OCCategories._processDeleteResult);
		} else {
			$.post(OC.filePath('core', 'ajax', 'vcategories/delete.php'), q, OCCategories._processDeleteResult);
		}
	},
	add:function(category) {
		$.post(OC.filePath('core', 'ajax', 'vcategories/add.php'),{'category':category, 'app':OCCategories.app},function(jsondata) {
			if(jsondata.status === 'success') {
				OCCategories._update(jsondata.data.categories);
			} else {
				OC.dialogs.alert(jsondata.data.message, 'Error');
			}
		});
	},
	rescan:function() {
		$.getJSON(OC.filePath(OCCategories.app, 'ajax', 'categories/rescan.php'),function(jsondata, status, xhr) {
			if(jsondata.status === 'success') {
				OCCategories._update(jsondata.data.categories);
			} else {
				OC.dialogs.alert(jsondata.data.message, 'Error');
			}
		}).error(function(xhr){
			if (xhr.status == 404) {
				OC.dialogs.alert(
					t('core', 'The required file {file} is not installed!', 
					  {file: OC.filePath(OCCategories.app, 'ajax', 'categories/rescan.php')}, t('core', 'Error')));
			}
		});
	},
	_update:function(categories) {
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

