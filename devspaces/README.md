# NextCloud Development with DF Devspaces

## Install DF Devspaces

1. Create and install devspaces client as it is written in help guide https://support.devspaces.io/article/22-devspaces-client-installation.

2. Deploy your development environment into DF devspaces following this guide https://support.devspaces.io/article/23-support-guidelines 

3. Here is some details about DF Devspaces https://devspaces.io/devspaces/help

Here follows the main commands used in Devspaces cli. 

|action   |Description                                                                                   |
|---------|----------------------------------------------------------------------------------------------|
|`devspaces --help`                    |Check the available command names.                               |
|`devspaces create [options]`          |Creates a DevSpace using your local DevSpaces configuration file |
|`devspaces start <devSpace>`          |Starts the DevSpace named \[devSpace\]                           |
|`devspaces bind <devSpace>`           |Syncs the DevSpace with the current directory                    |
|`devspaces info <devSpace> [options]` |Displays configuration info about the DevSpace.                  |

Use `devspaces --help` to know about updated commands.


### Start Devspaces 

1.  Create DevSpaces.
```bash
devspaces create
```

2. Start your devspaces.
```bash
devspaces start nextcloud
```

3. Start containers synchronization
Open terminal on folder you want to sync with devspaces and run:

```bash
cd ..
devspaces bind nextcloud
```

4. Update git submodules
```bash
git submodule update --init
```

5. Grab some container info

```bash
devspaces info nextcloud
```

Retrieve published DNS, endpoints using this command and 

6. Connect to development container

```bash
devspaces exec nextcloud
```

7. Run the application
Make sure /data folder has proper permissions by running:
```bash
chmod -R 777 /data
```
and then
```bash
service apache2 start
```

You can now access the application from the URL acquired from step `5. Grab some container info`

## Running NextCloud via Docker-Compose file

Currently, we have these command available to work using local docker compose.

```bash
devspaces/docker-cli.sh <command>
```

|action    |Description                                                               |
|----------|--------------------------------------------------------------------------|
|`build`   |Builds images                                                             |                                      
|`deploy`  |Deploy Docker compose containers                                          |
|`undeploy`|Undeploy Docker compose containers                                        |
|`start`   |Starts Docker compose containers                                          |
|`stop`    |Stops Docker compose containers                                           |
|`exec`    |Get into the container                                                    |


### Dockerfile
 Dockefile is created on top of `php:7.2.12-apache` image.

### Requirements
 - The project should be cloned from https://github.com/trilogy-group/nextcloud-server
 - Docker version 18.09.0-ce
 - Docker compose version 1.23.1 

### Quick Start
1. Clone the repository
2. Open a terminal session to that folder
3. Run `./docker-cli.sh deploy`
4. Run `./docker-cli.sh exec`
5. At this point you must be inside the docker container. You can continue building the product from `7. Run the application`
6. When you finish working with the container, type `exit`
7. Run `./docker-cli.sh stop` to stop running service.
8. Run `./docker-cli.sh start` to start stopped container (should be used only after `stop` command).
9. Run `./docker-cli.sh undeploy` to stop and remove running service







