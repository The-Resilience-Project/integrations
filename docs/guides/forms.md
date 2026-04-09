# Forms — WordPress Theme Customisations

## Overview

The `forms/` directory contains a local copy of the `hello-theme-child-master` WordPress child theme from the TRP forms server. This code customises Gravity Forms behaviour on the WordPress site.

## Server Details

- **Site URL:** https://forms.theresilienceproject.com.au/
- **cPanel:** https://syn03ed.syd6.hostyourservices.net:2083/
- **Server path:** `forms.theresilienceproject.com.au/wp-content/hello-theme-child-master/`

## Downloading / Uploading Code

1. Log in to cPanel at the URL above
2. Open **File Manager**
3. Navigate to `forms.theresilienceproject.com.au/wp-content/hello-theme-child-master/`
4. Download or upload files as needed

There is no automated deployment — files are managed manually via the cPanel File Manager.

## Local Path

```
forms/hello-theme-child-master/
├── calculate_shipping.php
├── functions.php
├── populate_dates.php
├── populate_event_date.php
├── readme.txt
├── screenshot.png
└── style.css
```

## Code Style

PHP files in `forms/` are covered by the project's PHP-CS-Fixer configuration, so `make lint` and `make fix` will format them. They are **not** included in PHPStan static analysis.
