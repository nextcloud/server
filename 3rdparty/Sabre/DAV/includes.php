<?php

/**
 * Sabre_DAV includes file
 *
 * Including this file will automatically include all files from the Sabre_DAV
 * package.
 *
 * This often allows faster loadtimes, as autoload-speed is often quite slow.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */

// Begin includes
include __DIR__ . '/Auth/IBackend.php';
include __DIR__ . '/Client.php';
include __DIR__ . '/Exception.php';
include __DIR__ . '/INode.php';
include __DIR__ . '/IProperties.php';
include __DIR__ . '/Locks/Backend/Abstract.php';
include __DIR__ . '/Locks/Backend/File.php';
include __DIR__ . '/Locks/Backend/FS.php';
include __DIR__ . '/Locks/Backend/PDO.php';
include __DIR__ . '/Locks/LockInfo.php';
include __DIR__ . '/Node.php';
include __DIR__ . '/Property/IHref.php';
include __DIR__ . '/Property.php';
include __DIR__ . '/Server.php';
include __DIR__ . '/ServerPlugin.php';
include __DIR__ . '/StringUtil.php';
include __DIR__ . '/TemporaryFileFilterPlugin.php';
include __DIR__ . '/Tree.php';
include __DIR__ . '/URLUtil.php';
include __DIR__ . '/UUIDUtil.php';
include __DIR__ . '/Version.php';
include __DIR__ . '/XMLUtil.php';
include __DIR__ . '/Auth/Backend/AbstractBasic.php';
include __DIR__ . '/Auth/Backend/AbstractDigest.php';
include __DIR__ . '/Auth/Backend/Apache.php';
include __DIR__ . '/Auth/Backend/File.php';
include __DIR__ . '/Auth/Backend/PDO.php';
include __DIR__ . '/Auth/Plugin.php';
include __DIR__ . '/Browser/GuessContentType.php';
include __DIR__ . '/Browser/MapGetToPropFind.php';
include __DIR__ . '/Browser/Plugin.php';
include __DIR__ . '/Exception/BadRequest.php';
include __DIR__ . '/Exception/Conflict.php';
include __DIR__ . '/Exception/Forbidden.php';
include __DIR__ . '/Exception/InsufficientStorage.php';
include __DIR__ . '/Exception/InvalidResourceType.php';
include __DIR__ . '/Exception/Locked.php';
include __DIR__ . '/Exception/LockTokenMatchesRequestUri.php';
include __DIR__ . '/Exception/MethodNotAllowed.php';
include __DIR__ . '/Exception/NotAuthenticated.php';
include __DIR__ . '/Exception/NotFound.php';
include __DIR__ . '/Exception/NotImplemented.php';
include __DIR__ . '/Exception/PaymentRequired.php';
include __DIR__ . '/Exception/PreconditionFailed.php';
include __DIR__ . '/Exception/ReportNotImplemented.php';
include __DIR__ . '/Exception/RequestedRangeNotSatisfiable.php';
include __DIR__ . '/Exception/UnsupportedMediaType.php';
include __DIR__ . '/FS/Node.php';
include __DIR__ . '/FSExt/Node.php';
include __DIR__ . '/ICollection.php';
include __DIR__ . '/IExtendedCollection.php';
include __DIR__ . '/IFile.php';
include __DIR__ . '/IQuota.php';
include __DIR__ . '/Locks/Plugin.php';
include __DIR__ . '/Mount/Plugin.php';
include __DIR__ . '/ObjectTree.php';
include __DIR__ . '/Property/GetLastModified.php';
include __DIR__ . '/Property/Href.php';
include __DIR__ . '/Property/HrefList.php';
include __DIR__ . '/Property/LockDiscovery.php';
include __DIR__ . '/Property/ResourceType.php';
include __DIR__ . '/Property/Response.php';
include __DIR__ . '/Property/ResponseList.php';
include __DIR__ . '/Property/SupportedLock.php';
include __DIR__ . '/Property/SupportedReportSet.php';
include __DIR__ . '/Tree/Filesystem.php';
include __DIR__ . '/Collection.php';
include __DIR__ . '/Directory.php';
include __DIR__ . '/Exception/ConflictingLock.php';
include __DIR__ . '/Exception/FileNotFound.php';
include __DIR__ . '/File.php';
include __DIR__ . '/FS/Directory.php';
include __DIR__ . '/FS/File.php';
include __DIR__ . '/FSExt/Directory.php';
include __DIR__ . '/FSExt/File.php';
include __DIR__ . '/SimpleCollection.php';
include __DIR__ . '/SimpleDirectory.php';
include __DIR__ . '/SimpleFile.php';
// End includes
