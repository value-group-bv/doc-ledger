# Release Notes

## Version 1.4.1 (05 October 2026)

- The sub category list when editing an entry now only shows sub categories for the selected subsidiary, and updates when you change it.
- FIX: Two sub categories with the same code (e.g. 000) could get the same document ID without an 'already exists' warning.
- FIX: Ticking one 'Also valid under' main category on the New page ticked all of them.

## Version 1.4.0 (02 October 2026)

- Admins can reserve reference codes (e.g. ones used by products) on the Admin API tab; the feasibility code API skips them.
- Users now get their first name from Microsoft 365 as display name, shown in the top right corner.
- Admins can change user display names on the `Admin` page.

## Version 1.3.0 (01 October 2026)

- Changing the title casing (upper- and lowercase) word lists now also updates the casing of existing document titles.
- Increased font size on login page to align more with the Value Wiki site.
- Removed Users count balloon on `Admin` page.
- Visual fixes on `Admin` page.
- Hid some non-critical columns in `Admin` environment on smaller screen devices.
- You can now find all created feasibility codes via API on `Admin` environment, rather than only latest 10.
- The `Logs` page now uses the same pagination buttons as the `Ledger`.
- Subsidiaries, main categories, document types and sub categories that are still in use can no longer be deleted (this could break the website).
- Fixed a security issue where non-admin users could create ledger entries.
- Updated dependencies.

## Version 1.2.1 (30 September 2026)

- Fixed bug where main category with number 0 wasn't possible to create.
- It's now possible to modify names of main categories without deleting them.

## Version 1.2.0 (30 September 2026)

- Document identifiers can now have a list of allowed main categories, rather than just one. Though you still need to set a default one.
- The `Predefined Document Number` section and it's codebase have been omitted. It wasn't as useful as I thought it'd be.
- Updated styling of the `project view` block (where you can set main category, reference code and revision) and now remembers values while you browse. It also dims ledger entries which are invalid for the set main category.
- The `New` entry page now warns right away when a document ID already exists.
- The `Admin` page is split into tabs.
- Deleting something now asks for confirmation in a proper popup instead of the browser's default one.
- After editing a sub category or feasibility code, the page returns to that row.
- Success and error messages now look more consistent in dark mode.
- Updated input field styling to look more the same on every page.
- Updated dependencies.

## Version 1.1.1 (24 September 2026)

- Fixed critical bugfix on `New` and `Edit` pages.

## Version 1.1.0 (24 September 2026)

- Added generic field for user to fill in project code and revision number. This code is then displayed on document identifiers.
- Improved clarity on `New` and `Edit` entry pages on allowable characters in document title.
- Updated dependencies.

## Version 1.0.3 (17 August 2026)

- Updated text for Microsoft button on `Login` page.
- Updated dependencies.

## Version 1.0.2 (31 July 2026)

- FIX: Bug on login resulting in invalid callback parameter error.
- Small styling update on `Logs` page.
- Small styling update on `Ledger` page table header colours.
- Updated external dependencies.

## Version 1.0.1 (19 July 2026)

- FIX: Bug on document number validation upon creating new ledger entry.

## Version 1.0.0 (16 July 2026)

- Bumped to first major release version! Jippieee 😁.
- Added simple audit log for superadmins to monitor events.
- Small styling updates on `Guide` page.
- Small routing update for `Admin` page.

## Version 0.13.1 (09 July 2026)

- FIX: Bug fixed making saving capitalisation list impossible.

## Version 0.13.0 (09 July 2026)

- Abbreviation capitalisation list is now on the `Admin` page rather than hardcoded in the backend.
- Short word lowercase list is now on the `Admin` page rather than hardcoded in the backend.
- API call added to retrieve latest published feasibility code.
- Improved mobile userfriendlyness for admin page.

## Version 0.12.2 (08 July 2026)

- Deletion and re-release of API keys now possible.

## Version 0.12.1 (08 July 2026)

- FIX: API authentication bug.

## Version 0.12.0 (08 July 2026)

- Added API preparations in the Ledger app framework.
- Created an API for creating feasibility reference codes.
- Updated styling on error pages in-line with login page updates.

## Version 0.11.2 (07 July 2026)

- Updated hover styling on ledger table.
- Updated styling on login page.
- Updated abbreviations list.
- Updated dependencies.

## Version 0.11.1 (02 July 2026)

- FIX: Superadmins could not edit entries created by 'regular' admins.
- FIX: Updating applicable subsidiary and/or maincat for existing sub categories did not work in all web browsers.
- FIX: Quickly clicking multiple times on same document ID would make the `Copied` message wrongfully persistent.

## Version 0.11.0 (02 July 2026)

- Enhanced `Guide` page with examples section.
- Enhanced title sanitation. It now also automatically applies _Title Case_ except for certain short words, and _UPPER CASE_ for certain abbreviations.
- Simplified and consolidated back-end code for title sanitation.
- Updated dependencies.

## Version 0.10.1 (01 July 2026)

- Updated page title of ledger page.
- FIX: Styling error on `Guide` page.

## Version 0.10.0 (01 July 2026)

- Started on an `Guide` page where the numbering scheme conventions are written.
- Adding, editing and deleting ledger entries is now only available for admins.
- Document types can now only contain letters, as it was supposed to.

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
