#!/usr/bin/env python3
"""Lightweight CLI/TUI monitor for TIME-PON on Raspberry Pi LCD."""

import argparse
import json
import shutil
import sys
import time
from datetime import datetime, timezone
from urllib.error import URLError, HTTPError
from urllib.request import urlopen


def fmt_sec(sec: int) -> str:
    sign = '-' if sec < 0 else ''
    sec = abs(sec)
    h, r = divmod(sec, 3600)
    m, s = divmod(r, 60)
    return f"{sign}{h:02d}:{m:02d}:{s:02d}"


def stage_state(last_seen_ms: int, now_ms: int, threshold_sec: int) -> str:
    if last_seen_ms <= 0:
        return "offline"
    lag_sec = (now_ms - last_seen_ms) / 1000
    return "online" if lag_sec <= threshold_sec else f"stale({int(lag_sec)}s)"


def fetch_snapshot(url: str) -> dict:
    with urlopen(url, timeout=4) as resp:
        return json.loads(resp.read().decode("utf-8"))


def print_snapshot(base_url: str, data: dict, offline_sec: int) -> None:
    now_ms = int(data.get("serverNowMs", int(time.time() * 1000)))
    room_ids = ", ".join(data.get("roomIds", [])) or "(none)"

    print(f"TIME-PON CLI Snapshot @ {datetime.now(timezone.utc).astimezone().strftime('%Y-%m-%d %H:%M:%S %Z')}")
    print(f"API: {base_url}/api.php?act=cli_snapshot")
    print(f"Fixed rooms: {room_ids}")
    print("-" * min(shutil.get_terminal_size((100, 30)).columns, 100))

    for room in data.get("rooms", []):
        sid = stage_state(int(room.get("stageLastSeen", 0)), now_ms, offline_sec)
        remain = fmt_sec(int(room.get("remainSec", 0)))
        print(f"[{room.get('id','?')}] {room.get('name','')}  state={room.get('state','?')}  remain={remain}  stage={sid}")
        print(f"  admin: {room.get('urls',{}).get('admin','-')}")
        print(f"  stage: {room.get('urls',{}).get('stage','-')}")
        msg = (room.get("message") or "").strip()
        if msg:
            print(f"  message: {msg[:80]}")


def main() -> int:
    p = argparse.ArgumentParser(description="TIME-PON Raspberry Pi monitor")
    p.add_argument("--base-url", default="http://127.0.0.1", help="TIME-PON base URL")
    p.add_argument("--interval", type=float, default=2.0, help="polling interval seconds")
    p.add_argument("--offline-sec", type=int, default=10, help="stage stale threshold")
    p.add_argument("--once", action="store_true", help="fetch once and exit")
    args = p.parse_args()

    api = args.base_url.rstrip("/") + "/api.php?act=cli_snapshot"

    while True:
        try:
            data = fetch_snapshot(api)
            if not data.get("ok"):
                print(f"API error: {data}", file=sys.stderr)
                return 2
            print("\033[2J\033[H", end="")
            print_snapshot(args.base_url.rstrip("/"), data, args.offline_sec)
        except (URLError, HTTPError, TimeoutError, json.JSONDecodeError) as e:
            print("\033[2J\033[H", end="")
            print(f"TIME-PON CLI Snapshot ERROR: {e}")
            print(f"Check API endpoint: {api}")
            if args.once:
                return 1
        if args.once:
            return 0
        time.sleep(max(args.interval, 0.5))


if __name__ == "__main__":
    raise SystemExit(main())
