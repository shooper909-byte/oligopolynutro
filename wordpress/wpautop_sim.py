import re

ALLBLOCKS = (r'(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre'
             r'|form|map|area|blockquote|address|style|p|h[1-6]|hr|fieldset|legend|section|article'
             r'|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)')

def wpautop(pee, br=True):
    """Port of WordPress wp-includes/formatting.php wpautop() (no pre/script protection)."""
    if pee.strip() == '':
        return ''
    pee = pee + "\n"
    pee = re.sub(r'<br />\s*<br />', "\n\n", pee)
    pee = re.sub(r'(<' + ALLBLOCKS + r'[\s/>])', r'\n\n\1', pee)
    pee = re.sub(r'(</' + ALLBLOCKS + r'>)', r'\1\n\n', pee)
    pee = pee.replace("\r\n", "\n").replace("\r", "\n")
    pee = re.sub(r'\n\n+', "\n\n", pee)
    parts = re.split(r'\n\s*\n', pee)
    pee = ''
    for t in parts:
        if t.strip():
            pee += '<p>' + t.strip("\n") + "</p>\n"
    pee = re.sub(r'<p>\s*</p>', '', pee)
    pee = re.sub(r'<p>([^<]+)</(div|address|form)>', r'<p>\1</p></\2>', pee)
    pee = re.sub(r'<p>\s*(</?' + ALLBLOCKS + r'[^>]*>)\s*</p>', r'\1', pee)
    pee = re.sub(r'<p>(<li.+?)</p>', r'\1', pee)
    pee = re.sub(r'<p><blockquote([^>]*)>', r'<blockquote\1><p>', pee, flags=re.I)
    pee = pee.replace('</blockquote></p>', '</p></blockquote>')
    pee = re.sub(r'<p>\s*(</?' + ALLBLOCKS + r'[^>]*>)', r'\1', pee)
    pee = re.sub(r'(</?' + ALLBLOCKS + r'[^>]*>)\s*</p>', r'\1', pee)
    if br:
        # WP protects newlines inside <script>/<style> before the <br /> pass.
        def _preserve(m):
            return m.group(0).replace("\n", '<WPPreserveNewline />')
        pee = re.sub(r'<(script|style).*?</\1>', _preserve, pee, flags=re.S)
        pee = re.sub(r'(?<!<br />)\s*\n', "<br />\n", pee)
        pee = pee.replace('<WPPreserveNewline />', "\n")
    pee = re.sub(r'(</?' + ALLBLOCKS + r'[^>]*>)\s*<br />', r'\1', pee)
    pee = re.sub(r'<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)>)', r'\1', pee)
    pee = re.sub(r'\n</p>$', '</p>', pee)
    return pee
