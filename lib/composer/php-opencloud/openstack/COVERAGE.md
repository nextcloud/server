# OpenStack services coverage

|Name|PHP classes|API definition|Unit tests|Sample files|Integration tests|Documentation|
|---|:--:|:--:|:--:|:--:|:--:|:--:|
|Block Storage v2|&#10003;|&#10003;|&#10003;|&#10003;|&#10003;|&#10003;|
|Compute v2|&#10003;|&#10003;|&#10003;|&#10003;|&#10003;|&#10003;|
|Compute v2 exts|||||||
|Data Processing v1|||||||
|Database v1|||||||
|Identity v2|&#10003;|&#10003;|&#10003;||||
|Identity v2 exts|||||||
|Identity v3|&#10003;|&#10003;|&#10003;|&#10003;|&#10003;|&#10003;|
|Identity v3 exts|||||||
|Images v2|&#10003;|&#10003;|&#10003;|&#10003;|&#10003;|&#10003;|
|Networking v2|&#10003;|&#10003;|&#10003;|&#10003;|&#10003;|&#10003;|
|Networking v2 exts|||||||
|Object Storage v1|&#10003;|&#10003;|&#10003;|&#10003;|&#10003;|&#10003;|
|Orchestration v1|||||||
|Telemetry v2|||||||

## Key

### PHP classes

These are the files that reside in `./src/<SERVICE>/<VERSION>` and compose the service layer. Usually this will 
be the main `Service.php` file, and a host of model files located in `Models` directory, relative to the service file. 
All PHP files need to abide by PSR standards, and include full phpdoc annotations for _all_ methods - regardless of 
visibility.

### API definitions

This is the `Api.php` and `Params.php` files for each service, which defines the contract between client and API. It outlines all of the 
available API operations, along with parameters, methods, URL paths, etc. In order for this to be marked complete, it 
must cover 100% of the remote API.

### Unit tests

In order for this to be marked complete, each service needs 100% unit test coverage.

### Sample files

In order for this to be marked complete, there needs to be a sample file for every public operation made available to 
users. If there are many, you can break them up into sub-folders based on the resource type.

### Integration tests

In order for this to be marked complete, each sample file needs to be tested against a live API.

### Documentation

In order for this to be marked complete, every public operation needs to be documented with these items:

* the code signature, along with all of its input arguments. Multidimensional arrays like `$options` or `$data` need 
  have all their keys defined. All types must be defined. Any required options must be marked.
* a human-readable description
* a code sample
