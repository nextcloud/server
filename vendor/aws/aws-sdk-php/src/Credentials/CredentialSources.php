<?php

namespace Aws\Credentials;

/**
 * @internal
 */
final class CredentialSources
{
    const STATIC = 'static';
    const ENVIRONMENT = 'env';
    const STS_WEB_ID_TOKEN = 'sts_web_id_token';
    const ENVIRONMENT_STS_WEB_ID_TOKEN = 'env_sts_web_id_token';
    const PROFILE_STS_WEB_ID_TOKEN = 'profile_sts_web_id_token';
    const STS_ASSUME_ROLE = 'sts_assume_role';
    const PROFILE = 'profile';
    const IMDS = 'instance_profile_provider';
    const ECS = 'ecs';
    const PROFILE_SSO = 'profile_sso';
    const PROFILE_SSO_LEGACY = 'profile_sso_legacy';
    const PROFILE_PROCESS = 'profile_process';
}
