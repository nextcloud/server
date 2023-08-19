<?php
$CONFIG = array (
    'htaccess.RewriteBase' => '/',
    'default_language' => 'de_DE',
    'integrity.check.disabled' => true, // not recommended for prod, but for customisation
    // 'config_is_read_only' => true,
    'auth.authtoken.v1.disabled' => true,
    'sharing.force_share_accept' => true,
    'status-email-message-provider' => '\\OCA\\EmailTemplateExample\\MessageProvider',
    'mail_template_class' => 'OCA\\EmailTemplateExample\\EMailTemplate',
    // "logfile_office_report" => "/var/log/nextcloud/office.log",
);