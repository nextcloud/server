OC.Tags= {
	edit:function(type, cb) {
		if(!type && !this.type) {
			throw {
				name: 'MissingParameter',
				message: t(
					'core',
					'The object type is not specified.'
				)
			};
		}
		type = type ? type : this.type;
		var self = this;
		$.when(this._getTemplate()).then(function($tmpl) {
			if(self.$dialog) {
				self.$dialog.ocdialog('close');
			}
			self.$dialog = $tmpl.octemplate({
				addText: t('core', 'Enter new')
			});
			$('body').append(self.$dialog);

			self.$dialog.ready(function() {
				self.$taglist = self.$dialog.find('.taglist');
				self.$taginput = self.$dialog.find('.addinput');
				self.$taglist.on('change', 'input:checkbox', function(event) {
					self._handleChanges(self.$taglist, self.$taginput);
				});
				self.$taginput.on('input', function(event) {
					self._handleChanges(self.$taglist, self.$taginput);
				});
				self.deleteButton = {
					text: t('core', 'Delete'),
					click: function() {
						self._deleteTags(
							self,
							type,
							self._selectedIds()
						);
					}
				};
				self.addButton = {
					text: t('core', 'Add'),
					click: function() {
						self._addTag(
							self,
							type,
							self.$taginput.val()
						);
					}
				};

				self._fillTagList(type, self.$taglist);
			});

			self.$dialog.ocdialog({
				title: t('core', 'Edit tags'),
				closeOnEscape: true,
				width: 250,
				height: 'auto',
				modal: true,
				//buttons: buttonlist,
				close: function(event, ui) {
					try {
						$(this).ocdialog('destroy').remove();
					} catch(e) {console.warn(e);}
					self.$dialog = null;
				}
			});
		})
		.fail(function(status, error) {
			// If the method is called while navigating away
			// from the page, it is probably not needed ;)
			if(status !== 0) {
				alert(t('core', 'Error loading dialog template: {error}', {error: error}));
			}
		});
	},
	/**
	 * @param {string} type
	 * @param {string} tag
	 * @return jQuery.Promise which resolves with an array of ids
	 */
	getIdsForTag:function(type, tag) {
		if(!type && !this.type) {
			throw new Error('The object type is not specified.');
		}
		type = type ? type : this.type;
		var defer = $.Deferred(),
			self = this,
			url = OC.generateUrl('/tags/{type}/ids', {type: type});
		$.getJSON(url, {tag: tag}, function(response) {
			if(response.status === 'success') {
				defer.resolve(response.ids);
			} else {
				defer.reject(response);
			}
		});
		return defer.promise();
	},
	/**
	 * @param {string} type
	 * @return {*} jQuery.Promise which resolves with an array of ids
	 */
	getFavorites:function(type) {
		if(!type && !this.type) {
			throw new Error('The object type is not specified.');
		}
		type = type ? type : this.type;
		var defer = $.Deferred(),
			self = this,
			url = OC.generateUrl('/tags/{type}/favorites', {type: type});
		$.getJSON(url, function(response) {
			if(response.status === 'success') {
				defer.resolve(response.ids);
			} else {
				defer.reject(response);
			}
		});
		return defer.promise();
	},
	/**
	 * @param {string} type
	 * @return {*} jQuery.Promise which resolves with an array of id/name objects
	 */
	getTags:function(type) {
		if(!type && !this.type) {
			throw new Error('The object type is not specified.');
		}
		type = type ? type : this.type;
		var defer = $.Deferred(),
			self = this,
			url = OC.generateUrl('/tags/{type}', {type: type});
		$.getJSON(url, function(response) {
			if(response.status === 'success') {
				defer.resolve(response.tags);
			} else {
				defer.reject(response);
			}
		});
		return defer.promise();
	},
	/**
	 * @param {number} id
	 * @param {string} tag
	 * @param {string} type
	 * @return {*} jQuery.Promise
	 */
	tagAs:function(id, tag, type) {
		if(!type && !this.type) {
			throw new Error('The object type is not specified.');
		}
		type = type ? type : this.type;
		var defer = $.Deferred(),
			self = this,
			url = OC.generateUrl('/tags/{type}/tag/{id}/', {type: type, id: id});
		$.post(url, {tag: tag}, function(response) {
			if(response.status === 'success') {
				defer.resolve(response);
			} else {
				defer.reject(response);
			}
		}).fail(function(jqXHR, textStatus, errorThrown) {
			defer.reject(jqXHR.status, errorThrown);
		});
		return defer.promise();
	},
	/**
	 * @param {number} id
	 * @param {string} tag
	 * @param {string} type
	 * @return {*} jQuery.Promise
	 */
	unTag:function(id, tag, type) {
		if(!type && !this.type) {
			throw new Error('The object type is not specified.');
		}
		type = type ? type : this.type;
		var defer = $.Deferred(),
			self = this,
			url = OC.generateUrl('/tags/{type}/untag/{id}/', {type: type, id: id});
		$.post(url, {tag: tag}, function(response) {
			if(response.status === 'success') {
				defer.resolve(response);
			} else {
				defer.reject(response);
			}
		}).fail(function(jqXHR, textStatus, errorThrown) {
			defer.reject(jqXHR.status, errorThrown);
		});
		return defer.promise();
	},
	/**
	 * @param {number} id
	 * @param {string} type
	 * @return {*} jQuery.Promise
	 */
	addToFavorites:function(id, type) {
		if(!type && !this.type) {
			throw new Error('The object type is not specified.');
		}
		type = type ? type : this.type;
		var defer = $.Deferred(),
			self = this,
			url = OC.generateUrl(
				'/tags/{type}/favorite/{id}/',
				{type: type, id: id}
			);
		$.post(url, function(response) {
			if(response.status === 'success') {
				defer.resolve(response);
			} else {
				defer.reject(response);
			}
		}).fail(function(jqXHR, textStatus, errorThrown) {
			defer.reject(jqXHR.status, errorThrown);
		});
		return defer.promise();
	},
	/**
	 * @param {number} id
	 * @param {string} type
	 * @return {*} jQuery.Promise
	 */
	removeFromFavorites:function(id, type) {
		if(!type && !this.type) {
			throw new Error('The object type is not specified.');
		}
		type = type ? type : this.type;
		var defer = $.Deferred(),
			self = this,
			url = OC.generateUrl(
				'/tags/{type}/unfavorite/{id}/',
				{type: type, id: id}
			);
		$.post(url, function(response) {
			if(response.status === 'success') {
				defer.resolve();
			} else {
				defer.reject(response);
			}
		}).fail(function(jqXHR, textStatus, errorThrown) {
			defer.reject(jqXHR.status, errorThrown);
		});
		return defer.promise();
	},
	/**
	 * @param {string} tag
	 * @param {string} type
	 * @return {*} jQuery.Promise which resolves with an object with the name and the new id
	 */
	addTag:function(tag, type) {
		if(!type && !this.type) {
			throw new Error('The object type is not specified.');
		}
		type = type ? type : this.type;
		var defer = $.Deferred(),
			self = this,
			url = OC.generateUrl('/tags/{type}/add', {type: type});
		$.post(url,{tag:tag}, function(response) {
			if(typeof cb == 'function') {
				cb(response);
			}
			if(response.status === 'success') {
				defer.resolve({id:response.id, name: tag});
			} else {
				defer.reject(response);
			}
		}).fail(function(jqXHR, textStatus, errorThrown) {
			defer.reject(jqXHR.status, errorThrown);
		});
		return defer.promise();
	},
	/**
	 * @param {array} tags
	 * @param {string} type
	 * @return {*} jQuery.Promise
	 */
	deleteTags:function(tags, type) {
		if(!type && !this.type) {
			throw new Error('The object type is not specified.');
		}
		type = type ? type : this.type;
		var defer = $.Deferred(),
			self = this,
			url = OC.generateUrl('/tags/{type}/delete', {type: type});
		if(!tags || !tags.length) {
			throw new Error(t('core', 'No tags selected for deletion.'));
		}
		var self = this;
		$.post(url, {tags:tags}, function(response) {
			if(response.status === 'success') {
				defer.resolve(response.tags);
			} else {
				defer.reject(response);
			}
		}).fail(function(jqXHR, textStatus, errorThrown) {
			defer.reject(jqXHR.status, errorThrown);
		});
		return defer.promise();
	},
	_update:function(tags, type) {
		if(!this.$dialog) {
			return;
		}
		var $taglist = this.$dialog.find('.taglist'),
			self = this;
		$taglist.empty();
		$.each(tags, function(idx, tag) {
			var $item = self.$listTmpl.octemplate({id: tag.id, name: tag.name});
			$item.appendTo($taglist);
		});
		$(this).trigger('change', {type: type, tags: tags});
		if(typeof this.changed === 'function') {
			this.changed(tags);
		}
	},
	_getTemplate: function() {
		var defer = $.Deferred();
		if(!this.$template) {
			var self = this;
			$.get(OC.filePath('core', 'templates', 'tags.html'), function(tmpl) {
				self.$template = $(tmpl);
				self.$listTmpl = self.$template.find('.taglist li:first-child').detach();
				defer.resolve(self.$template);
			})
			.fail(function(jqXHR, textStatus, errorThrown) {
				defer.reject(jqXHR.status, errorThrown);
			});
		} else {
			defer.resolve(this.$template);
		}
		return defer.promise();
	},
	_fillTagList: function(type) {
		var self = this;
		$.when(this.getTags(type))
		.then(function(tags) {
			self._update(tags, type);
		})
		.fail(function(response) {
			console.warn(response);
		});
	},
	_selectedIds: function() {
		return $.map(this.$taglist.find('input:checked'), function(b) {return $(b).val();});
	},
	_handleChanges: function($list, $input) {
		var ids = this._selectedIds();
		var buttons = [];
		if($input.val().length) {
			buttons.push(this.addButton);
		}
		if(ids.length) {
			buttons.push(this.deleteButton);
		}
		this.$dialog.ocdialog('option', 'buttons', buttons);
	},
	_deleteTags: function(self, type, ids) {
		$.when(self.deleteTags(ids, type))
		.then(function() {
			self._fillTagList(type);
			self.$dialog.ocdialog('option', 'buttons', []);
		})
		.fail(function(response) {
			console.warn(response);
		});
	},
	_addTag: function(self, type, tag) {
		$.when(self.addTag(tag, type))
		.then(function(tag) {
			self._fillTagList(type);
			self.$taginput.val('').trigger('input');
		})
		.fail(function(response) {
			console.warn(response);
		});
	}
};

