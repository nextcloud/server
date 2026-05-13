```plantuml
repository Components {
    package Oauth2Component {
        repository Oauth2ComponentAPI {
            class Oauth2ComponentAPI {
                + GetOauth2TokenInputPort startOauth2TokenRetrievalInputPort()
            }
        }

        repository Features {
            repository GetOauth2Token {
                repository GetOauth2TokenPublic {
                    repository GetOauth2TokenUseCase {
                        interface GetOauth2TokenInputPort {
                            + void getOauth2Token(GetOauth2TokenInput, GetOauth2TokenOutputPort)
                        }

                        class GetOauth2TokenInput {
                            - string grant_type
                            - string code
                            - string refresh_token
                            - string client_id
                            - string client_secret
                            + string getGrantType()
                            + self setGrantType(string grant_type)
                            + string getCode()
                            + self setCode(string code)
                            + string getRefreshToken()
                            + self setRefreshToken(string refresh_token)
                            + string getClientId()
                            + self setClientId(string client_id)
                            + string getClientSecret()
                            + self setClientSecret(string client_secret)
                        }

                        enum GetOauth2TokenAuthorisedGrantType {
                            case AUTHORIZATION_CODE = "authorization_code"
                            case REFRESH_TOKEN = "refresh_token"
                        }

                        class GetOauth2TokenOutput {
                            - string access_token
                            - string refresh_token
                            - string user_id
                            + string getAccessToken()
                            + self setAccessToken(string access_token)
                            + string getRefreshToken()
                            + self setRefreshToken(string refresh_token)
                            + string getUserId()
                            + self setUserId(string user_id)
                        }

                        interface GetOauth2TokenOutputPort {
                            + void retrieveGetOauth2TokenOutputPort(GetOauth2TokenOutputPort getOauth2TokenOutputPort)
                        }
                    }
                }

                repository GetOauth2TokenInternals {
                    repository GetOauth2TokenUseCase {
                        class GetOauth2TokenUseCase {
                            + construct(
                                GetOauth2TokenAccessTokenRepository getOauth2TokenAccessTokenRepository, 
                                GetOauth2TokenClientRepository getOauth2TokenClientRepository,
                                GetOauth2TokenDatabaseStateHandler getOauth2TokenDatabaseStateHandler,
                                GetOauth2TokenCryptographyHandler getOauth2TokenCryptographyHandler,
                                GetOauth2TokenTimeHandler getOauth2TokenTimeHandler,
                                GetOauth2TokenRandomGenerator getOauth2TokenRandomGenerator,
                                GetOauth2TokenApplicationTokenProvider getOauth2TokenApplicationTokenProvider
                            )
                            + void getOauth2Token(GetOauth2TokenInput getOauth2TokenInput, GetOauth2TokenOutputPort getOauth2TokenOutputPort)
                        }

                        interface GetOauth2TokenAccessTokenRepository {
                            + Oauth2AccessToken getAccessTokenByCode(string oauth2_access_token_hashed_code)
                            + void deleteAccessToken(integer oauth2_access_token_id)
                            + integer rotateToken(
                                integer oauth2_access_token_id,
                                string code,
                                string new_code,
                                string new_encrypted_token,
                                boolean is_grant_type_authorization_code
                            )
                        }

                        interface GetOauth2TokenApplicationTokenProvider {
                            + ApplicationToken getTokenById(integer token_id)
                            + ApplicationToken rotate(
                                ApplicationToken applicationToken, 
                                ApplicationToken decryptedToken,
                                ApplicationToken newToken
                            )
                            + void updateToken(ApplicationToken applicationToken)
                            + void invalidateToken(ApplicationToken applicationToken)
                        }

                        interface GetOauth2TokenClientRepository {
                            + Client getByUID(string client_UID)
                        }

                        interface GetOauth2TokenCryptographyHandler {
                            + string calculateHMAC(string client_secret)
                            + string encrypt(string value_to_encrypt, string secret_key)
                            + string decrypt(string encrypted_value, string secret_key)
                        }

                        interface GetOauth2TokenTimeHandler {
                            + integer getCurrentTimestamp()
                        }

                        interface GetOauth2TokenRandomGenerator {
                            + string generateToken()
                            + string generateCode()
                        }

                        interface GetOauth2TokenDatabaseStateHandler {
                            + void conserveDatabaseState()
                            + void revertDatabaseChanges()
                            + void commitDatabaseChanges()
                        }
                    }

                    repository GetOauth2TokenMain {
                        repository Database {
                            class Oauth2AccessTokenDatabaseOnQBMapper implements GetOauth2TokenAccessTokenRepository {
                                - AccessTokenMapper
                                + Oauth2AccessToken getAccessTokenByCode(string oauth2_access_token_hashed_code)
                                + void deleteAccessToken(integer oauth2_access_token_id)
                                + integer rotateToken(
                                    integer oauth2_access_token_id,
                                    string code,
                                    string new_code,
                                    string new_encrypted_token,
                                    boolean is_grant_type_authorization_code
                                )
                            }

                            class GetOauth2TokenClientMapper implements GetOauth2TokenClientRepository {
                                + Client getByUID(string client_UID)
                            }

                            class GetOauth2TokenDatabaseOnMySQLHandler implements GetOauth2TokenDatabaseStateHandler {
                                + void conserveDatabaseState()
                                + void revertDatabaseChanges()
                                + void commitDatabaseChanges()
                            }
                        }

                        repository Security {
                            class GetOauth2TokenInternalCryptographyHandler implements GetOauth2TokenCryptographyHandler {
                                + string calculateHMAC(string client_secret)
                                + string encrypt(string value_to_encrypt, string secret_key)
                                + string decrypt(string encrypted_value, string secret_key)
                            }

                            class GetOauth2TokenInternalSecureRandomGenerator implements GetOauth2TokenRandomGenerator {
                                + string generateToken()
                                + string generateCode()
                            }
                        }

                        repository Time {
                            class class GetOauth2TokenUtilityTimeFactory implements GetOauth2TokenTimeHandler {
                                + integer getCurrentTimestamp()
                            }
                        }

                        repository TokenProvider {
                            class GetOauth2TokenTokenProvider implements GetOauth2TokenApplicationTokenProvider {
                                // See lib/private/Authentication/Token/PublicKeyTokenProvider.php
                                + Oauth2ApplicationToken getTokenById(integer token_id)
                                + Oauth2ApplicationToken rotate(
                                    Oauth2ApplicationToken oauth2ApplicationToken, 
                                    string old_token_id,
                                    string new_token_id
                                )
                                + void updateToken(Oauth2ApplicationToken oauth2ApplicationToken)
                                + void invalidateToken(Oauth2ApplicationToken oauth2ApplicationToken)
                            }
                        }
                    }
                }
            }
        }
    }
}

repository Exceptions {
    repository UseCaseExceptions {
        // Used by use cases' input Data Transfer Objects
        class UnauthorisedActionException {
            - string error_message
            - string custom_error_code
            + string getErrorMessage()
            + string getCustomErrorCode()
            + self setCustomErrorCode(string custom_error_code)
        }

        // Used by use cases' output Data Transfer Objects
        class InternalErrorException {
            - string error_message
            - string custom_error_code
            + string getErrorMessage()
            + string getCustomErrorCode()
            + self setCustomErrorCode(string custom_error_code)
        }
    }
}
```