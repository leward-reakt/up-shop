# Production Configuration & Release Verification

This document records the minimum production configuration and release
verification requirements for the Up Shop MVP.

The application remains a Laravel monolith with synchronous transactional
notifications.

No queue worker, Redis, or Laravel Horizon is required for the MVP.

---

## Production Application Environment

At minimum, production should use:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-store-domain.example

QUEUE_CONNECTION=sync
```

`APP_URL` must use the real production HTTPS URL because transactional
notifications may generate links back to customer order pages.

Do not commit the production `.env` file or production secrets to source
control.

---

## Production Transactional Email

The MVP sends customer transactional emails synchronously through Laravel
Notifications.

The current notifications are:

- Order confirmation
- Payment confirmation
- Order processing
- Order shipped
- Order completed
- Order cancelled

No queue worker is required.

### Required SMTP Configuration

Configure the production mail provider using environment variables similar to:

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

Replace every example value with the credentials and settings supplied by the
selected SMTP provider.

`MAIL_SCHEME` and `MAIL_PORT` are provider-specific. Use the values documented
by the selected mail provider.

`MAIL_USERNAME` and `MAIL_PASSWORD` must contain the production SMTP
credentials when the provider requires authentication.

`MAIL_FROM_ADDRESS` should use a sender address authorized by the selected
mail provider.

Never:

- Commit SMTP credentials.
- Put real credentials in `.env.example`.
- Log SMTP passwords.
- Use `MAIL_MAILER=log` in production when real customer email delivery is required.
- Add a queue worker solely for transactional email during the MVP.

---

## Applying Production Configuration

After changing production environment variables, rebuild Laravel's production
configuration cache:

```bash
php artisan config:cache
```

Verify the effective configuration without printing SMTP credentials:

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

Expected values include:

```text
mail.default   => smtp
mail.host      => production SMTP host
mail.port      => production SMTP port
mail.from      => production sender
queue.default  => sync
```

Do not print or dump `MAIL_PASSWORD` during release verification.

---

# Transactional Email Release Checklist

Use a monitored test customer email address in the production environment.

Perform these checks before declaring transactional notifications production
ready.

## 1. Order Confirmation

- [ ] Add an available product to the cart.
- [ ] Complete checkout using valid customer details.
- [ ] Confirm the order is successfully created.
- [ ] Confirm exactly one email is received.
- [ ] Confirm the recipient matches the checkout email.
- [ ] Confirm the subject is `Order {order_number} received`.
- [ ] Confirm the order number is correct.
- [ ] Confirm the order total is correct.
- [ ] Confirm payment method and payment status are correct.
- [ ] For an authenticated customer, confirm `View order` opens the correct production order page over HTTPS.

## 2. Payment Confirmation

Use a Bank Transfer order for the release test.

- [ ] Open the payment in the Filament admin.
- [ ] Enter a valid payment reference.
- [ ] Mark the payment as Paid.
- [ ] Confirm the payment and order payment statuses become Paid.
- [ ] Confirm exactly one email is received.
- [ ] Confirm the subject is `Payment confirmed for {order_number}`.
- [ ] Confirm the amount received is correct.
- [ ] Confirm the payment reference is correct.
- [ ] Save the already-paid payment again without a new state transition.
- [ ] Confirm no duplicate payment confirmation email is sent.

## 3. Order Processing

Use an order currently in Confirmed status.

- [ ] Change the order from Confirmed to Processing.
- [ ] Confirm the status change succeeds.
- [ ] Confirm exactly one email is received.
- [ ] Confirm the subject is `Order {order_number} is being processed`.
- [ ] Confirm the email reports Processing status.

## 4. Order Shipped

Use a delivery order rather than a Store Pickup order.

- [ ] Change the order from Processing to Shipped.
- [ ] Confirm the status change succeeds.
- [ ] Confirm exactly one email is received.
- [ ] Confirm the subject is `Order {order_number} has shipped`.
- [ ] Confirm the email reports Shipped status.

Store Pickup orders use the Ready for Pickup workflow and therefore should not
be used for this specific shipped-email verification.

## 5. Order Completed

Use an order currently in Shipped status.

- [ ] Change the order from Shipped to Completed.
- [ ] Confirm the status change succeeds.
- [ ] Confirm exactly one email is received.
- [ ] Confirm the subject is `Order {order_number} is complete`.
- [ ] Confirm the email reports Completed status.

## 6. Order Cancelled

Use a separate unpaid order.

- [ ] Cancel the unpaid order.
- [ ] Confirm the order becomes Cancelled.
- [ ] Confirm inventory is restored correctly.
- [ ] Confirm the associated unpaid payment is cancelled when applicable.
- [ ] Confirm exactly one email is received.
- [ ] Confirm the subject is `Order {order_number} was cancelled`.
- [ ] Confirm the email reports Cancelled status.

Do not use a Paid order for this cancellation test because the application
requires paid payments to be resolved or refunded before cancellation.

---

# Cross-Cutting Email Verification

For every transactional email:

- [ ] Recipient address is correct.
- [ ] Customer name is correct.
- [ ] Order number is correct.
- [ ] Currency formatting is correct.
- [ ] Links use the production HTTPS domain.
- [ ] No localhost URLs appear.
- [ ] No credentials, tokens, internal stack traces, or sensitive environment values appear in the email.
- [ ] Only one email is received for one state transition.
- [ ] Application logs contain no mail transport exceptions.

---

# Failure Behavior

Transactional notifications are intentionally synchronous for the MVP.

The commerce actions persist their database changes before attempting customer
email delivery. Mail transport exceptions are reported by Laravel and do not
roll back an already committed order, payment, or order-status update.

Therefore:

- Email delivery must be verified during every production release.
- Production application logs should be checked for mail transport failures.
- SMTP provider availability should be checked when customers report missing transactional emails.

If synchronous mail delivery later creates a measured response-time or
reliability problem, asynchronous delivery may be evaluated as a future
architecture change. It is not part of the current MVP.
