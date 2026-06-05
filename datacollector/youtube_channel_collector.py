#!/usr/bin/env python3
"""
Collect unique YouTube channel names into datacollector/data/channel_names.txt.
"""

from __future__ import annotations

import argparse
import json
import time
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.parse import quote_plus

from main import (
    DEFAULT_CONTEXT,
    YOUTUBE_BASE,
    clean_text,
    collect_continuation_tokens,
    dedupe,
    extract_json_assignment,
    extract_ytcfg,
    fetch_url,
    post_json,
    text_from_runs,
    walk_dicts,
)


DEFAULT_QUERIES = (
    "programming tutorial",
    "technology review",
    "gaming highlights",
    "music video",
    "education documentary",
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
    "news analysis",
    "comedy sketch",
    "makeup tutorial",
    "gardening tips",
    "home repair",
)


def read_names(path: Path) -> list[str]:
    if not path.exists():
        return []

    with path.open("r", encoding="utf-8") as file:
        return dedupe(line.rstrip("\r\n") for line in file)


def add_name(names: list[str], seen: set[str], value: str) -> None:
    name = clean_text(value, 250)
    if not name:
        return

    key = name.casefold()
    if key in seen:
        return

    seen.add(key)
    names.append(name)


def collect_channel_names_from_data(
    data: dict,
    names: list[str],
    seen: set[str],
) -> None:
    for item in walk_dicts(data):
        channel_renderer = item.get("channelRenderer")
        if isinstance(channel_renderer, dict):
            add_name(names, seen, text_from_runs(channel_renderer.get("title")))
            continue

        video_renderer = item.get("videoRenderer")
        if not isinstance(video_renderer, dict):
            continue

        channel_name = (
            text_from_runs(video_renderer.get("ownerText"))
            or text_from_runs(video_renderer.get("longBylineText"))
            or text_from_runs(video_renderer.get("shortBylineText"))
        )
        add_name(names, seen, channel_name)


def collect_from_search_pages(
    query: str,
    names: list[str],
    seen: set[str],
    target_new_count: int,
    max_pages: int,
    sleep_seconds: float,
) -> None:
    page = fetch_url(f"{YOUTUBE_BASE}/results?search_query={quote_plus(query)}")
    initial_data = extract_json_assignment(page, ("ytInitialData",))
    ytcfg = extract_ytcfg(page)

    before = len(names)
    collect_channel_names_from_data(initial_data, names, seen)
    if len(names) - before >= target_new_count:
        return

    api_key = ytcfg.get("INNERTUBE_API_KEY")
    context = ytcfg.get("INNERTUBE_CONTEXT") or DEFAULT_CONTEXT
    if not api_key:
        return

    queue = collect_continuation_tokens(initial_data)
    seen_tokens: set[str] = set()
    pages_read = 1

    while queue and pages_read < max_pages and len(names) - before < target_new_count:
        token = queue.pop(0)
        if not token or token in seen_tokens:
            continue

        seen_tokens.add(token)
        response = post_json(
            f"{YOUTUBE_BASE}/youtubei/v1/search?key={api_key}&prettyPrint=false",
            {"context": context, "continuation": token},
        )
        pages_read += 1

        collect_channel_names_from_data(response, names, seen)

        for new_token in collect_continuation_tokens(response):
            if new_token not in seen_tokens:
                queue.append(new_token)

        if sleep_seconds > 0:
            time.sleep(sleep_seconds)


def collect_names(args: argparse.Namespace, existing_names: list[str]) -> list[str]:
    names: list[str] = []
    seen = {name.casefold() for name in existing_names}

    for query in args.queries or DEFAULT_QUERIES:
        try:
            print(f"[info] Searching channels: {query}")
            collect_from_search_pages(
                query=query,
                names=names,
                seen=seen,
                target_new_count=args.count,
                max_pages=args.search_pages,
                sleep_seconds=args.sleep,
            )
        except (HTTPError, URLError, TimeoutError, json.JSONDecodeError) as exc:
            print(f"[warn] Search failed for {query!r}: {exc}")

        if len(names) >= args.count:
            break
        if args.sleep > 0:
            time.sleep(args.sleep)

    return names[: args.count]


def write_names(path: Path, existing_names: list[str], new_names: list[str]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    names = dedupe([*existing_names, *new_names])
    path.write_text("\n".join(names) + ("\n" if names else ""), encoding="utf-8")


def build_arg_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description="Collect unique YouTube channel names into channel_names.txt."
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
        default=300,
        help="Number of new unique channel names to collect.",
    )
    parser.add_argument(
        "-o",
        "--output",
        type=Path,
        default=Path(__file__).resolve().parent / "data" / "channel_names.txt",
        help="Target channel_names.txt path.",
    )
    parser.add_argument(
        "--search-pages",
        type=int,
        default=30,
        help="Maximum YouTube search result pages to read per query.",
    )
    parser.add_argument(
        "--sleep",
        type=float,
        default=0.5,
        help="Seconds to wait between YouTube requests.",
    )
    return parser


def main() -> int:
    args = build_arg_parser().parse_args()
    existing_names = read_names(args.output)
    new_names = collect_names(args, existing_names)
    write_names(args.output, existing_names, new_names)

    print(f"[ok] Added {len(new_names)} new channel names to: {args.output}")
    print(f"[ok] Total unique channel names: {len(dedupe([*existing_names, *new_names]))}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
