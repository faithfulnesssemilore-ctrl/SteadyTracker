# Domain Model and Database Design

This document describes the current activity-focused persisted domain. It reflects Laravel migrations and models rather than planned fields.

## Domain Overview

Steady.io centers on activities owned by authenticated users. Users can manage activity lifecycle and import activities from CSV. Laravel notifications, sessions, jobs, cache, and passkeys support the application.

```text
┌──────────────┐       owns       ┌──────────────────┐
│ User         │ 1 ─────────── * │ Activity         │
└──────────────┘                  └──────────────────┘

┌──────────────┐      receives      ┌──────────────────┐
│ User         │ 1 ────────────── * │ Notification     │
└──────────────┘                    │ polymorphic      │
                                    └──────────────────┘
```

## Entity Relationship Diagram

```text
┌───────────────────────────────┐
│ users                         │
├───────────────────────────────┤
│ PK id                         │
│ user_name                     │
│ email (unique)                │
│ email_verified_at (nullable)  │
│ google_id (nullable, unique)  │
│ password (nullable)           │
│ remember_token                │
│ created_at / updated_at       │
└──────────────┬────────────────┘
               │ 1
               │
               │ *
┌──────────────▼────────────────┐
│ activities                    │
├───────────────────────────────┤
│ PK id                         │
│ FK user_id                    │
│ title                         │
│ description (nullable)        │
│ activity_status               │
│ priority                      │
│ due_at (nullable)             │
│ start_at (nullable)           │
│ completed_at (nullable)       │
│ emotional_bucket (nullable)   │
│ created_at / updated_at       │
└───────────────────────────────┘

┌────────────────────────────────┐
│ notifications                  │
├────────────────────────────────┤
│ PK id (UUID)                   │
│ type                           │
│ notifiable_type + notifiable_id│
│ data                           │
│ read_at (nullable)             │
│ created_at / updated_at        │
└────────────────────────────────┘
```

## Entities

### User

A user is the owner and security boundary for personal activities and their notifications.

| Field                      | Meaning                                |
| -------------------------- | -------------------------------------- |
| `id`                       | Primary key                            |
| `user_name`                | User-facing name                       |
| `email`                    | Unique login/contact address           |
| `email_verified_at`        | Email verification timestamp; nullable |
| `google_id`                | Optional unique external identity      |
| `password`                 | Nullable local password                |
| `remember_token`           | Laravel remember-session token         |
| `created_at`, `updated_at` | Laravel timestamps                     |

Related authentication tables include `sessions`, `password_reset_tokens`, `personal_access_tokens`, and `passkeys`. Two-factor authentication columns and endpoints are not part of the current design.

### Activity

An activity is a piece of work owned by one user.

| Field                      | Meaning                                                         |
| -------------------------- | --------------------------------------------------------------- |
| `id`                       | Primary key                                                     |
| `user_id`                  | Required owner; cascades when user is deleted                   |
| `title`                    | Activity name                                                   |
| `description`              | Optional long text                                              |
| `activity_status`          | `pending`, `in_progress`, or `completed`; defaults to `pending` |
| `priority`                 | `low`, `medium`, or `high`; defaults to `medium`                |
| `due_at`                   | Optional due date/time                                          |
| `start_at`                 | Optional time activity started                                  |
| `completed_at`             | Optional completion time                                        |
| `emotional_bucket`         | Optional string classification                                  |
| `created_at`, `updated_at` | Laravel timestamps                                              |

Lifecycle and priority values are database enums cast to PHP enums by the Activity model.

```text
pending ──────> in_progress ──────> completed
   └──────────────────────────────> completed
```

### Notification

Notifications use Laravel's polymorphic notification table. Current notification producers store activity status and overdue updates for the user. The inbox screen was removed; notifications remain available through the backend API and broadcast channel for future clients.

| Field                              | Meaning                            |
| ---------------------------------- | ---------------------------------- |
| `id`                               | UUID primary key                   |
| `type`                             | Notification class/type identifier |
| `notifiable_type`, `notifiable_id` | Polymorphic recipient              |
| `data`                             | Serialized notification payload    |
| `read_at`                          | Null until marked read             |
| `created_at`, `updated_at`         | Laravel timestamps                 |

## Ownership and Deletion Semantics

```text
User deletion
  └── deletes that user's activities

Activity deletion
  └── deletes the activity record; connected clients receive ActivityDeleted
```

Always assign `user_id` from the authenticated identity and scope reads and mutations to that owner. Database foreign keys protect referential integrity; application authorization protects privacy.

## Supporting Tables

| Table                                | Role                        |
| ------------------------------------ | --------------------------- |
| `sessions`                           | Laravel session storage     |
| `password_reset_tokens`              | Password reset flow         |
| `personal_access_tokens`             | Sanctum API tokens          |
| `cache`, `cache_locks`               | Cache and lock storage      |
| `jobs`, `job_batches`, `failed_jobs` | Queued work and failures    |
| `notifications`                      | User notification records   |
| `passkeys`                           | Passkey authentication data |

The exact set depends on installed packages and migrations. Two-factor authentication is disabled; passkeys are a separate feature and remain enabled.

## Data Integrity Rules

- Set `user_id` from the authenticated identity, never untrusted request data.
- Scope activity reads and mutations to the owner.
- Validate status transitions in application logic; a database enum does not enforce transition order.
- Maintain `start_at` and `completed_at` consistently with status changes.
- Keep broadcast payloads limited to information authorized subscribers need.

## Change Checklist

1. Update migrations and model casts/relationships.
2. Update request validation and authorization rules.
3. Review deletion and nullability behavior.
4. Update API resources and broadcast payloads.
5. Add tests for ownership, constraints, and lifecycle effects.
6. Update [ARCHITECTURE.md](ARCHITECTURE.md) and [FEATURE-BACKLOG.md](FEATURE-BACKLOG.md) when boundaries change.
