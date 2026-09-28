# SteadyTracker Product Requirements Document

**Product:** SteadyTracker  
**Document:** Product Requirements Document  
**Version:** 1.0  
**Status:** MVP definition  
**Owner:** Product and Engineering  
**Last updated:** September 25, 2026

## 1. Overview

SteadyTracker is a web-based activity and task management application for authenticated users. It provides a private place to create, organize, prioritize, start, complete, and review activities.

The MVP provides persistent task management through a versioned REST API and near-real-time updates through Laravel Reverb over WebSockets. It also supports validated CSV import so users can create multiple activities efficiently.

In this document, **activity** is the canonical product and API term. “Task” is a general description of an activity.

## 2. Problem Statement

People often lose track of responsibilities because tasks are scattered across memory, notes, and disconnected tools. SteadyTracker provides a single source of truth for personal work, helping users understand what needs attention, what is in progress, and what has been completed.

## 3. Goals

### Primary goals

1. Allow authenticated users to securely manage their own activities.
2. Persist activity data reliably.
3. Provide a predictable, versioned REST API.
4. Keep connected clients synchronized through Laravel Reverb WebSocket events.
5. Validate input and return consistent, understandable errors.
6. Support status, priority, due dates, and completion tracking.
7. Support validated bulk activity creation through CSV import.
8. Preserve useful activity history through timestamps and status changes.

### Secondary goals

1. Keep the architecture understandable and maintainable.
2. Separate HTTP, application, domain, persistence, and real-time concerns.
3. Cover critical business behavior with automated tests.
4. Avoid unnecessary MVP complexity.

## 4. Non-goals

The MVP excludes team collaboration, comments, attachments, calendar integration, SMS notifications, AI features, streaks, recurring activities, native mobile applications, third-party integrations, and advanced search or analytics.

## 5. Target User and Journey

The target user is an authenticated person who wants to capture responsibilities, prioritize them, track progress, and review completed work.

1. The user authenticates and verifies their email address.
2. The user opens the dashboard and sees their activities.
3. The user creates or imports activities.
4. The user updates activity details or status.
5. The system persists each successful change.
6. Laravel broadcasts a Reverb WebSocket event on the user's private channel.
7. Connected clients update their activity view.

## 6. Functional Requirements

### FR-001: Authentication and verification

Protected activity resources require authentication and verified email. Invalid credentials and unverified accounts must be rejected without exposing sensitive implementation details.

### FR-002: Create activities

Authenticated users must be able to create an activity with a title and optional metadata. The authenticated user is always the owner. Successful creation returns the activity and broadcasts `ActivityCreated` through Reverb.

### FR-003: List and retrieve activities

Users must be able to list and retrieve only their own activities. Another user's activity must not be exposed, even when its identifier is known.

### FR-004: Update activities

Users must be able to update their own allowed activity fields. Successful updates persist and broadcast `ActivityUpdated` through Reverb.

### FR-005: Start activities

Users must be able to move an activity from `pending` to `in_progress`. Invalid transitions must be rejected.

### FR-006: Complete activities

Users must be able to move an activity from `pending` or `in_progress` to `completed`. Completion state and timestamp must persist.

### FR-007: Delete activities

Users must be able to delete their own activities. A successful deletion broadcasts `ActivityDeleted` through Reverb. The MVP default is permanent deletion unless the retention decision changes.

### FR-008: CSV import

Authenticated and verified users must be able to upload one CSV file containing activities. The importer must validate the file and headers, process rows independently, persist valid rows for the authenticated user, report rejected rows with reasons, reject duplicates, and never accept ownership fields from the file.

## 7. Activity Model and Status Rules

| Field | Required | Description |
| --- | --- | --- |
| `id` | System-generated | Unique activity identifier |
| `user_id` | System-generated | Authenticated owner |
| `title` | Yes | Short description, maximum 255 characters |
| `description` | No | Additional context |
| `priority` | No | `low`, `medium`, or `high`; default `medium` |
| `activity_status` | No | `pending`, `in_progress`, or `completed`; default `pending` |
| `due_at` | No | Optional supported date and time |
| `created_at` | System-generated | Creation timestamp |
| `updated_at` | System-generated | Last modification timestamp |

```text
pending -> in_progress -> completed
   \---------------------------> completed
```

New activities default to `pending`. `start` changes `pending` to `in_progress`; `complete` changes `pending` or `in_progress` to `completed`. Invalid transitions return a client error. Completed activities remain readable.

## 8. Validation and CSV Rules

- `title` is required, trimmed, non-empty, and at most 255 characters.
- `description` is optional and must remain within its maximum length.
- `priority` must be `low`, `medium`, or `high`.
- `activity_status` must be `pending`, `in_progress`, or `completed`.
- `due_at` must use the supported date-time format when present.

The canonical CSV header is:

```csv
title,description,priority,activity_status,due_at
```

Blank optional values default to `medium`, `pending`, or `null` as appropriate. The CSV must not contain `id`, `user_id`, or other system-generated fields.

Duplicate detection uses `lowercase(trim(title)) + normalized due_at`. The later matching row is rejected. Existing activities are checked only within the authenticated user's activities; different users may have the same title and due date.

## 9. API Requirements

The API is versioned under `/api/v1`. Protected activity operations require authentication and email verification.

| Method | Endpoint | Purpose |
| --- | --- | --- |
| `GET` | `/api/v1/activities` | List owned activities |
| `POST` | `/api/v1/activities` | Create an activity |
| `PATCH` | `/api/v1/activities/{activity}` | Update an activity |
| `PATCH` | `/api/v1/activities/{activity}/start` | Start an activity |
| `PATCH` | `/api/v1/activities/{activity}/complete` | Complete an activity |
| `DELETE` | `/api/v1/activities/{activity}` | Delete an activity |
| `POST` | `/api/v1/csv-import` | Import activities from CSV |

API errors use appropriate HTTP status codes and a consistent structure:

```json
{
  "error": {
    "code": "VALIDATION_FAILED",
    "message": "The request contains invalid data.",
    "fields": {
      "title": ["The title field is required."]
    }
  }
}
```

Errors must not expose stack traces, credentials, SQL details, or other sensitive internals.

## 10. Real-Time Requirements

SteadyTracker uses **Laravel Reverb as its WebSocket server**. Server-Sent Events are not part of the MVP.

Laravel broadcasting events are sent to authenticated, private, user-scoped channels:

- `ActivityCreated`
- `ActivityUpdated`
- `ActivityDeleted`

The channel pattern is `user.{id}`. A user may subscribe only to their own channel. The frontend uses Laravel Echo with the Reverb broadcaster and listens for these events.

The client must reconnect after a dropped WebSocket connection and refresh state when delivery cannot be confirmed. Because broadcast events are queued by default, each environment must run a queue worker.

## 11. Security, Reliability, and Performance

The system must authenticate protected requests, require verified email, scope all queries and mutations to the authenticated user, validate input, prevent CSV ownership spoofing, avoid sensitive error output, rate-limit authentication and upload endpoints, and log relevant security events.

Failed operations must not leave inconsistent activity state. CSV row failures must not discard valid rows. Database failures must return controlled errors. Normal API operations must meet the agreed MVP response target.

## 12. Edge Cases and Success Metrics

Tests must cover empty or oversized titles, invalid descriptions, unauthenticated and unverified requests, nonexistent or unauthorized activities, invalid status transitions, invalid enum and date values, database failures, WebSocket disconnects and reconnects, duplicate requests, invalid CSV files, mixed valid and invalid CSV rows, and concurrent updates.

The MVP is successful when users can authenticate and manage their own activities, invalid requests are rejected correctly, unauthorized access is prevented, changes persist, authorized clients receive Reverb WebSocket events, CSV imports explain rejected rows, and critical business behavior is covered by automated tests.

## 13. MVP Scope

### Included

- Authentication and email verification
- Activity creation and management
- Status, priority, due dates, and timestamps
- Ownership and authorization
- Database persistence
- Versioned REST API
- Laravel Reverb WebSocket updates
- CSV import with row-level validation
- Consistent error handling
- Automated tests

### Excluded

- Collaboration, comments, and attachments
- AI features
- Calendar integration
- SMS and advanced notifications
- Recurring activities
- Third-party integrations
- Native mobile applications

## 14. Risks and Open Decisions

| Risk | Impact | Mitigation |
| --- | --- | --- |
| Unauthorized resource access | High | Ownership scopes and authorization tests |
| Database failure | High | Controlled errors and atomic mutations |
| WebSocket disconnects | Medium | Client reconnection and state refresh |
| Duplicate requests | Medium | Defined duplicate behavior and idempotency where required |
| Invalid CSV rows | Medium | Independent validation and row-level results |
| Concurrent updates | Medium | Defined conflict policy and tests |

Open decisions are whether deleted activities are archived, whether completed activities can be edited, the maximum description length, the exact Reverb payload format, the MVP traffic target, history retention, idempotency requirements, and the concurrent update conflict policy.

Recommended defaults are permanent deletion, editable completed activities, a 2,000-character description limit, and last-write-wins updates with server timestamps.

## 15. Definition of Done

The MVP is complete when requirements are implemented or explicitly deferred; authentication, verification, validation, and authorization are enforced; activity persistence and status transitions work; API behavior matches the contract; CSV import handles valid and invalid rows; Reverb events reach only authorized clients; critical tests pass; errors do not expose sensitive details; and documentation reflects the implementation.

## 16. Related Technical Documents

Architecture design, system design, database design, API specification, authentication design, Reverb and WebSocket design, security design, testing strategy, and deployment design should be maintained alongside this PRD.

This PRD defines what SteadyTracker should do and why. Technical documents define how it is implemented.
