# Architecture Design

## 1. Architectural Direction

This application is a  Laravel monolith with clear internal boundaries.

Why this is the right fit:
- one codebase and one database for the MVP
- shared authentication and ownership rules across features
- fast iteration for a personal tracker product
- real-time updates via Laravel Reverb and private user channels
- business logic is still isolated enough to remain maintainable

The system is structured as a layered monolith with feature-oriented boundaries inside the app.

```text
                     ┌────────────────────────────┐
                     │        Frontend           │
                     │ Vue + Pinia + Laravel Echo │
                     └──────────────┬─────────────┘
                                    │ HTTP / WebSocket
                                    ▼
                     ┌────────────────────────────┐
                     │      API / Web Layer       │
                     │ Routes / Controllers /     │
                     │ Request Validation / API   │
                     └──────────────┬─────────────┘
                                    │
                                    ▼
                     ┌────────────────────────────┐
                     │    Application Layer       │
                     │ Services / Use Cases /      │
                     │ Authorization / Commands   │
                     └──────────────┬─────────────┘
                                    │
                                    ▼
                     ┌────────────────────────────┐
                     │      Domain Layer          │
                     │ Models / Rules / Policies  │
                     │ Business invariants        │
                     └──────────────┬─────────────┘
                                    │
                                    ▼
                     ┌────────────────────────────┐
                     │     Infrastructure Layer   │
                     │ DB, Filesystem, Broadcasts │
                     │ Reverb, Redis/Queue, CSV   │
                     └────────────────────────────┘
```

## 2. System Context

```text
┌──────────────────────┐        HTTP       ┌────────────────────────────┐
│ User Browser         │ ----------------> │ Laravel App                │
│ Vue UI               │                   │ Routes / Controllers       │
└─────────┬────────────┘                   │ Auth / Policies            │
          │                                 │ Activity services          │
          │                                 └─────────────┬──────────────┘
          │                                               │
          │                                               │ Broadcast
          │                                               ▼
          │                                 ┌────────────────────────────┐
          │                                 │ Laravel Reverb            │
          │                                 │ WebSocket server          │
          │                                 └─────────────┬──────────────┘
          │                                               │
          └───────────────────────────────────────────────┘
                                  Private user channel
                                      user.{id}
```

## 3. Monolith Boundary

This is a monolith, but the internal boundaries are explicit.

```text
┌──────────────────────────────────────────────────────────────┐
│                    Steady.io (Laravel App)                  │
├──────────────────────────────────────────────────────────────┤
│ Auth & User Domain                                          │
│   - login/logout                                            │
│   - email verification                                       │
│   - user ownership / authorization                           │
├──────────────────────────────────────────────────────────────┤
│ Activity Domain                                             │
│   - create/update/start/complete/delete                     │
│   - priority & due dates                                    │
│   - validation rules                                        │
├──────────────────────────────────────────────────────────────┤
│ CSV Import Domain                                           │
│   - file parsing                                             │
│   - validation and row-level errors                          │
│   - per-user import ownership                                │
├──────────────────────────────────────────────────────────────┤
│ Realtime Domain                                             │
│   - Reverb websocket server                                  │
│   - broadcast events                                        │
│   - private channel subscriptions                            │
├──────────────────────────────────────────────────────────────┤
│ Persistence Layer                                           │
│   - MySQL database                                           │
│   - models and relationships                                 │
│   - queries scoped by authenticated user                     │
└──────────────────────────────────────────────────────────────┘
```

## 4. Dependency Direction

The flow of dependencies should always point inward.

```text
Frontend
    │
    ▼
HTTP Controllers
    │
    ▼
Application Services / Use Cases
    │
    ▼
Domain Models + Policies + Rules
    │
    ▼
Infrastructure (DB, Broadcast, File Storage)
```

Rules:
- controllers should not contain business logic
- services should not depend on UI code
- domain rules should not depend on HTTP concerns
- infrastructure concerns are called from the application layer
- data access stays behind a boundary

## 5. Feature Boundaries

The app is organized by features, not by technical kind alone.

```text
┌───────────────────────┐
│ Feature: Auth          │
│ - login                │
│ - verification         │
│ - session management   │
└──────────┬────────────┘
           │
           ▼
┌───────────────────────┐
│ Feature: Activities    │
│ - create               │
│ - list                 │
│ - update               │
│ - start                │
│ - complete             │
│ - delete               │
└──────────┬────────────┘
           │
           ▼
┌───────────────────────┐
│ Feature: Realtime      │
│ - user channel         │
│ - event broadcasts     │
│ - Echo subscriptions   │
└──────────┬────────────┘
           │
           ▼
┌───────────────────────┐
│ Feature: CSV Import    │
│ - parse file           │
│ - validate rows        │
│ - import activities    │
└───────────────────────┘
```

## 6. Realtime Architecture

The real-time path is built on Laravel Reverb over WebSockets, not SSE.

```text
┌────────────────────────┐      WebSocket       ┌────────────────────────┐
│ Browser / Vue client   │ --------------------> │ Laravel Reverb         │
│ Echo subscription      │                       │ WebSocket server       │
│ user.{id} channel      │ <-------------------- │ Broadcast gateway      │
└───────────┬────────────┘     event payload     └───────────┬────────────┘
            │                                                │
            │                                                ▼
            │                                      ┌────────────────────┐
            │                                      │ Laravel App         │
            │                                      │ Activity controller │
            │                                      │ emits event         │
            │                                      └─────────┬──────────┘
            │                                                │
            └────────────────────────────────────────────────┘
                                     event stored/queued then delivered
```

### Realtime flow

1. User performs an action in the UI.
2. HTTP request hits the Laravel API.
3. Controller/service persists the change.
4. Application emits a broadcast event such as `ActivityCreated` or `ActivityDeleted`.
5. Reverb delivers the event to the authenticated private channel for the owner.
6. Vue client listens with Echo and updates local state.

## 7. Activity Lifecycle Flow

```text
┌──────────────┐
│ User Action  │
└──────┬───────┘
       │
       ▼
┌──────────────────────┐
│ ActivityController   │
│ or service layer     │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│ Validate & Authorize │
│ user owns activity   │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│ Persist mutation     │
│ DB update            │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│ Emit domain event    │
│ ActivityUpdated      │
│ ActivityCreated      │
│ ActivityDeleted      │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│ Broadcast on         │
│ private user.{id}    │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│ Frontend Echo client │
│ updates local state  │
└──────────────────────┘
```

## 8. Feature Contract Pattern

Before generating code, define the feature as a contract.

Every feature should begin with:

```text
Feature: [Name]

Purpose:
[What business outcome this feature creates]

Input:
- field: rules

Rules:
- user permission requirements
- business constraints
- validation rules
- ownership rules

Success:
- HTTP status
- response payload

Failure:
- unauthorized
- validation error
- not found
- invalid state transition

Side effects:
- DB writes
- events emitted
- real-time notifications

Database:
- table or model involved

Tests:
- happy path
- auth failure
- validation failure
- authorization failure
```

This is better than asking AI to "build a controller" without context.

## 9. Feature Contracts for This Application

### Feature Contract: Create Activity

```text
Feature: Create Activity

Purpose:
Allow an authenticated user to create a personal activity.

Input:
- title: required string, trimmed, max 255 chars
- description: optional string
- priority: optional enum (low, medium, high)
- due_at: optional datetime

Rules:
- user must be authenticated
- user must have verified email
- activity belongs to authenticated user
- title cannot be blank after trimming
- default status is pending
- default priority is medium

Success:
- 201 Created
- returns created activity payload

Failure:
- 401 Unauthorized
- 403 Forbidden if not allowed
- 422 Validation Error

Side effects:
- inserts activity row
- emits ActivityCreated event
- connected clients receive Reverb broadcast on user.{id}

Database:
- activities table

Tests:
- creates activity for authenticated user
- rejects unauthenticated requests
- rejects empty title
- assigns ownership to the logged-in user
- emits realtime event after creation
```

### Feature Contract: Update Activity

```text
Feature: Update Activity

Purpose:
Allow an authenticated user to update their own activity.

Input:
- title: optional string
- description: optional string
- priority: optional enum
- due_at: optional datetime
- activity_status: optional enum

Rules:
- only the owner may update the activity
- request must be authorized
- all values must pass validation
- invalid transitions are rejected

Success:
- 200 OK
- returns updated activity

Failure:
- 401 Unauthorized
- 403 Forbidden
- 404 Not Found
- 422 Validation Error

Side effects:
- updates database record
- emits ActivityUpdated event
- broadcasts to private user channel

Database:
- activities table

Tests:
- updates owned activity
- prevents modification of another user's activity
- rejects invalid status transitions
- emits update event
```

### Feature Contract: Start Activity

```text
Feature: Start Activity

Purpose:
Move a pending activity into progress.

Input:
- activity id in route

Rules:
- user must own the activity
- activity must be in pending state
- only allowed transition is pending -> in_progress

Success:
- 200 OK
- updated status returned

Failure:
- 401 Unauthorized
- 403 Forbidden
- 404 Not Found
- 422 Invalid status transition

Side effects:
- updates activity status
- emits ActivityUpdated event
- broadcasts to realtime channel

Database:
- activities table

Tests:
- starts an owned pending activity
- blocks a completed activity
- rejects another user's activity
```

### Feature Contract: Complete Activity

```text
Feature: Complete Activity

Purpose:
Mark an activity as completed.

Input:
- activity id in route

Rules:
- user must own the activity
- activity must be in pending or in_progress state
- completion is a terminal state

Success:
- 200 OK

Failure:
- 401 Unauthorized
- 403 Forbidden
- 404 Not Found
- 422 Invalid transition

Side effects:
- persist completion timestamp/state
- emit ActivityUpdated event
- notify connected clients

Database:
- activities table

Tests:
- completes owned pending activity
- completes owned in_progress activity
- rejects invalid transition from completed state
```

### Feature Contract: Delete Activity

```text
Feature: Delete Activity

Purpose:
Remove a user's activity while keeping the system consistent.

Input:
- activity id in route

Rules:
- authenticated user only
- must own the activity
- record is removed after authorization and persistence

Success:
- 200 OK or 204 No Content

Failure:
- 401 Unauthorized
- 403 Forbidden
- 404 Not Found

Side effects:
- deletes row from activity table
- emits ActivityDeleted event
- connected clients remove the item from local state

Database:
- activities table

Tests:
- deletes owned activity
- rejects deleting another user's activity
- broadcasts delete event
```

### Feature Contract: CSV Import

```text
Feature: CSV Import

Purpose:
Allow a user to import many activities in one batch.

Input:
- uploaded CSV file
- required columns: title, description, priority, activity_status, due_at

Rules:
- user must be authenticated
- user must have verified email
- file must be valid CSV
- ownership is forced to authenticated user
- invalid rows are reported without discarding valid rows
- duplicate rows within the import are rejected

Success:
- 200 OK or 201 Created with summary

Failure:
- 401 Unauthorized
- 422 File/validation error

Side effects:
- inserts valid records
- logs rejected rows and reasons
- optionally emits per-row or batch realtime updates

Database:
- activities table

Tests:
- imports valid file rows
- rejects invalid headers
- rejects unauthorized ownership fields
- preserves valid rows when some rows fail
```

## 10. Engineering Guidance

When adding a new feature, do this in order:

1. Define the feature contract.
2. Define the ownership and authorization rules.
3. Model the persistence boundary.
4. Add validation and domain rules.
5. Implement API surface.
6. Add broadcast or realtime events if needed.
7. Write tests first for the key business behavior.
8. Only then write the implementation.

This keeps the product aligned with actual business intent and prevents accidental implementation drift.

## 11. Decision Summary

This architecture chooses:
- Monolith for product simplicity and speed
- Layered architecture for clear boundaries
- Feature-based internal organization
- Reverb + private channels for realtime communication
- Laravel conventions for routing, policy, validation, and event broadcasting
- Explicit ownership rules for every user-scoped resource

The application should remain easy to reason about while still supporting responsive live updates and a simple personal productivity workflow.
