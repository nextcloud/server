# Copilot Instructions for the ncw-server Repository

This document provides instructions for GitHub Copilot to effectively assist with development in this repository.

## Development Environment

The development environment for `ncw-server` is based on a custom Podman container. Here are the key aspects:

*   **Container-based:** All development is done inside a Podman container.
*   **Orchestration:** The development container is managed by a script located at `../<tools-dir>/container/dev`. This script is the primary entry point for all development tasks.
*   **Tools Directory:** The tools directory is located next to the `ncw-server` directory. It can have different names - the default is `nc-docs-and-tools`, but it might also be named `tools` or `d-n-t`.
*   **Usage:**
    *   To start an interactive shell in the container, run `../<tools-dir>/container/dev` from the `ncw-server` directory.
    *   To execute a command inside the container, run `../<tools-dir>/container/dev <command>`. For example, `../<tools-dir>/container/dev npm install`.
*   **Installation:** The initial setup of the Nextcloud instance inside the container is handled by the `dev-install.sh` script, which is located in `../<tools-dir>/container/`. This script is typically run automatically when the container is first started.
*   **Source Code:** The `ncw-server` directory is mounted as `/var/www/html` inside the container.
*   **Dependencies:**
    *   **PHP:** The container uses PHP 8.3 with a wide range of extensions. `composer` is used for PHP package management.
    *   **Node.js:** The container uses Node.js 20.15, managed by `nvm`.
    *   **Xdebug:** Xdebug is configured for both CLI and Apache, allowing for debugging of PHP code.

## Scripts

### `check_nc_release.sh`

This script is used to test a specific release of Nextcloud. It is not part of the regular development workflow, but rather a tool for testing releases. It clones the Nextcloud server repository at a specific tag, can optionally install additional apps, and then spins up a development container using the `ionos-nextcloud-dev-container` image.

### `clean.sh`

This script removes generated files and data, including:

*   Files in the `data` directory.
*   Configuration files in the `config` directory.
*   Data in the `minio/data` directory.

## Submodules

The repository makes extensive use of Git submodules to manage dependencies.

*   **`3rdparty`:** This submodule contains third-party libraries.
*   **`IONOS`:** This submodule contains IONOS-specific customizations and integrations.
*   **`apps-external`:** This directory contains a large number of external Nextcloud apps, which are included as submodules. This is a common practice in Nextcloud development to manage external applications.

### Adding New Submodules

Use the script `add_app_submodule.sh` in the tools directory that automates the process of adding new submodules with interactive setup.

## Architecture and Tech Stack

Nextcloud is a client-server software for creating and using file hosting services. It is functionally similar to Dropbox, although Nextcloud is free and open-source, allowing anyone to install and operate it on a private server.

*   **Backend:** The backend is primarily written in **PHP** and uses the **Symfony** framework. It interacts with a database (SQLite, MySQL/MariaDB, or PostgreSQL) to store metadata and user information. File storage can be on a local filesystem or an object store like S3.
*   **Frontend:** The frontend is built with **Vue.js** and **TypeScript**. It uses the **Vite** build tool.
*   **API:** Nextcloud exposes a rich set of APIs for clients to interact with, including a WebDAV API for file access and an OCS (Open Collaboration Services) API for other functionalities.
*   **Extensibility:** Nextcloud is highly extensible through its app system. Apps can be written in PHP and/or JavaScript and can add new features or integrate with external services.

## IONOS Integration

This project has a strong integration with IONOS services. When working on features related to IONOS, be aware of the following:

*   **Main Configuration Script:** Most of the application configuration is handled by the central `IONOS/configure.sh` script. This script orchestrates the entire Nextcloud Workspace setup process including:
    *   Server basics and theming configuration
    *   App installation and configuration (Collabora, antivirus, fulltext search, etc.)
    *   IONOS-specific integrations and API configurations
    *   Admin delegation settings

### Configuration Script Convenience Features

The `IONOS/configure.sh` script provides several convenience features:

*   **Colored Logging:** Built-in logging functions with color-coded output:
    *   `log_info()` - Blue info messages
    *   `log_warning()` - Yellow warning messages
    *   `log_error()` - Red error messages
    *   `log_fatal()` - Red fatal errors that exit the script
*   **Error Handling:** Robust error handling with the `execute_occ_command()` wrapper that catches and reports OCC command failures
*   **Environment Variable Validation:** Checks for required environment variables and gracefully skips configuration if they're missing
*   **Conditional Configuration:** Smart configuration that adapts based on available environment variables (e.g., skips antivirus config if ClamAV variables aren't set)
*   **App State Management:** Temporarily enables apps for configuration, then restores their original state
*   **Dependency Checking:** Verifies required tools (PHP) are available before proceeding
*   **Installation Verification:** Checks that Nextcloud is properly installed before attempting configuration

## Available Development Tools & Scripts

The tools directory (`<tools-dir>`) contains comprehensive development scripts and utilities for managing the Nextcloud Workspace development environment.

### Container Management (`container/`)
*   **`dev`** - Main development container script (Podman-based)
    *   Start interactive session: `../<tools-dir>/container/dev`
    *   Execute commands: `../<tools-dir>/container/dev <command>`
    *   Handles image building, container lifecycle, and environment setup
*   **`dev-install.sh`** - Initial Nextcloud installation and setup
*   **`dev-add_dummy_users.sh`** - Create test users for development
*   **`dev-setup-mail.sh`** - Configure mail settings for development
*   **`build.sh`** - Build the development container image
*   **`clean.sh`** - Remove generated files and reset development state
*   **`cleanup.sh`** - Container cleanup utilities
*   **`check_nc_release.sh`** - Test specific Nextcloud releases
*   **`check_release.sh`** - Validate release configurations

### Development Utilities (`tools/`)
*   **`add_app_submodule.sh`** - Interactive script to add Nextcloud apps as Git submodules
    *   Automates the entire process with guided setup
    *   Handles branch management and configuration
*   **`generate_core_patches.sh`** - Generate Git patch series for core directory synchronization
*   **`get_users_occ_commands.sh`** - Generate OCC commands for user management operations
*   **`relink-npm-modules.sh`** - Relink npm modules for development environment
*   **`set-quota`** - Set user quotas in Nextcloud

### Service Management
*   **`fix-permissions.sh`** - Fix file permissions in development environment

### External Service Runners
*   **`collabora/run`** - Start Collabora Office service for document editing
*   **`whiteboard/run`** - Start whiteboard collaboration service
*   **`imaginary/run`** - Start image processing service
*   **`mailer/run`** - Start mail service for development
*   **`imap/run`** - Start IMAP service for mail testing

### Configuration Files
*   **`.env`** and **`.env.secret`** - Environment configuration files

### Quick Start Development Workflow
1. **Initial Setup**: `../<tools-dir>/container/dev dev-install.sh`
2. **Build Dependencies**: `../<tools-dir>/container/dev make -f IONOS/Makefile build_locally`
3. **Configure Apps**: `../<tools-dir>/container/dev IONOS/configure.sh`
4. **Start Services**: Start individual services as needed (collabora, imaginary, etc.)
5. **Development**: `../<tools-dir>/container/dev` (interactive shell)

## Container Convenience Features

The development container provides several convenience features for development:

### Pre-installed Development Tools
*   **PHP 8.3** with extensive extensions including Xdebug for debugging
*   **Node.js 20** managed by nvm for frontend development
*   **Composer** for PHP package management
*   **Git** with your host `.gitconfig` mounted for proper identity
*   **OCC Command Line Tool** - Nextcloud's command-line interface for administration

### Container Environment Setup
*   **Persistent Cache:** npm cache and general cache directories are mounted to `/tmp` for persistence
*   **Bash History:** Development session history is persisted across container restarts
*   **SSH Agent Forwarding:** Your SSH agent is available inside the container for Git operations
*   **Network Access:** Host networking allows easy access to external services and localhost
*   **Auto-mounting:** Source code is automatically mounted to `/var/www/html`

### Development Workflow Integration
*   **Hot Reloading:** Frontend development server supports hot reload for rapid iteration
*   **Xdebug Integration:** Pre-configured for both CLI and web debugging on port 9003
*   **Composer Integration:** Automatic dependency resolution and autoloading
*   **File Permissions:** Proper permission handling between host and container

### OCC Command Convenience
*   **Direct Access:** Run `php occ <command>` directly inside the container
*   **Common Commands:**
    *   `php occ status` - Check Nextcloud status
    *   `php occ app:list` - List available apps
    *   `php occ config:list` - View configuration
    *   `php occ maintenance:mode --on/--off` - Toggle maintenance mode

## General Guidelines

*   When you need to run a command, use the `../<tools-dir>/container/dev` script. The tools directory is located next to `ncw-server` and its name could be `nc-docs-and-tools` (default), `tools`, or `d-n-t`.
*   Remember that the application is running inside a container, so file paths and services are relative to the container's environment.
*   Pay attention to the IONOS-specific scripts and configurations when working on related features.
*   Use the `IONOS/configure.sh` script for most configuration tasks rather than manual OCC commands.
*   Before committing any changes, make sure to run the relevant tests and linters.

## Code Organization & Structure

### Backend (PHP)
*   **Controllers:** Located in `lib/Controller/` - handle HTTP requests and responses
*   **Models:** Located in `lib/` - business logic and data models
*   **Services:** Located in `lib/Service/` - reusable business logic
*   **Database:** Located in `lib/Db/` - database entities and mappers
*   **Migration:** Located in `lib/Migration/` - database schema changes
*   **Background Jobs:** Located in `lib/BackgroundJob/` - scheduled tasks

### Frontend (Vue.js/TypeScript)
*   **Components:** Located in `src/components/` - reusable Vue components
*   **Views/Pages:** Located in `src/views/` - top-level page components
*   **Stores:** Located in `src/stores/` - Pinia state management
*   **Services:** Located in `src/services/` - API calls and business logic
*   **Types:** Located in `src/types/` - TypeScript type definitions
*   **Assets:** Located in `src/assets/` - images, styles, static files

### Configuration
*   **App Info:** `appinfo/info.xml` - app metadata and dependencies. The id should match the directory name. otherwise Nextcloud won't recognize the app.
*   **Routes:** `appinfo/routes.php` - API endpoint definitions
*   **Database:** `appinfo/database.xml` - database schema definition
*   **Config:** `config/config.php` - Nextcloud instance configuration

### Nextcloud App Structure
Each app follows the standard Nextcloud structure:
```
app-name/
├── appinfo/          # App metadata and configuration
├── lib/             # PHP backend code
├── src/             # Frontend source code
├── css/             # Compiled stylesheets
├── js/              # Compiled JavaScript
├── templates/       # PHP templates (if used)
├── tests/           # Unit and integration tests
└── package.json     # Node.js dependencies
```

## Development Workflows

### Creating New Features
1. Create a feature branch: `git checkout -b <developer initials>/dev/feature-name`
2. Implement backend changes in `lib/`
3. Add/update API routes in `appinfo/routes.php`
4. Implement frontend changes in `src/`
5. Add tests for new functionality


### Git Workflow
*   **Commit messages:** Follow conventional commits format
*   **Commit messages:** Use IONOS(<scope>): <subject> - e.g., IONOS(Auth): Add OIDC login support. for commits in forked repos to distinguish them from upstream commits.
*   **Commits:** Sign off commits with `git commit -s`
*   **Branching:** Use feature branches named `<developer initials>/dev/feature-name`

*   **Pull requests:** Require code review and passing tests

## Testing Information
If I am getting an error in php unit tests. Please fix. You can run tests locally via: /home/<absolute_path>/nc-docs-and-tools/container/dev bash -c "cd /var/www/html/apps-external/<your_app_foder> && composer run test:unit"

### Running Tests
*   **PHP Unit Tests:** `../<tools-dir>/container/dev composer test`
*   **JavaScript Tests:** `../<tools-dir>/container/dev npm run test`

### Test Structure
*   **PHP Tests:** Located in `tests/Unit/` and `tests/Integration/`
*   **JS Tests:** Located in `tests/javascript/` with `.test.js` or `.spec.js` suffix
*   **Test Data:** Located in `tests/fixtures/`

### Writing Tests
*   Use PHPUnit for PHP backend testing
*   Use Jest/Vitest for JavaScript unit testing
*   Use Cypress or Playwright for E2E testing
*   Mock external dependencies and API calls
*   Aim for >80% code coverage

## Debugging & Troubleshooting

### Accessing Logs
*   **Nextcloud Logs:** `/var/www/html/data/nextcloud.log` inside container
*   **Apache Logs:** `/var/log/apache2/error.log` inside container
*   **PHP Logs:** Check `error_log` directive in PHP configuration
*   **Frontend Console:** Browser developer tools console

### Debugging with Xdebug
*   Xdebug is pre-configured for both CLI and web requests
*   **IDE Setup:** Configure your IDE to listen on port 9003
*   **CLI Debugging:** `../<tools-dir>/container/dev php -dxdebug.start_with_request=yes script.php`
*   **Web Debugging:** Add `XDEBUG_SESSION=1` to URL parameters

### Common Issues
*   **Container not starting:** Check Docker/Podman installation and permissions
*   **Database errors:** Verify database connection in `config/config.php`
*   **Permission issues:** Files should be owned by `www-data` inside container
*   **Frontend build errors:** Clear `node_modules` and reinstall dependencies

### Performance Debugging
*   **Xdebug Profiler:** Enable profiling in `php.ini` for performance analysis
*   **Database Queries:** Use Nextcloud's query logger
*   **Frontend Performance:** Use browser dev tools performance tab
*   **Memory Usage:** Monitor with `memory_get_peak_usage()` in PHP

## Build & Deployment

### Frontend Build Process also for Apps
*   **Development:** `../<tools-dir>/container/dev npm run dev` - starts dev server with hot reload
*   **Production:** `../<tools-dir>/container/dev npm run build` - creates optimized bundle
*   **Watch Mode:** `../<tools-dir>/container/dev npm run watch` - rebuilds on file changes

### Asset Management
*   **Vite Configuration:** `vite.config.js` - build tool configuration
*   **Output Directory:** Built assets go to `js/` and `css/` directories
*   **Asset Loading:** Use Nextcloud's asset loading helpers in PHP templates

### Environment Configuration
*   **Development:** Uses `config/config.php` with debug settings but prefer to use granular config files in `IONOS/configs/...`
*   **Production:** Disable debug mode, enable caching, optimize database
*   **Environment Variables:** Can be set in container or loaded from `../<tools-dir>/container/.env` files


### Configuration Files
*   **Main Config:** `config/config.php` - primary Nextcloud configuration
*   **IONOS Configuration:** `IONOS/configure.sh` - central script that handles most application configuration including apps, theming, integrations, and IONOS-specific settings
*   **App Config:** Individual app settings stored in database (many set by `configure.sh`)
*   **Container Config:** `docker-compose.yml` or container scripts
*   **Web Server:** Apache configuration in container

### Inter-App Communication
*   **Events:** Use Nextcloud's event system for loose coupling
*   **Services:** Register services in DI container
*   **APIs:** Expose APIs for other apps to consume
*   **Capabilities:** Declare app capabilities for feature detection

### Plugin & Hook System
*   **Hooks:** Legacy hook system (deprecated)
*   **Events:** Modern event system with typed events
*   **Listeners:** Register event listeners in `appinfo/info.xml`
*   **Middleware:** Custom middleware for request/response handling

### Version Compatibility
*   **PHP:** Minimum 8.1, recommended 8.3
*   **Node.js:** Version 20+ required for build process
*   **Nextcloud:** Check `appinfo/info.xml` for supported versions
*   **Database:** MySQL 8.0+, PostgreSQL 12+, SQLite 3.x
