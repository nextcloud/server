/**
* @package shorty an ownCloud url shortener plugin
* @category internet
* @author Christian Reiner
* @copyright 2011-2012 Christian Reiner <foss@christian-reiner.info>
* @license GNU Affero General Public license (AGPL)
* @link information 
* @link repository https://svn.christian-reiner.info/svn/app/oc/shorty
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the license, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.
* If not, see <http://www.gnu.org/licenses/>.
*
*/

/**
 * @file js/add.js
 * @brief Client side desktop initialization in case of a call with an url to add
 * @author Christian Reiner
 */

$(document).ready(function(){
  // initialize desktop
  var dialog = $('#dialog-add');
  $.when(
    Shorty.WUI.Controls.init()
  ).pipe(function(){
    Shorty.WUI.List.build();
  }).done(function(){
    $.when(
      Shorty.WUI.Dialog.toggle(dialog)
    ).done(function(){
      // any referrer handed over from php (explicitly in markup) ?
      var target=$('#controls').attr('data-referrer');
      $('#controls').removeAttr('data-referrer');
      dialog.find('#target').val(target);
      dialog.find('#title').focus();
      Shorty.WUI.Meta.collect(dialog);
    })
  })
}); // document.ready
