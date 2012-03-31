/**
 * ownCloud
 *
 * @author Bartek Przybylski
 * @copyright 2012 Bartek Przybylski bartek@alefzero.eu
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 * todo(bartek): add select option in form
 */

/**
 * this class ease usage of jquery dialogs
 */
OCdialogs = {
  /**
   * displays alert dialog
   * @param text content of dialog
   * @param title dialog title
   * @param callback which will be triggered when user press OK
   */
  alert:function(text, title, callback) {
    var content = '<p><span class="ui-icon ui-icon-alert"></span>'+text+'</p>';
    OCdialogs.message(content, title, OCdialogs.ALERT_DIALOG, OCdialogs.OK_BUTTON, callback);
  },
  /**
   * displays info dialog
   * @param text content of dialog
   * @param title dialog title
   * @param callback which will be triggered when user press OK
   */
  info:function(text, title, callback) {
    var content = '<p><span class="ui-icon ui-icon-info"></span>'+text+'</p>';
    OCdialogs.message(content, title, OCdialogs.ALERT_DIALOG, OCdialogs.OK_BUTTON, callback);
  },
  /**
   * displays confirmation dialog
   * @param text content of dialog
   * @param title dialog title
   * @param callback which will be triggered when user press YES or NO (true or false would be passed to callback respectively)
   */
  confirm:function(text, title, callback) {
    var content = '<p><span class="ui-icon ui-icon-notice"></span>'+text+'</p>';
    OCdialogs.message(content, title, OCdialogs.ALERT_DIALOG, OCdialogs.YES_NO_BUTTONS, callback);
  },
  /**
   * prompt for user input
   * @param text content of dialog
   * @param title dialog title
   * @param callback which will be triggered when user press OK (input text will be passed to callback)
   */
  prompt:function(text, title, default_value, callback) {
    var content = '<p><span class="ui-icon ui-icon-pencil"></span>'+text+':<br/><input type="text" id="oc-dialog-prompt-input" value="'+default_value+'" style="width:90%"></p>';
    OCdialogs.message(content, title, OCdialogs.PROMPT_DIALOG, OCdialogs.OK_CANCEL_BUTTONS, callback);
  },
  /**
   * prompt user for input with custom form
   * fields should be passed in following format: [{text:'prompt text', name:'return name', type:'input type', value: 'dafault value'},...]
   * @param fields to display 
   * @param title dialog title
   * @param callback which will be triggered when user press OK (user answers will be passed to callback in following format: [{name:'return name', value: 'user value'},...])
   */
  form:function(fields, title, callback) {
    var content = '<table>';
    for (var a in fields) {
      content += '<tr><td>'+fields[a].text+'</td><td>';
      var type=fields[a].type;
      if (type == 'text' || type == 'checkbox' || type == 'password') {
          content += '<input type="'+type+'" name="'+fields[a].name+'"';
          if (type == 'checkbox') {
            if (fields[a].value != undefined && fields[a].value == true) {
              content += ' checked="checked">';
            } else content += '>';
          } else if (type == 'text' || type == 'password' && fields[a].value)
            content += ' value="'+fields[a].value+'">';
      } else if (type == 'select') {
        content += '<select name="'+fields[a].name+'"';
        if (fields[a].value != undefined)
          content += ' value="'+fields[a].value+'"';
        content += '>';
        for (var o in fields[a].options)
          content += '<option value="'+fields[a].options[o].value+'">'+fields[a].options[o].text+'</option>';
        content += '</select>';
      }
      content += '</td></tr>';
    }
    content += '</table>';
    OCdialogs.message(content, title, OCdialogs.FORM_DIALOG, OCdialogs.OK_CANCEL_BUTTONS, callback);
  },
  message:function(content, title, dialog_type, buttons, callback) {
    var c_name = 'oc-dialog-'+OCdialogs.dialogs_counter+'-content';
    var c_id = '#'+c_name;
    var d = '<div id="'+c_name+'" title="'+title+'">'+content+'</div>';
    $('body').append(d);
    var b = [];
    switch (buttons) {
      case OCdialogs.YES_NO_BUTTONS:
        b[1] = {text: t('dialogs', 'No'), click: function(){ if (callback != undefined) callback(false); $(c_id).dialog('close'); }};
        b[0] = {text: t('dialogs', 'Yes'), click: function(){ if (callback != undefined) callback(true); $(c_id).dialog('close');}};
      break;
      case OCdialogs.OK_CANCEL_BUTTONS:
        b[1] = {text: t('dialogs', 'Cancel'), click: function(){$(c_id).dialog('close'); }};
      case OCdialogs.OK_BUTTON: // fallthrough
        var f;
        switch(dialog_type) {
          case OCdialogs.ALERT_DIALOG:
            f = function(){$(c_id).dialog('close'); callback();};
          break;
          case OCdialogs.PROMPT_DIALOG:
            f = function(){OCdialogs.prompt_ok_handler(callback, c_id)};
          break;
          case OCdialogs.FORM_DIALOG:
            f = function(){OCdialogs.form_ok_handler(callback, c_id)};
          break;
        }
        b[0] = {text: t('dialogs', 'Ok'), click: f};
      break;
    }
    var possible_height = ($('tr', d).size()+1)*30;
    $(c_id).dialog({width: 4*$(document).width()/9, height: possible_height + 120, modal: false, buttons: b});
    OCdialogs.dialogs_counter++;
  },
  // dialogs buttons types
  YES_NO_BUTTONS: 70,
  OK_BUTTONS: 71,
  OK_CANCEL_BUTTONS: 72,
  // dialogs types
  ALERT_DIALOG: 80,
  INFO_DIALOG: 81,
  PROMPT_DIALOG: 82,
  FORM_DIALOG: 83,
  dialogs_counter: 0,
  determineValue: function(element) {
    switch ($(element).attr('type')) {
      case 'checkbox': return element.checked;
    }
    return $(element).val();
  },
  prompt_ok_handler: function(callback, c_id) { $(c_id).dialog('close'); if (callback != undefined) callback($(c_id + " input#oc-dialog-prompt-input").val()); },
  form_ok_handler: function(callback, c_id) {
    if (callback != undefined) {
      var r = [];
      var c = 0;
      $(c_id + ' input, '+c_id+' select').each(function(i, elem) {
        r[c] = {name: $(elem).attr('name'), value: OCdialogs.determineValue(elem)};
        c++;
      });
      $(c_id).dialog('close');
      callback(r);
    } else {
      $(c_id).dialog('close');
    }
  }
};
