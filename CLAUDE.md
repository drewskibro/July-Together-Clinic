# AT Health — National Prescriber Website

## Project Overview
Weight loss website for AT Health, built as a WordPress theme with ACF Pro.
Hosted on Kinsta. Deployed via GitHub Actions (SCP).

## Theme Architecture
- **Theme directory:** `at-health-theme/`
- **Prefix:** `ah_` (functions, fields, CSS classes)
- **ACF field key pattern:** `field_ah_[context]_[name]`
- **CSS class prefix per page:** `hp-` (home), `mj-` (mounjaro), `wg-` (wegovy), `tr-` (treatments), `el-` (eligibility), `sw-` (switching), `ab-` (about), `ct-` (contact), `cc-` (customer care), `hh-` (health hub), `ro-` (reorder), `tm-` (terms)

## Key Files
- `functions.php` — Theme setup, helpers, enqueue, includes
- `inc/acf-options.php` — Global settings pages (Branding, Contact, Compliance, Social, Nav)
- `inc/acf-fields.php` — All ACF field group definitions
- `header.php` / `footer.php` — Shared header and footer
- `page-templates/` — 12 custom page templates
- `template-parts/` — 4 shared section components
- `assets/css/globals.css` — CSS variables, base styles, shared components
- `assets/js/scroll-reveal.js` — Global scroll animations, FAQ accordion, count-up

## Critical Rules
1. **ACF helper functions use strict null checks:** `$value === null || $value === ''` — NEVER use `empty()` (breaks true_false fields where 0 = "No")
2. **ACF image fields:** Always `return_format => 'id'`, use `wp_get_attachment_image()` or `wp_get_attachment_url()`
3. **Escaping:** `esc_html()` for plain text, `wp_kses_post()` for HTML content, `esc_url()` for URLs, `esc_attr()` for attributes
4. **Gutenberg disabled** for all page templates (Classic Editor enforced)
5. **Cache busting:** `filemtime()` on `globals.css`, NOT `functions.php`
6. **Permalinks:** `/health-hub/%postname%/`

## Design System
- **Fonts:** DM Serif Display (headings), Inter (body)
- **Primary palette:** Purple `#a89dd6` / `#9b8fce` / `#8e88d0` / `#7d76ba`
- **Background:** Cream `#fdf8f3`, Lavender `#f7f4f9`, Dark `#0f1117`
- **Accent:** Indigo `#6366f1` (hero headlines)

## Deployment
- Push to `main` → GitHub Actions → SCP to Kinsta
- Required secrets: `KINSTA_SSH_HOST`, `KINSTA_SSH_USER`, `KINSTA_SSH_PASSWORD`, `KINSTA_SSH_PORT`
- No build tools — raw CSS/JS deployed as-is
- Never `git clone` on Kinsta

## Helper Functions
```php
ah_option('field_name', 'default')  // Global option field
ah_field('field_name', 'default')   // Page-level field
ah_company_name()                   // Returns 'AT Health'
ah_phone()                          // Returns phone number
ah_phone_link()                     // Returns tel: link
ah_email()                          // Returns email
ah_booking_url()                    // Returns eligibility/booking URL
ah_logo_url()                       // Returns logo URL (ACF > Customizer > SVG fallback)
```

---

# Engineering Standards — Sensitive Files & Patient Data

These rules come from a real incident on a **sister site to this one** — a UK
online pharmacy running WordPress/WooCommerce, where **208 patient identity
documents were left publicly downloadable for 18 hours.** Every rule below
traces to something that actually went wrong on a site built the same way
this one is.

Treat them as non-negotiable defaults, not suggestions.

## Why the bar is this high here

This site handles prescription medicines for UK patients. That means:

- **Identity documents are linked to health data.** An ID attached to a
  prescription order doesn't just reveal who someone is — the association
  reveals that they're being treated for a specific condition. Under UK GDPR
  that's **Article 9 special category data**, which carries the highest
  protection standard.
- **A breach has a 72-hour clock.** If patient data is exposed, the operator
  must assess and (if risk is likely) notify the ICO within 72 hours of
  becoming aware. Recovering from a breach is an order of magnitude more
  expensive than preventing one.
- **The operator is GPhC-registered.** Regulatory scrutiny extends to how
  patient data is handled, not just how medicines are dispensed.

This applies to ID documents, screening/consultation answers, prescription
records, and anything else that identifies a patient or their treatment.

---

## 0. Establish storage BEFORE building the feature

This site is pre-launch. That's a significant advantage: the sister site had
to be remediated under time pressure with 208 documents already exposed, and
the whole cleanup was only necessary because storage was designed after the
upload feature rather than before it.

**Do this before writing any upload code:**

1. **Identify the host and web server.** Nginx or Apache? This determines
   whether directory-level protections do anything at all (see §1).
2. **Establish a writable directory outside the web root**, and confirm it
   with the host before building. On Kinsta this meant support creating
   `/www/<site>/additional/` and adding it to PHP's `open_basedir` — the
   default `/private` directory was *not* reachable by PHP. Assume nothing;
   get it confirmed in writing.
3. **Prove it works both ways** before the first real upload:
   - PHP can write to it — `is_writable()` returns true
   - It is genuinely unreachable — no URL maps to it (see §3)
4. **Only then** build the upload feature against it.

If the host cannot provide writable storage outside the web root, that is a
blocking architectural decision to escalate — not something to work around
with a fallback (see §1, fail closed).

---

## 1. Sensitive file uploads

### Never use `media_handle_upload()` or the Media Library

WordPress's media handling is built for blog images. For anything sensitive
(ID documents, prescriptions, medical records, signed forms) it does three
actively dangerous things:

- writes the file into the **public** `wp-content/uploads/` tree, reachable
  by direct URL with no authentication
- generates multiple resized **copies** of every image
- lists it in the **Media Library**, visible to every admin-area user

Build a separate storage path. Never touch the attachment APIs for this data.

### Fail closed, never fail open — this is the one that caused the incident

The original code tried to store files outside the web root, and **silently
fell back to a directory inside `wp-content/uploads/` when that wasn't
writable.** The fallback was "protected" by a random 24-character directory
name. It was still served publicly by nginx.

If secure storage is unavailable:

```
✗ WRONG: fall back to a less secure location and carry on
✓ RIGHT: refuse the upload, return a clear error, log it loudly,
         and show a persistent admin warning until resolved
```

An unavailable secure store is an outage, not a reason to downgrade. Never
write sensitive data to a weaker location because the strong one failed.

**Design the patient-facing UX around this from the start.** On a greenfield
build this costs nothing: if secure storage is down, show *"We can't accept
uploads right now — we'll email you a link to complete this shortly"*, alert
the team, and let them re-send when it's back. A patient waiting an hour is
a minor inconvenience; a patient's passport being publicly downloadable is a
reportable breach.

> **Note on the sister site's remediation.** Its live fix keeps the fallback
> but adds automatic move-back, critical logging and a persistent admin
> notice, so exposure is now short and loud rather than long and silent.
> That was the right call *there* — it's an existing system with a live UX
> contract that couldn't be changed mid-incident. **Do not copy that pattern
> into a new build.** Store-then-move-back still has a real exposure window;
> fail-closed has none. You only get to choose fail-closed before launch,
> which is exactly where you are.

### Serve through a gated endpoint — never a static URL

The physical location matters less than this rule. If no URL maps to the
file, its location is a second line of defence rather than the only one.

```php
add_action( 'admin_post_view_secure_doc', function () {
    $id = (int) ( $_GET['id'] ?? 0 );
    check_admin_referer( 'view_secure_doc_' . $id );          // nonce
    if ( ! current_user_can( 'manage_woocommerce' ) ) {        // capability
        wp_die( 'Not allowed.' );
    }
    // realpath containment — the stored value must resolve INSIDE our dir
    $real_dir  = realpath( secure_doc_dir() );
    $candidate = realpath( secure_doc_dir() . $stored_filename );
    if ( ! $candidate || strpos( $candidate, $real_dir ) !== 0 ) {
        wp_die( 'Not found.' );
    }
    nocache_headers();
    header( 'Content-Type: ' . $mime );
    header( 'X-Content-Type-Options: nosniff' );
    readfile( $candidate );
    exit;
} );
```

All three checks matter: **nonce** (stops CSRF), **capability** (stops any
logged-in user reading everything), **realpath containment** (stops
`../../../wp-config.php` path traversal).

### `.htaccess` does nothing on nginx

Most managed WordPress hosts (Kinsta, WP Engine, Cloudways) run nginx, which
**ignores `.htaccess` entirely**. Dropping `Deny from all` into a directory on
those hosts provides zero protection while creating a false sense of security.
This exact mistake was made during the incident.

If you cannot store outside the web root, the protection must come from the
gated endpoint (above), not from directory-level files.

### Validate real content, not the extension

```php
$allowed = [ 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf' ];
$check   = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed );
if ( empty( $check['type'] ) ) { /* reject */ }
```

Checking `pathinfo($name, PATHINFO_EXTENSION)` alone is trivially spoofable
and a standard route for uploading disguised executables.

### Store the filename, not the full path

Persist only the filename in the database, and resolve it against a
directory constant at read time. When storage relocates (host change, new
policy, incident remediation) you move files and change one constant —
no database migration, nothing breaks. This design decision is the reason
the incident remediation required no DB changes.

Keep a list of *candidate* directories in the read path so files stored under
a previous location remain readable after a move.

### Filenames must carry no meaning

Use pure random (32+ chars). Never the user's original filename, and **never
embed an order number, patient ID, date or any other identifier.**

The sister site used `order-2022-<random>.jpg`. The random portion still made
individual files effectively unguessable, but embedded IDs are a real
weakness: they let anyone who obtains one URL infer the naming scheme and
correlate files to specific patients or orders. A filename should be an
opaque token that reveals nothing if it leaks. Keep the mapping in the
database, not in the path.

### Assume a CDN will cache any exposure

During the sister-site incident, files fetched during diagnosis were found
sitting in Cloudflare's edge cache with `max-age=315360000` — a **ten year**
TTL. Fixing the origin did not remove them; they had to be explicitly purged.

Two consequences:

- Any exposure window, however brief, can become effectively permanent at the
  edge if a file is fetched even once during it.
- **Purging the CDN is a mandatory remediation step, not a tidy-up.** Verify
  afterwards with cache-busted requests, not just a normal reload.

### Other baseline rules

- `chmod 0640` on stored files
- Explicitly deny PHP execution in the storage directory
- Rate-limit and size-cap the upload endpoint

---

## 2. Build these in from day one

Retrofitting these after launch is painful. They are cheap up front.

- **Encryption at rest.** Location hiding stops leaked URLs and nosy admins.
  It does *nothing* against server compromise (vulnerable plugin, stolen admin
  credentials) — at that point the attacker has the same filesystem access
  your code does. Encryption is what survives that. Keep the key somewhere
  other than the WP database.
- **Retention and auto-deletion.** Verify, then delete on a schedule. Don't
  accumulate documents indefinitely by default. The incident required
  building a bespoke migration tool to clean up files that should never
  have still existed.
- **Audit logging.** Who viewed which document, when. A GPhC-registered
  operator needs to be able to answer "who accessed this patient's records"
  — and after an incident, it's the difference between "we can prove nobody
  accessed it" and "we cannot rule it out."
- **Role-scoped access.** "Is a WordPress admin" is not sufficient for patient
  data. Access should require a pharmacy-staff capability
  (`manage_woocommerce` or a dedicated role) — not merely being logged into
  wp-admin, which typically includes marketing, agency and support accounts
  that have no clinical need to see patient IDs.

---

## 3. Verify empirically — do not reason about it

The single biggest lesson. During the incident the assumption "these files
have random names so they're effectively private" went unverified for
18 hours. A thirty-second check would have caught it immediately.

**Every time you store something sensitive, request it over HTTP and confirm
it is blocked:**

```bash
curl -I https://example.com/wp-content/uploads/secure-dir/testfile.jpg
# MUST return 403 or 404. Anything else means it is publicly downloadable.
```

Do this on staging *and* production, after every deploy that touches storage
paths. Add it to the release checklist. "It should be protected because X"
is not evidence — a 403 response is.

### How to actually prove a storage fix works

Don't just confirm the fixed state looks right — confirm the *broken* state
was real and that your change closes it. The sister site's fix was validated
like this on staging, and it's the standard to follow:

1. **Reproduce the failure** — force the secure directory unwritable
   (`chmod 500`) so the failure path engages
2. **Plant dummy files** (never real patient data) and fetch them publicly —
   confirm **HTTP 200**, i.e. the bug is real and reproduced
3. **Restore the directory and apply the fix**
4. **Re-fetch the exact same URLs with a cache-buster** — confirm **404**
5. **Confirm the app still works** — files resolve, staff can still view them
6. **Run the fix again with nothing to do** — confirm it's idempotent
7. **Remove the dummy files and test directories**

A fix you haven't seen fail first is a fix you haven't tested.

---

## 4. WordPress gotchas (each cost real debugging time)

**Meta boxes render inside `<form id="post">`.** HTML forbids nested forms, so
a `<form>` inside a meta box is silently dropped by the browser. Your inputs
become part of WordPress's post form and your submit button just saves the
post. Use plain buttons plus JS that constructs a form outside the WP form,
or post to a separate admin page.

**`esc_js()` breaks URLs.** It HTML-encodes `&` into `&amp;`, so a URL passed
through it into JavaScript arrives at the server with parameter names like
`amp;_wpnonce` — producing a baffling "The link you followed has expired."
Use `wp_json_encode()` for any URL or data going into a JS context.

**ACF field groups live in the database, not in code.** They do not deploy
with your files. Register them in PHP with `acf_add_local_field_group()` on
the `acf/init` hook so they ship with the theme and appear automatically.

**Never hardcode asset versions.** `wp_enqueue_style( ..., '1.0' )` means the
URL never changes, so browsers and CDNs serve stale CSS/JS indefinitely after
deploys. Version by file modification time:

```php
function asset_ver( $rel ) {
    $f = get_template_directory() . $rel;
    return file_exists( $f ) ? (string) filemtime( $f ) : '1.0';
}
```

---

## 5. Deploy verification

**Confirm the deploy actually ran.** GitHub Actions occasionally does not
create a workflow run for a push, with no error anywhere. This caused an hour
of debugging code that was correct but had never reached the server. After
every merge, confirm a run exists for that specific commit.

Better: have the deploy stamp its commit SHA into a file on the server, and a
scheduled workflow compare that against `main` — re-triggering the deploy
automatically on drift and only alerting if the self-heal fails.

**Clear the page cache AND the CDN cache.** These are separate. HTML changes
need the page cache cleared; asset changes need the CDN cleared too.

**Confirm the change is live before debugging it.** Check the deployed
artifact (view source, curl the asset, check the version stamp) before
assuming the code is wrong.

---

## 6. Pre-launch checklist for any sensitive-upload feature

Storage and architecture:
- [ ] Writable directory outside the web root confirmed **with the host** in writing
- [ ] Files stored there — nothing patient-identifying under `wp-content/uploads/`
- [ ] Storage failure refuses the upload (fail closed), never degrades silently
- [ ] Patient-facing copy written for the storage-unavailable case
- [ ] Filenames are opaque random tokens — no order numbers, IDs or dates
- [ ] Filename-only stored in DB (relocation-safe)

Access control:
- [ ] Read path enforces nonce + capability + realpath containment
- [ ] Capability is pharmacy-staff, not merely "logged into wp-admin"
- [ ] View access is audit-logged
- [ ] Real MIME validated, not just the extension

Verified, not assumed:
- [ ] `curl` confirms a direct request returns 403/404
- [ ] Failure path reproduced on staging and proven closed (§3 method)
- [ ] Uploaded test file does **not** appear in the Media Library
- [ ] Verified on production after deploy, not only on staging

Lifecycle:
- [ ] Retention/deletion policy implemented and documented
- [ ] Someone other than the developer knows where documents live and why

---

## 7. Patient data beyond files

The same thinking applies to screening answers, consultation notes and
eligibility submissions — not just uploads.

- **Store submissions server-side immediately**, before any payment, cart or
  third-party step. Anything that depends on a round-trip to an external
  service can lose data when that service hiccups.
- **Keep clinical data out of exports by default.** WordPress/WooCommerce CSV
  exports and order screens are seen by non-clinical staff. Prefix sensitive
  meta keys with `_` and keep them off export allow-lists unless there's a
  specific reason.
- **Never put patient-identifying detail in URLs.** Query strings end up in
  server logs, CDN logs, browser history and `Referer` headers sent to third
  parties. Use IDs that mean nothing on their own.
- **Emails are not a secure channel.** Sending a patient a copy of their own
  answers is fine and expected. Sending clinical detail to a shared inbox, or
  including document URLs in email, is not — link to a gated admin view
  instead.
