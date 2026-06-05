#!/usr/bin/env python3
"""
Collect YouTube video rows for src/generate_data.php.

Output format:
video_id(0)    url(1)    title(2)    description(3)    duration_seconds(4)
"""

from __future__ import annotations

import argparse
import csv
import json
import time
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.parse import quote_plus

from main import (
    CollectorState,
    DEFAULT_CONTEXT,
    YOUTUBE_BASE,
    add_channel_from_renderer,
    add_video_from_renderer,
    collect_continuation_tokens,
    collect_video_page,
    dedupe,
    extract_json_assignment,
    extract_ytcfg,
    fetch_url,
    fill_missing_seed_data,
    post_json,
    walk_dicts,
)


DEFAULT_QUERIES = (
    "programming tutorial",
    "technology review",
    "education documentary",
    "gaming highlights",
    "music video",
    "science explained",
    "history documentary",
    "cooking recipe",
    "travel vlog",
    "fitness workout",
    "movie review",
    "sports highlights",
    "car review",
    "finance education",
    "language learning",
    "math tutorial",
    "design tutorial",
    "photography tips",
    "software engineering",
    "web development",
)

VIDEO_FIELDS = ("video_id", "url", "title", "description", "duration_seconds")


def read_existing_video_keys(path: Path) -> set[str]:
    if not path.exists():
        return set()

    keys: set[str] = set()
    with path.open("r", encoding="utf-8", newline="") as file:
        reader = csv.DictReader(file, delimiter="\t")
        if reader.fieldnames and {"video_id", "url"}.issubset(reader.fieldnames):
            for row in reader:
                video_id = (row.get("video_id") or "").strip()
                url = (row.get("url") or "").strip()
                if video_id:
                    keys.add(video_id)
                if url:
                    keys.add(url)
            return keys

    with path.open("r", encoding="utf-8") as file:
        for line in file:
            parts = line.rstrip("\r\n").split("\t")
            if len(parts) >= 2 and parts[0] != "video_id":
                keys.add(parts[0].strip())
                keys.add(parts[1].strip())
    return keys


def add_search_results(data: dict, state: CollectorState) -> None:
    for item in walk_dicts(data):
        if "videoRenderer" in item:
            add_video_from_renderer(state, item["videoRenderer"])
        elif "channelRenderer" in item:
            add_channel_from_renderer(state, item["channelRenderer"])


def is_new_video(video_id: str, existing_keys: set[str]) -> bool:
    return (
        video_id not in existing_keys
        and f"{YOUTUBE_BASE}/watch?v={video_id}" not in existing_keys
    )


def new_video_ids(state: CollectorState, existing_keys: set[str]) -> list[str]:
    return [
        video_id
        for video_id in dedupe(state.video_ids)
        if is_new_video(video_id, existing_keys)
    ]


def collect_from_search_pages(
    query: str,
    state: CollectorState,
    existing_keys: set[str],
    max_new_ids: int,
    max_pages: int,
    sleep_seconds: float,
) -> None:
    page = fetch_url(f"{YOUTUBE_BASE}/results?search_query={quote_plus(query)}")
    initial_data = extract_json_assignment(page, ("ytInitialData",))
    ytcfg = extract_ytcfg(page)

    add_search_results(initial_data, state)
    if len(new_video_ids(state, existing_keys)) >= max_new_ids:
        return

    api_key = ytcfg.get("INNERTUBE_API_KEY")
    context = ytcfg.get("INNERTUBE_CONTEXT") or DEFAULT_CONTEXT
    if not api_key:
        return

    queue = collect_continuation_tokens(initial_data)
    seen_tokens: set[str] = set()
    pages_read = 1

    while (
        queue
        and pages_read < max_pages
        and len(new_video_ids(state, existing_keys)) < max_new_ids
    ):
        token = queue.pop(0)
        if not token or token in seen_tokens:
            continue
        seen_tokens.add(token)

        response = post_json(
            f"{YOUTUBE_BASE}/youtubei/v1/search?key={api_key}&prettyPrint=false",
            {"context": context, "continuation": token},
        )
        pages_read += 1
        add_search_results(response, state)

        for new_token in collect_continuation_tokens(response):
            if new_token not in seen_tokens:
                queue.append(new_token)

        if sleep_seconds > 0:
            time.sleep(sleep_seconds)


def complete_loaded_video_count(
    state: CollectorState,
    existing_keys: set[str],
    loaded_ids: set[str],
) -> int:
    count = 0
    for record in state.videos.values():
        if record.video_id not in loaded_ids:
            continue
        if not is_new_video(record.video_id, existing_keys):
            continue
        if record.url and record.title and record.duration_seconds:
            count += 1
    return count


def collect_videos(
    args: argparse.Namespace,
    existing_keys: set[str],
) -> tuple[CollectorState, set[str]]:
    state = CollectorState()
    target_scan_count = args.count + max(50, args.count // 2)
    loaded_ids: set[str] = set()

    for query in args.queries or DEFAULT_QUERIES:
        try:
            collect_from_search_pages(
                query=query,
                state=state,
                existing_keys=existing_keys,
                max_new_ids=target_scan_count,
                max_pages=args.search_pages,
                sleep_seconds=args.sleep,
            )
        except (HTTPError, URLError, TimeoutError, json.JSONDecodeError) as exc:
            print(f"[warn] Search failed for {query!r}: {exc}")

        if len(new_video_ids(state, existing_keys)) >= target_scan_count:
            break
        if args.sleep > 0:
            time.sleep(args.sleep)

    new_ids = new_video_ids(state, existing_keys)

    for index, video_id in enumerate(new_ids, start=1):
        try:
            print(f"[info] Reading video {index}/{len(new_ids)}: {video_id}")
            collect_video_page(
                video_id=video_id,
                state=state,
                comments_per_video=0,
                max_comment_pages=0,
                sleep_seconds=args.sleep,
            )
            loaded_ids.add(video_id)
        except (HTTPError, URLError, TimeoutError, json.JSONDecodeError) as exc:
            print(f"[warn] Video failed for {video_id}: {exc}")
        if complete_loaded_video_count(state, existing_keys, loaded_ids) >= args.count:
            break
        if args.sleep > 0:
            time.sleep(args.sleep)

    fill_missing_seed_data(state, use_fallback_comments=False)
    return state, loaded_ids


def write_videos(
    path: Path,
    state: CollectorState,
    existing_keys: set[str],
    loaded_ids: set[str],
    max_count: int,
) -> int:
    path.parent.mkdir(parents=True, exist_ok=True)
    written_keys: set[str] = set(existing_keys)
    rows: list[dict[str, str]] = []

    for record in state.videos.values():
        if record.video_id not in loaded_ids:
            continue
        if record.video_id in written_keys or record.url in written_keys:
            continue
        if not record.url or not record.title or not record.duration_seconds:
            continue

        rows.append(
            {
                "video_id": record.video_id,
                "url": record.url,
                "title": record.title,
                "description": record.description or record.title,
                "duration_seconds": record.duration_seconds,
            }
        )
        written_keys.add(record.video_id)
        written_keys.add(record.url)

        if len(rows) >= max_count:
            break

    file_exists = path.exists() and path.stat().st_size > 0
    with path.open("a", encoding="utf-8", newline="") as file:
        writer = csv.DictWriter(file, fieldnames=VIDEO_FIELDS, delimiter="\t")
        if not file_exists:
            writer.writeheader()
        writer.writerows(rows)

    return len(rows)


def build_arg_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description="Collect unique YouTube videos into datacollector/data/videos.txt."
    )
    parser.add_argument(
        "-q",
        "--query",
        action="append",
        dest="queries",
        help="YouTube search query. Can be used multiple times.",
    )
    parser.add_argument(
        "-c",
        "--count",
        type=int,
        default=100,
        help="Number of new unique videos to collect.",
    )
    parser.add_argument(
        "-o",
        "--output",
        type=Path,
        default=Path(__file__).resolve().parent / "data" / "videos.txt",
        help="Target videos.txt path.",
    )
    parser.add_argument(
        "--sleep",
        type=float,
        default=0.5,
        help="Seconds to wait between YouTube requests.",
    )
    parser.add_argument(
        "--search-pages",
        type=int,
        default=20,
        help="Maximum YouTube search result pages to read per query.",
    )
    return parser


def main() -> int:
    args = build_arg_parser().parse_args()
    existing_keys = read_existing_video_keys(args.output)
    state, loaded_ids = collect_videos(args, existing_keys)
    written_count = write_videos(
        args.output,
        state,
        existing_keys,
        loaded_ids,
        args.count,
    )

    print(f"[ok] Added {written_count} new videos to: {args.output}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
