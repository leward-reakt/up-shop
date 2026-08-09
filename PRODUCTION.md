# Production Bootstrap & Release Verification

This document defines the minimum production bootstrap and release verification
procedure for the Up Shop MVP.

The application remains a Laravel monolith using:

- Laravel 13
- PHP 8.3+
- MySQL in production
- Filament 5 administration
- Local public file storage
- Synchronous Laravel Notifications
- Database-backed sessions and cache

No Docker, Redis, Horizon, queue worker, or additional infrastructure is
required for the locked MVP.

---

# 1. Pre-Release Quality Gate

Before deploying the release, verify the repository quality pipeline from the
release candidate checkout:

```bash
composer ci:check
npm run build
```

The GitHub Actions workflow also runs the browser smoke suite using Playwright.

Do not continue with production release when required CI checks are failing.

Do not claim a release is verified unless these checks have actually completed
successfully for the release candidate.

---

# 2. Production Application Environment

Create the production `.env` from `.env.example` and replace all placeholders
with production values.

At minimum:

```dotenv
APP_NAME="Up Shop"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your-store-domain.example

SEO_INDEXING=false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=up_shop
DB_USERNAME=up_shop
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync

FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.provider.example
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=orders@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

Rules:

- `APP_URL` must be the real HTTPS storefront URL.
- `APP_DEBUG` must remain `false` in production.
- `SEO_INDEXING` must initially remain `false`.
- Do not commit the production `.env`.
- Do not place real production credentials in `.env.example`.
- Generate `APP_KEY` once for a new production installation and preserve it
  across subsequent deployments.
- Keep `QUEUE_CONNECTION=sync` for the locked MVP.
- Redis is not required.
- The production MySQL account should be an application-specific database user
  rather than an administrative MySQL account.

For a new installation only:

```bash
php artisan key:generate
```

Do not regenerate `APP_KEY` on normal deployments.

---

# 3. Verify Production MySQL Configuration

Before running migrations, clear any stale cached configuration:

```bash
php artisan config:clear
```

Verify Laravel is actually connected to MySQL without printing credentials:

```bash
php artisan tinker --execute="
dump([
    'driver' => DB::connection()->getDriverName(),
    'database' => DB::connection()->getDatabaseName(),
    'mysql_version' => DB::selectOne('select version() as version')->version,
]);
"
```

Expected:

```text
driver        => mysql
database      => production database name
mysql_version => installed MySQL version
```

If the driver is not `mysql`, stop the release and correct the environment
configuration.

---

# 4. Run Production Migrations

For a new production database or normal deployment:

```bash
php artisan migrate --force
```

Verify migration state:

```bash
php artisan migrate:status
```

All required migrations should report as completed.

Never use the following against the production database:

```text
migrate:fresh
migrate:reset
db:wipe
```

Production bootstrap does not require sample data.

Do not run the development seeder. `DatabaseSeeder` intentionally loads
`DevelopmentSeeder` only in the local environment.

---

# 5. Create Public Storage Link

Product images and the store logo use Laravel's `public` filesystem disk.

Create the public storage link:

```bash
php artisan storage:link
```

Verify it exists:

```bash
test -L public/storage && echo "public/storage link exists"
```

The expected mapping is:

```text
public/storage
    -> storage/app/public
```

Ensure the PHP/web-server process can write to:

```text
storage/
bootstrap/cache/
```

Because the MVP uses local public storage, `storage/app/public` contains
persistent store assets and must not be discarded during deployments.

After the admin uploads the store logo and at least one product image, verify
those files load successfully over HTTPS from the storefront.

---

# 6. Provision the First Production Admin

Do not use `DevelopmentSeeder` to create the production administrator.

The current Filament authorization requires the administrator to be:

```text
is_admin = true
is_active = true
email_verified_at != null
```

Use Tinker once for initial provisioning:

```bash
php artisan tinker
```

Then run:

```php
use App\Models\User;
use Illuminate\Support\Str;

$email = 'admin@example.com';

if (User::query()->where('email', $email)->exists()) {
    throw new RuntimeException(
        'A user with this email already exists. Review that account instead of creating another one.'
    );
}

$password = Str::password(24);

$admin = User::query()->create([
    'name' => 'Store Administrator',
    'email' => $email,
    'password' => $password,
    'is_admin' => true,
    'is_active' => true,
]);

$admin->forceFill([
    'email_verified_at' => now(),
])->save();

dump([
    'email' => $admin->email,
    'temporary_password' => $password,
]);
```

Use the real administrator email before executing this command.

The generated password is displayed once in the terminal and is not stored in
the source repository.

Immediately:

- [ ] Sign in at `/admin`.
- [ ] Confirm the account can access Filament.
- [ ] Change the temporary password.
- [ ] Store the production administrator credentials in the approved password
      manager.
- [ ] Confirm a normal customer account cannot access `/admin`.
- [ ] Confirm an inactive admin cannot access `/admin`.

Do not leave development credentials such as `admin@example.com / password`
configured in production.

---

# 7. Configure Store Settings

Before testing checkout, open:

```text
/admin
→ Store Settings
```

Exactly one Store Settings record should exist.

Configure and verify:

- [ ] Store name.
- [ ] Store logo when required.
- [ ] Store email.
- [ ] Contact number.
- [ ] Business address.
- [ ] Currency.
- [ ] Default shipping fee.
- [ ] Free-shipping threshold when applicable.
- [ ] Tax rate when applicable.
- [ ] Social links when applicable.
- [ ] Correct storefront theme.
- [ ] Bank-transfer instructions when Bank Transfer should be available.

Important:

Checkout intentionally fails closed when no Store Settings record exists.

The store currency must be finalized before the first real order. The
application intentionally prevents changing the currency after an order exists.

Bank Transfer is exposed at checkout only when bank-transfer instructions are
configured.

Bank-transfer instructions should contain the information the customer needs,
for example:

```text
Bank name
Account name
Account number
Payment/reference instructions
```

Do not put private banking login credentials or other secrets in these
instructions.

---

# 8. Configure Required Content Pages

Open:

```text
/admin
→ Pages
```

Create or review the six public MVP content pages using exactly these slugs:

```text
about
contact
privacy-policy
terms-and-conditions
shipping-policy
return-refund-policy
```

For each page:

- [ ] Correct title.
- [ ] Correct required legal/business content.
- [ ] Correct slug.
- [ ] SEO title when appropriate.
- [ ] SEO description when appropriate.
- [ ] `Published` enabled only after the content has been reviewed.
- [ ] Public page returns HTTP 200.
- [ ] Draft pages remain unavailable publicly.

Only the approved public slugs above are exposed by the storefront content-page
route.

---

# 9. Configure SMTP

Transactional notifications remain synchronous for the MVP.

Configure production SMTP:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.provider.example
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_FROM_ADDRESS=orders@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

Use the exact scheme, port, authentication settings, and authorized sender
required by the selected SMTP provider.

Never:

- Commit SMTP credentials.
- Put production credentials in `.env.example`.
- Log SMTP passwords.
- Use `MAIL_MAILER=log` when real customer delivery is required.
- Add a queue worker solely for transactional email during the MVP.

After the production environment is complete:

```bash
php artisan config:cache
```

Verify effective non-secret mail configuration:

```bash
php artisan tinker --execute="
dump([
    'mail.default' => config('mail.default'),
    'mail.host' => config('mail.mailers.smtp.host'),
    'mail.port' => config('mail.mailers.smtp.port'),
    'mail.from' => config('mail.from'),
    'queue.default' => config('queue.default'),
]);
"
```

Expected:

```text
mail.default  => smtp
mail.host     => production SMTP host
mail.port     => production SMTP port
mail.from     => production sender
queue.default => sync
```

Never dump `MAIL_PASSWORD`.

---

# 10. Production Optimization

After environment configuration, migrations, and bootstrap configuration are
complete:

```bash
php artisan optimize
```

Verify the application health endpoint:

```bash
curl -fsS https://your-store-domain.example/up
```

Expected response:

```text
Application up
```

Also confirm production is served through the Laravel `public` directory and
not from the project repository root.

---

# 11. Keep Search Indexing Disabled During Smoke Testing

Production must initially use:

```dotenv
SEO_INDEXING=false
```

Rebuild the configuration cache after any environment change:

```bash
php artisan config:cache
```

Verify robots protection:

```bash
curl -fsS https://your-store-domain.example/robots.txt
```

Before activation it should include:

```text
User-agent: *
Disallow: /
```

This prevents an incomplete production bootstrap from being intentionally
advertised for search-engine indexing.

---

# 12. Final Commerce Smoke Test

Use a monitored production test customer and a real test product with a small
controlled stock quantity.

## Storefront

- [ ] Home page loads over HTTPS.
- [ ] Shop page loads.
- [ ] Product search/filtering works.
- [ ] Product detail page loads.
- [ ] Product image loads through `/storage`.
- [ ] Required content pages load.
- [ ] No localhost URLs are visible.

## Cart

- [ ] Add an active in-stock product.
- [ ] Update quantity.
- [ ] Cart totals are correct.
- [ ] Invalid or excessive stock quantity is rejected.
- [ ] Remove item works.

## Checkout

- [ ] Checkout loads with Store Settings configured.
- [ ] Shipping methods display correctly.
- [ ] Default shipping fee is correct.
- [ ] Free-shipping threshold behaves correctly when configured.
- [ ] Tax calculation is correct when configured.
- [ ] Cash on Delivery is available.
- [ ] Bank Transfer is available only when instructions exist.
- [ ] Bank-transfer instructions are visible when Bank Transfer is selected.
- [ ] Guest checkout succeeds.
- [ ] Registered-customer checkout succeeds.
- [ ] Excessive automated checkout submissions are throttled.

## Order and Inventory

Record the product stock before checkout.

- [ ] Exactly one order is created.
- [ ] Order number is generated.
- [ ] Correct order items are stored.
- [ ] Correct customer/shipping snapshot is stored.
- [ ] Correct payment record is created.
- [ ] Correct payment method/status is stored.
- [ ] Inventory decreases exactly once.
- [ ] Cart clears after successful checkout.
- [ ] Customer can view the resulting order when authenticated.

## Admin Operations

- [ ] Administrator can find the order.
- [ ] Non-admin cannot access Filament.
- [ ] Bank Transfer cannot enter Processing while unpaid.
- [ ] COD can continue through its allowed pending-payment flow.
- [ ] Unpaid order cannot be completed.
- [ ] Valid pre-shipment cancellation restores inventory exactly once.
- [ ] Shipped/Completed order cannot use normal cancellation.
- [ ] Paid Bank Transfer payment can be recorded with its reference.
- [ ] Order can move through the expected workflow.
- [ ] Inventory remains correct after each operation.

---

# 13. Transactional Email Release Verification

Use the production smoke-test order and a monitored customer email address.

## Order Confirmation

- [ ] Exactly one order-confirmation email arrives.
- [ ] Recipient is correct.
- [ ] Order number is correct.
- [ ] Total is correct.
- [ ] Payment method/status are correct.
- [ ] Authenticated `View order` link uses the production HTTPS domain.

## Payment Confirmation

Use a Bank Transfer order.

- [ ] Enter a payment reference.
- [ ] Mark the payment Paid.
- [ ] Payment and order payment statuses become Paid.
- [ ] Exactly one payment-confirmation email arrives.
- [ ] Amount is correct.
- [ ] Payment reference is correct.
- [ ] Saving an already-paid payment does not send another confirmation.

## Processing

- [ ] Move an eligible order to Processing.
- [ ] Exactly one processing email arrives.
- [ ] Email contains the correct order/status.

## Shipped

Use a delivery order.

- [ ] Move Processing to Shipped.
- [ ] Exactly one shipped email arrives.
- [ ] Email contains the correct order/status.

Do not use Store Pickup for this specific shipped-email check.

## Completed

- [ ] Move an eligible Shipped order to Completed.
- [ ] Exactly one completed email arrives.
- [ ] Email contains the correct order/status.

## Cancelled

Use a separate eligible unpaid pre-shipment order.

- [ ] Cancel the order.
- [ ] Order becomes Cancelled.
- [ ] Inventory is restored exactly once.
- [ ] Associated unpaid payment is cancelled when applicable.
- [ ] Exactly one cancellation email arrives.

For every transactional email:

- [ ] Customer name is correct.
- [ ] Currency formatting is correct.
- [ ] Production links use HTTPS.
- [ ] No localhost URLs appear.
- [ ] No credentials, tokens, stack traces, or environment values appear.
- [ ] Application logs contain no SMTP transport exceptions.

---

# 14. Activate Search Indexing

Only enable search indexing after:

```text
Production configuration complete
MySQL migrations complete
Admin provisioned
Store Settings configured
Content reviewed and published
Public storage verified
SMTP verified
Commerce smoke test passed
Transactional notifications verified
```

Then update:

```dotenv
SEO_INDEXING=true
```

Rebuild cached configuration:

```bash
php artisan config:cache
```

Verify:

```bash
curl -fsS https://your-store-domain.example/robots.txt
```

Expected behavior includes:

```text
User-agent: *
Allow: /
Disallow: /admin
Disallow: /cart
Disallow: /checkout
Disallow: /dashboard
Disallow: /account
Disallow: /settings
```

Verify the sitemap:

```bash
curl -fsS https://your-store-domain.example/sitemap.xml
```

Confirm that it contains:

- [ ] Home.
- [ ] Shop.
- [ ] Published approved content pages.
- [ ] Active storefront products.
- [ ] Production HTTPS URLs only.

---

# 15. Final Release Checklist

## Environment

- [ ] `APP_ENV=production`.
- [ ] `APP_DEBUG=false`.
- [ ] `APP_URL` is the real HTTPS URL.
- [ ] Production `APP_KEY` exists.
- [ ] Production secrets are not committed.
- [ ] `QUEUE_CONNECTION=sync`.
- [ ] No Redis/Horizon/queue infrastructure was introduced.

## Database

- [ ] `DB_CONNECTION=mysql`.
- [ ] Laravel reports the expected production database.
- [ ] `php artisan migrate --force` completed.
- [ ] `php artisan migrate:status` shows all migrations completed.
- [ ] No development seed data was loaded.

## Administration

- [ ] First administrator exists.
- [ ] Admin is active.
- [ ] Admin email is verified.
- [ ] Admin can access `/admin`.
- [ ] Non-admin cannot access `/admin`.
- [ ] Temporary admin password was changed.

## Store

- [ ] Store Settings exist.
- [ ] Currency finalized before first real order.
- [ ] Shipping configuration reviewed.
- [ ] Tax configuration reviewed.
- [ ] Bank-transfer instructions configured when required.
- [ ] Required pages are reviewed and published.

## Files

- [ ] `php artisan storage:link` completed.
- [ ] Store logo loads.
- [ ] Product images load.
- [ ] `storage/app/public` is persistent.
- [ ] `storage` and `bootstrap/cache` are writable.

## Email

- [ ] SMTP configuration verified.
- [ ] Order confirmation delivered.
- [ ] Payment confirmation delivered.
- [ ] Processing notification delivered.
- [ ] Shipped notification delivered.
- [ ] Completed notification delivered.
- [ ] Cancelled notification delivered.
- [ ] No duplicate transition emails observed.

## Commerce

- [ ] Browse/search/product flow works.
- [ ] Cart works.
- [ ] Checkout works.
- [ ] Totals are correct.
- [ ] Inventory validation works.
- [ ] Inventory deducts correctly.
- [ ] Order creation works.
- [ ] Payment workflow works.
- [ ] Admin processing works.
- [ ] Cancellation rules work.
- [ ] Customer order access is protected.

## SEO

- [ ] Indexing remained disabled during bootstrap.
- [ ] Production canonical URLs use HTTPS.
- [ ] `/sitemap.xml` is correct.
- [ ] `/robots.txt` is correct.
- [ ] `SEO_INDEXING=true` enabled only after final smoke verification.

## Quality

- [ ] Required GitHub Actions checks are green for the release.
- [ ] `composer ci:check` passed for the release candidate.
- [ ] `npm run build` passed for the release candidate.
- [ ] No unresolved production-blocking errors appear in Laravel logs.

The MVP is production-ready only after every applicable release-blocking item
above has been verified.
