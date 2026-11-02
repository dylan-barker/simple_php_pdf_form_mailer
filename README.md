# Simple PHP PDF Form Mailer

A small PHP form that collects a **first name, surname and date of birth**,
renders a PDF **in memory** with [dompdf](https://github.com/dompdf/dompdf), and
emails it as an attachment with [PHPMailer](https://github.com/PHPMailer/PHPMailer).

The PDF is never written to disk, and no personal data is stored on the server.

## Features

- Plain HTML, CSS and JavaScript front end with inline validation.
- Server-side validation, CSRF token and honeypot field.
- PDF generated in memory and attached with `addStringAttachment()`.
- SMTP over TLS (port 587) or SSL (port 465); plaintext port 25 is rejected.
- Optional password protection for the PDF attachment.
- Works without JavaScript via a plain HTML response page.
- Shared-host friendly layout: everything lives in `public_html/`, so the folder
  can be uploaded as-is; `private/` is blocked by its own `.htaccess`.

## Requirements

- PHP **8.1+** with `mbstring`, `dom`/`libxml` and `openssl`.
- [Composer](https://getcomposer.org/) 2.
- An SMTP account that supports TLS or SSL.

## Project structure

```
.
├── composer.json
└── public_html/            web root - upload this folder's contents
    ├── index.php           form page
    ├── submit.php          AJAX / HTML endpoint
    ├── .htaccess
    ├── assets/
    │   ├── css/style.css
    │   └── js/app.js
    └── private/            blocked from HTTP by private/.htaccess
        ├── .htaccess
        ├── config.example.php
        ├── lib/            bootstrap, validation, PDF, mailer
        ├── templates/      PDF layout and no-JS response
        ├── tmp/            dompdf scratch and error log
        └── vendor/         Composer packages
```

## Getting started

```bash
git clone <your-repo-url> simple-php-pdf-form-mailer
cd simple-php-pdf-form-mailer

composer install

cp public_html/private/config.example.php public_html/private/config.php
# edit public_html/private/config.php with your SMTP details

php -S localhost:8000 -t public_html
```

Open <http://localhost:8000>.

Composer installs packages into `public_html/private/vendor`, so dependencies
travel with the rest of the `private/` folder.

## Configuration

`public_html/private/config.php` is gitignored. Start from
`public_html/private/config.example.php`.

| Key | Description |
| --- | --- |
| `smtp_host`, `smtp_port`, `smtp_secure` | Mail server. Use `tls` with 587 or `ssl` with 465. |
| `smtp_user`, `smtp_pass` | SMTP credentials. |
| `from_email`, `from_name` | Sender address and name. |
| `to_email`, `to_name` | Fixed recipient of the PDF. |
| `app_name` | Letterhead text in the PDF and the page title. |
| `timezone` | Timezone for the "generated on" timestamp. |
| `pdf_password` | Optional password for the PDF attachment. Empty disables it. |
| `app_debug` | Log the SMTP conversation. Keep `false` in production. |
| `storage_dir` | Optional scratch path. Defaults to `public_html/private/tmp`. |

## How it works

```
index.php → submit.php
              ├─ CSRF, honeypot and server-side validation
              ├─ dompdf renders the template and returns a string
              ├─ PHPMailer attaches the string with addStringAttachment()
              └─ JSON for fetch, HTML for browsers without JavaScript
```

The PDF exists only as a PHP string for the duration of the request. dompdf is
given a per-request scratch directory under `public_html/private/tmp`, which is
deleted before the response is sent.

## Security

- Sensitive files live in `public_html/private/`, which `.htaccess` denies to
  HTTP clients (defence in depth for setups where that folder is reachable).
- The PDF is never written to disk, and responses are marked `no-store` so
  form data is not cached by the browser.
- Email subject, body and attachment name contain no personal data.
- Submitted values are redacted from error logs.
- SMTP encryption is required and validated before sending.
- The generated PDF can optionally be encrypted with a password.

Email is the delivery channel, so its security also depends on the sending and
receiving mailboxes. Use TLS, strong passwords and 2FA on both.

## Deployment

Upload the contents of `public_html/` into the host's web root. The `private/`
folder travels inside it:

```
/home/you/public_html/            ← contents of public_html/
/home/you/public_html/private/    ← the whole private/ folder
```

1. Run `composer install --no-dev --optimize-autoloader` locally and upload
   `public_html/private/vendor/` with the rest.
2. Make `public_html/private/tmp/` writable and keep
   `public_html/private/config.php` readable only by the PHP user (`0644`).
3. Keep `public_html/private/.htaccess` in place: it blocks HTTP access to the
   folder (Apache). On Nginx or LiteSpeed without `.htaccess` support, add an
   equivalent deny rule for `/private/` in the server config.
4. Serve the site over HTTPS.

## Testing

- Submit valid details and confirm the email arrives with a readable PDF.
- Check that empty fields, a future date, a date before 1900 and an impossible
  date such as `31 February` are all rejected.
- Confirm the honeypot and CSRF token are enforced.
- Disable JavaScript and confirm the plain HTML response page still works.
- After a submission, verify no PDF exists anywhere under the project or
  `public_html/private/tmp`.
