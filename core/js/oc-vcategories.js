var OCCategories= {
	category_favorites:'_$!<Favorite>!$_',
	edit:function(type, cb) {
		if(!type && !this.type) {
			throw { name: 'MissingParameter', message: t('core', 'The object type is not specified.') };
		}
		type = type ? type : this.type;
		$('body').append('<div id="category_dialog"></div>');
		$('#category_dialog').load(
			OC.filePath('core', 'ajax', 'vcategories/edit.php') + '?type=' + type, function(response) {
			try {
				var jsondata = jQuery.parseJSON(response);
				if(response.status == 'error') {
					OC.dialogs.alert(response.data.message, 'Error');
					return;
				}
			} catch(e) {
				var setEnabled = function(d, enable) {
					if(enable) {
						d.css('cursor', 'default').find('input,button:not(#category_addbutton)')
							.prop('disabled', false).css('cursor', 'default');
					} else {
						d.css('cursor', 'wait').find('input,button:not(#category_addbutton)')
							.prop('disabled', true).css('cursor', 'wait');
					}
				}
				var dlg = $('#edit_categories_dialog').dialog({
						modal: true,
						height: 350, minHeight:200, width: 250, minWidth: 200,
						buttons: {
							'Close': function() {
								$(this).dialog('close');
							},
							'Delete':function() {
								var categories = $('#categorylist').find('input:checkbox').serialize();
								setEnabled(dlg, false);
								OCCategories.doDelete(categories, function() {
									setEnabled(dlg, true);
								});
							},
							'Rescan':function() {
								setEnabled(dlg, false);
								OCCategories.rescan(function() {
									setEnabled(dlg, true);
								});
							}
						},
						close : function(event, ui) {
							$(this).dialog('destroy').remove();
							$('#category_dialog').remove();
						},
						open : function(event, ui) {
							$('#category_addinput').on('input',function() {
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
							$('#category_addbutton').on('click',function(e) {
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
	_processDeleteResult:function(jsondata) {
		if(jsondata.status == 'success') {
			OCCategories._update(jsondata.data.categories);
		} else {
			OC.dialogs.alert(jsondata.data.message, 'Error');
		}
	},
	favorites:function(type, cb) {
		if(!type && !this.type) {
			throw { name: 'MissingParameter', message: t('core', 'The object type is not specified.') };
		}
		type = type ? type : this.type;
		$.getJSON(OC.filePath('core', 'ajax', 'categories/favorites.php'), {type: type},function(jsondata) {
			if(typeof cb == 'function') {
				cb(jsondata);
			} else {
				if(jsondata.status === 'success') {
					OCCategories._update(jsondata.data.categories);
				} else {
					OC.dialogs.alert(jsondata.data.message, t('core', 'Error'));
				}
			}
		});
	},
	addToFavorites:function(id, type, cb) {
		if(!type && !this.type) {
			throw { name: 'MissingParameter', message: t('core', 'The object type is not specified.') };
		}
		type = type ? type : this.type;
		$.post(OC.filePath('core', 'ajax', 'vcategories/addToFavorites.php'), {id:id, type:type}, function(jsondata) {
			if(typeof cb == 'function') {
				cb(jsondata);
			} else {
				if(jsondata.status !== 'success') {
					OC.dialogs.alert(jsondata.data.message, 'Error');
				}
			}
		});
	},
	removeFromFavorites:function(id, type, cb) {
		if(!type && !this.type) {
			throw { name: 'MissingParameter', message: t('core', 'The object type is not specified.') };
		}
		type = type ? type : this.type;
		$.post(OC.filePath('core', 'ajax', 'vcategories/removeFromFavorites.php'), {id:id, type:type}, function(jsondata) {
			if(typeof cb == 'function') {
				cb(jsondata);
			} else {
				if(jsondata.status !== 'success') {
					OC.dialogs.alert(jsondata.data.message, t('core', 'Error'));
				}
			}
		});
	},
	doDelete:function(categories, type, cb) {
		if(!type && !this.type) {
			throw { name: 'MissingParameter', message: t('core', 'The object type is not specified.') };
		}
		type = type ? type : this.type;
		if(categories == '' || categories == undefined) {
			OC.dialogs.alert(t('core', 'No categories selected for deletion.'), t('core', 'Error'));
			return false;
		}
		var self = this;
		var q = categories + '&type=' + type;
		if(this.app) {
			q += '&app=' + this.app;
			$.post(OC.filePath(this.app, 'ajax', 'categories/delete.php'), q, function(jsondata) {
				if(typeof cb == 'function') {
					cb(jsondata);
				} else {
					self._processDeleteResult(jsondata);
				}
			});
		} else {
			$.post(OC.filePath('core', 'ajax', 'vcategories/delete.php'), q, function(jsondata) {
				if(typeof cb == 'function') {
					cb(jsondata);
				} else {
					self._processDeleteResult(jsondata);
				}
			});
		}
	},
	add:function(category, type, cb) {
		if(!type && !this.type) {
			throw { name: 'MissingParameter', message: t('core', 'The object type is not specified.') };
		}
		type = type ? type : this.type;
		$.post(OC.filePath('core', 'ajax', 'vcategories/add.php'),{'category':category, 'type':type},function(jsondata) {
			if(typeof cb == 'function') {
				cb(jsondata);
			} else {
				if(jsondata.status === 'success') {
					OCCategories._update(jsondata.data.categories);
				} else {
					OC.dialogs.alert(jsondata.data.message, 'Error');
				}
			}
		});
	},
	rescan:function(app, cb) {
		if(!app && !this.app) {
			throw { name: 'MissingParameter', message: t('core', 'The app name is not specified.') };
		}
		app = app ? app : this.app;
		$.getJSON(OC.filePath(app, 'ajax', 'categories/rescan.php'),function(jsondata, status, xhr) {
			if(typeof cb == 'function') {
				cb(jsondata);
			} else {
				if(jsondata.status === 'success') {
					OCCategories._update(jsondata.data.categories);
				} else {
					OC.dialogs.alert(jsondata.data.message, 'Error');
				}
			}
		}).error(function(xhr){
			if (xhr.status == 404) {
				var errormessage = t('core', 'The required file {file} is not installed!',
					  {file: OC.filePath(app, 'ajax', 'categories/rescan.php')}, t('core', 'Error'));
				if(typeof cb == 'function') {
					cb({status:'error', data:{message:errormessage}});
				} else {
					OC.dialogs.alert(errormessage);
				}
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
		if(typeof OCCategories.changed === 'function') {
			OCCategories.changed(categories);
		}
	}
}

