# Setting the SEO title in Rank Math

## What's wrong

The live title on `/research-partner-program/` is 106 characters:

> OligoPoly Research Partner Program | Research Peptides & Laboratory Compounds | OligoPoly Laboratories

Google truncates around 60. The last two phrases are your **site tagline** and **site name**,
which Rank Math's page title template appends automatically. Overriding the title on this one
page stops that.

Target:

> Research Partner Program | OligoPoly Laboratories

Nothing is broken — this is cosmetic. The meta description is already correct.

## Why I can't do it from here

`rank_math_title` isn't exposed to the connection I'm using. I set it twice; both times
WordPress accepted the request and returned the page without the field. It has to be typed in
wp-admin.

---

## The steps

**1. Open the page for editing**

https://www.oligopolypeptides.com/wp-admin/post.php?post=3500&action=edit

**2. Find the Rank Math button — top right of the editor**

In the toolbar row with the blue **Update** button, look right of it for a small circular
**score badge** (a number out of 100, often coloured orange or green) or the Rank Math logo.
That is the button. Click it.

It sits next to the settings gear ⚙ and the three-dot ⋮ menu. It is *not* in the left
sidebar and *not* under Settings.

**3. Click "Edit Snippet"**

The Rank Math panel opens on the right, on the **General** tab. At the top is a preview of
how the page looks in Google. Directly beneath that preview is a grey **Edit Snippet**
button. Click it.

**4. Type the title**

A **Title** field appears (with Permalink and Description below it). Clear it and paste:

```
Research Partner Program | OligoPoly Laboratories
```

**5. Update**

Close the snippet editor and click **Update**. Hard-refresh the live page to confirm.

---

## If you can't see the Rank Math button

**It may be switched off for Pages.** Go to
https://www.oligopolypeptides.com/wp-admin/admin.php?page=rank-math-options-titles → click
**Pages** in the left column → make sure **"Add SEO Controls"** is toggled **on**. Save, then
reopen the page.

**It may be hidden in editor preferences.** In the page editor, click the three-dot ⋮ menu
(far top right) → **Preferences** → **Panels** (or **Plugins**) → make sure Rank Math is
enabled.

**You may be in the Classic editor.** Then Rank Math is a big metabox *below* the content
box, not in the top bar. Scroll down. If it's missing, click **Screen Options** at the very
top right and tick Rank Math.

---

## Easier alternative: Quick Edit from the Pages list

If hunting in the editor is annoying, turn on bulk editing once and you can set SEO titles
straight from the Pages list:

1. https://www.oligopolypeptides.com/wp-admin/admin.php?page=rank-math-options-general
2. Open the **Others** tab.
3. Set **Bulk Editing** to **Enabled**. Save.
4. Go to **Pages**, hover **OligoPoly Research Partner Program**, click **Quick Edit**.
5. An **SEO Title** field is now there. Paste the title, click **Update**.

That path also lets you fix the other two pages in a few seconds:

| Page | Suggested title |
|------|-----------------|
| OligoPoly Research Partner Program | `Research Partner Program \| OligoPoly Laboratories` |
| Research Partner Program Terms | `Research Partner Program Terms \| OligoPoly Laboratories` |
| Partner Compliance Rules | `Partner Compliance Rules \| OligoPoly Laboratories` |

---

## What NOT to do

Don't change the global template under **Titles & Meta → Pages** to fix this. That would
change the title of every page on the site, which is well outside this program's scope.

Don't rename the WordPress page title either — the tagline and site name would still be
appended, so it wouldn't get you under the limit, and it would change how the page is listed
in wp-admin.
