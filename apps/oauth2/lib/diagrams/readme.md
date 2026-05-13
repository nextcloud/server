# Architecture Redesign

This readme aims at helping to navigate the architecture refactoring proposition. It might be subject to further editing in order to be more explicit over the architecture redesign structure or details.

## Inspiration

This redesign is strongly inspired by Robert C. Martin's *Clean Architecture* book and the Packaged by Component approach presented in the *Missing Chapter*.

## General structure

The structure aims at a strong separation of concerns and modularity. It allows to abstract what each feature should do without them depending on low-level specifications, although it might still be tied to the general PHP programming language.

### The "Main" layer

The "Main" layer, presented in the **GetOauth2TokenFeature.md** file under the GetOauth2TokenInternal/GetOauth2TokenMain repository, encapsulate low-level specifications. It implements interfaces declared in the "Use Case" layer.

### The "Controller" layer

The "Controller" layer, presented in the **GetOauth2TokenController.md** file, is an abstraction of the runtime. In this case, the refactored architecture will present a **GetOauth2TokenWebController** that will not be tied to internal database, framework, or any kind of library. It plays the role of adapter between the runtime declared in the "Main" layer and the "Use Case" layer.

### The "Use Case" layer

The "Use Case" layer, presented in the **GetOauth2TokenFeature.md** file under the GetOauth2TokenPublic/GetOauth2TokenUseCase and GetOauth2TokenInternal/GetOauth2TokenUseCase repositories, is an abstraction of the business logic and validation of the feature. It specifies what the feature should do without knowing anything of its low-level implementations.

For example, the Oauth2 token retrieval feature should:
- Check that the comunicated grant type matches the accepted grant types;
- Attempt to retrieve the AccessToken entity by informing the sent "code" parameter to a dedicated access token repository;
- Handle different feature behaviour based on what grant type was sent through the input;
- Call the AccessToken entity method to check the token authorization code state;
- etc...

The "Use Case" layer doesn't know if its using a SQL database, a specific validation library, a 3rd-party token provider or if it runs on the web, will be used to serve a front page, a HTTP REST API Response or a command line exit code and message.

### The "Domain" layer

The "Domain" layer, presented in the **Oauth2Domain.md** file, is an abstraction of the core business rules. In the Oauth2 component, the Oauth2AccessToken entity exposes methods to evaluate if the token is expired and its authorization code state, the Client entity can tell if the sent client identifier or secret match its own client identifier or secret.

Unlike the other layer, this one should aim to work accross features, enforcing validation wherever they are used.

## Implementation details

### Data Transfer Objects

This architecture redesign propose to use Data Transfer Objects to validate input and output.

#### Controller Data Transfer Objects

The Data Tranfer Objects declared in the "Controller" layer should check that basic request and response parameters are being informed or returned without knowing in-depth validation rules. 
For example:
- It should throw an exception if the request does not inform a mandatory parameter;
- It should throw an exception if the request informs an empty string for a mandatory string parameter.

In the **GetOauth2TokenController.md** file, the Oauth2WebControllerHTTPRequest and Oauth2WebControllerHTTPResponse are Data Transfer Objects.

#### Use Case Data Transfer Objects

The Data Transfer Objects declared in the "Use Case" layer should check that input and ouput data match the feature validation rules, with in-depth knowledge of the feature.
For example:
- A client's name should be letters and white space only, beginning with an uppercase letter a followed by lowercase letters;
- A token should be hexadecimal, no white space, joined by hyphens.

### Tests structure

Unless required for good reasons, tests should work as their specific runtime and follow the full flow of data by calling the GetOauth2TokenController and pass it the GetOauth2TokenInputPort and the Oauth2WebControllerHTTPRequest. They should be "feature level" tests.

#### Integration and Feature level tests

As much as possible, there should be tests for:
- Features, as presented in the above paragraph;
- Integrations, in this case, testing the flow of data through the already existing OauthApiController, to check behaviour in its live runtime.

### Repositories and classes naming conventions

Each part of the architecture is named according to the feature it is part of, including:
- Repositories;
- Classes;
- Interfaces;
- Enums;
- etc...

So the Oauth2 Controllers and Components expose:
- A Oauth2WebController class;
- A GetOauth2TokenPublic and GetOauth2TokenInternals repository;
- A Oauth2WebControllerHTTPResponse class;
- and more.