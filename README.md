# Kareza Jean Bol-anon — Personal Portfolio

This repository upgrades the existing portfolio while preserving its public GitHub Pages address:

**https://jeankareza-beep.github.io/Personal-portfolio/**

The site uses a hybrid deployment because GitHub Pages serves static files and cannot run PHP or MySQL. The public pages remain on GitHub Pages. The PHP API, MySQL database, image uploads, and password-protected admin dashboard are intended to run on the separate PHP/MySQL host.

No production credentials, database host, Tinkerhost account URL, or API host are committed here. Configure those only after the hosting account and database have been provisioned.

## Project structure

```text
.
├── index.html, about.html, skills.html, projects.html, resume.html, contact.html
├── css/                         Public portfolio styles
├── js/config.js                 Public API base URL (blank until API hosting is ready)
├── js/script.js                 Navigation, theme, API rendering, filters, contact form
├── images/                      Existing static portfolio images
├── .github/workflows/static.yml GitHub Pages static-only deployment
└── backend/
    ├── api/index.php            Public read API and protected contact submission
    ├── admin/                   First-admin setup, sign-in, dashboard, CRUD, and inbox
    ├── database/schema.sql      MySQL table definitions
    ├── database/seed.sql        Starter portfolio records
    ├── includes/                PDO, security, admin and upload helpers
    ├── uploads/                 Runtime image uploads; files are not tracked by Git
    └── config.example.php       Safe configuration template; contains placeholders only
```

The GitHub Pages workflow copies only the HTML pages, `css/`, `js/`, and `images/` into its publication artifact. The PHP code is not deployed to Pages. Until a PHP API is configured in `js/config.js`, the existing static portfolio content remains visible; the contact form explains that backend setup is pending.

## Admin dashboard features

After PHP/MySQL hosting is configured, the dashboard provides:

- One-time setup of the first administrator, followed by username/password sign-in and logout.
- Editing of profile details, email/social information, site title, logo text, and accent color.
- Create, edit, and delete management for skills, projects, education, experience, and achievements.
- An inbox for contact-form submissions.
- JPEG, PNG, GIF, and WebP upload support for the profile and project images. SVG and other file types are rejected; the default application upload limit is 3 MB. Uploads use generated filenames and are excluded from Git.

The public contact endpoint uses an origin allowlist, a short-lived CSRF token, server-side validation, a honeypot, and a per-sender rate limit. It stores submissions in MySQL; it does not send email.

## Deployment preparation

The steps below are for the future PHP/MySQL deployment. They do not provision or change a hosting account. Confirm the exact PHP version, database hostname, database credentials, public host name, upload limits, and control-panel steps from the selected hosting account before proceeding.

### 1. Prepare the PHP host

1. Create a PHP site and a MySQL database/user using the host's control panel. Use the exact database hostname, database name, username, and password provided by that panel; do not assume the hostname is `localhost`.
2. Use a PHP version with support for the PHP 8 language/runtime functions used by this project, and enable `PDO MySQL` and `fileinfo`.
3. Serve the contents of the repository's `backend/` directory from the PHP site's document root. If instead you serve it beneath a URL subdirectory such as `/portfolio-api/`, include that path in both URLs in step 3.
4. Preserve the supplied `.htaccess` files where the host honors Apache access rules. They disable directory listing, protect configuration/include/database files, and prevent PHP/script execution in the upload directory. Confirm the hosting server applies the upload-directory rule.
5. Ensure the `uploads/` directory is writable by PHP (normally `0755` for the directory and `0644` for image files). Do not grant broad write permission such as `0777`.

### 2. Create and seed the database

Using the hosting control panel or its phpMyAdmin link:

1. Select the new database.
2. Import `backend/database/schema.sql` first.
3. Import `backend/database/seed.sql` second. Import the seed data once to avoid duplicating the starter skills, projects, education, and experience records.

The schema creates the portfolio profile/settings, content, administrator, contact-message, and login-attempt tables. The seed file uses the existing portfolio's introductory content and projects; add or change records later in the dashboard.

### 3. Add private PHP configuration

On the PHP host only, copy `backend/config.example.php` to `config.local.php` in the deployed PHP document root and replace every placeholder. Do not add `config.local.php` to this repository or paste its contents into an issue or chat.

Set these values:

- `db.host`, `db.name`, `db.user`, and `db.password`: exact values from the hosting control panel.
- `app.site_origin`: `https://jeankareza-beep.github.io` (the GitHub Pages origin only, without the repository path or a trailing slash).
- `app.base_url`: the HTTPS API application base URL with a trailing slash. If the contents of `backend/` are the document root, this is the host URL, such as `https://your-host.example/`. For a subdirectory deployment, include it, such as `https://your-host.example/portfolio-api/`.
- `app.setup_key`: a random, private, one-time setup key of at least 32 characters.
- `app.csrf_secret`: a different random, private secret of at least 32 characters.
- `app.upload_max_bytes`: the application's maximum image size in bytes (the template defaults to 3,145,728 bytes, or 3 MiB).
- `app.timezone`: a valid PHP time-zone identifier; the template uses `Asia/Manila`.

For example, generate distinct secrets on a trusted computer with `openssl rand -hex 32`; paste the generated strings only into the private host configuration. Also check the host's PHP `upload_max_filesize` and `post_max_size` limits. They must allow the selected image-size limit plus multipart form overhead.

### 4. Create the first administrator

After the configuration and database are ready, open the PHP host's `/admin/setup.php` page over HTTPS and create the first administrator using the setup key and a strong password. The setup page closes itself once an administrator exists. Then sign in at `/admin/login.php` and manage the portfolio at `/admin/` (or `/admin/index.php`). Keep the setup key private even after setup.

### 5. Connect the GitHub Pages frontend

Only after the PHP API is deployed over HTTPS, edit `js/config.js` and set `apiBaseUrl` to the same public API application base URL as `app.base_url`, including any subdirectory path and a trailing slash. Examples:

```js
window.PORTFOLIO_CONFIG = {
    apiBaseUrl: "https://your-host.example/"
};
```

If the PHP app is served from `/portfolio-api/`, use `https://your-host.example/portfolio-api/`. Commit and push that non-secret URL change to `main`; the GitHub Pages workflow will continue to publish the existing site address. Never put database passwords, setup keys, or CSRF secrets in `js/config.js` because frontend JavaScript is public.

The PHP API must allow the exact GitHub Pages origin in `app.site_origin`. Do not use a wildcard CORS origin. The admin dashboard is on the PHP host and is not part of GitHub Pages.

## API routes

All routes are served from `api/index.php` on the PHP host:

- `?resource=portfolio` — public profile, settings, skills, projects, education, experience, and achievements.
- `?resource=contact-csrf` — short-lived token used by the public contact form.
- `?resource=contact` — accepts a validated JSON contact submission; POST only.

The portfolio frontend reads content from the API when configured and falls back to its existing static page content when the API is unavailable. Keep the API URL blank until the backend is installed and verified.

## Local validation

From the repository root, the basic checks used during development are:

```sh
node --check js/config.js
node --check js/script.js
find backend -name '*.php' -print0 | xargs -0 -n1 php -l
git diff --check
```

The PHP API and admin require a configured MySQL database to run. For local development, use a private `backend/config.local.php` and a separate test database; that file is ignored by Git. Never point tests at production data.

## Hosting references

Tinkerhost's public information describes FTP, MySQL, SSL, and phpMyAdmin access as hosting features. The selected account's exact plan and connection details still need to be confirmed before deployment. Its SQL-import guide is available at [Import an SQL file / database backup](https://community.tinkerhost.net/forum/d/36-how-to-import-a-sql-file-database-backup-file), and its PHP/MySQL support category is [here](https://community.tinkerhost.net/forum/t/php-mysql).
