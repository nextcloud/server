#!/usr/bin/env python3
"""
Minimal OAuth2 (Authorization Code + PKCE) client for a Nextcloud instance.

Setup on the Nextcloud side:
  1. Enable the "OAuth2" app (Settings > Apps).
  2. Settings > Administration > Security > OAuth 2.0 clients:
     add a client with redirection URI matching --redirect-uri below
     (default: http://localhost:8080/callback).
  3. Copy the generated Client Identifier / Secret.

Usage:
  pip install requests
  python nextcloud_oauth2_client.py \
      --server https://cloud.example.com \
      --client-id XXXX \
      --client-secret YYYY

This opens a browser for the user to log in / approve access, then
exchanges the returned authorization code for an access token and
does a simple authenticated OCS API call to prove it works.
"""

import argparse
import base64
import hashlib
import http.server
import secrets
import threading
import urllib.parse
import webbrowser
import xml.etree.ElementTree as ET

import requests

AUTHORIZE_PATH = "/index.php/apps/oauth2/authorize"
TOKEN_PATH = "/index.php/apps/oauth2/api/v1/token"
DAV_NS = {"d": "DAV:"}


class _CallbackHandler(http.server.BaseHTTPRequestHandler):
    def do_GET(self):
        params = urllib.parse.parse_qs(urllib.parse.urlsplit(self.path).query)
        self.server.auth_code = params.get("code", [None])[0]
        self.server.state = params.get("state", [None])[0]

        self.send_response(200)
        self.send_header("Content-Type", "text/html")
        self.end_headers()
        self.wfile.write(b"<html><body>Login complete, you can close this tab.</body></html>")

    def log_message(self, *args):
        pass  # silence default request logging


def parse_json(response, url):
    try:
        return response.json()
    except:
        print(f"Non-JSON response ({response.status_code}) ({response.url}, {url}):\n{response.text}")
        raise


def wait_for_callback(host, port):
    server = http.server.HTTPServer((host, port), _CallbackHandler)
    server.auth_code = None
    server.state = None
    thread = threading.Thread(target=server.handle_request)
    thread.start()
    thread.join()
    return server.auth_code, server.state


def make_pkce_pair():
    verifier = base64.urlsafe_b64encode(secrets.token_bytes(32)).rstrip(b"=").decode()
    challenge = base64.urlsafe_b64encode(
        hashlib.sha256(verifier.encode()).digest()
    ).rstrip(b"=").decode()
    return verifier, challenge


def authorize(server_url, client_id, redirect_uri, callback_host, callback_port):
    state = secrets.token_urlsafe(16)
    code_verifier, code_challenge = make_pkce_pair()

    query = urllib.parse.urlencode({
        "response_type": "code",
        "client_id": client_id,
        "redirect_uri": redirect_uri,
        "state": state,
        "code_challenge": code_challenge,
        "code_challenge_method": "S256",
    })
    auth_url = f"{server_url}{AUTHORIZE_PATH}?{query}"

    print(f"Opening browser for authorization:\n  {auth_url}")
    webbrowser.open(auth_url)

    code, returned_state = wait_for_callback(callback_host, callback_port)
    if not code:
        raise RuntimeError("No authorization code received from callback")
    if returned_state != state:
        raise RuntimeError("State mismatch, possible CSRF - aborting")

    return code, code_verifier


def exchange_code_for_token(server_url, client_id, client_secret, redirect_uri, code, code_verifier):
    response = requests.post(
        f"{server_url}{TOKEN_PATH}",
        data={
            "grant_type": "authorization_code",
            "code": code,
            "redirect_uri": redirect_uri,
            "client_id": client_id,
            "client_secret": client_secret,
            "code_verifier": code_verifier,
        },
        timeout=30,
    )
    response.raise_for_status()
    return parse_json(response, f"{server_url}{TOKEN_PATH}")


def refresh_token(server_url, client_id, client_secret, refresh_token_value):
    response = requests.post(
        f"{server_url}{TOKEN_PATH}",
        data={
            "grant_type": "refresh_token",
            "refresh_token": refresh_token_value,
            "client_id": client_id,
            "client_secret": client_secret,
        },
        timeout=30,
    )
    response.raise_for_status()
    return parse_json(response, f"{server_url}{TOKEN_PATH}")


def request_forward_auth(method, url, headers=None, data=None, params=None, timeout=30, max_redirects=5):
    """
    Like requests.request(), but manually follows redirects while always
    re-sending the original headers (including Authorization).

    requests' built-in redirect handling (Session.resolve_redirects) strips
    Authorization when the redirect target's host differs from the original
    request's host - this is by design (avoid leaking creds to a third
    party), but it's exactly what breaks the GSS master -> slave OCS/DAV
    redirect, since master and slave are different hosts. Following
    redirects manually here forwards Authorization regardless of host, to
    mirror what a non-browser OCS/DAV client would do.
    """
    headers = dict(headers or {})
    url = f"{url}?{urllib.parse.urlencode(params)}" if params else url

    for _ in range(max_redirects):
        response = requests.request(
            method, url, headers=headers, data=data, timeout=timeout, allow_redirects=False
        )
        if response.is_redirect or response.is_permanent_redirect:
            location = response.headers["Location"]
            url = urllib.parse.urljoin(url, location)
            # Mirror browser semantics: 301/302/303 downgrade non-GET/HEAD to GET.
            if response.status_code in (301, 302, 303) and method not in ("GET", "HEAD"):
                method, data = "GET", None
            print(f"  -> following {response.status_code} redirect to {url} (forwarding Authorization)")
            continue
        return response

    raise RuntimeError(f"Too many redirects (>{max_redirects}) while requesting {url}")


def whoami(server_url, access_token):
    url = f"{server_url}/ocs/v2.php/cloud/user"
    response = request_forward_auth(
        "GET",
        url,
        params={"format": "json"},
        headers={
            "Authorization": f"Bearer {access_token}",
            "OCS-APIRequest": "true",
        },
    )
    response.raise_for_status()
    return parse_json(response, url)


def propfind(server_url, access_token, user_id, path="", depth="1"):
    dav_url = f"{server_url}/remote.php/dav/files/{user_id}/{path.lstrip('/')}"
    body = """<?xml version="1.0" encoding="utf-8"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:displayname/>
    <d:getcontentlength/>
    <d:getcontenttype/>
    <d:getlastmodified/>
    <d:resourcetype/>
  </d:prop>
</d:propfind>"""
    response = request_forward_auth(
        "PROPFIND",
        dav_url,
        data=body,
        headers={
            "Authorization": f"Bearer {access_token}",
            "Depth": depth,
            "Content-Type": "application/xml",
        },
    )
    response.raise_for_status()
    return response.text


def parse_propfind(xml_text):
    root = ET.fromstring(xml_text)
    entries = []
    for response in root.findall("d:response", DAV_NS):
        href = response.findtext("d:href", namespaces=DAV_NS)
        prop = response.find("d:propstat/d:prop", DAV_NS)
        is_collection = prop is not None and prop.find("d:resourcetype/d:collection", DAV_NS) is not None
        entries.append({
            "href": urllib.parse.unquote(href) if href else href,
            "is_dir": is_collection,
            "displayname": prop.findtext("d:displayname", namespaces=DAV_NS) if prop is not None else None,
            "length": prop.findtext("d:getcontentlength", namespaces=DAV_NS) if prop is not None else None,
            "last_modified": prop.findtext("d:getlastmodified", namespaces=DAV_NS) if prop is not None else None,
        })
    return entries


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--server", required=True, help="Base URL of the Nextcloud instance, e.g. https://cloud.example.com")
    parser.add_argument("--client-id", required=True)
    parser.add_argument("--client-secret", required=True)
    parser.add_argument("--redirect-uri", default="http://localhost:8080/callback")
    parser.add_argument("--path", default="", help="Path within the user's files to PROPFIND, e.g. 'Documents'")
    args = parser.parse_args()

    server_url = args.server.rstrip("/")
    redirect = urllib.parse.urlsplit(args.redirect_uri)
    callback_host = redirect.hostname or "localhost"
    callback_port = redirect.port or 8080

    code, code_verifier = authorize(server_url, args.client_id, args.redirect_uri, callback_host, callback_port)
    print("Authorization code received, exchanging for token...")

    token = exchange_code_for_token(
        server_url, args.client_id, args.client_secret, args.redirect_uri, code, code_verifier
    )
    print("Access token:", token["access_token"])
    print("Refresh token:", token.get("refresh_token"))
    print("Expires in:", token.get("expires_in"), "seconds")

    print("\nCalling OCS user API with the access token...")
    user_info = whoami(server_url, token["access_token"])
    print(user_info)
    user_id = user_info["ocs"]["data"]["id"]

    print(f"\nRunning PROPFIND on /{args.path} for user '{user_id}'...")
    propfind_xml = propfind(server_url, token["access_token"], user_id, args.path)
    for entry in parse_propfind(propfind_xml):
        kind = "DIR " if entry["is_dir"] else "FILE"
        size = entry["length"] or "-"
        print(f"  [{kind}] {entry['href']} (size={size}, mtime={entry['last_modified']})")


if __name__ == "__main__":
    main()
