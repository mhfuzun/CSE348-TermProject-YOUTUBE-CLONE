#!/usr/bin/env python3
"""
Collect public YouTube seed data into separate text files.

The generated files are intentionally independent lists. For example,
comment authors and comment bodies are not stored as pairs; install.php can
randomly combine them later when creating fake rows.
"""

from __future__ import annotations

import argparse
import csv
import html
import json
import random
import re
import time
from dataclasses import dataclass, field
from pathlib import Path
from typing import Any, Iterable
from urllib.error import HTTPError, URLError
from urllib.parse import quote_plus
from urllib.request import Request, urlopen


YOUTUBE_BASE = "https://www.youtube.com"
DEFAULT_QUERIES = (
    "programming tutorial",
    "music video",
    "gaming highlights",
    "technology review",
    "education documentary",
)
DEFAULT_CONTEXT = {
    "client": {
        "hl": "en",
        "gl": "US",
        "clientName": "WEB",
        "clientVersion": "2.20240501.00.00",
    }
}
FALLBACK_COMMENTS = (
    "Great video, thanks for sharing.",
    "This was helpful.",
    "Nice explanation.",
    "I learned something new from this.",
    "Good content.",
    "Thanks, this helped a lot.",
    "Very clear and useful.",
    "I like this video.",
    "This is exactly what I needed.",
    "Please make more videos like this.",
)


@dataclass
class VideoRecord:
    video_id: str
    url: str = ""
    title: str = ""
    description: str = ""
    duration_seconds: str = ""
    view_count: str = ""
    channel_name: str = ""
    channel_link: str = ""


@dataclass
class CollectorState:
    videos: dict[str, VideoRecord] = field(default_factory=dict)
    usernames: list[str] = field(default_factory=list)
    comments: list[str] = field(default_factory=list)
    video_ids: list[str] = field(default_factory=list)
    video_links: list[str] = field(default_factory=list)
    video_titles: list[str] = field(default_factory=list)
    video_descriptions: list[str] = field(default_factory=list)
    video_durations_seconds: list[str] = field(default_factory=list)
    video_view_counts: list[str] = field(default_factory=list)
    channel_names: list[str] = field(default_factory=list)
    channel_links: list[str] = field(default_factory=list)
    channel_descriptions: list[str] = field(default_factory=list)


def clean_text(value: Any, limit: int | None = None) -> str:
    """Normalize YouTube text into a single SQL-friendly line."""
    if value is None:
        return ""
    text = html.unescape(str(value))
    text = re.sub(r"\s+", " ", text).strip()
    text = text.replace("\x00", "")
    if limit and len(text) > limit:
        return text[: limit - 1].rstrip() + "."
    return text


def text_from_runs(obj: Any) -> str:
    """Read the common YouTube text formats: simpleText and runs."""
    if not obj:
        return ""
    if isinstance(obj, str):
        return obj
    if isinstance(obj, dict):
        if "simpleText" in obj:
            return clean_text(obj.get("simpleText"))
        if "runs" in obj:
            return clean_text("".join(run.get("text", "") for run in obj["runs"]))
        if "content" in obj:
            content = obj.get("content")
            if isinstance(content, dict):
                return text_from_runs(content)
            return clean_text(content)
    return ""


def dedupe(values: Iterable[str]) -> list[str]:
    seen: set[str] = set()
    result: list[str] = []
    for value in values:
        cleaned = clean_text(value)
        key = cleaned.casefold()
        if cleaned and key not in seen:
            seen.add(key)
            result.append(cleaned)
    return result


def duration_to_seconds(value: str) -> str:
    text = clean_text(value).lower()
    if not text:
        return ""
    if re.fullmatch(r"\d+", text):
        return text
    if re.fullmatch(r"\d{1,2}(?::\d{2}){1,2}", text):
        seconds = 0
        for part in text.split(":"):
            seconds = seconds * 60 + int(part)
        return str(seconds)

    total = 0
    patterns = (
        (r"(\d+)\s*(?:hour|hours|hr|hrs|saat)", 3600),
        (r"(\d+)\s*(?:minute|minutes|min|mins|dakika)", 60),
        (r"(\d+)\s*(?:second|seconds|sec|secs|saniye)", 1),
    )
    for pattern, multiplier in patterns:
        match = re.search(pattern, text)
        if match:
            total += int(match.group(1)) * multiplier
    return str(total) if total else ""


def compact_count_to_int(value: str) -> str:
    text = clean_text(value).lower().replace(",", "").replace(" ", "")
    if not text or "no" in text:
        return "0"

    match = re.search(r"(\d+(?:\.\d+)?)([kmb]?)", text)
    if not match:
        return ""

    number = float(match.group(1))
    multiplier = {"": 1, "k": 1_000, "m": 1_000_000, "b": 1_000_000_000}
    return str(int(number * multiplier[match.group(2)]))


def fetch_url(url: str, timeout: int = 20) -> str:
    request = Request(
        url,
        headers={
            "User-Agent": (
                "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 "
                "(KHTML, like Gecko) Chrome/124.0 Safari/537.36"
            ),
            "Accept-Language": "en-US,en;q=0.9",
            "Cookie": "CONSENT=YES+; SOCS=CAI",
        },
    )
    with urlopen(request, timeout=timeout) as response:
        return response.read().decode("utf-8", errors="replace")


def post_json(url: str, payload: dict[str, Any], timeout: int = 20) -> dict[str, Any]:
    body = json.dumps(payload).encode("utf-8")
    request = Request(
        url,
        data=body,
        headers={
            "User-Agent": (
                "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 "
                "(KHTML, like Gecko) Chrome/124.0 Safari/537.36"
            ),
            "Accept": "*/*",
            "Accept-Language": "en-US,en;q=0.9",
            "Content-Type": "application/json",
            "Cookie": "CONSENT=YES+; SOCS=CAI",
            "Origin": YOUTUBE_BASE,
            "Referer": YOUTUBE_BASE,
        },
    )
    with urlopen(request, timeout=timeout) as response:
        return json.loads(response.read().decode("utf-8", errors="replace"))


def raw_decode_object(text: str, start_index: int) -> Any:
    decoder = json.JSONDecoder()
    while start_index < len(text) and text[start_index].isspace():
        start_index += 1
    data, _ = decoder.raw_decode(text[start_index:])
    return data


def extract_json_assignment(page: str, names: tuple[str, ...]) -> dict[str, Any]:
    for name in names:
        match = re.search(rf"(?:var\s+)?{re.escape(name)}\s*=\s*", page)
        if not match:
            continue
        try:
            data = raw_decode_object(page, match.end())
        except json.JSONDecodeError:
            continue
        if isinstance(data, dict):
            return data
    return {}


def extract_ytcfg(page: str) -> dict[str, Any]:
    config: dict[str, Any] = {}
    for match in re.finditer(r"ytcfg\.set\(", page):
        json_start = page.find("{", match.end())
        if json_start == -1:
            continue
        try:
            data = raw_decode_object(page, json_start)
        except json.JSONDecodeError:
            continue
        if isinstance(data, dict):
            config.update(data)
    return config


def walk_dicts(data: Any) -> Iterable[dict[str, Any]]:
    if isinstance(data, dict):
        yield data
        for value in data.values():
            yield from walk_dicts(value)
    elif isinstance(data, list):
        for item in data:
            yield from walk_dicts(item)


def absolute_youtube_url(path_or_url: str) -> str:
    if not path_or_url:
        return ""
    if path_or_url.startswith("http"):
        return path_or_url
    if path_or_url.startswith("/"):
        return YOUTUBE_BASE + path_or_url
    if path_or_url.startswith("UC"):
        return f"{YOUTUBE_BASE}/channel/{path_or_url}"
    return YOUTUBE_BASE + "/" + path_or_url


def merge_video_record(
    state: CollectorState,
    video_id: str,
    *,
    url: str = "",
    title: str = "",
    description: str = "",
    duration_seconds: str = "",
    view_count: str = "",
    channel_name: str = "",
    channel_link: str = "",
) -> None:
    video_id = clean_text(video_id)
    if not video_id:
        return

    record = state.videos.setdefault(
        video_id,
        VideoRecord(video_id=video_id, url=url or f"{YOUTUBE_BASE}/watch?v={video_id}"),
    )

    updates = {
        "url": clean_text(url),
        "title": clean_text(title, 100),
        "duration_seconds": duration_to_seconds(duration_seconds),
        "view_count": compact_count_to_int(view_count),
        "channel_name": clean_text(channel_name, 250),
        "channel_link": absolute_youtube_url(clean_text(channel_link)),
    }
    for field_name, value in updates.items():
        if value and not getattr(record, field_name):
            setattr(record, field_name, value)

    description = clean_text(description, 1024)
    if description and len(description) > len(record.description):
        record.description = description


def add_video_from_renderer(state: CollectorState, renderer: dict[str, Any]) -> None:
    video_id = clean_text(renderer.get("videoId"))
    if not video_id:
        return

    title = clean_text(text_from_runs(renderer.get("title")), 100)
    description = clean_text(text_from_runs(renderer.get("descriptionSnippet")), 1024)
    duration_seconds = duration_to_seconds(text_from_runs(renderer.get("lengthText")))
    view_count = compact_count_to_int(text_from_runs(renderer.get("viewCountText")))
    state.video_ids.append(video_id)
    state.video_links.append(f"{YOUTUBE_BASE}/watch?v={video_id}")
    state.video_titles.append(title)
    state.video_descriptions.append(description)
    state.video_durations_seconds.append(duration_seconds)
    state.video_view_counts.append(view_count)

    channel_text = (
        text_from_runs(renderer.get("ownerText"))
        or text_from_runs(renderer.get("longBylineText"))
        or text_from_runs(renderer.get("shortBylineText"))
    )
    state.channel_names.append(clean_text(channel_text, 250))

    channel_url = ""
    for key in ("ownerText", "longBylineText", "shortBylineText"):
        runs = renderer.get(key, {}).get("runs", [])
        if runs:
            endpoint = runs[0].get("navigationEndpoint", {})
            browse = endpoint.get("browseEndpoint", {})
            channel_url = browse.get("canonicalBaseUrl") or browse.get("browseId", "")
            break
    state.channel_links.append(absolute_youtube_url(channel_url))
    merge_video_record(
        state,
        video_id,
        title=title,
        description=description,
        duration_seconds=duration_seconds,
        view_count=view_count,
        channel_name=channel_text,
        channel_link=channel_url,
    )


def add_channel_from_renderer(state: CollectorState, renderer: dict[str, Any]) -> None:
    title = text_from_runs(renderer.get("title"))
    channel_id = clean_text(renderer.get("channelId"))
    url = renderer.get("navigationEndpoint", {}).get("browseEndpoint", {}).get(
        "canonicalBaseUrl", ""
    )

    state.channel_names.append(clean_text(title, 250))
    state.channel_links.append(absolute_youtube_url(url or channel_id))
    state.channel_descriptions.append(
        clean_text(text_from_runs(renderer.get("descriptionSnippet")), 250)
    )


def add_video_from_player_response(
    state: CollectorState, player_response: dict[str, Any]
) -> None:
    details = player_response.get("videoDetails", {})
    microformat = player_response.get("microformat", {}).get("playerMicroformatRenderer", {})
    video_id = clean_text(details.get("videoId"))
    if not video_id:
        return

    url = f"{YOUTUBE_BASE}/watch?v={video_id}"
    title = clean_text(details.get("title"), 100)
    description = clean_text(details.get("shortDescription"), 1024)
    duration_seconds = duration_to_seconds(details.get("lengthSeconds"))
    view_count = compact_count_to_int(details.get("viewCount"))
    channel_name = clean_text(details.get("author"), 250)
    channel_url = microformat.get("ownerProfileUrl") or details.get("channelId", "")

    state.video_ids.append(video_id)
    state.video_links.append(url)
    state.video_titles.append(title)
    state.video_descriptions.append(description)
    state.video_durations_seconds.append(duration_seconds)
    state.video_view_counts.append(view_count)
    state.channel_names.append(channel_name)
    state.channel_links.append(absolute_youtube_url(channel_url))
    merge_video_record(
        state,
        video_id,
        url=url,
        title=title,
        description=description,
        duration_seconds=duration_seconds,
        view_count=view_count,
        channel_name=channel_name,
        channel_link=channel_url,
    )


def best_description_from_watch_data(data: dict[str, Any]) -> str:
    candidates: list[str] = []
    keys = (
        "attributedDescriptionBodyText",
        "descriptionText",
        "description",
        "shortDescription",
    )
    for item in walk_dicts(data):
        for key in keys:
            text = clean_text(text_from_runs(item.get(key)), 1024)
            if len(text) >= 20:
                candidates.append(text)
    return max(candidates, key=len, default="")


def add_comments_from_data(state: CollectorState, data: dict[str, Any]) -> int:
    before = len(state.comments)
    for item in walk_dicts(data):
        entity = item.get("commentEntityPayload")
        if isinstance(entity, dict):
            properties = entity.get("properties", {})
            author_data = entity.get("author", {})
            comment = text_from_runs(properties.get("content"))
            author = (
                clean_text(author_data.get("displayName"))
                or clean_text(properties.get("authorButtonA11y"))
            )
            if comment:
                state.comments.append(clean_text(comment, 240))
            if author:
                state.usernames.append(clean_text(author, 100))
            continue

        renderer = item.get("commentRenderer") or item.get("commentThreadRenderer", {}).get(
            "comment", {}
        ).get("commentRenderer")
        view_model = item.get("commentViewModel")
        if not renderer and not view_model:
            continue

        source = renderer or view_model
        comment = (
            text_from_runs(source.get("contentText"))
            or text_from_runs(source.get("commentText"))
            or text_from_runs(source.get("content"))
            or text_from_runs(source.get("body"))
        )
        author = (
            text_from_runs(source.get("authorText"))
            or text_from_runs(source.get("author"))
            or text_from_runs(source.get("authorName"))
        )
        if comment:
            state.comments.append(clean_text(comment, 240))
        if author:
            state.usernames.append(clean_text(author, 100))
    return len(state.comments) - before


def collect_continuation_tokens(data: Any) -> list[str]:
    tokens: list[str] = []
    for item in walk_dicts(data):
        command = item.get("continuationCommand")
        if isinstance(command, dict):
            tokens.append(clean_text(command.get("token")))

        for key in ("nextContinuationData", "reloadContinuationData"):
            continuation_data = item.get(key)
            if isinstance(continuation_data, dict):
                tokens.append(clean_text(continuation_data.get("continuation")))
                tokens.append(clean_text(continuation_data.get("token")))

        endpoint = item.get("continuationEndpoint")
        if isinstance(endpoint, dict):
            command = endpoint.get("continuationCommand")
            if isinstance(command, dict):
                tokens.append(clean_text(command.get("token")))
            for key in ("nextContinuationData", "reloadContinuationData"):
                continuation_data = endpoint.get(key)
                if isinstance(continuation_data, dict):
                    tokens.append(clean_text(continuation_data.get("continuation")))
                    tokens.append(clean_text(continuation_data.get("token")))
    return dedupe(tokens)


def likely_comment_tokens(data: dict[str, Any]) -> list[str]:
    comment_tokens: list[str] = []
    fallback_tokens = collect_continuation_tokens(data)

    for item in walk_dicts(data):
        renderer = item.get("itemSectionRenderer", item)
        target_id = clean_text(renderer.get("targetId"))
        renderer_text = json.dumps(renderer, ensure_ascii=False)
        if target_id == "comments-section" or any(
            marker in renderer_text
            for marker in (
                "commentsHeaderRenderer",
                "commentThreadRenderer",
                "commentSectionRenderer",
                "commentSimpleboxRenderer",
                "comments-section",
                "Comments",
            )
        ):
            comment_tokens.extend(collect_continuation_tokens(renderer))

    return dedupe(comment_tokens) or fallback_tokens


def collect_from_search(query: str, state: CollectorState, max_videos: int) -> None:
    page = fetch_url(f"{YOUTUBE_BASE}/results?search_query={quote_plus(query)}")
    initial_data = extract_json_assignment(page, ("ytInitialData",))

    for item in walk_dicts(initial_data):
        if "videoRenderer" in item:
            add_video_from_renderer(state, item["videoRenderer"])
        elif "channelRenderer" in item:
            add_channel_from_renderer(state, item["channelRenderer"])

        if len(dedupe(state.video_ids)) >= max_videos:
            return


def collect_video_page(
    video_id: str,
    state: CollectorState,
    comments_per_video: int,
    max_comment_pages: int,
    sleep_seconds: float,
) -> None:
    page = fetch_url(f"{YOUTUBE_BASE}/watch?v={quote_plus(video_id)}")
    initial_data = extract_json_assignment(page, ("ytInitialData",))
    player_response = extract_json_assignment(page, ("ytInitialPlayerResponse",))
    ytcfg = extract_ytcfg(page)

    add_video_from_player_response(state, player_response)
    merge_video_record(
        state,
        video_id,
        description=best_description_from_watch_data(initial_data),
    )
    add_comments_from_data(state, initial_data)

    api_key = ytcfg.get("INNERTUBE_API_KEY")
    context = ytcfg.get("INNERTUBE_CONTEXT") or DEFAULT_CONTEXT
    if not api_key or comments_per_video <= 0:
        return

    collected_before = len(state.comments)
    queue = likely_comment_tokens(initial_data)
    seen_tokens: set[str] = set()
    pages_read = 0

    while queue and pages_read < max_comment_pages:
        token = queue.pop(0)
        if not token or token in seen_tokens:
            continue
        seen_tokens.add(token)

        response = post_json(
            f"{YOUTUBE_BASE}/youtubei/v1/next?key={api_key}&prettyPrint=false",
            {"context": context, "continuation": token},
        )
        pages_read += 1
        add_comments_from_data(state, response)

        if len(state.comments) - collected_before >= comments_per_video:
            return

        for new_token in collect_continuation_tokens(response):
            if new_token not in seen_tokens:
                queue.append(new_token)

        if sleep_seconds > 0:
            time.sleep(sleep_seconds)


def fill_missing_seed_data(state: CollectorState, use_fallback_comments: bool) -> None:
    for record in state.videos.values():
        if not record.description:
            record.description = record.title or record.url

    if use_fallback_comments and not dedupe(state.comments):
        state.comments.extend(FALLBACK_COMMENTS)


def write_lines(path: Path, lines: Iterable[str]) -> None:
    values = [clean_text(line) for line in lines]
    path.write_text("\n".join(values) + ("\n" if values else ""), encoding="utf-8")


def write_video_records(state: CollectorState, output_dir: Path) -> list[VideoRecord]:
    records = list(state.videos.values())
    fieldnames = (
        "video_id",
        "url",
        "title",
        "description",
        "duration_seconds",
        "view_count",
        "channel_name",
        "channel_link",
    )
    with (output_dir / "videos.txt").open("w", encoding="utf-8", newline="") as file:
        writer = csv.DictWriter(file, fieldnames=fieldnames, delimiter="\t")
        writer.writeheader()
        for record in records:
            writer.writerow({field: getattr(record, field) for field in fieldnames})
    return records


def channel_records_from_videos(state: CollectorState) -> list[dict[str, str]]:
    records: list[dict[str, str]] = []
    seen: set[str] = set()

    for video in state.videos.values():
        name = clean_text(video.channel_name, 250)
        link = absolute_youtube_url(clean_text(video.channel_link))
        if not name and not link:
            continue
        key = (name or link).casefold()
        if key in seen:
            continue
        seen.add(key)
        records.append(
            {
                "name": name or link,
                "link": link,
                "description": f"YouTube channel: {name or link}",
            }
        )

    if records:
        return records

    links = dedupe(state.channel_links)
    for index, name in enumerate(dedupe(state.channel_names)):
        link = links[index] if index < len(links) else ""
        records.append(
            {
                "name": name,
                "link": absolute_youtube_url(link),
                "description": f"YouTube channel: {name}",
            }
        )
    return records


def write_channel_records(state: CollectorState, output_dir: Path) -> list[dict[str, str]]:
    records = channel_records_from_videos(state)
    fieldnames = ("name", "link", "description")
    with (output_dir / "channels.txt").open("w", encoding="utf-8", newline="") as file:
        writer = csv.DictWriter(file, fieldnames=fieldnames, delimiter="\t")
        writer.writeheader()
        writer.writerows(records)
    return records


def write_text_files(state: CollectorState, output_dir: Path) -> None:
    output_dir.mkdir(parents=True, exist_ok=True)
    video_records = write_video_records(state, output_dir)
    channel_records = write_channel_records(state, output_dir)
    video_files = {
        "video_ids.txt": [record.video_id for record in video_records],
        "video_links.txt": [record.url for record in video_records],
        "video_titles.txt": [record.title for record in video_records],
        "video_descriptions.txt": [record.description for record in video_records],
        "video_durations_seconds.txt": [
            record.duration_seconds for record in video_records
        ],
        "video_view_counts.txt": [record.view_count for record in video_records],
    }
    channel_files = {
        "channel_names.txt": [record["name"] for record in channel_records],
        "channel_links.txt": [record["link"] for record in channel_records],
        "channel_descriptions.txt": [
            record["description"] for record in channel_records
        ],
    }
    independent_files = {
        "usernames.txt": state.usernames + state.channel_names,
        "comments.txt": state.comments,
    }

    for filename, values in video_files.items():
        write_lines(output_dir / filename, values)

    for filename, values in channel_files.items():
        write_lines(output_dir / filename, values)

    for filename, values in independent_files.items():
        write_lines(output_dir / filename, dedupe(values))


def build_arg_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description="Collect YouTube names, comments, video links and channel data."
    )
    parser.add_argument(
        "-q",
        "--query",
        action="append",
        dest="queries",
        help="YouTube search query. Can be used multiple times.",
    )
    parser.add_argument(
        "-o",
        "--output",
        type=Path,
        default=Path(__file__).resolve().parent / "data",
        help="Directory where txt files will be written.",
    )
    parser.add_argument(
        "--max-videos",
        type=int,
        default=25,
        help="Maximum unique videos to collect from search results.",
    )
    parser.add_argument(
        "--comments-per-video",
        type=int,
        default=15,
        help="Maximum comments to collect from each video.",
    )
    parser.add_argument(
        "--max-comment-pages",
        type=int,
        default=4,
        help="Maximum YouTube continuation pages to request per video.",
    )
    parser.add_argument(
        "--sleep",
        type=float,
        default=0.5,
        help="Seconds to wait between continuation requests.",
    )
    parser.add_argument(
        "--shuffle",
        action="store_true",
        help="Shuffle independent output lists before writing them.",
    )
    parser.add_argument(
        "--no-fallback-comments",
        action="store_true",
        help="Do not write generic seed comments if YouTube returns no comments.",
    )
    return parser


def main() -> int:
    args = build_arg_parser().parse_args()
    queries = args.queries or list(DEFAULT_QUERIES)
    state = CollectorState()

    for query in queries:
        try:
            collect_from_search(query, state, args.max_videos)
        except (HTTPError, URLError, TimeoutError, json.JSONDecodeError) as exc:
            print(f"[warn] Search failed for {query!r}: {exc}")
        if len(dedupe(state.video_ids)) >= args.max_videos:
            break
        if args.sleep > 0:
            time.sleep(args.sleep)

    video_ids = dedupe(state.video_ids)[: args.max_videos]
    for index, video_id in enumerate(video_ids, start=1):
        try:
            print(f"[info] Reading video {index}/{len(video_ids)}: {video_id}")
            collect_video_page(
                video_id=video_id,
                state=state,
                comments_per_video=args.comments_per_video,
                max_comment_pages=args.max_comment_pages,
                sleep_seconds=args.sleep,
            )
        except (HTTPError, URLError, TimeoutError, json.JSONDecodeError) as exc:
            print(f"[warn] Video failed for {video_id}: {exc}")
        if args.sleep > 0:
            time.sleep(args.sleep)

    fill_missing_seed_data(state, use_fallback_comments=not args.no_fallback_comments)

    if args.shuffle:
        independent_lists = (
            state.usernames,
            state.comments,
            state.channel_names,
            state.channel_links,
            state.channel_descriptions,
        )
        for values in independent_lists:
            random.shuffle(values)

    write_text_files(state, args.output)

    print(f"[ok] Wrote txt files to: {args.output}")
    print(
        "[ok] Counts: "
        f"usernames={len(dedupe(state.usernames + state.channel_names))}, "
        f"comments={len(dedupe(state.comments))}, "
        f"videos={len(dedupe(state.video_links))}, "
        f"channels={len(dedupe(state.channel_names))}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
