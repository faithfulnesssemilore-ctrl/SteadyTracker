# Authentication Flow

- Registration creates the user with `email_verified_at` set to `null`.
- Fortify sends the verification email and keeps the user in a temporary authenticated session.
- Unverified users may access only the verification screen and verification endpoints.
- Dashboard and activity routes require `auth:sanctum` and `verified`.
- After verification, the user can continue to the dashboard without logging in again.
- Email and password authentication is enabled now. Google login and calendar integration are deferred.
- Reverb delivers activity updates to authenticated clients. Two-factor authentication is disabled; passkeys remain available.