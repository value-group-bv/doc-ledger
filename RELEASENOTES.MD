# Release Notes

## Version 0.10.0 (xx xx 2026)

- Started on an `Info` page where explainations on how to use the numbering scheme will be given.

## Version 0.9.0 (29 June 2026)

- Added an export function to download all current entries in XLSX.
- It's no longer possible to create duplicate document IDs.
- Updated the ledger table styling to use content-card style like the rest of the site.
- Did the same for the /new and /edit pages.
- Enhanced readability in dark mode of the destructive red colour.

## Version 0.8.1 (29 June 2026)

- Changed the term 'Document number' for the big code to 'Document ID'.
- Small styling updates on the Edit and New Ledger pages.
- Updated dependencies.

## Version 0.8.0 (27 June 2026)

- When you now click on an document number it is copied to your clipboard (including `-00` suffix).
- Improved layout of the searchbar and filters on mobile screens. By hiding the filter buttons.
- Improved ledger table layout on mobile screens by hiding certain columns.
- Document titles can now be up to 48 characters long.
- Updated dependencies.
- FIX: CSS styling on mobile website login page had small issue with the blue hue.

## Version 0.7.2 (26 June 2026)

- FIX: Creating a sub category applicable to ALL subsidiaries, BUT only one specific main category would fail silently.
- FIX: spelling error on admin page User role.
- FIX: Auto-propose doc number started at 001 rather than 000.
- FIX: Auto-propose doc number would not always format correctly in the preview to three-digits.

## Version 0.7.1 (26 June 2026)

- FIX: `User` role badge not showing up on Admin page.
- FIX: Footer URL update to version log on GitHub.
- FIX: Validation mistake in subsidiary codes resulted in user being able to make subsidiary codes with digits in them.

## Version 0.7.0 (26 June 2026)

- Switched the Docker build process to Github Actions rather than on-server.
- Changed login page background.
- Removed the `Delete entry` button from the overview ledger page.
- Changed the URL scheme so that the ledger is on the base domain (`ledger.valuegroup.vg`) rather than a subpage (`ledger.valuegroup.vg/ledger`).
- Updated CSS styling on the search bar on the main page to be more in-line with the rest.
- Removed the 'name' tag on admin user page.
- Added Superadmin role.
- Simplified the Docker Compose file.
- FIX: Some out-of-bounds admin page buttons on mobile. It's not yet perfect though, continues to be a WIP.

## Version 0.6.4 (24 June 2026)

- FIX: GitHub release body was always empty due to awk range pattern matching start and end on the same header line.

## Version 0.6.3 (24 June 2026)

- CHANGE: App version is now read from the committed `VERSION` file at Docker build time. The `APP_VERSION` variable no longer needs to be set in the server's `.env` file.

## Version 0.6.2 (24 June 2026)

- FIX: Docker build failed because APP_VERSION environment variable was not available during asset compilation.

## Version 0.6.1 (24 June 2026)

- FIX: Docker Build bug would fail because of missing .env variables during build process.

## Version 0.6.0 (24 June 2026)

- Added Comments / Search tags field on each ledger entry. This text is included in the _Search_ function on the main ledger page.
- Added new reference code type: 'PRO', mainly useful for Product main category.
- Replaced monospace font (used for codes) with Roboto Mono.
- Cleaned up overall styling a bit.
- Updated footer to show version number with link to releases.
- FIX: Sorting of document number on Ledger page was incorrect. It now sorts in a sequence structure (subsidiary → main category → reference code → doc type → sub category → doc number).
- FIX: Searching on document number on Ledger page did not work.
- FIX: Upon visiting the Admin page, user would be prompted with a backlog of error/success messages.
- FIX: Mobile hamburger menu button would disappear upon clicking.
- FIX: Mobile dropdown menu would have a wrong gradient in dark mode.

## Version 0.5.0 (24 June 2026)

- Darkened login page in-line with other internal websites.
- Added custom error handling pages (403, 404, 500).
- Updated 11 dependencies.
- Cleaned up Docker Build and Compose instructions.

## Version 0.4.0 (23 June 2026)

- Instantiated version numbering.
- Renamed navbar to 'Number Ledger' rather than 'Document Ledger'.
- You are now first prompted with the login page rather than redirected automatically to Microsoft OIDC.
- FIX: Favicon not displayed properly.
