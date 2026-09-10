# Technical SEO policy and deployment gate

## Canonical policy

- Public canonical origin: `https://www.oligopolypeptides.com`.
- Existing, equity-bearing paths retain their established spelling and trailing slash.
- HTTP, apex-host, and confirmed legacy `shop` URLs must make one permanent redirect to
  the exact `www` equivalent. A redirect is approved only after its destination is verified
  as an equivalent, indexable 200 page.
- Parameter, search, cart, checkout, account, preview, and other utility pages are excluded
  from XML sitemaps. Public duplicate HTML should remain crawlable with `noindex,follow` when
  Google needs to observe that directive; private/system areas may remain robots-blocked.
- Removed content returns 404/410 unless a genuine replacement exists. Missing URLs are
  never blanket-redirected to the homepage.

## Release gate

Run `python3 scripts/seo_audit.py --label pre-deploy`. Do not deploy while it exits nonzero.
After the approved WordPress/server release, run it again with `--label post-deploy`, then
compare the two JSON reports. The report is deliberately strict about sitemap status,
canonicals, indexability, robots eligibility, internal 4xx links, redirect chains/loops,
orphan pages, titles, H1s, and soft-404 candidates.

Host-level redirects must be configured where the legacy host terminates; a WordPress rule
on the `www` application cannot consolidate traffic that never reaches it. In particular,
publish the Selank route only after both its equivalent `www` product page and the one-hop
response from `shop` have passed the audit.

## Intentional exclusions

Cart, checkout, customer accounts, internal search, previews, tracking parameters, private
system endpoints, and non-canonical facets are not organic landing pages. They must not be
listed in sitemaps. This policy does not authorize blocking CSS, JavaScript, product,
category, research, documentation, quality/testing, COA, About, FAQ, or institutional
procurement resources.

## Human-access actions

Deployment requires WordPress/hosting access that is not stored in this repository. Search
Console sitemap submission and issue validation require a verified GSC user. Those actions
must occur only after a zero-blocker production report is archived here.
