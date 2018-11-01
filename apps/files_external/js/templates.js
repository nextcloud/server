(function() {
  var template = Handlebars.template, templates = OCA.Files_External.Templates = OCA.Files_External.Templates || {};
templates['credentialsDialog'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<div id=\"files_external_div_form\"><div>\n	<div>"
    + alias4(((helper = (helper = helpers.credentials_text || (depth0 != null ? depth0.credentials_text : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"credentials_text","hash":{},"data":data}) : helper)))
    + "</div>\n		<form>\n			<input type=\"text\" name=\"username\" placeholder=\""
    + alias4(((helper = (helper = helpers.placeholder_username || (depth0 != null ? depth0.placeholder_username : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"placeholder_username","hash":{},"data":data}) : helper)))
    + "\"/>\n			<input type=\"password\" name=\"password\" placeholder=\""
    + alias4(((helper = (helper = helpers.placeholder_password || (depth0 != null ? depth0.placeholder_password : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"placeholder_password","hash":{},"data":data}) : helper)))
    + "\"/>\n		</form>\n	</div>\n</div>\n";
},"useData":true});
templates['mountOptionsDropDown'] = template({"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<div class=\"popovermenu open\">\n	<ul>\n		<li class=\"optionRow\">\n			<span class=\"menuitem\">\n				<input id=\"mountOptionsEncrypt\" class=\"checkbox\" name=\"encrypt\" type=\"checkbox\" value=\"true\" checked=\"checked\"/>\n				<label for=\"mountOptionsEncrypt\">"
    + alias4(((helper = (helper = helpers.mountOptionsEncryptLabel || (depth0 != null ? depth0.mountOptionsEncryptLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"mountOptionsEncryptLabel","hash":{},"data":data}) : helper)))
    + "</label>\n			</span>\n		</li>\n		<li class=\"optionRow\">\n			<span class=\"menuitem\">\n				<input id=\"mountOptionsPreviews\" class=\"checkbox\" name=\"previews\" type=\"checkbox\" value=\"true\" checked=\"checked\"/>\n				<label for=\"mountOptionsPreviews\">"
    + alias4(((helper = (helper = helpers.mountOptionsPreviewsLabel || (depth0 != null ? depth0.mountOptionsPreviewsLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"mountOptionsPreviewsLabel","hash":{},"data":data}) : helper)))
    + "</label>\n			</span>\n		</li>\n		<li class=\"optionRow\">\n			<span class=\"menuitem\">\n				<input id=\"mountOptionsSharing\" class=\"checkbox\" name=\"enable_sharing\" type=\"checkbox\" value=\"true\"/>\n				<label for=\"mountOptionsSharing\">"
    + alias4(((helper = (helper = helpers.mountOptionsSharingLabel || (depth0 != null ? depth0.mountOptionsSharingLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"mountOptionsSharingLabel","hash":{},"data":data}) : helper)))
    + "</label>\n			</span>\n		</li>\n		<li class=\"optionRow\">\n			<span class=\"menuitem icon-search\">\n				<label for=\"mountOptionsFilesystemCheck\">"
    + alias4(((helper = (helper = helpers.mountOptionsFilesystemCheckLabel || (depth0 != null ? depth0.mountOptionsFilesystemCheckLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"mountOptionsFilesystemCheckLabel","hash":{},"data":data}) : helper)))
    + "</label>\n				<select id=\"mountOptionsFilesystemCheck\" name=\"filesystem_check_changes\" data-type=\"int\">\n					<option value=\"0\">"
    + alias4(((helper = (helper = helpers.mountOptionsFilesystemCheckOnce || (depth0 != null ? depth0.mountOptionsFilesystemCheckOnce : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"mountOptionsFilesystemCheckOnce","hash":{},"data":data}) : helper)))
    + "</option>\n					<option value=\"1\" selected=\"selected\">"
    + alias4(((helper = (helper = helpers.mountOptionsFilesystemCheckDA || (depth0 != null ? depth0.mountOptionsFilesystemCheckDA : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"mountOptionsFilesystemCheckDA","hash":{},"data":data}) : helper)))
    + "</option>\n				</select>\n			</span>\n		</li>\n		<li class=\"optionRow\">\n			<span class=\"menuitem\">\n				<input id=\"mountOptionsEncoding\" class=\"checkbox\" name=\"encoding_compatibility\" type=\"checkbox\" value=\"true\"/>\n				<label for=\"mountOptionsEncoding\">"
    + alias4(((helper = (helper = helpers.mountOptionsEncodingLabel || (depth0 != null ? depth0.mountOptionsEncodingLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"mountOptionsEncodingLabel","hash":{},"data":data}) : helper)))
    + "</label>\n			</span>\n		</li>\n		<li class=\"optionRow\">\n			<span class=\"menuitem\">\n				<input id=\"mountOptionsReadOnly\" class=\"checkbox\" name=\"readonly\" type=\"checkbox\" value=\"true\"/>\n				<label for=\"mountOptionsReadOnly\">"
    + alias4(((helper = (helper = helpers.mountOptionsReadOnlyLabel || (depth0 != null ? depth0.mountOptionsReadOnlyLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"mountOptionsReadOnlyLabel","hash":{},"data":data}) : helper)))
    + "</label>\n			</span>\n		</li>\n		<li class=\"optionRow persistent\">\n			<a href=\"#\" class=\"menuitem remove icon-delete\">\n				<span>"
    + alias4(((helper = (helper = helpers.deleteLabel || (depth0 != null ? depth0.deleteLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"deleteLabel","hash":{},"data":data}) : helper)))
    + "</span>\n			</a>\n		</li>\n	</ul>\n</div>\n";
},"useData":true});
})();