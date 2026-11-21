# SteadyTraker Task Manager

## Introduction
SteadyTraker is a simple and efficient task management application designed to help you organize your tasks and help in boosting consistency and productivity. it is built using laravel and Vue.js,provviding a simple interface built from the ground up to help you manage your tasks and projects.

# Features
- User Authentication and Authorization
- Task creation,editing, and deletion
- Task status and priority management levels
- Responsive design for mobile and desktop
- Notifications and reminders for tasks
- User-friendly interface with intuitive navigation
- Task history and activity tracking
- CSV import pipeline for bulk task creation

This application is designed to be simple and easy to use, allowing users to focus on their tasks and projects without unnecessary distractions. With SteadyTraker, you can easily manage your tasks and stay on top of your work. See the [Product Requirements Document](docs/PRD.md) for more details.

# Tech stack
Using  Laravel monolith this are the main technologies used in the project:
- Laravel 13.33.0
- PHP 8.3 up to 8.5.
- MySQL 8.0
- Vue.js 3.0
- Composer
- Pest for testing

# Setup Installation
To install SteadyTraker, follow these steps:
1. Clone the repository to your local machine:
   ```bash
   git clone https://github.com/faithfulnesssemilore-ctrl/SteadyTracker.git
   cd Steady.io
   ```  
2. Install the dependencies using Composer:
   ```bash
   composer install
   npm install
   ```
3. Create a copy of the `.env.example` file and rename it to `.env`. Update the database configuration in the `.env` file to match your local setup.
4. Generate an application key:
   ```bash
   php artisan key:generate
   ```
5. Run the database migrations and seed the database:
   ```bash
   php artisan migrate --seed
   ```
6. Start the development server:
   ```bash
   php artisan serve
   php artisan queue:work
   php artisan schedule:work.              ---> for those who want to use the scheduler for sending notifications and reminders
   php artisan reverb:serve.                        ---> for those who want to use the live updates feature
   ```
   
   ## Usage
   - Access the application in your web browser at `http://localhost:8000`and register a new account or log in with existing credentials.

## CSV import pipeline

Verified users can import multiple activities from the **Import** page. Download the CSV template from that page, fill in one activity per row, then upload the file. The template uses these headers:

```csv
title,description,priority,activity_status,due_at
Plan the week,Choose three priorities,medium,pending,2026-10-01 09:00:00
```

All five headers should be present. `title` is required for each activity; the other fields may be blank. Blank priority defaults to `medium`, blank status to `pending`, and blank description or due date to empty. Priority values are `low`, `medium`, and `high`; status values are `pending`, `in_progress`, and `completed`. Common title, status, and due-date header aliases are also recognized, but the downloaded template is the most reliable format.

The importer reads the header, maps columns, trims and parses each row, validates the values, checks for duplicates, and saves accepted activities to the signed-in user's account. A duplicate is identified by its normalized title and due date, including an existing activity owned by that user. Each row is handled independently, so valid rows are imported even when other rows are rejected. The results show imported and rejected counts and explain rejected rows; the rejection report can be downloaded from the results view.

The API endpoint is `POST /api/v1/csv-import` and requires authentication and verified email. The uploaded file field is named `file`.

## Contributing
We welcome contributions to SteadyTraker! If you would like to contribute, please follow these steps:
1. Fork the repository and create a new branch for your feature or bug fix.
2. Make your changes and ensure that the code follows the existing coding style and conventions and read the PRD.md document for more details at [Steady.io/docs/PRD.md]







