(function() {
  var template = Handlebars.template, templates = OC.Share.Templates = OC.Share.Templates || {};
templates['sharedialoglinkshareview'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=depth0 != null ? depth0 : (container.nullContext || {});

  return "<ul class=\"shareWithList\">\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.nolinkShares : depth0),{"name":"if","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + ((stack1 = helpers.each.call(alias1,(depth0 != null ? depth0.linkShares : depth0),{"name":"each","hash":{},"fn":container.program(7, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "</ul>\n";
},"2":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "		<li data-share-id=\""
    + alias4(((helper = (helper = helpers.newShareId || (depth0 != null ? depth0.newShareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"newShareId","hash":{},"data":data}) : helper)))
    + "\">\n			<div class=\"avatar icon-public-white\"></div>\n			<span class=\"username\">"
    + alias4(((helper = (helper = helpers.newShareLabel || (depth0 != null ? depth0.newShareLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"newShareLabel","hash":{},"data":data}) : helper)))
    + "</span>\n			<span class=\"sharingOptionsGroup\">\n				<div class=\"share-menu\">\n					<a href=\"#\" class=\"icon icon-add new-share has-tooltip "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.showPending : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\" title=\""
    + alias4(((helper = (helper = helpers.newShareTitle || (depth0 != null ? depth0.newShareTitle : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"newShareTitle","hash":{},"data":data}) : helper)))
    + "\"></a>\n					<span class=\"icon icon-loading-small "
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.showPending : depth0),{"name":"unless","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\"></span>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.showPending : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "				</div>\n			</span>\n		</li>\n";
},"3":function(container,depth0,helpers,partials,data) {
    return "hidden";
},"5":function(container,depth0,helpers,partials,data) {
    var stack1, helper;

  return "						"
    + ((stack1 = ((helper = (helper = helpers.pendingPopoverMenu || (depth0 != null ? depth0.pendingPopoverMenu : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"pendingPopoverMenu","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + "\n";
},"7":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "		<li data-share-id=\""
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\">\n			<div class=\"avatar icon-public-white\"></div>\n			<span class=\"username\" title=\""
    + alias4(((helper = (helper = helpers.linkShareCreationDate || (depth0 != null ? depth0.linkShareCreationDate : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"linkShareCreationDate","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.linkShareLabel || (depth0 != null ? depth0.linkShareLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"linkShareLabel","hash":{},"data":data}) : helper)))
    + "</span>\n			\n			<span class=\"sharingOptionsGroup\">\n				<a href=\"#\" class=\"clipboard-button icon icon-clippy has-tooltip\" data-clipboard-text=\""
    + alias4(((helper = (helper = helpers.shareLinkURL || (depth0 != null ? depth0.shareLinkURL : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareLinkURL","hash":{},"data":data}) : helper)))
    + "\" title=\""
    + alias4(((helper = (helper = helpers.copyLabel || (depth0 != null ? depth0.copyLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"copyLabel","hash":{},"data":data}) : helper)))
    + "\"></a>\n				<div class=\"share-menu\">\n					<a href=\"#\" class=\"icon icon-more "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.showPending : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\"></a>\n					<span class=\"icon icon-loading-small "
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.showPending : depth0),{"name":"unless","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\"></span>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.showPending : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.program(8, data, 0),"data":data})) != null ? stack1 : "")
    + "				</div>\n			</span>\n		</li>\n";
},"8":function(container,depth0,helpers,partials,data) {
    var stack1, helper;

  return "						"
    + ((stack1 = ((helper = (helper = helpers.popoverMenu || (depth0 != null ? depth0.popoverMenu : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"popoverMenu","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + "\n";
},"10":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.noSharingPlaceholder : depth0),{"name":"if","hash":{},"fn":container.program(11, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\n";
},"11":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<input id=\"shareWith-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\" class=\"shareWithField\" type=\"text\" placeholder=\""
    + alias4(((helper = (helper = helpers.noSharingPlaceholder || (depth0 != null ? depth0.noSharingPlaceholder : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"noSharingPlaceholder","hash":{},"data":data}) : helper)))
    + "\" disabled=\"disabled\" />";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.shareAllowed : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.program(10, data, 0),"data":data})) != null ? stack1 : "");
},"useData":true});
templates['sharedialoglinkshareview_popover_menu'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "			<li>\n				<span class=\"menuitem\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<input type=\"radio\" name=\"publicUpload\" value=\""
    + alias4(((helper = (helper = helpers.publicUploadRValue || (depth0 != null ? depth0.publicUploadRValue : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicUploadRValue","hash":{},"data":data}) : helper)))
    + "\" id=\"sharingDialogAllowPublicUpload-r-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\" class=\"radio publicUploadRadio\" "
    + ((stack1 = ((helper = (helper = helpers.publicUploadRChecked || (depth0 != null ? depth0.publicUploadRChecked : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicUploadRChecked","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + " />\n					<label for=\"sharingDialogAllowPublicUpload-r-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.publicUploadRLabel || (depth0 != null ? depth0.publicUploadRLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicUploadRLabel","hash":{},"data":data}) : helper)))
    + "</label>\n				</span>\n			</li>\n			<li>\n				<span class=\"menuitem\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<input type=\"radio\" name=\"publicUpload\" value=\""
    + alias4(((helper = (helper = helpers.publicUploadRWValue || (depth0 != null ? depth0.publicUploadRWValue : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicUploadRWValue","hash":{},"data":data}) : helper)))
    + "\" id=\"sharingDialogAllowPublicUpload-rw-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\" class=\"radio publicUploadRadio\" "
    + ((stack1 = ((helper = (helper = helpers.publicUploadRWChecked || (depth0 != null ? depth0.publicUploadRWChecked : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicUploadRWChecked","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + " />\n					<label for=\"sharingDialogAllowPublicUpload-rw-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.publicUploadRWLabel || (depth0 != null ? depth0.publicUploadRWLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicUploadRWLabel","hash":{},"data":data}) : helper)))
    + "</label>\n				</span>\n			</li>\n			<li>\n				<span class=\"menuitem\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<input type=\"radio\" name=\"publicUpload\" value=\""
    + alias4(((helper = (helper = helpers.publicUploadWValue || (depth0 != null ? depth0.publicUploadWValue : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicUploadWValue","hash":{},"data":data}) : helper)))
    + "\" id=\"sharingDialogAllowPublicUpload-w-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\" class=\"radio publicUploadRadio\" "
    + ((stack1 = ((helper = (helper = helpers.publicUploadWChecked || (depth0 != null ? depth0.publicUploadWChecked : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicUploadWChecked","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + " />\n					<label for=\"sharingDialogAllowPublicUpload-w-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.publicUploadWLabel || (depth0 != null ? depth0.publicUploadWLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicUploadWLabel","hash":{},"data":data}) : helper)))
    + "</label>\n				</span>\n			</li>\n";
},"3":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "			<li id=\"allowPublicEditingWrapper\">\n				<span class=\"menuitem\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<input type=\"checkbox\" name=\"allowPublicEditing\" id=\"sharingDialogAllowPublicEditing-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\" class=\"checkbox publicEditingCheckbox\" "
    + ((stack1 = ((helper = (helper = helpers.publicEditingChecked || (depth0 != null ? depth0.publicEditingChecked : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicEditingChecked","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + " />\n					<label for=\"sharingDialogAllowPublicEditing-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.publicEditingLabel || (depth0 != null ? depth0.publicEditingLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"publicEditingLabel","hash":{},"data":data}) : helper)))
    + "</label>\n				</span>\n			</li>\n";
},"5":function(container,depth0,helpers,partials,data) {
    return "checked=\"checked\"";
},"7":function(container,depth0,helpers,partials,data) {
    return "disabled=\"disabled\"";
},"9":function(container,depth0,helpers,partials,data) {
    return "hidden";
},"11":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "			<li>\n				<span class=\"shareOption menuitem\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<input type=\"checkbox\" name=\"passwordByTalk\" id=\"passwordByTalk-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\" class=\"checkbox passwordByTalkCheckbox\"\n					"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isPasswordByTalkSet : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + " />\n					<label for=\"passwordByTalk-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.passwordByTalkLabel || (depth0 != null ? depth0.passwordByTalkLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordByTalkLabel","hash":{},"data":data}) : helper)))
    + "</label>\n				</span>\n			</li>\n";
},"13":function(container,depth0,helpers,partials,data) {
    return "datepicker";
},"15":function(container,depth0,helpers,partials,data) {
    var helper;

  return container.escapeExpression(((helper = (helper = helpers.expireDate || (depth0 != null ? depth0.expireDate : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"expireDate","hash":{},"data":data}) : helper)));
},"17":function(container,depth0,helpers,partials,data) {
    var helper;

  return container.escapeExpression(((helper = (helper = helpers.defaultExpireDate || (depth0 != null ? depth0.defaultExpireDate : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"defaultExpireDate","hash":{},"data":data}) : helper)));
},"19":function(container,depth0,helpers,partials,data) {
    return "readonly";
},"21":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "			<li>\n				<a href=\"#\" class=\"menuitem pop-up\" data-url=\""
    + alias4(((helper = (helper = helpers.url || (depth0 != null ? depth0.url : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"url","hash":{},"data":data}) : helper)))
    + "\" data-window=\""
    + alias4(((helper = (helper = helpers.newWindow || (depth0 != null ? depth0.newWindow : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"newWindow","hash":{},"data":data}) : helper)))
    + "\">\n					<span class=\"icon "
    + alias4(((helper = (helper = helpers.iconClass || (depth0 != null ? depth0.iconClass : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"iconClass","hash":{},"data":data}) : helper)))
    + "\"></span>\n					<span>"
    + alias4(((helper = (helper = helpers.label || (depth0 != null ? depth0.label : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"label","hash":{},"data":data}) : helper)))
    + "</span>\n				</a>\n			</li>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<div class=\"popovermenu menu\">\n	<ul>\n		<li class=\"hidden linkTextMenu\">\n			<span class=\"menuitem icon-link-text\">\n				<input id=\"linkText-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\" class=\"linkText\" type=\"text\" readonly=\"readonly\" value=\""
    + alias4(((helper = (helper = helpers.shareLinkURL || (depth0 != null ? depth0.shareLinkURL : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareLinkURL","hash":{},"data":data}) : helper)))
    + "\" />\n			</span>\n		</li>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.publicUpload : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.publicEditing : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "			<li>\n				<span class=\"menuitem\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<input type=\"checkbox\" name=\"hideDownload\" id=\"sharingDialogHideDownload-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\" class=\"checkbox hideDownloadCheckbox\"\n					"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hideDownload : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + " />\n					<label for=\"sharingDialogHideDownload-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.hideDownloadLabel || (depth0 != null ? depth0.hideDownloadLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"hideDownloadLabel","hash":{},"data":data}) : helper)))
    + "</label>\n				</span>\n			</li>\n			<li>\n				<span class=\"menuitem\">\n					<input type=\"checkbox\" name=\"showPassword\" id=\"showPassword-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\" class=\"checkbox showPasswordCheckbox\"\n					"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isPasswordSet : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + " "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isPasswordEnforced : depth0),{"name":"if","hash":{},"fn":container.program(7, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + " value=\"1\" />\n					<label for=\"showPassword-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.enablePasswordLabel || (depth0 != null ? depth0.enablePasswordLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"enablePasswordLabel","hash":{},"data":data}) : helper)))
    + "</label>\n				</span>\n			</li>\n			<li class=\""
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.isPasswordSet : depth0),{"name":"unless","hash":{},"fn":container.program(9, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + " linkPassMenu\">\n				<span class=\"menuitem icon-share-pass\">\n					<input id=\"linkPassText-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\" class=\"linkPassText\" type=\"password\" placeholder=\""
    + alias4(((helper = (helper = helpers.passwordPlaceholder || (depth0 != null ? depth0.passwordPlaceholder : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordPlaceholder","hash":{},"data":data}) : helper)))
    + "\" autocomplete=\"new-password\" />\n					<input type=\"submit\" class=\"icon-confirm share-pass-submit\" value=\"\" />\n					<span class=\"icon icon-loading-small hidden\"></span>\n				</span>\n			</li>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.showPasswordByTalkCheckBox : depth0),{"name":"if","hash":{},"fn":container.program(11, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "		<li>\n			<span class=\"menuitem\">\n				<input id=\"expireDate-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\" type=\"checkbox\" name=\"expirationDate\" class=\"expireDate checkbox\"\n				"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasExpireDate : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + " "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isExpirationEnforced : depth0),{"name":"if","hash":{},"fn":container.program(7, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + " />\n				<label for=\"expireDate-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.expireDateLabel || (depth0 != null ? depth0.expireDateLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"expireDateLabel","hash":{},"data":data}) : helper)))
    + "</label>\n			</span>\n		</li>\n		<li class=\""
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.hasExpireDate : depth0),{"name":"unless","hash":{},"fn":container.program(9, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\">\n			<span class=\"menuitem icon-expiredate expirationDateContainer-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\">\n				<label for=\"expirationDatePicker-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\" class=\"hidden-visually\" value=\""
    + alias4(((helper = (helper = helpers.expirationDate || (depth0 != null ? depth0.expirationDate : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"expirationDate","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.expirationLabel || (depth0 != null ? depth0.expirationLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"expirationLabel","hash":{},"data":data}) : helper)))
    + "</label>\n				<!-- do not use the datepicker if enforced -->\n				<input id=\"expirationDatePicker-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\" class=\""
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.isExpirationEnforced : depth0),{"name":"unless","hash":{},"fn":container.program(13, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\" type=\"text\"\n					placeholder=\""
    + alias4(((helper = (helper = helpers.expirationDatePlaceholder || (depth0 != null ? depth0.expirationDatePlaceholder : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"expirationDatePlaceholder","hash":{},"data":data}) : helper)))
    + "\" value=\""
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasExpireDate : depth0),{"name":"if","hash":{},"fn":container.program(15, data, 0),"inverse":container.program(17, data, 0),"data":data})) != null ? stack1 : "")
    + "\"\n					data-max-date=\""
    + alias4(((helper = (helper = helpers.maxDate || (depth0 != null ? depth0.maxDate : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"maxDate","hash":{},"data":data}) : helper)))
    + "\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isExpirationEnforced : depth0),{"name":"if","hash":{},"fn":container.program(19, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + " />\n			</span>\n			</li>\n		<li>\n			<a href=\"#\" class=\"share-add\">\n				<span class=\"icon-loading-small hidden\"></span>\n				<span class=\"icon icon-edit\"></span>\n				<span>"
    + alias4(((helper = (helper = helpers.addNoteLabel || (depth0 != null ? depth0.addNoteLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"addNoteLabel","hash":{},"data":data}) : helper)))
    + "</span>\n				<input type=\"button\" class=\"share-note-delete icon-delete "
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.hasNote : depth0),{"name":"unless","hash":{},"fn":container.program(9, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\">\n			</a>\n		</li>\n		<li class=\"share-note-form share-note-link "
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.hasNote : depth0),{"name":"unless","hash":{},"fn":container.program(9, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\">\n			<span class=\"menuitem icon-note\">\n				<textarea class=\"share-note\">"
    + alias4(((helper = (helper = helpers.shareNote || (depth0 != null ? depth0.shareNote : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareNote","hash":{},"data":data}) : helper)))
    + "</textarea>\n				<input type=\"submit\" class=\"icon-confirm share-note-submit\" value=\"\" id=\"add-note-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\" />\n			</span>\n		</li>\n"
    + ((stack1 = helpers.each.call(alias1,(depth0 != null ? depth0.social : depth0),{"name":"each","hash":{},"fn":container.program(21, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "		<li>\n			<a href=\"#\" class=\"unshare\"><span class=\"icon-loading-small hidden\"></span><span class=\"icon icon-delete\"></span><span>"
    + alias4(((helper = (helper = helpers.unshareLinkLabel || (depth0 != null ? depth0.unshareLinkLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"unshareLinkLabel","hash":{},"data":data}) : helper)))
    + "</span></a>\n		</li>\n		<li>\n			<a href=\"#\" class=\"new-share\">\n				<span class=\"icon-loading-small hidden\"></span>\n				<span class=\"icon icon-add\"></span>\n				<span>"
    + alias4(((helper = (helper = helpers.newShareLabel || (depth0 != null ? depth0.newShareLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"newShareLabel","hash":{},"data":data}) : helper)))
    + "</span>\n			</a>\n		</li>\n	</ul>\n</div>\n";
},"useData":true});
templates['sharedialoglinkshareview_popover_menu_pending'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "			<li>\n				<span class=\"menuitem icon-info\">\n					<p>"
    + alias4(((helper = (helper = helpers.enforcedPasswordLabel || (depth0 != null ? depth0.enforcedPasswordLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"enforcedPasswordLabel","hash":{},"data":data}) : helper)))
    + "</p>\n				</span>\n			</li>\n			<li class=\"linkPassMenu\">\n				<span class=\"menuitem\">\n					<form autocomplete=\"off\" class=\"enforcedPassForm\">\n						<input id=\"enforcedPassText\" required class=\"enforcedPassText\" type=\"password\"\n							placeholder=\""
    + alias4(((helper = (helper = helpers.passwordPlaceholder || (depth0 != null ? depth0.passwordPlaceholder : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordPlaceholder","hash":{},"data":data}) : helper)))
    + "\" autocomplete=\"enforcedPassText\" minlength=\""
    + alias4(((helper = (helper = helpers.minPasswordLength || (depth0 != null ? depth0.minPasswordLength : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"minPasswordLength","hash":{},"data":data}) : helper)))
    + "\" />\n						<input type=\"submit\" value=\" \" class=\"primary icon-checkmark-white\">\n					</form>\n				</span>\n			</li>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1;

  return "<div class=\"popovermenu open menu pending\">\n	<ul>\n"
    + ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.isPasswordEnforced : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "	</ul>\n</div>\n";
},"useData":true});
templates['sharedialogresharerinfoview'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper;

  return "<div class=\"share-note\">"
    + container.escapeExpression(((helper = (helper = helpers.shareNote || (depth0 != null ? depth0.shareNote : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"shareNote","hash":{},"data":data}) : helper)))
    + "</div>";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<span class=\"reshare\">\n	<div class=\"avatar\" data-userName=\""
    + alias4(((helper = (helper = helpers.reshareOwner || (depth0 != null ? depth0.reshareOwner : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"reshareOwner","hash":{},"data":data}) : helper)))
    + "\"></div>\n	"
    + alias4(((helper = (helper = helpers.sharedByText || (depth0 != null ? depth0.sharedByText : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"sharedByText","hash":{},"data":data}) : helper)))
    + "\n</span>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasShareNote : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\n";
},"useData":true});
templates['sharedialogshareelistview'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers.unless.call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.isShareWithCurrentUser : depth0),{"name":"unless","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "");
},"2":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "		<li data-share-id=\""
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\" data-share-type=\""
    + alias4(((helper = (helper = helpers.shareType || (depth0 != null ? depth0.shareType : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareType","hash":{},"data":data}) : helper)))
    + "\" data-share-with=\""
    + alias4(((helper = (helper = helpers.shareWith || (depth0 != null ? depth0.shareWith : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareWith","hash":{},"data":data}) : helper)))
    + "\">\n			<div class=\"avatar "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.modSeed : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\" data-username=\""
    + alias4(((helper = (helper = helpers.shareWith || (depth0 != null ? depth0.shareWith : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareWith","hash":{},"data":data}) : helper)))
    + "\" data-avatar=\""
    + alias4(((helper = (helper = helpers.shareWithAvatar || (depth0 != null ? depth0.shareWithAvatar : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareWithAvatar","hash":{},"data":data}) : helper)))
    + "\" data-displayname=\""
    + alias4(((helper = (helper = helpers.shareWithDisplayName || (depth0 != null ? depth0.shareWithDisplayName : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareWithDisplayName","hash":{},"data":data}) : helper)))
    + "\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.modSeed : depth0),{"name":"if","hash":{},"fn":container.program(5, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "></div>\n			<span class=\"username\" title=\""
    + alias4(((helper = (helper = helpers.shareWithTitle || (depth0 != null ? depth0.shareWithTitle : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareWithTitle","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.shareWithDisplayName || (depth0 != null ? depth0.shareWithDisplayName : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareWithDisplayName","hash":{},"data":data}) : helper)))
    + "</span>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.canUpdateShareSettings : depth0),{"name":"if","hash":{},"fn":container.program(7, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "		</li>\n";
},"3":function(container,depth0,helpers,partials,data) {
    return "imageplaceholderseed";
},"5":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "data-seed=\""
    + alias4(((helper = (helper = helpers.shareWith || (depth0 != null ? depth0.shareWith : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareWith","hash":{},"data":data}) : helper)))
    + " "
    + alias4(((helper = (helper = helpers.shareType || (depth0 != null ? depth0.shareType : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareType","hash":{},"data":data}) : helper)))
    + "\"";
},"7":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {});

  return "			<span class=\"sharingOptionsGroup\">\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.editPermissionPossible : depth0),{"name":"if","hash":{},"fn":container.program(8, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "				<div tabindex=\"0\" class=\"share-menu\"><span class=\"icon icon-more\"></span>\n					"
    + ((stack1 = ((helper = (helper = helpers.popoverMenu || (depth0 != null ? depth0.popoverMenu : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(alias1,{"name":"popoverMenu","hash":{},"data":data}) : helper))) != null ? stack1 : "")
    + "\n				</div>\n			</span>\n";
},"8":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "					<span>\n						<input id=\"canEdit-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\" type=\"checkbox\" name=\"edit\" class=\"permissions checkbox\" />\n						<label for=\"canEdit-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.canEditLabel || (depth0 != null ? depth0.canEditLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"canEditLabel","hash":{},"data":data}) : helper)))
    + "</label>\n					</span>\n";
},"10":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "		<li data-share-id=\""
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\" data-share-type=\""
    + alias4(((helper = (helper = helpers.shareType || (depth0 != null ? depth0.shareType : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareType","hash":{},"data":data}) : helper)))
    + "\">\n			<div class=\"avatar\" data-username=\""
    + alias4(((helper = (helper = helpers.shareInitiator || (depth0 != null ? depth0.shareInitiator : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareInitiator","hash":{},"data":data}) : helper)))
    + "\"></div>\n			<span class=\"has-tooltip username\" title=\""
    + alias4(((helper = (helper = helpers.shareInitiator || (depth0 != null ? depth0.shareInitiator : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareInitiator","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.shareInitiatorText || (depth0 != null ? depth0.shareInitiatorText : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareInitiatorText","hash":{},"data":data}) : helper)))
    + "</span>\n			<span class=\"sharingOptionsGroup\">\n				<a href=\"#\" class=\"unshare\"><span class=\"icon-loading-small hidden\"></span><span class=\"icon icon-delete\"></span><span class=\"hidden-visually\">"
    + alias4(((helper = (helper = helpers.unshareLabel || (depth0 != null ? depth0.unshareLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"unshareLabel","hash":{},"data":data}) : helper)))
    + "</span></a>\n			</span>\n		</li>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=depth0 != null ? depth0 : (container.nullContext || {});

  return "<ul id=\"shareWithList\" class=\"shareWithList\">\n"
    + ((stack1 = helpers.each.call(alias1,(depth0 != null ? depth0.sharees : depth0),{"name":"each","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + ((stack1 = helpers.each.call(alias1,(depth0 != null ? depth0.linkReshares : depth0),{"name":"each","hash":{},"fn":container.program(10, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "</ul>\n";
},"useData":true});
templates['sharedialogshareelistview_popover_menu'] = template({"1":function(container,depth0,helpers,partials,data) {
    var stack1;

  return " "
    + ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.sharePermissionPossible : depth0),{"name":"if","hash":{},"fn":container.program(2, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + " ";
},"2":function(container,depth0,helpers,partials,data) {
    var stack1;

  return " "
    + ((stack1 = helpers.unless.call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.isMailShare : depth0),{"name":"unless","hash":{},"fn":container.program(3, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + " ";
},"3":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "\n			<li>\n				<span class=\"menuitem\">\n					<input id=\"canShare-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\" type=\"checkbox\" name=\"share\" class=\"permissions checkbox\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasSharePermission : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + " data-permissions=\""
    + alias4(((helper = (helper = helpers.sharePermission || (depth0 != null ? depth0.sharePermission : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"sharePermission","hash":{},"data":data}) : helper)))
    + "\" />\n					<label for=\"canShare-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.canShareLabel || (depth0 != null ? depth0.canShareLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"canShareLabel","hash":{},"data":data}) : helper)))
    + "</label>\n				</span>\n				</li>\n			";
},"4":function(container,depth0,helpers,partials,data) {
    return "checked=\"checked\"";
},"6":function(container,depth0,helpers,partials,data) {
    var stack1, alias1=depth0 != null ? depth0 : (container.nullContext || {});

  return "			"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.createPermissionPossible : depth0),{"name":"if","hash":{},"fn":container.program(7, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\n			"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.updatePermissionPossible : depth0),{"name":"if","hash":{},"fn":container.program(10, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\n			"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.deletePermissionPossible : depth0),{"name":"if","hash":{},"fn":container.program(13, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\n";
},"7":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers.unless.call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.isMailShare : depth0),{"name":"unless","hash":{},"fn":container.program(8, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "");
},"8":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "\n				<li>\n					<span class=\"menuitem\">\n						<input id=\"canCreate-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\" type=\"checkbox\" name=\"create\" class=\"permissions checkbox\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasCreatePermission : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + " data-permissions=\""
    + alias4(((helper = (helper = helpers.createPermission || (depth0 != null ? depth0.createPermission : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"createPermission","hash":{},"data":data}) : helper)))
    + "\"/>\n						<label for=\"canCreate-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.createPermissionLabel || (depth0 != null ? depth0.createPermissionLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"createPermissionLabel","hash":{},"data":data}) : helper)))
    + "</label>\n					</span>\n				</li>\n			";
},"10":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers.unless.call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.isMailShare : depth0),{"name":"unless","hash":{},"fn":container.program(11, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "");
},"11":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "\n				<li>\n					<span class=\"menuitem\">\n						<input id=\"canUpdate-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\" type=\"checkbox\" name=\"update\" class=\"permissions checkbox\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasUpdatePermission : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + " data-permissions=\""
    + alias4(((helper = (helper = helpers.updatePermission || (depth0 != null ? depth0.updatePermission : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"updatePermission","hash":{},"data":data}) : helper)))
    + "\"/>\n						<label for=\"canUpdate-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.updatePermissionLabel || (depth0 != null ? depth0.updatePermissionLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"updatePermissionLabel","hash":{},"data":data}) : helper)))
    + "</label>\n					</span>\n				</li>\n				";
},"13":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers.unless.call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.isMailShare : depth0),{"name":"unless","hash":{},"fn":container.program(14, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "");
},"14":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "\n				<li>\n					<span class=\"menuitem\">\n						<input id=\"canDelete-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\" type=\"checkbox\" name=\"delete\" class=\"permissions checkbox\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasDeletePermission : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + " data-permissions=\""
    + alias4(((helper = (helper = helpers.deletePermission || (depth0 != null ? depth0.deletePermission : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"deletePermission","hash":{},"data":data}) : helper)))
    + "\"/>\n						<label for=\"canDelete-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.deletePermissionLabel || (depth0 != null ? depth0.deletePermissionLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"deletePermissionLabel","hash":{},"data":data}) : helper)))
    + "</label>\n					</span>\n				</li>\n				";
},"16":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasCreatePermission : depth0),{"name":"if","hash":{},"fn":container.program(17, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "			<li>\n				<span class=\"menuitem\">\n					<input id=\"password-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\" type=\"checkbox\" name=\"password\" class=\"password checkbox\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isPasswordSet : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isPasswordSet : depth0),{"name":"if","hash":{},"fn":container.program(19, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\" />\n					<label for=\"password-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.passwordLabel || (depth0 != null ? depth0.passwordLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordLabel","hash":{},"data":data}) : helper)))
    + "</label>\n				</span>\n			</li>\n			<li class=\"passwordMenu-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + " "
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.isPasswordSet : depth0),{"name":"unless","hash":{},"fn":container.program(22, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\">\n				<span class=\"passwordContainer-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + " icon-passwordmail menuitem\">\n					<label for=\"passwordField-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\" class=\"hidden-visually\" value=\""
    + alias4(((helper = (helper = helpers.password || (depth0 != null ? depth0.password : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"password","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.passwordLabel || (depth0 != null ? depth0.passwordLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordLabel","hash":{},"data":data}) : helper)))
    + "</label>\n					<input id=\"passwordField-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\" class=\"passwordField\" type=\"password\" placeholder=\""
    + alias4(((helper = (helper = helpers.passwordPlaceholder || (depth0 != null ? depth0.passwordPlaceholder : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordPlaceholder","hash":{},"data":data}) : helper)))
    + "\" value=\""
    + alias4(((helper = (helper = helpers.passwordValue || (depth0 != null ? depth0.passwordValue : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordValue","hash":{},"data":data}) : helper)))
    + "\" autocomplete=\"new-password\" />\n					<span class=\"icon-loading-small hidden\"></span>\n				</span>\n			</li>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isTalkEnabled : depth0),{"name":"if","hash":{},"fn":container.program(24, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "");
},"17":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "				<li>\n					<span class=\"menuitem\">\n						<input id=\"secureDrop-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\" type=\"checkbox\" name=\"secureDrop\" class=\"checkbox secureDrop\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.secureDropMode : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + " data-permissions=\""
    + alias4(((helper = (helper = helpers.readPermission || (depth0 != null ? depth0.readPermission : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"readPermission","hash":{},"data":data}) : helper)))
    + "\"/>\n						<label for=\"secureDrop-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.secureDropLabel || (depth0 != null ? depth0.secureDropLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"secureDropLabel","hash":{},"data":data}) : helper)))
    + "</label>\n					</span>\n				</li>\n";
},"19":function(container,depth0,helpers,partials,data) {
    var stack1;

  return ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.isPasswordForMailSharesRequired : depth0),{"name":"if","hash":{},"fn":container.program(20, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "");
},"20":function(container,depth0,helpers,partials,data) {
    return "disabled=\"\"";
},"22":function(container,depth0,helpers,partials,data) {
    return "hidden";
},"24":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "				<li>\n					<span class=\"menuitem\">\n						<input id=\"passwordByTalk-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\" type=\"checkbox\" name=\"passwordByTalk\" class=\"passwordByTalk checkbox\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isPasswordByTalkSet : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + " />\n						<label for=\"passwordByTalk-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.passwordByTalkLabel || (depth0 != null ? depth0.passwordByTalkLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordByTalkLabel","hash":{},"data":data}) : helper)))
    + "</label>\n					</span>\n				</li>\n				<li class=\"passwordByTalkMenu-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + " "
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.isPasswordByTalkSet : depth0),{"name":"unless","hash":{},"fn":container.program(22, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\">\n					<span class=\"passwordByTalkContainer-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + " icon-passwordtalk menuitem\">\n						<label for=\"passwordByTalkField-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\" class=\"hidden-visually\" value=\""
    + alias4(((helper = (helper = helpers.password || (depth0 != null ? depth0.password : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"password","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.passwordByTalkLabel || (depth0 != null ? depth0.passwordByTalkLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordByTalkLabel","hash":{},"data":data}) : helper)))
    + "</label>\n						<input id=\"passwordByTalkField-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\" class=\"passwordField\" type=\"password\" placeholder=\""
    + alias4(((helper = (helper = helpers.passwordByTalkPlaceholder || (depth0 != null ? depth0.passwordByTalkPlaceholder : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordByTalkPlaceholder","hash":{},"data":data}) : helper)))
    + "\" value=\""
    + alias4(((helper = (helper = helpers.passwordValue || (depth0 != null ? depth0.passwordValue : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"passwordValue","hash":{},"data":data}) : helper)))
    + "\" autocomplete=\"new-password\" />\n						<span class=\"icon-loading-small hidden\"></span>\n					</span>\n				</li>\n";
},"26":function(container,depth0,helpers,partials,data) {
    var helper;

  return container.escapeExpression(((helper = (helper = helpers.expireDate || (depth0 != null ? depth0.expireDate : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"expireDate","hash":{},"data":data}) : helper)));
},"28":function(container,depth0,helpers,partials,data) {
    var helper;

  return container.escapeExpression(((helper = (helper = helpers.defaultExpireDate || (depth0 != null ? depth0.defaultExpireDate : depth0)) != null ? helper : helpers.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"defaultExpireDate","hash":{},"data":data}) : helper)));
},"30":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "			<li>\n				<a href=\"#\" class=\"share-add\">\n					<span class=\"icon-loading-small hidden\"></span>\n					<span class=\"icon icon-edit\"></span>\n					<span>"
    + alias4(((helper = (helper = helpers.addNoteLabel || (depth0 != null ? depth0.addNoteLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"addNoteLabel","hash":{},"data":data}) : helper)))
    + "</span>\n					<input type=\"button\" class=\"share-note-delete icon-delete "
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.hasNote : depth0),{"name":"unless","hash":{},"fn":container.program(22, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\">\n				</a>\n			</li>\n			<li class=\"share-note-form "
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.hasNote : depth0),{"name":"unless","hash":{},"fn":container.program(22, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\">\n				<span class=\"menuitem icon-note\">\n					<textarea class=\"share-note\">"
    + alias4(((helper = (helper = helpers.shareNote || (depth0 != null ? depth0.shareNote : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareNote","hash":{},"data":data}) : helper)))
    + "</textarea>\n					<input type=\"submit\" class=\"icon-confirm share-note-submit\" value=\"\" id=\"add-note-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\" />\n				</span>\n			</li>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "<div class=\"popovermenu bubble hidden menu\">\n	<ul>\n		"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isResharingAllowed : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isFolder : depth0),{"name":"if","hash":{},"fn":container.program(6, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isMailShare : depth0),{"name":"if","hash":{},"fn":container.program(16, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "		<li>\n			<span class=\"menuitem\">\n				<input id=\"expireDate-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\" type=\"checkbox\" name=\"expirationDate\" class=\"expireDate checkbox\" "
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasExpireDate : depth0),{"name":"if","hash":{},"fn":container.program(4, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\" />\n				<label for=\"expireDate-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.expireDateLabel || (depth0 != null ? depth0.expireDateLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"expireDateLabel","hash":{},"data":data}) : helper)))
    + "</label>\n			</span>\n		</li>\n		<li class=\"expirationDateMenu-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + " "
    + ((stack1 = helpers.unless.call(alias1,(depth0 != null ? depth0.hasExpireDate : depth0),{"name":"unless","hash":{},"fn":container.program(22, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "\">\n			<span class=\"expirationDateContainer-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + " icon-expiredate menuitem\">\n				<label for=\"expirationDatePicker-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\" class=\"hidden-visually\" value=\""
    + alias4(((helper = (helper = helpers.expirationDate || (depth0 != null ? depth0.expirationDate : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"expirationDate","hash":{},"data":data}) : helper)))
    + "\">"
    + alias4(((helper = (helper = helpers.expirationLabel || (depth0 != null ? depth0.expirationLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"expirationLabel","hash":{},"data":data}) : helper)))
    + "</label>\n				<input id=\"expirationDatePicker-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "-"
    + alias4(((helper = (helper = helpers.shareId || (depth0 != null ? depth0.shareId : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareId","hash":{},"data":data}) : helper)))
    + "\" class=\"datepicker\" type=\"text\" placeholder=\""
    + alias4(((helper = (helper = helpers.expirationDatePlaceholder || (depth0 != null ? depth0.expirationDatePlaceholder : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"expirationDatePlaceholder","hash":{},"data":data}) : helper)))
    + "\" value=\""
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.hasExpireDate : depth0),{"name":"if","hash":{},"fn":container.program(26, data, 0),"inverse":container.program(28, data, 0),"data":data})) != null ? stack1 : "")
    + "\" />\n			</span>\n		</li>\n"
    + ((stack1 = helpers["if"].call(alias1,(depth0 != null ? depth0.isNoteAvailable : depth0),{"name":"if","hash":{},"fn":container.program(30, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "		<li>\n			<a href=\"#\" class=\"unshare\"><span class=\"icon-loading-small hidden\"></span><span class=\"icon icon-delete\"></span><span>"
    + alias4(((helper = (helper = helpers.unshareLabel || (depth0 != null ? depth0.unshareLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"unshareLabel","hash":{},"data":data}) : helper)))
    + "</span></a>\n		</li>\n	</ul>\n</div>\n";
},"useData":true});
templates['sharedialogview'] = template({"1":function(container,depth0,helpers,partials,data) {
    var helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=helpers.helperMissing, alias3="function", alias4=container.escapeExpression;

  return "	<label for=\"shareWith-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\" class=\"hidden-visually\">"
    + alias4(((helper = (helper = helpers.shareLabel || (depth0 != null ? depth0.shareLabel : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"shareLabel","hash":{},"data":data}) : helper)))
    + "</label>\n	<div class=\"oneline\">\n		<input id=\"shareWith-"
    + alias4(((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data}) : helper)))
    + "\" class=\"shareWithField\" type=\"text\" placeholder=\""
    + alias4(((helper = (helper = helpers.sharePlaceholder || (depth0 != null ? depth0.sharePlaceholder : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"sharePlaceholder","hash":{},"data":data}) : helper)))
    + "\" />\n		<span class=\"shareWithLoading icon-loading-small hidden\"></span>\n		<span class=\"shareWithConfirm icon icon-confirm\"></span>\n	</div>\n";
},"compiler":[7,">= 4.0.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1;

  return "<div class=\"resharerInfoView subView\"></div>\n"
    + ((stack1 = helpers["if"].call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? depth0.isSharingAllowed : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data})) != null ? stack1 : "")
    + "<div class=\"linkShareView subView\"></div>\n<div class=\"shareeListView subView\"></div>\n<div class=\"loading hidden\" style=\"height: 50px\"></div>\n";
},"useData":true});
})();