#!/usr/bin/env python3
"""Crawl a site and fail on blocking technical-SEO inconsistencies.

The script intentionally uses only Python's standard library so it can run in CI or on a
locked-down WordPress host. It writes JSON (machine readable), CSV (URL inventory), and
Markdown (review summary) from the same crawl.
"""

from __future__ import annotations

import argparse
import csv
import json
import re
import sys
import time
from collections import Counter, defaultdict, deque
from dataclasses import asdict, dataclass, field
from html.parser import HTMLParser
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.parse import parse_qsl, urljoin, urlsplit, urlunsplit
from urllib.request import HTTPRedirectHandler, Request, build_opener
from urllib.robotparser import RobotFileParser
from xml.etree import ElementTree

USER_AGENT = "OligoPolyTechnicalSEOAudit/1.0 (+https://www.oligopolypeptides.com/)"
HTML_TYPES = ("text/html", "application/xhtml+xml")
SITEMAP_TYPES = ("application/xml", "text/xml")


class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):  # noqa: D401
        return None


@dataclass
class Page:
    url: str
    status: int = 0
    redirect_target: str = ""
    redirect_hops: int = 0
    final_url: str = ""
    canonical: str = ""
    canonical_count: int = 0
    meta_robots: str = ""
    x_robots_tag: str = ""
    robots_allowed: bool = True
    title: str = ""
    meta_description: str = ""
    h1: list[str] = field(default_factory=list)
    content_length: int = 0
    schema_types: list[str] = field(default_factory=list)
    internal_inlinks: int = 0
    internal_outlinks: int = 0
    sitemap_membership: list[str] = field(default_factory=list)
    host: str = ""
    protocol: str = ""
    query_parameters: list[str] = field(default_factory=list)
    error: str = ""


class Extractor(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.links, self.canonicals, self.hreflang, self.feeds = [], [], [], []
        self.title, self.description, self.robots = "", "", ""
        self.h1, self.schema_types, self.text = [], set(), []
        self._capture, self._buffer = "", []
        self._jsonld = False

    def handle_starttag(self, tag, attrs):
        a = {k.lower(): (v or "") for k, v in attrs}
        if tag == "a" and a.get("href"):
            self.links.append(a["href"])
        if tag == "link" and a.get("href"):
            rel = set(a.get("rel", "").lower().split())
            if "canonical" in rel:
                self.canonicals.append(a["href"])
            if "alternate" in rel and a.get("hreflang"):
                self.hreflang.append(a["href"])
            if "alternate" in rel and ("rss" in a.get("type", "") or "atom" in a.get("type", "")):
                self.feeds.append(a["href"])
        if tag == "meta":
            name = a.get("name", "").lower()
            prop = a.get("property", "").lower()
            if name == "description": self.description = a.get("content", "").strip()
            if name in ("robots", "googlebot"): self.robots += (", " if self.robots else "") + a.get("content", "")
            if prop == "og:url" and a.get("content"): self.links.append(a["content"])
        if tag in ("title", "h1"):
            self._capture, self._buffer = tag, []
        if tag == "script" and a.get("type", "").lower() == "application/ld+json":
            self._jsonld, self._buffer = True, []

    def handle_data(self, data):
        if self._capture or self._jsonld: self._buffer.append(data)
        if not self._jsonld: self.text.append(data)

    def handle_endtag(self, tag):
        value = " ".join("".join(self._buffer).split())
        if tag == "title" and self._capture == "title": self.title, self._capture = value, ""
        elif tag == "h1" and self._capture == "h1": self.h1.append(value); self._capture = ""
        elif tag == "script" and self._jsonld:
            try:
                def types(obj):
                    if isinstance(obj, dict):
                        t = obj.get("@type", [])
                        for item in ([t] if isinstance(t, str) else t): self.schema_types.add(str(item))
                        for v in obj.values(): types(v)
                    elif isinstance(obj, list):
                        for v in obj: types(v)
                types(json.loads("".join(self._buffer)))
            except (ValueError, TypeError):
                self.schema_types.add("INVALID_JSON_LD")
            self._jsonld = False
        self._buffer = []


def normalize(url, base=""):
    url = urljoin(base, url.strip()).split("#", 1)[0]
    p = urlsplit(url)
    if p.scheme not in ("http", "https") or not p.netloc: return ""
    path = re.sub(r"/{2,}", "/", p.path or "/")
    return urlunsplit((p.scheme.lower(), p.netloc.lower(), path, p.query, ""))


class Crawler:
    def __init__(self, start, output, max_urls=5000, timeout=15):
        self.start, self.output = normalize(start), Path(output)
        self.host = urlsplit(self.start).netloc
        self.max_urls, self.timeout = max_urls, timeout
        self.opener = build_opener(NoRedirect)
        self.pages, self.edges, self.sitemap_map = {}, defaultdict(set), defaultdict(set)
        self.sitemaps, self.external = set(), set()
        self.robot = RobotFileParser(urljoin(self.start, "/robots.txt"))
        try: self.robot.read()
        except Exception: self.robot = None

    def request(self, url):
        req = Request(url, headers={"User-Agent": USER_AGENT, "Accept": "text/html,application/xml;q=0.9,*/*;q=0.1"})
        try:
            res = self.opener.open(req, timeout=self.timeout)
            return res.status, dict(res.headers), res.read(5_000_000), ""
        except HTTPError as e:
            body = e.read(5_000_000)
            e.close()
            return e.code, dict(e.headers), body, ""
        except (URLError, TimeoutError, OSError) as e:
            return 0, {}, b"", str(e)

    def redirects(self, url):
        current, seen, first = url, set(), ""
        for hops in range(0, 11):
            status, headers, body, error = self.request(current)
            location = headers.get("Location", headers.get("location", ""))
            if status not in (301, 302, 303, 307, 308) or not location:
                return status, headers, body, error, first, hops, current
            nxt = normalize(location, current)
            if not first: first = nxt
            if nxt in seen or not nxt: return status, headers, body, "redirect loop", first, hops + 1, nxt
            seen.add(current); current = nxt
        return status, headers, body, "more than 10 redirects", first, 11, current

    def discover_sitemaps(self):
        candidates = {urljoin(self.start, "/sitemap_index.xml"), urljoin(self.start, "/sitemap.xml")}
        status, _, body, _ = self.request(urljoin(self.start, "/robots.txt"))
        if status == 200:
            for value in re.findall(rb"(?im)^sitemap:\s*(\S+)", body): candidates.add(normalize(value.decode(), self.start))
        queue = deque(candidates)
        while queue and len(self.sitemaps) < 100:
            sm = queue.popleft()
            if not sm or sm in self.sitemaps: continue
            status, _, body, _ = self.request(sm); self.sitemaps.add(sm)
            if status != 200: continue
            try: root = ElementTree.fromstring(body)
            except ElementTree.ParseError: continue
            locs = [normalize(x.text or "", sm) for x in root.iter() if x.tag.rsplit("}", 1)[-1] == "loc"]
            if root.tag.rsplit("}", 1)[-1] == "sitemapindex": queue.extend(locs)
            else:
                for loc in locs:
                    if urlsplit(loc).netloc == self.host: self.sitemap_map[loc].add(sm)

    def crawl(self):
        self.discover_sitemaps()
        queue, queued = deque([self.start, *self.sitemap_map]), {self.start, *self.sitemap_map}
        while queue and len(self.pages) < self.max_urls:
            url = queue.popleft()
            status, headers, body, error, target, hops, final = self.redirects(url)
            p = urlsplit(url)
            page = Page(url=url, status=status, redirect_target=target, redirect_hops=hops,
                        final_url=final, x_robots_tag=headers.get("X-Robots-Tag", headers.get("x-robots-tag", "")),
                        robots_allowed=self.robot.can_fetch(USER_AGENT, url) if self.robot else True,
                        sitemap_membership=sorted(self.sitemap_map[url]), host=p.netloc, protocol=p.scheme,
                        query_parameters=sorted({k for k, _ in parse_qsl(p.query)}), error=error)
            ctype = headers.get("Content-Type", headers.get("content-type", "")).lower()
            if status == 200 and any(t in ctype for t in HTML_TYPES):
                ex = Extractor()
                try: ex.feed(body.decode(headers.get_content_charset() or "utf-8", "replace") if hasattr(headers, "get_content_charset") else body.decode("utf-8", "replace"))
                except Exception as e: page.error = f"HTML parse: {e}"
                page.canonical_count, page.canonical = len(ex.canonicals), normalize(ex.canonicals[0], url) if ex.canonicals else ""
                page.meta_robots, page.title, page.meta_description = ex.robots.strip(), ex.title, ex.description
                page.h1, page.content_length, page.schema_types = ex.h1, len(" ".join(" ".join(ex.text).split())), sorted(ex.schema_types)
                links = ex.links + ex.hreflang + ex.feeds
                for raw in links:
                    link = normalize(raw, url)
                    if not link: continue
                    if urlsplit(link).netloc == self.host:
                        self.edges[url].add(link)
                        if link not in queued and len(queued) < self.max_urls * 2: queue.append(link); queued.add(link)
                    else: self.external.add(link)
            self.pages[url] = page
        incoming = Counter(link for links in self.edges.values() for link in links)
        for url, page in self.pages.items():
            page.internal_inlinks, page.internal_outlinks = incoming[url], len(self.edges[url])
        return self.validate()

    def validate(self):
        issues = []
        titles = defaultdict(list)
        for url, p in self.pages.items():
            directives = (p.meta_robots + "," + p.x_robots_tag).lower()
            indexable = p.status == 200 and p.robots_allowed and "noindex" not in directives
            if p.status == 0: issues.append(("BLOCK", "fetch_failed", url))
            if p.title and indexable: titles[p.title].append(url)
            if p.sitemap_membership:
                if p.status != 200: issues.append(("BLOCK", "sitemap_non_200", url))
                if p.canonical != url: issues.append(("BLOCK", "sitemap_not_self_canonical", url))
                if "noindex" in directives: issues.append(("BLOCK", "sitemap_noindex", url))
                if not p.robots_allowed: issues.append(("BLOCK", "sitemap_robots_blocked", url))
            if p.redirect_hops > 1: issues.append(("BLOCK", "redirect_chain", url))
            if p.error == "redirect loop": issues.append(("BLOCK", "redirect_loop", url))
            if p.canonical_count > 1: issues.append(("BLOCK", "multiple_canonicals", url))
            if p.canonical:
                if urlsplit(p.canonical).scheme != "https": issues.append(("BLOCK", "non_https_canonical", url))
                if urlsplit(p.canonical).netloc == "shop.oligopolypeptides.com": issues.append(("BLOCK", "legacy_shop_canonical", url))
                target = self.pages.get(p.canonical)
                if target and (target.status != 200 or not target.robots_allowed or "noindex" in (target.meta_robots + target.x_robots_tag).lower()):
                    issues.append(("BLOCK", "bad_canonical_target", url))
            if indexable and p.internal_inlinks == 0 and url != self.start: issues.append(("BLOCK", "indexable_orphan", url))
            if indexable and p.sitemap_membership and not p.h1: issues.append(("BLOCK", "missing_h1", url))
            if indexable and p.content_length < 80: issues.append(("BLOCK", "soft_404_candidate", url))
        for title, urls in titles.items():
            if len(urls) > 1:
                for url in urls: issues.append(("BLOCK", "duplicate_title", url))
        for source, links in self.edges.items():
            for link in links:
                if link in self.pages and 400 <= self.pages[link].status < 500: issues.append(("BLOCK", "internal_4xx", f"{source} -> {link}"))
        self.issues = sorted(set(issues))
        return self.issues

    def write(self, label):
        self.output.mkdir(parents=True, exist_ok=True)
        rows = [asdict(self.pages[u]) for u in sorted(self.pages)]
        payload = {"generated_at": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()), "start_url": self.start,
                   "pages": rows, "sitemaps": sorted(self.sitemaps), "external_urls": sorted(self.external),
                   "issues": [{"severity": s, "code": c, "url": u} for s, c, u in self.issues]}
        (self.output / f"{label}.json").write_text(json.dumps(payload, indent=2) + "\n")
        if rows:
            with (self.output / f"{label}.csv").open("w", newline="") as f:
                w = csv.DictWriter(f, fieldnames=rows[0], lineterminator="\n"); w.writeheader()
                for row in rows:
                    row = {k: " | ".join(v) if isinstance(v, list) else v for k, v in row.items()}; w.writerow(row)
        status = Counter(str(p.status) for p in self.pages.values())
        codes = Counter(c for _, c, _ in self.issues)
        md = [f"# SEO crawl: {label}", "", f"Generated: `{payload['generated_at']}`", f"Target: `{self.start}`", "",
              "## Metrics", "", f"- Discovered/crawled URLs: **{len(rows)}**", f"- Sitemap URLs: **{sum(bool(v) for v in self.sitemap_map.values())}**",
              f"- Redirecting URLs: **{sum(p.redirect_hops > 0 for p in self.pages.values())}**",
              f"- Blocking findings: **{len(self.issues)}**", "", "### HTTP status", ""]
        md += [f"- {k}: {v}" for k, v in sorted(status.items())]
        md += ["", "### Findings", ""] + ([f"- `{k}`: {v}" for k, v in sorted(codes.items())] or ["- None"])
        md += ["", "## Scope notes", "", "Inventory includes HTML links, canonical/hreflang/feed links, JSON-LD types, robots eligibility, and all discovered XML sitemap URLs.", ""]
        (self.output / f"{label}.md").write_text("\n".join(md))


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--url", default="https://www.oligopolypeptides.com/")
    ap.add_argument("--output", default="reports")
    ap.add_argument("--label", default="seo-crawl")
    ap.add_argument("--max-urls", type=int, default=5000)
    ap.add_argument("--timeout", type=int, default=15)
    ap.add_argument("--allow-blocking", action="store_true", help="write reports but exit zero")
    args = ap.parse_args()
    crawler = Crawler(args.url, args.output, args.max_urls, args.timeout)
    crawler.crawl(); crawler.write(args.label)
    print(f"Crawled {len(crawler.pages)} URLs; {len(crawler.issues)} blocking finding(s).")
    return 0 if args.allow_blocking or not crawler.issues else 1


if __name__ == "__main__": sys.exit(main())
