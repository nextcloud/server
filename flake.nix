{
  inputs = {
    nixpkgs.url = "github:nixos/nixpkgs/nixos-25.05";
    flake-utils.url = "github:numtide/flake-utils";
    haze = {
      url = "git+https://codeberg.org/icewind/haze.git";
      inputs.nixpkgs.follows = "nixpkgs";
    };
  };

  outputs = { nixpkgs, flake-utils, haze, ... }:
    flake-utils.lib.eachDefaultSystem (system:
      let
        pkgs = nixpkgs.legacyPackages.${system};
        lib = pkgs.lib;
      in
      {
        devShells.default =
          let
            php_version = lib.strings.concatStrings (builtins.match ".*PHP_VERSION_ID < ([0-9])0([0-9])00.*" (builtins.readFile ./lib/versioncheck.php));
            php = pkgs.pkgs."php${php_version}".buildEnv {
              # Based off https://docs.nextcloud.com/server/latest/admin_manual/installation/php_configuration.html
              extensions = ({ enabled, all }: enabled ++ (with all; [
                # Required
                ctype
                curl
                dom
                fileinfo
                filter
                gd
                mbstring
                openssl
                posix
                session
                simplexml
                xmlreader
                xmlwriter
                zip
                zlib
                # Database connectors
                pdo_sqlite
                pdo_mysql
                pdo_pgsql
                # Recommended
                intl
                sodium
                # Required for specific apps
                ldap
                smbclient
                ftp
                imap
                # Recommended for specific apps (optional)
                gmp
                exif
                # For enhanced server performance (optional)
                apcu
                memcached
                redis
                # For preview generation (optional)
                imagick
                # For command line processing (optional)
                pcntl

                # Debugging
                xdebug
              ]));

              extraConfig = ''
                max_execution_time=300
                memory_limit=-1

                xdebug.mode=debug
              '';
            };
            node_version = builtins.substring 1 (-1) (builtins.elemAt (lib.strings.splitString "." (builtins.fromJSON (builtins.readFile ./package.json)).engines.node) 0);
            node = pkgs."nodejs_${node_version}";
          in
          pkgs.mkShell {
            NOCOVERAGE = 1;

            packages = [
              php
              php.packages.composer
              node
              # Preview generation
              pkgs.ffmpeg
              pkgs.libreoffice

              haze.packages.${system}.default
            ];
          };
      }
    );
}
