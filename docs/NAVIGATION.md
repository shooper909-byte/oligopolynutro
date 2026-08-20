# Adding the program to the site navigation

**Status: not done — this one needs a human. Here is exactly what to paste and where.**

## Why I could not do it

The site has two headers. The theme's own header (`#site-header`, fed by the WordPress
"Primary Menu") is **hidden sitewide** by this rule:

```css
body.wp-theme-hello-elementor #site-header,
body.wp-theme-hello-elementor #site-footer { display: none !important; }
```

The header visitors actually see is a custom block of HTML injected into every page —
`<div id="opl-shared-site-header">` containing `<nav id="opl-shared-nav-20260815">`, with its
styles in `<style id="opl-shared-header-css">`. The same is true of the footer.

That markup is **hardcoded in a snippet, not in a WordPress menu**. So:

- Editing **Appearance → Menus** changes nothing visible — the menu it feeds is hidden.
- The snippet lives in a plugin's database table (Code Snippets or WPCode Lite, both active),
  which the WordPress.com MCP connection I have does not expose. There is no API surface for
  it from here.

So the link has to be added by editing that snippet in wp-admin.

## Find the snippet

In wp-admin, search both plugins for `opl-shared-nav-20260815`:

- **Snippets → All Snippets** (Code Snippets)
- **Code Snippets → Header & Footer** or **All Snippets** (WPCode Lite)

The snippet is the one containing `opl-shared-site-header`.

## Edit 1 — header "Resources" dropdown (recommended)

This is where Research Library, Research Use Policy, and Contact Support already live, so the
program sits naturally beside them.

**Find** this exact string in the snippet:

```html
<a href="https://www.oligopolypeptides.com/contact/">Contact Support</a></div></div>
```

**Replace** with:

```html
<a href="https://www.oligopolypeptides.com/contact/">Contact Support</a><a href="https://www.oligopolypeptides.com/research-partner-program/">Research Partner Program</a></div></div>
```

That adds one item to the Resources dropdown. No CSS changes needed — the panel styles apply
to every `<a>` inside it automatically.

## Edit 2 — footer "Company" column (recommended alongside Edit 1)

**Find:**

```html
        <a href="/refund-policy/">Refund Policy</a>
      </nav>
```

**Replace** with:

```html
        <a href="/refund-policy/">Refund Policy</a>
        <a href="/research-partner-program/">Research Partner Program</a>
      </nav>
```

## Alternative — a top-level header link

If you want it visible in the bar itself rather than inside a dropdown, **find**:

```html
<a class="opl-shared-link" href="https://www.oligopolypeptides.com/my-account/">Account</a>
```

**Replace** with:

```html
<a class="opl-shared-link" href="https://www.oligopolypeptides.com/research-partner-program/">Partners</a><a class="opl-shared-link" href="https://www.oligopolypeptides.com/my-account/">Account</a>
```

Keep the label short ("Partners") — the bar is already close to full at tablet widths, and a
long label is the thing most likely to wrap the row.

## After editing

1. Hard-refresh the homepage and confirm the link appears.
2. Click it and confirm it lands on `/research-partner-program/`.
3. Check a phone width — the shared header has its own mobile menu; confirm the new item
   appears there too.
4. If the site runs a page cache, purge it.

## If you would rather I did it

Give me either wp-admin access or the snippet's current contents pasted back to me, and I
will produce the exact edited snippet ready to paste.
