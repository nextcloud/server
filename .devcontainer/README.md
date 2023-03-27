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
| Adminer | `8080` | Database viewer. Use credentials from above and connect to `localhost:5432` to get access to the NC database | 

