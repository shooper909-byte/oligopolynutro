#!/usr/bin/env python3
"""
Applies the Stripe/VERIFIED RUO remediation to www.oligopolypeptides.com over the
WordPress REST API, using an Application Password.

Read codex/RUNBOOK.md before running this. Dry-run is the default: every command
prints a unified diff of what it *would* change and writes nothing until you pass
--apply. Every post is backed up to codex/backups/ before it is modified.

    export WP_SITE=https://www.oligopolypeptides.com
    export WP_USER=oligopoly
    export WP_APP_PASSWORD='xxxx xxxx xxxx xxxx xxxx xxxx'

    python3 codex/remediate.py check
    python3 codex/remediate.py backup
    python3 codex/remediate.py p0            # dry run
    python3 codex/remediate.py p0 --apply
    python3 codex/remediate.py p1
    python3 codex/remediate.py products
    python3 codex/remediate.py verify

Standard library only — no pip install.
"""

import argparse
import base64
import difflib
import json
import os
import re
import sys
import urllib.error
import urllib.parse
import urllib.request

HERE = os.path.dirname(os.path.abspath(__file__))
BACKUPS = os.path.join(HERE, "backups")
TARGETS = os.path.join(HERE, "targets.json")

SITE = os.environ.get("WP_SITE", "https://www.oligopolypeptides.com").rstrip("/")
USER = os.environ.get("WP_USER", "")
APPPW = os.environ.get("WP_APP_PASSWORD", "")

# Posts carrying the P0 internal build note.
P0_IDS = [2074, 2075, 2077, 2078, 2079]

# Stat tiles: human-outcome labels -> receptor-count replacements.
# Receptor counts are the honest differentiator and carry no outcome claim.
STAT_MAP = [
    (r"(?i)retatrutide", "3", "Receptor targets (GLP-1R / GIPR / GCGR)"),
    (r"(?i)\breta\b", "3", "Receptor targets (GLP-1R / GIPR / GCGR)"),
    (r"(?i)tirzepatide", "2", "Receptor targets (GLP-1R / GIPR)"),
    (r"(?i)\btirz\b", "2", "Receptor targets (GLP-1R / GIPR)"),
    (r"(?i)semaglutide", "1", "Receptor target (GLP-1R)"),
    (r"(?i)(added|extra).*(weight loss)", "+GIPR", "Added receptor arm"),
]

# Prose rewrites. Keys are distinctive substrings of the live sentence.
# Rule for anything not listed: describe what the molecule binds, not what
# happened to people. Delete any figure tied to weight, body weight, adipose or fat.
PROSE_MAP = {
    "15.6% body weight reduction": (
        "CagriSema is a fixed-ratio combination of a long-acting amylin analog and a "
        "GLP-1 receptor agonist. It is the reference model for studying amylin-receptor "
        "and GLP-1R signalling in parallel: the two components act on non-overlapping "
        "receptor families, which is why co-administration models are used to investigate "
        "additive versus independent pathway activity in vitro."
    ),
    "visceral adipose tissue": (
        "Tesamorelin is a well-characterised GHRH analog with an extensively documented "
        "pharmacology profile, which makes it a useful reference compound for GH/IGF-1 "
        "axis assay work. Published datasets report measurable IGF-1 elevation, giving "
        "researchers a known-response benchmark when validating GH-axis readouts."
    ),
    "superior weight loss efficacy": (
        "This receptor balance — the addition of GCGR agonism to the GLP-1R/GIPR pair — "
        "is the mechanistic difference researchers isolate when designing triple- versus "
        "dual-agonist comparison studies."
    ),
    "highest recorded for any single compound": (
        "its triple-receptor profile (GLP-1R / GIPR / GCGR), the broadest receptor "
        "coverage of any compound in this class and the reason it is used as a reference "
        "agonist in multi-pathway study designs."
    ),
    "4.5 kg absolute weight reduction": (
        "Published network meta-analyses separate these compounds by receptor coverage: "
        "the triple agonist adds GCGR activation to the GLP-1R/GIPR pair, which is the "
        "mechanistic variable isolated in comparative pathway studies."
    ),
    "82%": (
        "Hepatic lipid handling is one of the pathways attributed to GCGR activation, "
        "which is why triple-agonist compounds are used as reference tools in hepatic "
        "lipid-metabolism and MASH pathway models."
    ),
    "22% body weight reduction": (
        "The dual agonist adds GIPR activation to GLP-1R agonism. That second receptor "
        "arm is the mechanistic difference researchers isolate when comparing mono- and "
        "dual-incretin signalling in vitro."
    ),
    "28.7% mean body weight reduction": (
        "Retatrutide carries the broadest receptor coverage in this class — GLP-1R, GIPR "
        "and GCGR — which is the basis for its use as a reference agonist in "
        "multi-pathway metabolic study designs."
    ),
}

# Whole table columns whose header is a human outcome. Removed from every table
# on every P1 page, header cell and body cells together.
OUTCOME_COLUMNS = [
    "Peak Weight Loss", "Weight Loss", "Weight Loss Data", "Peak Weight Loss Data",
    "Body Weight Reduction", "Absolute weight reduction (meta-analysis)",
    "Weight loss (highest dose, ~72wk)", "Responders >15% loss", "Peak weight loss data",
]

# Plain-text substitutions applied to the WHOLE document, so they reach the FAQ
# JSON-LD (<script type="application/ld+json">) as well as body prose. The FAQ
# schema repeats the same efficacy figures in machine-readable form: invisible to
# a reader, fully visible to a compliance scanner. Replacements below contain no
# quotes or angle brackets, so they are safe to substitute inside JSON strings.
TEXT_SUBS = [
    (re.compile(r"(?i)the source of retatrutide(?:&#8217;|')?s superior weight loss efficacy in published Phase 2 data"),
     "the mechanistic difference isolated in triple- versus dual-agonist comparison studies"),
    (re.compile(r"(?i)Maximum weight loss endpoint protocols"), "Multi-receptor endpoint protocols"),
    (re.compile(r"(?i)Which metabolic peptide has the highest weight loss data\?"),
     "Which metabolic peptide has the broadest receptor coverage?"),
    (re.compile(r"(?i)Retatrutide showed the highest published weight loss in Phase 2 trials at approximately [\d.]+% at \d+ weeks\.[^\"]*?(?=\")"),
     "Retatrutide activates GLP-1R, GIPR and GCGR, the broadest receptor coverage in this class. "),
    (re.compile(r"(?i)tirzepatide consistently shows approximately [\d–\-]+ percentage points greater weight reduction than semaglutide, attributed primarily to the GIPR contribution"),
     "tirzepatide differs from semaglutide by the addition of GIPR agonism, which is the receptor variable isolated in comparative studies"),
    (re.compile(r"(?i)accounting for the additional weight loss observed in Phase 2 data"),
     "which is the pathway difference attributed to GCGR activation"),
    (re.compile(r"(?i)found consistently greater weight reduction with tirzepatide across all follow-up periods"),
     "isolate the GIPR contribution across all follow-up periods"),
    (re.compile(r"(?i)Retatrutide Phase 2 data showed approximately [\d.]+% mean body weight reduction at \d+ weeks\.\s*Tirzepatide Phase 3 \(SURMOUNT-1\) showed approximately [\d.]+% at \d+ weeks\.\s*The approximately \d+% di[^\"]*?(?=\")"),
     "Retatrutide adds GCGR agonism to the GLP-1R and GIPR pair carried by tirzepatide. That third receptor arm is the mechanistic variable in comparative pathway work. "),
    (re.compile(r"(?i)weight loss outcomes from Phase 2/3 studies"), "receptor pharmacology across published studies"),
    (re.compile(r"(?i)shows retatrutide outperforming tirzepatide in weight reduction endpoints"),
     "separates retatrutide from tirzepatide by GCGR receptor coverage"),
    (re.compile(r"(?i)Phase 2 data showed approximately [\d.]+% vs approximately [\d.]+% weight reduction\."),
     "The compounds differ by receptor coverage rather than by a single endpoint."),
    (re.compile(r"(?i)This is why retatrutide consistently shows greater weight reduction in published data compared to tirzepatide and semaglutide"),
     "This is the receptor difference researchers isolate when comparing retatrutide with tirzepatide and semaglutide"),
    (re.compile(r"(?i)The GIP receptor addition accounts for approximately \d+% additional weight loss versus semaglutide, and the GCGR addition accounts for a further approximately [\d\-–.]+% above tirzepatide\."),
     "The GIP receptor addition and the GCGR addition are the two receptor arms that distinguish these compounds."),
    (re.compile(r"(?i)the addition of GIPR agonism was associated with meaningfully greater weight reduction \(approximately \+?[\d–\-]+ percentage points\)"),
     "the addition of GIPR agonism is the receptor variable under comparison"),
    (re.compile(r"(?i)these GCGR contributions appear to account for the additional ~?[\d–\-]+ percentage point weight loss advantage retatrutide shows over tirzepatide in published network meta-analyses"),
     "these GCGR contributions are the pathway difference between retatrutide and tirzepatide"),
    (re.compile(r"(?i)Phase 2 data showed approximately [\d.]+% body weight reduction versus approximately \d+% and approximately [\d.]+% for each agent alone[^\"<]*"),
     "The combination is studied because its two components act on non-overlapping receptor families. "),
    (re.compile(r"(?i)Hepatic fat reduction and MASH pathway studies"), "Hepatic lipid and MASH pathway studies"),
]

# Benefit-framed names -> compliant replacements.
BENEFIT_MAP = [
    (re.compile(r"(?i)Anti-?Aging\s*&amp;\s*Repair Research Panel"), "Longevity &amp; Repair Pathway Panel"),
    (re.compile(r"(?i)Anti-?Aging\s*&\s*Repair Research Panel"), "Longevity & Repair Pathway Panel"),
    (re.compile(r"(?i)Anti-?Aging Peptide Research"), "Cellular Longevity Pathway Research"),
    (re.compile(r"(?i)Best Peptide Research Panels for Fat Loss Research"), "Metabolic Pathway Research Panels"),
    (re.compile(r"(?i)Metabolic\s*/\s*fat loss pathways"), "Metabolic &amp; incretin pathways"),
    (re.compile(r"(?i)appetite suppression, body weight, and glycemic control"),
     "signalling across amylin and incretin receptor families"),
    (re.compile(r"(?i)\banti-?aging\b"), "longevity-pathway"),
    (re.compile(r"(?i)\bfat[-\s]loss\b"), "metabolic-pathway"),
    (re.compile(r"(?i)\bpeak weight loss\b"), "Receptor coverage"),
]

# Bacteriostatic water: every field must read 10 mL on the single 10 mL SKU.
BACWATER_SUBS = [
    (re.compile(r"OP-AUX-BACWATER-30ML"), "OP-AUX-BACWATER-10ML"),
    (re.compile(r"(?i)\b30\s?mL\b"), "10 mL"),
]


def req(path, method="GET", payload=None):
    url = path if path.startswith("http") else f"{SITE}/wp-json/{path.lstrip('/')}"
    data = json.dumps(payload).encode() if payload is not None else None
    r = urllib.request.Request(url, data=data, method=method)
    r.add_header("Content-Type", "application/json")
    r.add_header("User-Agent", "oligopoly-remediation/1.0")
    if USER and APPPW:
        tok = base64.b64encode(f"{USER}:{APPPW.replace(' ', '')}".encode()).decode()
        r.add_header("Authorization", "Basic " + tok)
    try:
        with urllib.request.urlopen(r, timeout=60) as resp:
            return json.loads(resp.read().decode())
    except urllib.error.HTTPError as e:
        body = e.read().decode()[:400]
        raise SystemExit(f"HTTP {e.code} on {method} {url}\n{body}")


def route_for(kind):
    return {"post": "posts", "page": "pages", "product": "product"}[kind]


def fetch(pid, kind):
    return req(f"wp/v2/{route_for(kind)}/{pid}?context=edit&_fields=id,slug,type,title,content")


def raw(doc):
    c = doc.get("content", {})
    return c.get("raw", c.get("rendered", ""))


def show(pid, slug, before, after):
    if before == after:
        print(f"  = {pid} /{slug}/  no change")
        return False
    d = difflib.unified_diff(
        re.sub(r">\s*<", ">\n<", before).splitlines(),
        re.sub(r">\s*<", ">\n<", after).splitlines(),
        lineterm="", n=1, fromfile=f"{slug} BEFORE", tofile=f"{slug} AFTER")
    lines = [l for l in d if l.startswith(("+", "-")) and not l.startswith(("+++", "---"))]
    print(f"  * {pid} /{slug}/  {len(lines)} changed line(s)")
    for l in lines[:24]:
        print("      " + l[:170])
    if len(lines) > 24:
        print(f"      … {len(lines) - 24} more")
    return True


def save(pid, kind, doc, new_content, apply):
    os.makedirs(BACKUPS, exist_ok=True)
    with open(os.path.join(BACKUPS, f"{pid}.json"), "w") as fh:
        json.dump(doc, fh, indent=1)
    if not apply:
        return
    req(f"wp/v2/{route_for(kind)}/{pid}", "POST", {"content": new_content})
    print(f"      -> written to {pid}")


def strip_table_column(html, header_text):
    """Remove the column whose <th>/<td> header matches header_text, from every row."""
    def fix(tbl):
        rows = re.findall(r"(?is)<tr[^>]*>.*?</tr>", tbl)
        idx = None
        for r in rows:
            cells = re.findall(r"(?is)<t[hd][^>]*>.*?</t[hd]>", r)
            for i, c in enumerate(cells):
                if re.sub(r"<[^>]+>", "", c).strip().lower() == header_text.strip().lower():
                    idx = i
                    break
            if idx is not None:
                break
        if idx is None:
            return tbl
        out = tbl
        for r in rows:
            cells = re.findall(r"(?is)<t[hd][^>]*>.*?</t[hd]>", r)
            if idx < len(cells):
                out = out.replace(r, r.replace(cells[idx], "", 1), 1)
        return out
    return re.sub(r"(?is)<table[^>]*>.*?</table>", lambda m: fix(m.group(0)), html)


def cmd_check(_):
    print(f"site: {SITE}\nuser: {USER or '(unset)'}")
    if not USER or not APPPW:
        raise SystemExit("Set WP_USER and WP_APP_PASSWORD first. See RUNBOOK.md.")
    me = req("wp/v2/users/me?_fields=id,name,slug")
    print(f"authenticated as: id={me['id']} {me.get('slug')}")
    d = fetch(2077, "post")
    can_edit = "raw" in d.get("content", {})
    print("edit context available:", can_edit)
    if not can_edit:
        print("  ! context=edit returned no raw content — the account may lack edit rights.")
    live = raw(d)
    print("P0 build note still present on 2077:", "sanitized for public" in live)
    print("\nOK" if can_edit else "\nNOT READY")


def cmd_backup(_):
    os.makedirs(BACKUPS, exist_ok=True)
    tg = json.load(open(TARGETS))
    ids = {(t["id"], "post" if t["id"] >= 2073 else "page") for t in tg}
    ids |= {(i, "post") for i in P0_IDS}
    for pid, kind in sorted(ids):
        d = fetch(pid, kind)
        with open(os.path.join(BACKUPS, f"{pid}.json"), "w") as fh:
            json.dump(d, fh, indent=1)
        print(f"  saved {pid} /{d['slug']}/ ({len(raw(d))} chars)")
    print(f"\n{len(ids)} document(s) backed up to {BACKUPS}")


def cmd_p0(a):
    print("P0 — remove internal build note, orphan heading and stray markdown title\n")
    for pid in P0_IDS:
        d = fetch(pid, "post")
        c = before = raw(d)
        # 1. the leak block
        c = re.sub(r'(?is)<section id="introduction">\s*<h2>Introduction</h2>\s*'
                   r'<p>[^<]*Claude package[^<]*</p>\s*</section>\s*', "", c)
        # 2. TOC entry + heading for the internal wrapper section
        c = re.sub(r'(?is)<li>\s*<a href="#source-derived-comparison-guide">'
                   r'Source-derived comparison guide</a>\s*</li>\s*', "", c)
        c = re.sub(r"(?is)<h2>\s*Source-derived comparison guide\s*</h2>\s*", "", c)
        # 3. stray markdown heading rendered as body text
        c = re.sub(r"(?s)<p>#{1,6}\s+[^<]{1,300}</p>\s*", "", c)
        if show(pid, d["slug"], before, c):
            save(pid, "post", d, c, a.apply)
    print("\nDry run — nothing written. Re-run with --apply." if not a.apply else "\nApplied.")


def cmd_p1(a):
    print("P1 — human-outcome figures and benefit-framed naming\n")
    tg = json.load(open(TARGETS))
    by_id = {}
    for t in tg:
        by_id.setdefault(t["id"], []).append(t)
    manual = []
    for pid, items in sorted(by_id.items()):
        kind = "post" if pid >= 2073 else "page"
        d = fetch(pid, kind)
        c = before = raw(d)

        for t in items:
            if t["kind"] == "stat_tile":
                repl = None
                for pat, val, lab in STAT_MAP:
                    if re.search(pat, t["label"]):
                        repl = f'<div class="pl-stat-item"><strong>{val}</strong><span>{lab}</span></div>'
                        break
                c = c.replace(t["find"], repl or "")
            elif t["kind"] == "table_header":
                c = strip_table_column(c, t["label"])
            elif t["kind"] == "prose":
                hit = next((v for k, v in PROSE_MAP.items() if k.lower() in t["find"].lower()), None)
                if hit:
                    c = c.replace(t["find"], f"<p>{hit}</p>")
                else:
                    manual.append((pid, d["slug"], t.get("match", "")[:110]))

        for header in OUTCOME_COLUMNS:
            c = strip_table_column(c, header)
        for rx, rep in TEXT_SUBS:
            c = rx.sub(rep, c)
        for rx, rep in BENEFIT_MAP:
            c = rx.sub(rep, c)

        if show(pid, d["slug"], before, c):
            save(pid, kind, d, c, a.apply)

    if manual:
        print("\nNEEDS A HUMAN-WRITTEN REPLACEMENT (rule: describe what the molecule binds,")
        print("not what happened to people; delete any figure tied to weight/body/adipose/fat):")
        for pid, slug, m in manual:
            print(f"  - {pid} /{slug}/  …{m}…")
    print("\nDry run — nothing written. Re-run with --apply." if not a.apply else "\nApplied.")


def cmd_products(a):
    print("Product data fixes\n")
    res = req("wp/v2/product?per_page=100&_fields=id,slug,title&search=bacteriostatic")
    bac = next((p for p in res if "bacteriostatic" in p["slug"]), None)
    if bac:
        d = fetch(bac["id"], "product")
        c = before = raw(d)
        for rx, rep in BACWATER_SUBS:
            c = rx.sub(rep, c)
        if show(bac["id"], d["slug"], before, c):
            save(bac["id"], "product", d, c, a.apply)
        print("      NOTE: title, short description, SEO title and tags are separate fields —")
        print("      see RUNBOOK.md; confirm the $19.99 price and Research Panel contents first.")
    res = req("wp/v2/product?per_page=100&_fields=id,slug,title&search=retatrutide")
    for p in res:
        t = p["title"]["rendered"]
        if "Research Material" in t:
            new = t.replace("Research Material", "Research Peptide")
            print(f"  * {p['id']} /{p['slug']}/  title: {t!r} -> {new!r}")
            if a.apply:
                req(f"wp/v2/product/{p['id']}", "POST", {"title": new})
                print("      -> written")
    print("\nDry run — nothing written. Re-run with --apply." if not a.apply else "\nApplied.")


def cmd_verify(_):
    print("Re-fetching live content…\n")
    bad = 0
    for pid in P0_IDS:
        c = raw(fetch(pid, "post"))
        hit = "sanitized for public" in c
        bad += hit
        print(f"  {pid}: build note {'STILL PRESENT' if hit else 'gone'}")
    tg = json.load(open(TARGETS))
    for pid in sorted({t["id"] for t in tg}):
        kind = "post" if pid >= 2073 else "page"
        c = raw(fetch(pid, kind))
        n = len(re.findall(r"(?i)(peak weight loss|anti-?aging|fat[-\s]loss|weight loss\b)", c))
        if n:
            bad += n
            print(f"  {pid}: {n} outcome/benefit term(s) remain")
    print(f"\n{'FAIL' if bad else 'PASS'} — {bad} item(s) outstanding")
    print("Then run: python3 compliance/scan.py --refresh")


def main():
    ap = argparse.ArgumentParser()
    sub = ap.add_subparsers(dest="cmd", required=True)
    for name, fn in (("check", cmd_check), ("backup", cmd_backup), ("p0", cmd_p0),
                     ("p1", cmd_p1), ("products", cmd_products), ("verify", cmd_verify)):
        p = sub.add_parser(name)
        p.add_argument("--apply", action="store_true", help="write changes (default: dry run)")
        p.set_defaults(fn=fn)
    a = ap.parse_args()
    if a.cmd != "check" and (not USER or not APPPW):
        raise SystemExit("Set WP_USER and WP_APP_PASSWORD first.")
    a.fn(a)


if __name__ == "__main__":
    main()
