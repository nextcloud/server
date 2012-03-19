<?php

OC::$CLASSPATH['OC_Admin_Audit_Hooks_Handlers'] = 'apps/admin_audit/lib/hooks_handlers.php';

OCP\Util::connectHook(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_rename, 'OC_Admin_Audit_Hooks_Handlers', 'rename');
OCP\Util::connectHook(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_create, 'OC_Admin_Audit_Hooks_Handlers', 'create');
OCP\Util::connectHook(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_copy, 'OC_Admin_Audit_Hooks_Handlers', 'copy');
OCP\Util::connectHook(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_write, 'OC_Admin_Audit_Hooks_Handlers', 'write');
OCP\Util::connectHook(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_read, 'OC_Admin_Audit_Hooks_Handlers', 'read');
OCP\Util::connectHook(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_delete, 'OC_Admin_Audit_Hooks_Handlers', 'delete');
