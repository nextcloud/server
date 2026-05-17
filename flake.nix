# The flake is compatible with >=stable30, so make sure to backport all changes to stable30 and up.
{
  nixConfig = {
    extra-substituters = [ "https://fossar.cachix.org" ];
    extra-trusted-public-keys = [ "fossar.cachix.org-1:Zv6FuqIboeHPWQS7ysLCJ7UT7xExb4OE8c4LyGb5AsE=" ];
  };

  inputs = {
    nixpkgs.url = "github:nixos/nixpkgs/nixos-25.11";
    flake-utils.url = "github:numtide/flake-utils";
	phps = {
	  url = "github:fossar/nix-phps";
	  inputs = {
	    nixpkgs.follows = "nixpkgs";
	    utils.follows = "flake-utils";
	  };
	};
    haze = {
      url = "git+https://codeberg.org/icewind/haze.git";
      inputs = {
        nixpkgs.follows = "nixpkgs";
        phps.follows = "phps";
      };
    };
  };

  outputs = { nixpkgs, flake-utils, phps, haze, ... }:
    flake-utils.lib.eachDefaultSystem (system:
      let
        pkgs = nixpkgs.legacyPackages.${system};
        lib = pkgs.lib;
      in
      {
        devShells.default =
          let
            php_version = lib.strings.concatStrings (builtins.match ".*PHP_VERSION_ID < ([0-9])0([0-9])00.*" (builtins.readFile ./lib/versioncheck.php));
            php = phps.packages.${system}."php${php_version}".buildEnv {
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
            php_stubs_updater = php.buildComposerProject2 (finalAttrs: {
              pname = "php-stubs-updater";
              version = "0.0.1";

              src = pkgs.fetchFromGitea {
                domain = "codeberg.org";
                owner = "provokateurin";
                repo = "php-stubs-updater";
                rev = "fd8a76461dc409ea041bf5dcd3f91df2f4480415";
                hash = "sha256-X37uw++oBwzTibNa7Qz5eONOnUEs2DKU/vm83+M3KH0=";
              };

              composerStrictValidation = false;
              vendorHash = "sha256-DULjWnP+gBEq2x4GDj7yNK7wVFWuRLadHJym0EiRLjA=";
           });
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
              pkgs.reuse
              php_stubs_updater
            ];
          };
      }
    );
}
