## Building and enabling the Common Run Time

1. **Follow instructions on crt repo** – Clone and build the repo as shown [here][https://github.com/awslabs/aws-crt-php].
1. **Enable the CRT** – add the following line to your php.ini file `extension=path/to/aws-crt-php/modules/awscrt.so`