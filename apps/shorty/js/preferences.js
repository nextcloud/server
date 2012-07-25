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
 * @file js/preferences.js
 * @brief Client side activity initialization for the user preferences dialog
 * @author Christian Reiner
 */

$(document).ready(function(){
  // backend preferences, activate hints for currently selected backend
  var type=$('#shorty #backend-type').val()||'';
  if (type.length){
    $('#shorty #backend-'+type).show();
  }
  // backend 'static': initialize example that depends on backend-base system setting
  if ($('#shorty #backend-static #backend-static-base').val().length)
    $('#shorty #backend-static #example').text($('#shorty #backend-static #backend-static-base').val()+'<shorty id>');
  // backend 'static': offer a clickable example link to verify the correct setup
  $('#shorty #backend-static #example').bind('click',function(event){
    event.preventDefault();
    Shorty.Action.Setting.verify();
  });
  // react with a matching explanation and example url when backend type is chosen
  $('.chosen').chosen();
  $('#shorty #backend-type').change(
    function(){
      var type=$('#shorty #backend-type').val();
      $('#shorty .backend-supplement').hide();
      if (type.length){
        $('#shorty .backend-supplement').filter('#backend-'+type).fadeIn('slow');
        // save preference
        Shorty.Action.Preference.set($('#shorty #backend-type').serialize());
        return false;
      }
    }
  );
  // safe preferences
  $('#shorty .backend-supplement').focusout(function(){
    // save preference
    Shorty.Action.Preference.set($(this).find('input').serialize());
  });
  // safe sms-control
  $('#shorty #sms-control').change(function(){
    Shorty.Action.Preference.set($('#shorty #sms-control').serialize());
  });
});
