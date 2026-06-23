# Document numbering ledger for Value Group

A Symfony-based document numbering ledger application for Value Group. It helps manage main categories, subcategories, document codes, and related metadata with a web UI. The app utilises Docker deployment.

## Status

### TO ADD

- [x] Keyword search
- [x] Add sub category uniqueness per main category
- [x] Build to Docker
- [ ] Fixed alphabetic codes for products (EGC, CAR, etc..)

### TO FIX

- [x] Dark mode selector
- [ ] Mobile userfriendlyness
- [x] BUG: 'All subsidiaries' in admin page for subcategories doesn't work

## Releases

### Version 0.5.0

- Instantiated version numbering.
- Renamed navbar to 'Number Ledger' rather than 'Document Ledger'.
- You are now first prompted with the login page rather than redirected automatically to Microsoft OIDC.
- FIX: Favicon not displayed properly.
