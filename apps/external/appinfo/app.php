<?php

/**
* ownCloud - External plugin
*
* @author Frank Karlitschek
* @copyright 2011 Frank Karlitschek karlitschek@kde.org
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

OC_APP::registerAdmin('external','settings');

OC_App::register( array( 'order' => 70, 'id' => 'external', 'name' => 'External' ));

if(OC_Appconfig::getValue( "external","site1name", '' )<>'')  OC_App::addNavigationEntry( array( 'id' => 'external_index1', 'order' => 80, 'href' => OC_Helper::linkTo( 'external', 'index.php' ).'?id=1', 'icon' => OC_Helper::imagePath( 'external', 'external.png' ), 'name' => OC_Appconfig::getValue( "external","site1name", '' )));

if(OC_Appconfig::getValue( "external","site2name", '' )<>'')  OC_App::addNavigationEntry( array( 'id' => 'external_index2', 'order' => 80, 'href' => OC_Helper::linkTo( 'external', 'index.php' ).'?id=2', 'icon' => OC_Helper::imagePath( 'external', 'external.png' ), 'name' => OC_Appconfig::getValue( "external","site2name", '' )));

if(OC_Appconfig::getValue( "external","site3name", '' )<>'')  OC_App::addNavigationEntry( array( 'id' => 'external_index3', 'order' => 80, 'href' => OC_Helper::linkTo( 'external', 'index.php' ).'?id=3', 'icon' => OC_Helper::imagePath( 'external', 'external.png' ), 'name' => OC_Appconfig::getValue( "external","site3name", '' )));

if(OC_Appconfig::getValue( "external","site4name", '' )<>'')  OC_App::addNavigationEntry( array( 'id' => 'external_index4', 'order' => 80, 'href' => OC_Helper::linkTo( 'external', 'index.php' ).'?id=4', 'icon' => OC_Helper::imagePath( 'external', 'external.png' ), 'name' => OC_Appconfig::getValue( "external","site4name", '' )));

if(OC_Appconfig::getValue( "external","site5name", '' )<>'')  OC_App::addNavigationEntry( array( 'id' => 'external_index5', 'order' => 80, 'href' => OC_Helper::linkTo( 'external', 'index.php' ).'?id=5', 'icon' => OC_Helper::imagePath( 'external', 'external.png' ), 'name' => OC_Appconfig::getValue( "external","site5name", '' )));

