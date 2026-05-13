```plantuml
repository Domain {
    repository Oauth2AccessToken {
        class Oauth2AccessToken {
            - Oauth2AccessTokenId oauth2AccessTokenId
            - Oauth2AccessTokenTokenId oauth2AccessTokenTokenId
            - Oauth2AccessTokenClientId oauth2AccessTokenClientId
            - Oauth2AccessTokenHashedCode oauth2AccessTokenHashedCode
            - Oauth2AccessTokenEncryptedToken oauth2AccessTokenEncryptedToken
            - Oauth2AccessTokenCodeCreationTimestamp oauth2AccessTokenCodeCreationTimestamp
            - Oauth2AccessTokenTokenCount oauth2AccessTokenTokenCount
            + construct(
                Oauth2AccessTokenId oauth2AccessTokenId,
                Oauth2AccessTokenTokenId oauth2AccessTokenTokenId,
                Oauth2AccessTokenClientId oauth2AccessTokenClientId,
                Oauth2AccessTokenHashedCode oauth2AccessTokenHashedCode,
                Oauth2AccessTokenEncryptedToken oauth2AccessTokenEncryptedToken,
                Oauth2AccessTokenCodeCreationTimestamp oauth2AccessTokenCodeCreationTimestamp,
                Oauth2AccessTokenTokenCount oauth2AccessTokenTokenCount
            )
            + boolean isTokenInAuthorizationCodeState()
            + boolean isTokenExpired(integer current_timestamp)
            + integer retrieveId()
            + integer retrieveTokenId()
            + integer retrieveClientId()
            + string retrieveEncryptedToken()
        }

        repository ValueObjects {
            class Oauth2AccessTokenId {
                - integer oauth2_access_token_id
                + integer getOauth2AccessTokenId()
                + self setOauth2AccessTokenId(integer oauth2_access_token_id)
            }

            class Oauth2AccessTokenTokenId {
                - integer oauth2_access_token_token_id
                + integer getOauth2AccessTokenTokenId()
                + self setOauth2AccessTokenTokenId(integer oauth2_access_token_token_id)
            }

            class Oauth2AccessTokenClientId {
                - integer oauth2_access_token_client_id
                + integer getOauth2AccessTokenClientId()
                + self setOauth2AccessTokenClientId(integer oauth2_access_token_client_id)
            }

            class Oauth2AccessTokenHashedCode {
                - string oauth2_access_token_hashed_code
                + string getOauth2AccessTokenHashedCode()
                + self setOauth2AccessTokenHashedCode(string oauth2_access_token_hashed_code)
            }

            class Oauth2AccessTokenEncryptedToken {
                - string oauth2_access_token_encrypted_token
                + string getOauth2AccessTokenEncryptedToken()
                + self setOauth2AccessTokenEncryptedToken(string oauth2_access_token_encrypted_token)
            }

            class Oauth2AccessTokenCodeCreationTimestamp {
                - integer oauth2_access_token_code_creation_timestamp
                + integer getOauth2AccessTokenCodeCreationTimestamp()
                + self setOauth2AccessTokenCodeCreationTimestamp(integer oauth2_access_token_code_creation_timestamp)
            }

            class Oauth2AccessTokenTokenCount {
                - integer oauth2_access_token_token_count
                + integer getOauth2AccessTokenTokenCount()
                + self setOauth2AccessTokenTokenCount(integer oauth2_access_token_token_count)
            }
        }

        repository Exceptions {
            repository ValueObjects {
                class InvalidOauth2AccessTokenIdException {
                    - string error_message
                    - string custom_error_code
                    + string getErrorMessage()
                    + string getCustomErrorCode()
                    + self setCustomErrorCode(string custom_error_code)
                }

                class InvalidOauth2AccessTokenTokenIdException {
                    - string error_message
                    - string custom_error_code
                    + string getErrorMessage()
                    + string getCustomErrorCode()
                    + self setCustomErrorCode(string custom_error_code)
                }

                class InvalidOauth2AccessTokenClientIdException {
                    - string error_message
                    - string custom_error_code
                    + string getErrorMessage()
                    + string getCustomErrorCode()
                    + self setCustomErrorCode(string custom_error_code)
                }

                class InvalidOauth2AccessTokenHashedCodeException {
                    - string error_message
                    - string custom_error_code
                    + string getErrorMessage()
                    + string getCustomErrorCode()
                    + self setCustomErrorCode(string custom_error_code)
                }

                class InvalidOauth2AccessTokenEncryptedTokenException {
                    - string error_message
                    - string custom_error_code
                    + string getErrorMessage()
                    + string getCustomErrorCode()
                    + self setCustomErrorCode(string custom_error_code)
                }

                class InvalidOauth2AccessTokenCodeCreationTimestampException {
                    - string error_message
                    - string custom_error_code
                    + string getErrorMessage()
                    + string getCustomErrorCode()
                    + self setCustomErrorCode(string custom_error_code)
                }

                class InvalidOauth2AccessTokenTokenCountException {
                    - string error_message
                    - string custom_error_code
                    + string getErrorMessage()
                    + string getCustomErrorCode()
                    + self setCustomErrorCode(string custom_error_code)
                }

                // ... etc
            }
        }
    }

    repository Client {
        class Client {
            - ClientSecret clientSecret
            - ClientIdentifier clientIdentifier
            + boolean clientIdentifierMatches(ClientIdentifier clientIdentifierToCompareTo)
            + boolean clientSecretHashMatches(ClientSecret clientSecretToCompareTo)
            + string retrieveClientSecret()
        }

        repository ValueObjects {
            class ClientSecret {
                - string client_secret
                + string getClientSecret()
                + self setClientSecret(string client_secret)
            }

            class ClientIdentifier {
                - integer client_identifier
                + integer getClientIdentifier()
                + self setClientIdentifier(integer client_identifier)
            }
        }

        repository Exceptions {
            repository ValueObjects {
                class InvalidClientSecretException {
                    - string error_message
                    - string custom_error_code
                    + string getErrorMessage()
                    + string getCustomErrorCode()
                    + self setCustomErrorCode(string custom_error_code)
                }

                class InvalidClientIdentifierException {
                    - string error_message
                    - string custom_error_code
                    + string getErrorMessage()
                    + string getCustomErrorCode()
                    + self setCustomErrorCode(string custom_error_code)
                }
            }
        }
    }

    repository Oauth2ApplicationToken {
        repository ValueObjects {
            class Oauth2ApplicationToken {
                // See lib/private/Authentication/Token/PublicKeyToken.php
                - string uid
                - string login_name
                - string password
                - string password_hash
                - string name
                - string token
                - integer type
                - integer remember
                - integer last_activity
                - integer last_check
                - string scope
                - integer expires
                - string public_key
                - string private_key
                - integer version
                - boolean is_password_valid
                + string getUID()
                + self setUID(string uid)
                + string getLoginName()
                + self setLoginName(string login_name)
                + string getPassword()
                + self setPassword(string password)
                + string getPasswordHash()
                + self setPasswordHash(string password_hash)
                + string getName()
                + self setName(string name)
                + string getToken()
                + self setToken(string token)
                + integer getType()
                + self setType(integer type)
                + integer getRemember()
                + self setRemember(integer remember)
                + integer getLastActivity()
                + self setLastActivity(integer last_activity)
                + integer getLastCheck()
                + self setLastCheck(integer last_check)
                + string getScope()
                + self setScope(string scope)
                + integer getExpires()
                + self setExpires(integer expires)
                + string getPublicKey()
                + self setPublicKey(string public_key)
                + string getPrivateKey()
                + self setPrivateKey(string private_key)
                + integer getVersion()
                + self setVersion(integer version)
                + boolen isPasswordValid()
                + self setWhetherPasswordIsValid(boolean is_password_valid)
            }
        }

        repository Exceptions {
            repository ValueObjects {
                class InvalidOauth2ApplicationTokenUIDException {
                    - string error_message
                    - string custom_error_code
                    + string getErrorMessage()
                    + string getCustomErrorCode()
                    + self setCustomErrorCode(string custom_error_code)
                }

                class InvalidOauth2ApplicationTokenLoginNameException {
                    - string error_message
                    - string custom_error_code
                    + string getErrorMessage()
                    + string getCustomErrorCode()
                    + self setCustomErrorCode(string custom_error_code)
                }

                class InvalidOauth2ApplicationTokenPasswordException {
                    - string error_message
                    - string custom_error_code
                    + string getErrorMessage()
                    + string getCustomErrorCode()
                    + self setCustomErrorCode(string custom_error_code)
                }

                // ... etc
            }
        }
    }
}
```