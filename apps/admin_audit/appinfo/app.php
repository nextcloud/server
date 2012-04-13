<?php

OC::$CLASSPATH['OC_Admin_Audit_Hooks_Handlers'] = 'apps/admin_audit/lib/hooks_handlers.php';

OCP\Util::connectHook('OCP\User', 'pre_login', 'OC_Admin_Audit_Hooks_Handlers', 'pre_login');
OCP\Util::connectHook('OCP\User', 'post_login', 'OC_Admin_Audit_Hooks_Handlers', 'post_login');
OCP\Util::connectHook('OCP\User', 'logout', 'OC_Admin_Audit_Hooks_Handlers', 'logout');

OCP\Util::connectHook(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_rename, 'OC_Admin_Audit_Hooks_Handlers', 'rename');
OCP\Util::connectHook(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_create, 'OC_Admin_Audit_Hooks_Handlers', 'create');
OCP\Util::connectHook(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_copy, 'OC_Admin_Audit_Hooks_Handlers', 'copy');
OCP\Util::connectHook(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_write, 'OC_Admin_Audit_Hooks_Handlers', 'write');
OCP\Util::connectHook(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_read, 'OC_Admin_Audit_Hooks_Handlers', 'read');
OCP\Util::connectHook(OC_Filesystem::CLASSNAME, OC_Filesystem::signal_delete, 'OC_Admin_Audit_Hooks_Handlers', 'delete');

OCP\Util::connectHook('OC_Share', 'public', 'OC_Admin_Audit_Hooks_Handlers', 'share_public');
OCP\Util::connectHook('OC_Share', 'public-download', 'OC_Admin_Audit_Hooks_Handlers', 'share_public_download');
OCP\Util::connectHook('OC_Share', 'user', 'OC_Admin_Audit_Hooks_Handlers', 'share_user');
