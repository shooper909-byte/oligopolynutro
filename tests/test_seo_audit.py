import tempfile
import threading
import unittest
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path

from scripts.seo_audit import Crawler


class Site(BaseHTTPRequestHandler):
    def log_message(self, *_): pass
    def do_GET(self):
        pages = {
            "/robots.txt": (200, "text/plain", "User-agent: *\nAllow: /\nSitemap: {base}/sitemap.xml\n"),
            "/sitemap.xml": (200, "application/xml", "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'><url><loc>{base}/</loc></url><url><loc>{base}/product/</loc></url></urlset>"),
            "/": (200, "text/html", "<title>Home</title><link rel='canonical' href='{base}/'><h1>Research compounds</h1><a href='/product/'>Product</a>" + " useful" * 20),
            "/product/": (200, "text/html", "<title>Product</title><link rel='canonical' href='{base}/product/'><h1>Product identity</h1><script type='application/ld+json'>{{\"@type\":\"Product\"}}</script><a href='/'>Home</a>" + " research" * 20),
        }
        status, ctype, body = pages.get(self.path, (404, "text/html", "Not found"))
        base = f"http://127.0.0.1:{self.server.server_port}"
        body = body.format(base=base).encode(); self.send_response(status); self.send_header("Content-Type", ctype); self.end_headers(); self.wfile.write(body)


class AuditTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.server = ThreadingHTTPServer(("127.0.0.1", 0), Site)
        cls.thread = threading.Thread(target=cls.server.serve_forever, daemon=True); cls.thread.start()

    @classmethod
    def tearDownClass(cls): cls.server.shutdown()

    def test_complete_inventory_and_reports(self):
        base = f"http://127.0.0.1:{self.server.server_port}/"
        with tempfile.TemporaryDirectory() as d:
            crawl = Crawler(base, d, max_urls=20)
            issues = crawl.crawl(); crawl.write("fixture")
            self.assertEqual(2, len(crawl.pages))
            self.assertEqual(["Product"], crawl.pages[base + "product/"].schema_types)
            self.assertTrue((Path(d) / "fixture.json").exists())
            self.assertFalse(any(code in {"internal_4xx", "sitemap_non_200"} for _, code, _ in issues))


if __name__ == "__main__": unittest.main()
