# Listing approval workflow — what changed

## 1. Run the migration
Run `migration_property_status.sql` against your `realestate` database
(after `migration_user_uploads.sql`, which you should already have applied).

It adds:
- `properties.status` — `pending` / `approved` / `rejected`, defaults to `pending`
- `properties.rejection_reason` — optional text shown to the owner

Existing rows are backfilled to `approved` so nothing currently live disappears.

## 2. New listings start as pending
`add.php` now inserts every new listing with `status = 'pending'`. After saving,
the user is redirected to `index.php?submitted=1`, which shows a
"submitted for review" banner.

## 3. Public visibility rules (`search.php`)
- Anonymous visitors and admins browsing the public grid only see `approved` listings.
- A logged-in user also sees their **own** listings regardless of status, with a
  "Pending Review" or "Rejected" badge on the card (plus the rejection reason, if any).
- `details.php` / `property-details.php` redirect away from a non-approved listing
  unless you're the owner or an admin (so the direct link can't be shared around
  the review step).

## 4. Admin dashboard (`admin.php`)
- New "Pending Approval" stat card on the dashboard tab.
- Properties table has a Status column (with the rejection reason shown inline)
  and gets pending listings sorted to the top.
- Each row gets **Approve** / **Reject** buttons (in addition to the existing
  Edit/Delete). Reject prompts for an optional reason, stored and shown to the owner.

## One security fix bundled in
`admin.php` had its admin-only check commented out, so any logged-in user could
open `/admin.php` and approve their own pending listing — which would have made
this whole feature pointless. I re-enabled the check (`$_SESSION['role'] === 'admin'`).

**But `register.php` still lets anyone tick "Register as Admin" and get an admin
account outright.** That's a much bigger hole than this listing feature — it means
approval isn't really enforced by anyone if strangers can just register their way
into the admin role. I didn't touch this since it's a bigger decision (how *should*
someone become an admin — a fixed seed account? an invite code? promoted manually
in the DB?) — but you'll want to close it before this goes anywhere real.

## Not changed (worth deciding later)
- Editing an already-approved listing does **not** currently reset it back to
  `pending` — if you want edits to a live listing to require re-review, say so
  and I'll wire that into `edit.php`.
- There's no email/notification when a listing is approved or rejected — the
  user only finds out by revisiting the site and seeing the badge.
