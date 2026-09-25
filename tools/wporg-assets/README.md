# wordpress.org listing assets

The icon, banners and screenshots for **Like Dislike** on wordpress.org live in
[`.wordpress-org/`](../../.wordpress-org). This folder holds what builds them. Neither
folder is part of the plugin: `.gitattributes` marks both `export-ignore`, so they never
reach the plugin zip.

## How they reach wordpress.org

wordpress.org serves listing assets from the **`/assets/` directory of the SVN
repository**, not from `trunk/` or a tag. The **Deploy to WordPress.org** workflow copies
`.wordpress-org/` there with every release, and **Update readme and assets on
WordPress.org** does it between releases, so `.wordpress-org/` must hold only these files:

| File | Size | Used for |
| --- | --- | --- |
| `banner-772x250.png` | 772x250 | Standard banner |
| `banner-1544x500.png` | 1544x500 | High-DPI banner |
| `icon.svg` | vector | Plugin icon, used where supported |
| `icon-256x256.png`, `icon-128x128.png` | | Icon fallbacks (required with an SVG icon) |
| `screenshot-1.png` … `screenshot-8.png` | | Screenshots; captions come from `== Screenshots ==` in `README.txt` |

## Regenerating

```bash
node tools/wporg-assets/build-assets.mjs                                        # icon PNGs and banners
node tools/wporg-assets/build-assets.mjs --screenshots                          # also the screenshots
node tools/wporg-assets/build-assets.mjs --screenshots --only=stats,settings    # just those screenshots
```

Edit the copy and colours in `banner.html`, and the icon in `.wordpress-org/icon.svg`.
The script renders every PNG in headless Chrome at the exact size wordpress.org expects
and checks each file's dimensions. The banners use the plugin's own
`assets/css/buttons.css`. The icon also ships inside the plugin as
`assets/images/icon.svg` (the settings page and review notice); keep the two copies
identical.

It needs **Google Chrome**, plus **puppeteer-core** and the **Inter** and **Manrope**
fonts from the WPAnkit Product theme's QA tools (`tools/qa`). The default path is the
`pushrow-lp` Local site; set `LDFW_QA_DIR` to use another.

### Screenshots

`--screenshots` also needs WP-CLI and a local WordPress site with Like Dislike active.
Set `LDFW_SITE_PATH`, `LDFW_SITE_URL` and `LDFW_DB_SOCKET` for a site other than the
default Local one.

1. Create the users `priya`, `maria`, `rahul` and `sofia` if the site doesn't have them.
2. From the plugin folder, build the demo content: `wp eval-file tools/wporg-assets/demo.php`.
   It creates a small coffee blog with votes, comments and feedback, kept as drafts.
   Running it again replaces the earlier demo.
3. Run the script with `--screenshots`.

While it runs, the script publishes the demo posts, applies showcase settings and loads a
temporary must-use plugin that shows a made-up site name and limits the Posts screen to
the demo posts, only for the script's own browser. Afterwards the posts go back to drafts,
the site's settings are restored, and the must-use plugin is removed. Votes clicked in
screenshots are answered by the script and never saved.

The screenshot order is the `SHOTS` list in the script, and it must match the captions in
`README.txt`.
