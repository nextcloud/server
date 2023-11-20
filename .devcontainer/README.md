# Nextcloud DevContainer

## Usage

Make sure you have the [VSCode DevContainer](https://code.visualstudio.com/docs/devcontainers/containers) extensions installed. If you open the project, VSCode will ask you if you want to open it inside of the DevContainer. If that's not the case, use <kbd>F1</kbd>&rarr;*Dev Containers: Open Folder in Container*.

Alternatively open the project directly in [GitHub Codespaces](https://github.com/features/codespaces).

That's already it. Everything else will be configured automatically by the Containers startup routine.

## Credentials

On first start the Container installs and configures Nextcloud with the following credentials:

**Nextcloud Admin Login**

Username: `admin` <br>
Password: `admin`

**Postgres credentials**

Username: `postgres` <br>
Password: `postgres` <br>
Database: `postgres`

## Services

The following services will be started:

| Service | Local port | Description |
|---------|------------|-------------|
| Nextcloud (served via Apache) | `80` | The main application |
| Mailhog | `8025` | SMTP email delivery for testing |
| Adminer | `8080` | Database viewer. Use credentials from above and connect to `localhost` to get access to the NC database | 

## Permissions

The container runs with the user `devcontainer` who is also running the Apache2 process. All mounted source files have
proper permissions so that this user can access everything which is inside the current workspace. If you need to
get root permissions for whatever reason, use `sudo su` or `sudo <command>` (for example `sudo service apache2 restart`).
Everything else (like building the application, adjusting files, ...) should be done as `devcontainer` user.

## NodeJs and NVM

The container comes with [`nvm`](https://github.com/nvm-sh/nvm) and Node 16 installed. This should be sufficient to
build Nextcloud Core sources via `make`. If you need a different Node Version (for example for
app development), you can easily switch between different versions by running:

```bash
# Install and use Node 14
nvm install 14
nvm use 14

# Check version 
node -v

# Switch back to Node 16
nvm use 16

# Check version
node -v
```

Note that `nvm` is only installed for the user `devcontainer` and won't work out of the box for
any other user.

## Debugging

The Apache webserver is already configured to automatically try to connect to a debugger process
listening on port `9003`. To start the VSCode debugger process, use the delivered debug profile `Listen for XDebug`.
After you started the VSCode debugger, just navigate to the appropriate Nextcloud URL to get your
debug hits. 