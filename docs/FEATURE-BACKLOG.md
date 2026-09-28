# Feature Implementation Backlog

This backlog describes the feature contracts and implementation sequence for Steady.io. Status is based on the current routes, models, controllers, events, and the CSV implementation summary; it is not a release commitment.

## Status Legend

- **Present:** a route or implementation is present in the current codebase; contract-level tests and edge cases may still need confirmation.
- **Follow-up:** known work remains or behavior needs verification.
- **Decision:** confirm product behavior before implementation.

## Feature Map

```text
┌────────────────────┐
│ Authentication     │
│ Sanctum + verified │
└─────────┬──────────┘
          │ authorizes and scopes
          ▼
┌────────────────────┐       ┌────────────────────┐
│ Activities         │
│ CRUD + status      │
└─────────┬──────────┘
          │ emits broadcasts
          ▼
┌────────────────────┐       ┌────────────────────┐
│ Reverb / Echo      │       │ Notifications      │
│ private user channel│      │ backend API/events │
└────────────────────┘       └────────────────────┘

┌────────────────────┐
│ CSV Import         │──────> Activities
│ validate and write │
└────────────────────┘
```

## Recommended Order

1. **P0: Authentication, verification, and ownership guarantees.** Every user-scoped read and mutation depends on these boundaries.
2. **P1: Activity lifecycle and realtime synchronization.** Confirm CRUD, status transitions, authorization, persistence, and private broadcasts as one vertical slice.
3. **P1: CSV import completion.** Verify row-level behavior and test real-world file encodings and sizes.
4. **P2: Notifications.** The inbox UI is removed; decide whether backend notification records should remain or be retired.
5. **P2: Operational reliability.** Verify queue, Reverb, reconnect, logging, and recovery behavior in the target environment.

## Feature Contracts

### Authentication and Verified Access

```text
Feature: Authenticate and verify user

Purpose:
Allow a user to access their account and protect private application resources.

Input:
- credentials or supported third-party identity
- verification action when required

Rules:
- private API endpoints require Sanctum authentication
- activity, notification, and CSV endpoints require verified email
- identity and session credentials must not be exposed in responses

Success:
- authenticated user/session is established
- GET /api/user returns the current authenticated user

Failure:
- 401 Unauthorized for missing or invalid authentication
- verification failure prevents access to verified-only routes

Side effects:
- session or access token lifecycle
- verification state may be updated

Database:
- users, sessions, password_reset_tokens, personal_access_tokens

Tests:
- authenticated user can access their user endpoint
- unauthenticated requests are rejected
- unverified user cannot access verified-only endpoints
- serialized user does not expose password or remember token
```

Status: **Present**. Confirm authentication edge-case tests and the documented verification response behavior.

### Activity Lifecycle

```text
Feature: Manage activities

Purpose:
Allow an authenticated user to create, view, update, start, complete, and delete their own activities.

Input:
- title: required string
- description: optional text
- activity_status: pending | in_progress | completed
- priority: low | medium | high
- due_at: optional datetime

Rules:
- user must be authenticated and verified
- every activity belongs to the authenticated user
- activity reads and mutations are owner-scoped
- new activities default to pending and medium priority
- start_at and completed_at reflect lifecycle transitions
- only supported state transitions are accepted

Success:
- list/create/update/start/complete/delete responses follow their endpoint contract

Failure:
- 401 Unauthorized
- 403 Forbidden or 404 Not Found for unauthorized resources, consistently applied
- 422 Validation Error or invalid transition

Side effects:
- activity database mutation
- ActivityCreated, ActivityUpdated, or ActivityDeleted broadcast as applicable
- authorized connected clients update their local state

Database:
- activities

Tests:
- create/list/update/start/complete/delete succeeds for an owner
- another user cannot read or mutate the activity
- validation and invalid state transitions are rejected
- expected event is emitted for each successful mutation
```

Status: **Present**. Focus follow-up verification on authorization, transition rules, and broadcast behavior for each lifecycle operation.

### CSV Activity Import

```text
Feature: Import activities from CSV

Purpose:
Allow a user to create many activities from one validated CSV upload.

Input:
- file: required CSV or text upload within the configured size limit
- canonical columns: title, description, priority, activity_status, due_at

Rules:
- user must be authenticated and verified
- ownership always comes from the authenticated user
- invalid rows are reported individually while valid rows are imported
- duplicate detection and normalization follow the import service contract

Success:
- POST /api/v1/csv-import returns imported count, rejected count, and rejection details

Failure:
- 400 Bad Request when no file is supplied
- 422 Unprocessable Entity for invalid upload or import data

Side effects:
- valid activity rows are inserted
- per-row errors are returned to the caller
- realtime notifications for imported records require an explicit product decision

Database:
- activities

Tests:
- valid file imports for authenticated owner
- invalid file, headers, and fields are rejected
- mixed valid/invalid file imports valid rows and reports rejected rows
- ownership cannot be supplied or spoofed through CSV content
- duplicate rows follow the declared duplicate policy
```

Status: **Present**. The upload screen provides a downloadable template, staged file selection, size/extension checks, and row-level import results. Continue testing real-world CSV encodings and large-file behavior.

### Realtime Activity Updates

```text
Feature: Synchronize activity changes over WebSockets

Purpose:
Keep a user's connected clients synchronized after activity mutations.

Input:
- ActivityCreated, ActivityUpdated, or ActivityDeleted broadcast payload
- private subscription to user.{id}

Rules:
- transport is Laravel Reverb over WebSockets, not SSE
- only the matching authenticated user may join the private channel
- client applies events idempotently where practical
- after disconnect or uncertain delivery, client refreshes authoritative API state

Success:
- event is delivered to connected clients subscribed to the owner's channel

Failure:
- unauthorized channel subscription is denied
- unavailable server/queue does not corrupt persisted activity state

Side effects:
- broadcast event delivery, potentially through the configured queue

Database:
- activities; queue/cache tables and services are operational dependencies

Tests:
- channel authorization permits the owner and rejects other users
- lifecycle events include the expected activity identifier and owner channel
- client updates local state after create/update/delete
- queued delivery and reconnect/reload behavior are verified in deployment checks
```

Status: **Present; runtime verification required**. Keep the Reverb server and queue worker configuration aligned across environments.

### Notifications

```text
Feature: Maintain user notifications

Purpose:
Persist and broadcast activity-related notifications for authenticated clients.

Input:
- authenticated user identity

Rules:
- notifications are scoped to their notifiable recipient
- only the current user's records are returned
- ordering is newest first
- pagination and mark-as-read behavior need a product decision

Success:
- GET /api/v1/notifications returns notification summary fields

Failure:
- 401 Unauthorized

Side effects:
- read operation only for the current endpoint

Database:
- notifications polymorphic table

Tests:
- only current user's notifications are returned
- latest-first ordering is stable
- missing optional payload fields have safe response values
```

Status: **Present** for listing. Confirm notification producers and decide whether to add pagination and mark-as-read endpoints.

## Definition of Done for a Feature

- Contract is agreed before implementation.
- Authentication, ownership, and validation behavior are explicit.
- Persistence and external side effects are defined.
- Success and failure responses are covered by tests.
- Realtime behavior, where applicable, is covered from event to client state.
- Documentation reflects shipped behavior rather than intended behavior.
