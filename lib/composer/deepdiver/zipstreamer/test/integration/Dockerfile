FROM alpine:3.6
MAINTAINER Johannes Holzfuß <DataWraith@fastmail.fm>

# This Dockerfile containerizes p7zip.
#
# You must run it using the correct UID/GID via the -u switch to `docker run`
# or the permissions will be wrong.
#
# Example usage
#     docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd)":/data datawraith/p7zip a archive.7z file1 file2 file3

RUN apk --update add \
	p7zip \
 && rm -rf /var/cache/apk/*

RUN mkdir /data
WORKDIR /data

ENTRYPOINT ["7z"]
