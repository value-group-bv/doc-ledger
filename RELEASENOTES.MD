# Release Notes

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
