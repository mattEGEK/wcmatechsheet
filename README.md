# WCMA Race Tech Sheet Form

Mobile-friendly form for race car competitors to fill out the WCMA/UCTA tech inspection sheet. Generates a PDF and emails it to the tech director and competitor.

## Requirements

- PHP 7.4+
- PHP `mail()` enabled
- No database required

## Setup

1. Copy `config.example.php` to `config.php`
2. Edit `config.php` with your tech director email
3. Ensure `lib/tcpdf/` contains the TCPDF library (see Dependencies)
4. Upload to your web host or configure your deploy

## Dependencies

**TCPDF** – Bundled in `lib/tcpdf/`. No install needed.

**signature_pad.js** – Loaded from CDN; no local install needed.

## Deploy

Configure your own deploy process. Typical steps:

1. Clone or pull the repo to your web root
2. Copy `config.example.php` to `config.php` and configure
3. Ensure the web server can write to temp directory if needed
4. Point your domain (e.g. techsheet.nascc.ab.ca) at this directory

## License

Internal use for NASCC.
