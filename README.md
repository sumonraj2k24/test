# Meridian

A modular PHP learning studio for students, instructors, and administrators. Courses, live rooms, quizzes, assignments, carts, commissions, and the operations around them live in one codebase with a Laravel-shaped layout: front controller, middleware, services, and views.

## Run

Requires PHP 8.2+ with PDO SQLite, mbstring, curl, and fileinfo.

```bash
php -S 0.0.0.0:8080 -t public public/router.php
```

or:

```bash
php bin/meridian serve
```

The first request creates `storage/database.sqlite` and seeds the studio.

## Demo accounts

Password for every seeded account: `meridian`

| Role | Email |
| --- | --- |
| Administrator | mira@meridian.test |
| Instructor | amara@meridian.test |
| Instructor | jonah@meridian.test |
| Instructor | priya@meridian.test |
| Student | nora@meridian.test |
| Student | chris@meridian.test |

Also signed in as instructors: leo@meridian.test, helen@meridian.test, idris@meridian.test. Mina Park and Samir Shah are students. Lina Berg has a pending instructor application.

Coupons: `WELCOME10`, `STUDIO20`.

A certificate you can verify without signing in: `MRD-4N7Q-SQL2`.

## What is included

- Catalog search by text, category, level, instructor, rating, and price
- Enrollments, progress, watch history, drip schedules, and sequential courses
- Quizzes with attempt limits and timers, assignments with grading
- Cart, wishlist, coupons, and sandbox checkout for Stripe, PayPal, Mollie, and Paystack
- Live API checkout when gateway keys are saved in settings, plus signed webhooks
- Zoom meeting creation when server-to-server credentials are set, plus in-app rooms
- Google sign-in when OAuth credentials are set
- Forums, messages, notifications, certificates, and SEO (sitemap, robots, course metadata)
- Instructor studio: curriculum builder, earnings, payout requests, analytics
- Admin: users, roles, course review, categories, orders, refunds, payouts, pages, appearance, newsletter, media, SMTP, S3, backups, and versioned updates
- Collaborative or centralized course management

Update `1.1.0` adds private lesson notes. Apply it from Admin → Updates. A backup is taken first. Cron backups: `/cron/run?token=meridian-cron-demo`.

## Layout

```
app/Http/Controllers    request handling, grouped by role
app/Http/Middleware     auth, guest, role, permission
app/Services            payments, progress, zoom, mail, storage, backups, updates
app/Core                router, database, session, view
resources/views         PHP templates
database/schema.sql     install
database/updates        versioned SQL the admin can apply
routes/web.php          HTTP map
```
