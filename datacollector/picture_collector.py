#!/usr/bin/env python3
"""
Collect random public image links from Lorem Picsum.

Outputs:
- image_links.txt: direct image URLs
- images.txt: tab-separated id, url, author, source_url
"""

from __future__ import annotations

import argparse
import csv
import json
import random
from pathlib import Path
from typing import Any
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen


PICSUM_LIST_URL = "https://picsum.photos/v2/list"


def fetch_json(url: str, timeout: int = 20) -> Any:
    request = Request(
        url,
        headers={
            "User-Agent": (
                "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 "
                "(KHTML, like Gecko) Chrome/124.0 Safari/537.36"
            ),
            "Accept": "application/json",
        },
    )
    with urlopen(request, timeout=timeout) as response:
        return json.loads(response.read().decode("utf-8", errors="replace"))


def direct_image_url(image_id: str, width: int, height: int) -> str:
    return f"https://picsum.photos/id/{image_id}/{width}/{height}"


def fallback_seed_images(count: int, width: int, height: int) -> list[dict[str, str]]:
    records: list[dict[str, str]] = []
    for index in range(1, count + 1):
        seed = f"youtube-clone-{index}"
        records.append(
            {
                "id": seed,
                "url": f"https://picsum.photos/seed/{seed}/{width}/{height}",
                "author": "Lorem Picsum",
                "source_url": "https://picsum.photos",
            }
        )
    return records


def collect_picsum_images(
    count: int,
    width: int,
    height: int,
    page_size: int,
    max_pages: int,
) -> list[dict[str, str]]:
    records: list[dict[str, str]] = []
    seen_ids: set[str] = set()

    for page in range(1, max_pages + 1):
        data = fetch_json(f"{PICSUM_LIST_URL}?page={page}&limit={page_size}")
        if not isinstance(data, list):
            continue

        for item in data:
            if not isinstance(item, dict):
                continue
            image_id = str(item.get("id", "")).strip()
            if not image_id or image_id in seen_ids:
                continue

            seen_ids.add(image_id)
            records.append(
                {
                    "id": image_id,
                    "url": direct_image_url(image_id, width, height),
                    "author": str(item.get("author", "")).strip(),
                    "source_url": str(item.get("url", "")).strip(),
                }
            )

            if len(records) >= count:
                return records

    return records


def write_outputs(records: list[dict[str, str]], output_dir: Path) -> None:
    output_dir.mkdir(parents=True, exist_ok=True)

    image_links = [record["url"] for record in records]
    (output_dir / "image_links.txt").write_text(
        "\n".join(image_links) + ("\n" if image_links else ""),
        encoding="utf-8",
    )

    with (output_dir / "images.txt").open("w", encoding="utf-8", newline="") as file:
        writer = csv.DictWriter(
            file,
            fieldnames=("id", "url", "author", "source_url"),
            delimiter="\t",
        )
        writer.writeheader()
        writer.writerows(records)


def build_arg_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description="Collect random public image links from Lorem Picsum."
    )
    parser.add_argument(
        "-c",
        "--count",
        type=int,
        default=100,
        help="Number of image links to collect.",
    )
    parser.add_argument(
        "-o",
        "--output",
        type=Path,
        default=Path(__file__).resolve().parent / "data",
        help="Directory where txt files will be written.",
    )
    parser.add_argument(
        "--width",
        type=int,
        default=640,
        help="Direct image URL width.",
    )
    parser.add_argument(
        "--height",
        type=int,
        default=360,
        help="Direct image URL height.",
    )
    parser.add_argument(
        "--page-size",
        type=int,
        default=100,
        help="Picsum API page size.",
    )
    parser.add_argument(
        "--max-pages",
        type=int,
        default=10,
        help="Maximum API pages to read.",
    )
    parser.add_argument(
        "--shuffle",
        action="store_true",
        help="Shuffle image records before writing.",
    )
    parser.add_argument(
        "--no-fallback",
        action="store_true",
        help="Do not generate seed URLs if the API request fails.",
    )
    return parser


def main() -> int:
    args = build_arg_parser().parse_args()

    try:
        records = collect_picsum_images(
            count=args.count,
            width=args.width,
            height=args.height,
            page_size=args.page_size,
            max_pages=args.max_pages,
        )
    except (HTTPError, URLError, TimeoutError, json.JSONDecodeError) as exc:
        if args.no_fallback:
            raise
        print(f"[warn] Picsum API failed, writing seed image URLs instead: {exc}")
        records = fallback_seed_images(args.count, args.width, args.height)

    if len(records) < args.count and not args.no_fallback:
        records.extend(
            fallback_seed_images(args.count - len(records), args.width, args.height)
        )

    records = records[: args.count]
    if args.shuffle:
        random.shuffle(records)

    write_outputs(records, args.output)
    print(f"[ok] Wrote {len(records)} image links to: {args.output}")
    print(f"[ok] Direct links: {args.output / 'image_links.txt'}")
    print(f"[ok] Detailed rows: {args.output / 'images.txt'}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
