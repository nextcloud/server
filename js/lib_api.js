/**
* ownCloud - ajax frontend
*
* @author Robin Appelman
* @copyright 2010 Robin Appelman icewind1991@gmail.com
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
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

OC_API=new Object();

OC_API.run=function(action,params,callback,callbackparams){
	var xmlloader=new OCXMLLoader();
	xmlloader.setCallBack(callback);
	xmlloader.method="POST";
	var paramString='action='+action;
	for(name in params){
		paramString+='&'+name+'='+encodeURIComponent(params[name]);
	}
	xmlloader.arg=callbackparams;
	xmlloader.load('files/api.php',paramString);
}