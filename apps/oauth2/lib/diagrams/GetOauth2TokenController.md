```plantuml
repository Controller {
    repository Oauth2Controller {
        package Oauth2WebController {
            // GetOauth2TokenInputPort is defined in the Features/GetOauth2Token/GetOauth2TokenPublic/GetOauth2TokenUseCase repository
            class Oauth2WebController {
                + Oauth2WebControllerHTTPResponse getOauth2Token(GetOauth2TokenInputPort getOauth2TokenInputPort, Oauth2WebControllerHTTPRequest oauth2WebControllerHTTPRequest)
            }

            class Oauth2WebControllerHTTPRequest {
                - Oauth2WebControllerHTTPRequestBody oauth2WebControllerHTTPRequestBody
                + Oauth2WebControllerHTTPRequestBody getRequestBody()
                + self setRequestBody(Oauth2WebControllerHTTPRequestBody oauth2WebControllerHTTPRequestBody)
            }

            class Oauth2WebControllerHTTPRequestBody {
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

            class Oauth2WebControllerHTTPResponse {
                - integer response_code
                - Oauth2WebControllerHTTPResponseBody oauth2WebControllerHTTPResponseBody
                + integer getResponseCode()
                + self setResponseCode(integer response_code)
                + Oauth2WebControllerHTTPResponseBody getResponseBody()
                + self setResponseBody(Oauth2WebControllerHTTPResponseBody oauth2WebControllerHTTPResponseBody)
            }

            class Oauth2WebControllerHTTPResponseBody {
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

            // GetOauth2TokenOutputPort is defined in the Features/GetOauth2Token/GetOauth2TokenPublic/GetOauth2TokenUseCase repository
            class Oauth2WebControllerHTTPPresenter implements GetOauth2TokenOutputPort {
                + void retrieveGetOauth2TokenOutputPort(GetOauth2TokenOutputPort getOauth2TokenOutputPort)
                + Oauth2WebControllerHTTPResponse getHTTPResponse()
            }
        }
    }

    respository Exceptions {
        // Used by contollers' input Data Transfer Objects
        class BadRequestException {
            - string error_message
            - string custom_error_code
            + string getErrorMessage()
            + string getCustomErrorCode()
            + self setCustomErrorCode(string custom_error_code)
        }
    }
}
```