#!/usr/bin/env python3
"""
RUO compliance scanner for oligopolypeptides.com.

Crawls every URL in the Rank Math sitemap and reports language that the VERIFIED /
Stripe RUO review flags: human-benefit and weight-loss outcome claims, dosing and
administration content, cycle/stack/protocol guidance, dosing calculators, and
restricted product naming.

Two things keep the signal-to-noise usable:

  * Sitewide boilerplate (nav, footer, age gate, RUO disclaimers) appears on more
    than half the pages and is subtracted before reporting. Without this the
    footer's own "NOT FOR HUMAN CONSUMPTION" disclaimer dominates every result.
  * Negated sentences are dropped. "Do not publish dosing content" and "not for
    human use" are compliant language, not violations.

    python3 compliance/scan.py                 # crawl (cached) and report
    python3 compliance/scan.py --refresh       # discard the cache and re-crawl
    python3 compliance/scan.py --json out.json # machine-readable findings

Exit status is 1 if any P0 or P1 finding is present, so this can gate a deploy.
"""

import argparse
import collections
import html
import json
import os
import re
import subprocess
import sys
import urllib.request

SITE = "https://www.oligopolypeptides.com"
SITEMAPS = ["post", "page", "product", "category", "product_cat"]
CACHE = os.path.join(os.path.dirname(os.path.abspath(__file__)), ".cache")
UA = "Mozilla/5.0 (compatible; OligoPolyComplianceScan/1.0)"

# Sentences appearing on more than this share of pages are site chrome, not content.
BOILERPLATE_SHARE = 0.5

# A match inside a negated or prohibitive sentence is compliant language.
NEGATED = re.compile(
    r"(?i)\b(not for|no |never|do not|does not|cannot|prohibit|forbid|without|"
    r"rather than|instead of|not intended|not a |not to |avoid|exclude|is not|"
    r"are not|nor )\b"
)

CHECKS = [
    # (id, priority, description, pattern)
    ("internal-leak", "P0", "Internal build note published live",
     r"(?i)(Claude package|sanitized for public|source-derived comparison guide)"),
    ("weight-loss-figure", "P1", "Human weight-loss / body-composition outcome figure",
     r"(?i)(~?\s?[\d.]+\s?%|−?[\d.]+\s?kg)[^.]{0,60}"
     r"(weight (loss|reduction)|body weight|adipose|fat)"
     r"|(weight (loss|reduction)|body weight|visceral adipose)[^.]{0,60}"
     r"(~?\s?[\d.]+\s?%|−?[\d.]+\s?kg)"),
    ("benefit-framing", "P1", "Benefit-framed naming or heading",
     r"(?i)\b(fat[- ]loss|anti-?aging|appetite suppress\w*|peak weight loss|"
     r"muscle (growth|gain|building)|lean mass|libido|erectile)\b"),
    ("dosing-admin", "P2", "Dosing / administration / self-administration guidance",
     r"(?i)\b(dosage (chart|guide|calculator)|injection (site|schedule|protocol)|"
     r"subcutaneous(ly)?|intramuscular(ly)?|self-?administ\w*|insulin syringe|"
     r"mcg/kg|mg/kg|units? per (day|week)|how to use (this|it))\b"),
    ("cycle-stack", "P2", "Cycle / stack / personal-use protocol content",
     r"(?i)\b(loading phase|maintenance phase|\d+-week (cycle|protocol|run)|"
     r"pct\b|stacking (guide|protocol)|cycle length)\b"),
    ("calculator", "P0", "Dosing or reconstitution calculator / interactive tool",
     r"(?i)\b(peptide calculator|dos(e|age|ing) calculator|reconstitution "
     r"(tool|calculator)|syringe (tool|calculator)|unit converter|calculate your)\b"),
    ("restricted-naming", "P2", "Restricted / pre-clearance product naming (flag to VERIFIED)",
     r"(?i)\b(retatrutide|tesamorelin|semaglutide|tirzepatide|cagrilintide|"
     r"ss-?31|elamipretide|bacteriostatic water|bac water|glp-?1)\b"),
]


def fetch(url, path):
    if os.path.exists(path) and os.path.getsize(path) > 0:
        return
    req = urllib.request.Request(url, headers={"User-Agent": UA})
    try:
        with urllib.request.urlopen(req, timeout=45) as r:
            body = r.read()
    except Exception as exc:                                  # noqa: BLE001
        print(f"  ! {url}: {exc}", file=sys.stderr)
        body = b""
    with open(path, "wb") as fh:
        fh.write(body)


def discover():
    urls = set()
    for name in SITEMAPS:
        try:
            req = urllib.request.Request(f"{SITE}/{name}-sitemap.xml",
                                         headers={"User-Agent": UA})
            with urllib.request.urlopen(req, timeout=45) as r:
                xml = r.read().decode("utf-8", "replace")
        except Exception as exc:                              # noqa: BLE001
            print(f"  ! {name}-sitemap.xml: {exc}", file=sys.stderr)
            continue
        urls.update(re.findall(r"<loc>([^<]+)</loc>", xml))
    urls.add(f"{SITE}/")
    return sorted(urls)


def slug(url):
    p = url.replace(SITE, "").strip("/")
    return (p.replace("/", "__") or "HOME") + ".html"


def visible(markup):
    for tag in ("script", "style", "noscript"):
        markup = re.sub(rf"(?is)<{tag}[^>]*>.*?</{tag}>", " ", markup)
    markup = re.sub(r"(?is)<!--.*?-->", " ", markup)
    markup = re.sub(r"(?is)<(br|/p|/div|/li|/h[1-6]|/td|/tr)[^>]*>", "\n", markup)
    markup = re.sub(r"(?is)<[^>]+>", " ", markup)
    return re.sub(r"[ \t]+", " ", html.unescape(markup))


def sentences(text):
    out = []
    for line in text.split("\n"):
        line = line.strip()
        if len(line) < 25:
            continue
        for s in re.split(r"(?<=[.!?])\s+", line):
            s = s.strip()
            if 25 <= len(s) <= 400:
                out.append(s)
    return out


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--refresh", action="store_true", help="discard cache and re-crawl")
    ap.add_argument("--json", metavar="PATH", help="write findings as JSON")
    args = ap.parse_args()

    if args.refresh and os.path.isdir(CACHE):
        subprocess.run(["rm", "-rf", CACHE], check=True)
    os.makedirs(CACHE, exist_ok=True)

    urls = discover()
    print(f"Crawling {len(urls)} URLs (cache: {CACHE})")
    for url in urls:
        fetch(url, os.path.join(CACHE, slug(url)))

    docs = {}
    for url in urls:
        path = os.path.join(CACHE, slug(url))
        if os.path.getsize(path) == 0:
            continue                                   # redirect or fetch failure
        with open(path, encoding="utf-8", errors="replace") as fh:
            docs[url] = sentences(visible(fh.read()))

    seen = collections.Counter()
    for lines in docs.values():
        seen.update(set(lines))
    cutoff = len(docs) * BOILERPLATE_SHARE
    boilerplate = {s for s, n in seen.items() if n > cutoff}
    print(f"Scanned {len(docs)} pages · {len(boilerplate)} boilerplate sentences excluded\n")

    findings = collections.defaultdict(lambda: collections.defaultdict(list))
    for url, lines in docs.items():
        for line in sorted(set(lines)):
            if line in boilerplate:
                continue
            for cid, pri, _desc, pat in CHECKS:
                if re.search(pat, line) and not NEGATED.search(line):
                    findings[cid][url].append(line)

    blocking = 0
    for cid, pri, desc, _pat in CHECKS:
        pages = findings.get(cid, {})
        count = sum(len(v) for v in pages.values())
        status = "clean" if not pages else f"{count} instance(s) on {len(pages)} page(s)"
        print(f"[{pri}] {desc}: {status}")
        if pages and pri in ("P0", "P1"):
            blocking += count
        for url in sorted(pages):
            print(f"      {url}")
            for line in pages[url][:3]:
                print(f"          · {line[:150]}")
        if pages:
            print()

    if args.json:
        with open(args.json, "w") as fh:
            json.dump({cid: dict(v) for cid, v in findings.items()}, fh, indent=1)
        print(f"Findings written to {args.json}")

    if blocking:
        print(f"\nFAIL: {blocking} P0/P1 instance(s) outstanding.")
        return 1
    print("\nPASS: no P0/P1 findings.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
