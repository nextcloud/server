#!/usr/bin/env python3
# SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
"""Minimal HTTP service that extracts a single still from a video using ffmpeg.

POST /thumbnail?width=W&height=H&time=5,1,0  (request body: the video)
  -> 200 image/jpeg, scaled to fit into W x H
GET  /health -> 200
"""

import hmac
import os
import shutil
import subprocess
import tempfile
import threading
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import parse_qs, urlparse

PORT = int(os.environ.get("PORT", "8080"))
API_KEY = os.environ.get("API_KEY", "")
MAX_UPLOAD_SIZE = int(os.environ.get("MAX_UPLOAD_SIZE", "0"))  # bytes, 0 = unlimited
FFMPEG_TIMEOUT = int(os.environ.get("FFMPEG_TIMEOUT", "60"))
MAX_JOBS = threading.BoundedSemaphore(int(os.environ.get("MAX_JOBS", str(os.cpu_count() or 1))))
MAX_DIMENSION = 4096
HDR_TRANSFERS = ("smpte2084", "arib-std-b67")
HDR_FILTER = ("zscale=min=2020_ncl:t=linear:npl=100,format=gbrpf32le,zscale=p=bt709,"
              "tonemap=tonemap=hable:desat=0,zscale=t=bt709:m=bt709:r=tv,format=yuv420p,")


def has_zscale() -> bool:
    out = subprocess.run(["ffmpeg", "-hide_banner", "-filters"], capture_output=True, text=True).stdout
    return " zscale " in out


ZSCALE = has_zscale()


def is_hdr(path: str) -> bool:
    if not ZSCALE:
        return False
    try:
        out = subprocess.run(
            ["ffprobe", "-v", "error", "-select_streams", "v:0", "-show_entries", "stream=color_transfer",
             "-of", "default=noprint_wrappers=1:nokey=1", path],
            capture_output=True, text=True, timeout=FFMPEG_TIMEOUT).stdout.strip()
    except subprocess.TimeoutExpired:
        return False
    return out in HDR_TRANSFERS


def grab_frame(path: str, second: int, width: int, height: int, hdr: bool) -> bytes | None:
    scale = f"scale=w='min(iw,{width})':h='min(ih,{height})':force_original_aspect_ratio=decrease"
    cmd = ["ffmpeg", "-hide_banner", "-loglevel", "error", "-nostdin", "-ss", str(second), "-i", path,
           "-frames:v", "1", "-vf", (HDR_FILTER if hdr else "") + scale, "-f", "mjpeg", "pipe:1"]
    try:
        proc = subprocess.run(cmd, capture_output=True, timeout=FFMPEG_TIMEOUT)
    except subprocess.TimeoutExpired:
        return None
    return proc.stdout if proc.returncode == 0 and proc.stdout else None


def int_param(query: dict, name: str, default: int) -> int:
    try:
        return max(1, min(MAX_DIMENSION, int(query.get(name, [default])[0])))
    except ValueError:
        return default


class Handler(BaseHTTPRequestHandler):
    protocol_version = "HTTP/1.1"

    def reply(self, status: int, body: bytes = b"", content_type: str = "text/plain") -> None:
        self.send_response(status)
        self.send_header("Content-Type", content_type)
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def do_GET(self) -> None:
        if urlparse(self.path).path == "/health":
            self.reply(200, b"ok")
        else:
            self.reply(404, b"not found")

    def do_POST(self) -> None:
        url = urlparse(self.path)
        if url.path != "/thumbnail":
            self.close_connection = True
            return self.reply(404, b"not found")

        if API_KEY and not hmac.compare_digest(self.headers.get("Authorization", ""), f"Bearer {API_KEY}"):
            self.close_connection = True
            return self.reply(401, b"unauthorized")

        length = self.headers.get("Content-Length")
        if length is None or not length.isdigit():
            self.close_connection = True
            return self.reply(411, b"content-length required")
        length = int(length)
        if MAX_UPLOAD_SIZE and length > MAX_UPLOAD_SIZE:
            self.close_connection = True
            return self.reply(413, b"file too large")

        query = parse_qs(url.query)
        width = int_param(query, "width", 256)
        height = int_param(query, "height", 256)
        try:
            times = [max(0, int(t)) for t in query.get("time", ["5,1,0"])[0].split(",")][:5]
        except ValueError:
            times = [5, 1, 0]

        with tempfile.NamedTemporaryFile(prefix="video-") as video:
            remaining = length
            while remaining > 0:
                chunk = self.rfile.read(min(1024 * 1024, remaining))
                if not chunk:
                    self.close_connection = True
                    return self.reply(400, b"incomplete body")
                video.write(chunk)
                remaining -= len(chunk)
            video.flush()

            with MAX_JOBS:
                hdr = is_hdr(video.name)
                for second in times:
                    image = grab_frame(video.name, second, width, height, hdr)
                    if image:
                        return self.reply(200, image, "image/jpeg")
        self.reply(422, b"could not extract a frame")

    def log_message(self, fmt: str, *args) -> None:
        if os.environ.get("ACCESS_LOG"):
            super().log_message(fmt, *args)


if __name__ == "__main__":
    if shutil.which("ffmpeg") is None or shutil.which("ffprobe") is None:
        raise SystemExit("ffmpeg and ffprobe must be installed")
    ThreadingHTTPServer(("", PORT), Handler).serve_forever()
