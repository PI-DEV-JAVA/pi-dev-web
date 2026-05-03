# Talent & Activity Management Platform

A comprehensive, enterprise-grade Talent Management and HR Platform built with **Symfony 7**. This application serves as a central hub for recruitment, employee training, performance analytics, and internal communication.

---

## 🚀 Tech Stack & Tools

- **Framework:** Symfony 7.x (PHP 8.2+)
- **Database:** MySQL / MariaDB
- **ORM:** Doctrine
- **Templating:** Twig
- **Frontend Logic:** Vanilla JavaScript (Fetch API, DOM Events for tracking)
- **Styling:** Custom Vanilla CSS with Bootstrap utilities
- **Package Manager:** Composer

---

## 🧠 Core Concepts & Logic

The application is structured into several interconnected modules designed to handle a candidate's entire lifecycle—from applying to a job, to onboarding, training, and performance tracking.

### 1. Identity & Role Management (RBAC)
The system uses a strict Role-Based Access Control (RBAC) mechanism.
- **Entity:** `User`, `Profile`
- **Roles:**
  - `ADMIN` / `HR`: Full access to project creation, event management, and overall analytics.
  - `RECRUITER`: Manages job offers and interviews.
  - `INSTRUCTOR`: Manages training courses (Formations) and quizzes.
  - `CANDIDATE`: Standard users applying for jobs, taking courses, and submitting activity reports.

### 2. Recruitment Module
Manages the hiring pipeline.
- **Entities:** `Offer`, `Application`, `Interview`
- **Logic:** Recruiters publish `Offers`. Candidates submit `Applications` (with CVs and cover letters). Recruiters can accept or reject applications. If accepted, an `Interview` is scheduled containing a Google Meet link and a specific date.

### 3. Learning & Evaluation Module
A built-in Learning Management System (LMS).
- **Entities:** `Formation`, `Seance`, `Quiz`, `Question`, `Choix`
- **Logic:** Instructors create `Formations` (Courses) that are broken down into `Seances` (Video Lessons/Modules). To evaluate candidates, a `Quiz` can be attached to a Formation, containing multiple `Questions` with specific `Choix` (Multiple choice answers where one or more `isCorrect`).

### 4. Advanced Activity Tracking & Performance Analytics
The core engine for monitoring productivity and quality of work.
- **Entities:** `Project`, `Activity`
- **Logic:**
  - **Background Time Tracking:** When candidates work on an activity, a hidden JS timer tracks active session time (with idle detection after 30 mins) and auto-saves to the backend every 60 seconds.
  - **Late Detection:** Submissions are compared against an `expectedDeadline`. If late, the system flags the activity (`isLateSubmission`) and calculates the exact `delayInHours`.
  - **Quality Control (Revision Loop):** Admins review submitted reports. They can `Approve`, `Reject`, or `Request Revision`. If a revision is requested, the candidate must fix their work and resubmit, incrementing the `revisionCount`.
  - **Efficiency Score Leaderboard:** Instead of ranking employees purely by "hours worked" (which rewards slow workers), the system calculates an **Efficiency Score** (`Base Points + First-Time Approval Bonus - Late Penalty - Rework Penalty`). It also tracks the **FTAR (First-Time Approval Rate)** to visualize work quality.

### 5. Event Management
Allows HR to organize webinars, meetups, and job fairs.
- **Entities:** `Event`, `EventParticipation`, `EventComment`, `EventLike`, `EventFeedback`
- **Logic:** Users can browse upcoming `Events` and register (`EventParticipation`). They can interact by leaving `EventComments` or submitting post-event `EventFeedback`.

### 6. Support & Communication
Internal helpdesk and chat tools.
- **Entities:** `SupportTicket`, `TicketReply`, `Sync`, `SyncMessage`
- **Logic:** Candidates facing technical or administrative issues can open a `SupportTicket`. HR can resolve these via `TicketReply`. Real-time chat is handled via `Sync` (Conversations) and `SyncMessages`.

---

## 🛠️ API & Endpoints Overview

While the application primarily uses Server-Side Rendering (SSR) with Twig, it exposes several internal API endpoints to handle async operations without page reloads:

- **`POST /activities/{id}/timer-save`**: Receives background pings from the JS tracker to incrementally update `hoursWorked`.
- **`POST /activities/{id}/submit-report`**: Submits the user's work report and calculates late penalties.
- **`POST /activities/{id}/review`**: HR endpoint to trigger the Revision Loop (Approve, Request Revision, Reject) and save `adminFeedback`.
- **`GET /leaderboard`**: Calculates and serves the Efficiency Score matrix and ranking logic.

---

## ⚙️ Installation & Setup

1. **Clone the repository and install dependencies:**
   ```bash
   composer install
   ```

2. **Configure the Environment:**
   Copy `.env` to `.env.local` and configure your `DATABASE_URL`.
   ```env
   DATABASE_URL="mysql://root:password@127.0.0.1:3306/pi_dev"
   ```

3. **Initialize the Database:**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

4. **Populate the Database (Massive Data Seeder):**
   We have created a custom Symfony command that generates realistic data across all modules (Recruitment, Learning, Events, and Performance Tracking).
   ```bash
   php bin/console app:populate
   ```
   *Note: This command will automatically clear existing tables to prevent foreign-key constraint violations before seeding the new data.*

5. **Start the Development Server:**
   ```bash
   symfony server:start
   ```

---

## 🧪 Testing Scenarios (via app:populate)

If you ran the `app:populate` command, the following accounts are available to test the platform (Password for all is `123456A`):

- **HR Manager (Admin):** `omar.hamdi720@gmail.com`
- **Recruiter:** `recruiter@test.com`
- **Instructor:** `instructor@test.com`
- **Top Performer Candidate:** `unimeet7@gmail.com` (100% FTAR)
- **High-Rework Candidate:** `alaeddine.sahih01@gmail.com` (Requires revisions)
- **Late Submissions Candidate:** `skandernafti0@gmail.com` (Tested against deadlines)
