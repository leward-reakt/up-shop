# AGENTS.md

## Project

`up-shop` is a production-ready E-Commerce MVP built as a Laravel monolith.

The goal is to ship the approved MVP quickly without over-engineering, unnecessary abstractions, or scope creep.

## Source of Truth

Always make decisions in this order:

1. Approved project documentation
2. Current repository implementation
3. Official framework/package documentation
4. Laravel best practices
5. Simplicity and production safety

If the repository conflicts with approved project documentation, the documentation wins.

## Locked Stack

* Laravel 13
* PHP 8.3+
* Eloquent ORM
* Inertia.js 3
* React 19
* TypeScript
* Tailwind CSS 4
* Existing shadcn / Radix components
* Filament 5
* Laravel Fortify
* SQLite for local development
* MySQL for production when needed
* Vite + npm
* PHPUnit
* Larastan / PHPStan
* Laravel Pint
* ESLint
* Prettier

Do not replace the existing React/Inertia storefront with Blade or Livewire.

## Architecture

Keep the application simple and follow Laravel conventions.

Use:

* Eloquent for simple database operations
* Controllers for storefront requests
* Filament Resources for admin CRUD
* Form Requests for reusable validation
* Actions for meaningful business workflows
* Policies for authorization
* PHP Enums for stable statuses
* Services for external integrations only
* Laravel Notifications for transactional notifications

Do not introduce unnecessary architectural layers.

Avoid:

* Repository pattern
* DDD folder structures
* CQRS
* Microservices
* Separate frontend/backend applications
* GraphQL
* Redis
* Docker
* Queue infrastructure unless genuinely required
* Complex state-management libraries
* Generic abstractions built for hypothetical future requirements

## MVP Scope

Only implement features included in the approved MVP.

Prioritize the commerce path:

```text
Product
→ Cart
→ Checkout
→ Order
→ Payment
→ Inventory
→ Admin Processing
```

Do not add Phase 2 features unless explicitly approved.

Current MVP payments are:

* Cash on Delivery
* Bank Transfer

Online gateways such as PayMongo, GCash, and Maya are deferred until explicitly approved.

## Implementation Rules

Before implementing a feature:

1. Read the relevant approved documentation.
2. Inspect the current repository implementation.
3. Reuse existing patterns and components.
4. Prefer Laravel/Filament generators where applicable.
5. Make the smallest correct change.
6. Preserve existing behavior unless the requirement explicitly changes it.
7. Validate authorization, validation, security, and edge cases.
8. Add or update relevant automated tests.
9. Keep CI green.

Do not create abstractions for code that is only used once unless there is a clear business or maintainability reason.

## Database

* Store money as integers using the smallest currency unit.
* Never use floating-point values for money.
* Preserve historical order snapshots.
* Checkout/order persistence must be transaction-safe.
* Avoid database-specific features that break SQLite/MySQL compatibility.
* Migrations must avoid unnecessary data loss.

## Frontend

* Reuse existing design tokens and components.
* Keep the storefront consistent across pages.
* Prefer React local state, Inertia props, URL state, and server state.
* Do not add Redux, Zustand, TanStack Query, or similar libraries without a verified need.
* Maintain responsive behavior for desktop, tablet, and mobile.

## Security

Always enforce:

* Server-side authorization
* Input validation
* CSRF protection
* Authenticated ownership checks
* Admin/customer access separation
* Secure handling of sensitive data
* Inventory validation during checkout
* Transaction safety for commerce workflows

Never trust frontend state for authorization, pricing, payment status, or inventory.

## Verification

Run relevant tests during development.

Before considering work complete, run:

```bash
composer ci:check
npm run build
```

Do not claim tests or CI pass unless they were actually executed successfully.

Include manual UI verification when the feature affects customer or admin workflows.

## Scope Control

When uncertain, choose the simpler implementation.

Do not build functionality because it might be useful later.
