<p align="center">
  <img src="public/images/logo.png" alt="Talentos Logo" width="80"/>
</p>

<h1 align="center">🚀 Talentos — Intelligent HR & Talent Management Platform</h1>

<p align="center">
  <strong>A full-stack Symfony 6.4 web application for recruitment, training, event management, and HR operations — powered by AI.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Symfony-6.4-purple?style=for-the-badge&logo=symfony" alt="Symfony"/>
  <img src="https://img.shields.io/badge/PHP-8.1-blue?style=for-the-badge&logo=php" alt="PHP"/>
  <img src="https://img.shields.io/badge/MySQL-8.0-orange?style=for-the-badge&logo=mysql" alt="MySQL"/>
  <img src="https://img.shields.io/badge/Bootstrap-5.3-purple?style=for-the-badge&logo=bootstrap" alt="Bootstrap"/>
  <img src="https://img.shields.io/badge/PHPUnit-391_tests-green?style=for-the-badge" alt="Tests"/>
  <img src="https://img.shields.io/badge/PHPStan-0_errors-brightgreen?style=for-the-badge" alt="PHPStan"/>
</p>

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Architecture](#-architecture)
- [Module Breakdown](#-module-breakdown)
- [Entity Diagram](#-entity-diagram)
- [Tech Stack](#-tech-stack)
- [AI Integration](#-ai-integration)
- [Installation](#-installation)
- [Testing](#-testing)
- [API Endpoints](#-api-endpoints)
- [Project Structure](#-project-structure)
- [Team](#-team)

---

## 🌟 Overview

**Talentos** is a comprehensive talent management platform connecting **candidates**, **recruiters (HR)**, and **administrators** in a unified ecosystem. It covers the full employee lifecycle:

```
🔐 Register → 📝 Complete Profile → 💼 Apply to Jobs → 🎥 Interview → 📊 Get Scored by AI
                                      📅 Join Events → 🎓 Take Courses → 🏆 Earn Points
```

### Key Highlights

| Feature | Description |
|---------|-------------|
| 🤖 **AI-Powered Scoring** | Groq LPU + OpenRouter race architecture for sub-2s candidate compatibility analysis |
| 🎥 **Live Video Interviews** | WebRTC-based meeting rooms with real-time code execution |
| 🎓 **Course & Quiz System** | Formations with séances, AI-generated quizzes, anti-cheat tab-switch detection |
| 💳 **Stripe Payments** | Points-based economy for premium courses |
| 🌍 **i18n** | Full French/English internationalization |
| 📱 **Responsive Design** | Modern glassmorphism UI with dark admin panel |
| 🔔 **Real-time Notifications** | Push notifications for both candidate and admin dashboards |
| 💬 **Messaging System** | Messenger-style chat widget with real-time sync |

---

## 🏗 Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        TALENTOS PLATFORM                        │
├──────────────────────┬──────────────────────────────────────────┤
│   FRONT OFFICE (FO)  │           BACK OFFICE (BO)              │
│   Light Theme        │           Dark Admin Panel               │
│                      │                                          │
│  • Home / Landing    │  • Dashboard (stats & charts)            │
│  • Job Offers List   │  • Offer Management (CRUD)               │
│  • Event Listing     │  • Application Pipeline                  │
│  • Course Catalog    │  • Interview Scheduling                  │
│  • Profile Editor    │  • Event CRUD + Presence + Feedback      │
│  • Interview Room    │  • Course/Quiz Management                │
│  • AI Chatbot        │  • Project Kanban Board                  │
│  • Support Tickets   │  • Activity Leaderboard                  │
│  • Notifications     │  • User Administration                   │
│  • Messaging         │  • Support Ticket Resolution             │
│  • Calendar View     │  • Unified Calendar                      │
└──────────────────────┴──────────────────────────────────────────┘
                              │
              ┌───────────────┼───────────────┐
              │               │               │
         ┌────▼────┐   ┌─────▼─────┐   ┌─────▼─────┐
         │ MySQL   │   │ Groq API  │   │  Stripe   │
         │ Database│   │ OpenRouter│   │  Payments │
         └─────────┘   └───────────┘   └───────────┘
```

### Role System

| Role | Access | Capabilities |
|------|--------|-------------|
| `CANDIDATE` | Front Office | Apply to jobs, join events, take courses, earn points, chat |
| `RECRUITER` / `HR` | Front + Back Office | Manage offers, schedule interviews, AI-analyze candidates |
| `ADMIN` | Full Access | User management, support tickets, all BO features |

---

## 📦 Module Breakdown

### 1. 👤 User & Profile (`User`, `Profile`, `ProfileView`)

**Controllers:** `SecurityController`, `RegistrationController`, `ProfileController`, `GoogleController`, `ForgotPasswordController`, `EmailVerificationController`

| Feature | Details |
|---------|---------|
| **Registration** | Email/password with verification token, or Google OAuth 2.0 |
| **Login** | Symfony security with failed attempts tracking + account lockout |
| **Profile** | First/last name, phone, location, birth date, professional title, years of experience, summary |
| **CV Upload** | PDF upload stored in `public/uploads/cvs/` |
| **Profile Picture** | Image upload with preview |
| **Skills** | JSON array with autocomplete from internal API (`/api/skills/search`) — 100+ tech & soft skills |
| **Profile Views** | Track who viewed your profile (LinkedIn-style) |
| **Password Reset** | Token-based reset flow with expiry |
| **Points Balance** | Gamification currency used for premium courses |

---

### 2. 💼 Offers & Applications (`Offer`, `Application`, `Bookmark`)

**Controllers:** `OfferController`, `OfferAiController`

| Feature | Details |
|---------|---------|
| **Job Listing** | Paginated, searchable by title/location/contract type, with cover images |
| **Apply** | Upload CV + motivation letter, auto-track application date |
| **Bookmarks** | Save/unsave offers for later |
| **Status Workflow** | `Nouvelle` → `En attente` → `Entretien` → `Acceptée` / `Refusée` |
| **AI Compatibility** | Groq + OpenRouter race to score candidates 0-100 based on CV + job requirements |
| **Bulk Analysis** | Recruiter can AI-analyze all applicants at once with progress bar |
| **Recruiter Response** | Accept/reject with feedback message |

#### 🤖 AI Scoring Architecture
```
Candidate CV (PDF) ──► smalot/pdfparser ──► Extract text (max 800 chars)
                                                    │
Job Offer Data ──────────────────────────────────────┤
                                                    ▼
                                    ┌──── Groq LPU (Primary) ────┐
                                    │                            │
                                    │    RACE: First to respond  │
                                    │                            │
                                    └──── OpenRouter (Fallback) ─┘
                                                    │
                                                    ▼
                                          Score: 0-100
                                          Strengths / Weaknesses
                                          Stored in Application.score
```

---

### 3. 🎥 Interviews & Meets (`Interview`, `Meet`)

**Controllers:** `InterviewController`, `AdminInterviewController`

| Feature | Details |
|---------|---------|
| **Scheduling** | Set date, location (in-person) or meeting link (online) |
| **Status Tracking** | `SCHEDULED` → `IN_PROGRESS` → `COMPLETED` / `CANCELLED` |
| **Meeting Rooms** | WebRTC-based video rooms with unique room IDs |
| **Meet Types** | `GENERAL`, `TECHNICAL`, `HR` — each with separate grading |
| **Grading** | 0-10 scale per meet session |
| **Notes** | Interviewer can save detailed notes per session |
| **Code Execution** | Live code editor in interview room for technical assessments |
| **Calendar Integration** | All interviews visible in candidate & admin calendars |

---

### 4. 🎓 Courses & Quiz (`Formation`, `Seance`, `Quiz`, `Question`, `Choix`, `QuizAttempt`, `FormationEnrollment`)

**Controllers:** `CourseController`, `CertificateController`

| Feature | Details |
|---------|---------|
| **Formation CRUD** | Title, description, level (Débutant/Intermédiaire/Avancé), duration, dates, cover image |
| **Séances** | Individual lessons — `PRESENTIEL` or `EN_LIGNE`, with geolocation, video path, duration |
| **Enrollment** | `PENDING` → `APPROVED` / `REJECTED` — free or points-based |
| **Paid Courses** | Stripe Checkout integration — buy points packages (100/250/500 pts) |
| **Quiz System** | Timed quizzes with multiple-choice questions |
| **Anti-Cheat** | Tab-switch counter — 3+ switches flags as `cheated = true` |
| **Scoring** | Automatic percentage calculation, stored in `QuizAttempt` |
| **Certificates** | PDF certificate generation upon course completion |

#### 💳 Stripe Points Flow
```
Buy Points ──► Stripe Checkout ──► Success Webhook ──► Add points to User.pointsBalance
                                                              │
Enroll in Paid Course ──► Check pointsBalance >= pricePoints ─┤
                                                              ▼
                                                    Deduct points, create enrollment
```

---

### 5. 📅 Events & Participation (`Event`, `EventParticipation`, `EventLike`, `EventComment`, `EventFeedback`)

**Controllers:** `EventController`

| Feature | Details |
|---------|---------|
| **Event Types** | `MEETUP`, `CONFERENCE`, `WORKSHOP`, `HACKATHON`, `WEBINAR` |
| **Online/Offline** | In-person (with geolocation map) or online (with meeting link) |
| **Capacity** | Max capacity enforcement (0 = unlimited) |
| **Registration** | QR code generated per participant for check-in |
| **QR Verification** | Scan QR at event entrance to confirm attendance |
| **Status** | `UPCOMING` → `ONGOING` → `COMPLETED` / `CANCELLED` |
| **Likes** | Toggle like/unlike per event |
| **Comments** | Threaded comments on event pages |
| **Feedback** | Post-event rating (1-5) + recommendation + comment |
| **Presence Tracking** | Admin marks attendance from QR scans |
| **Statistics** | Charts: participation rates, feedback averages, attendance trends |

---

### 6. 📊 Activities & Projects (`Activity`, `Project`)

**Controllers:** `ActivityController`, `DashboardController`

| Feature | Details |
|---------|---------|
| **Activity Tracking** | Employee logs daily work: description, hours worked, date |
| **Timer** | Built-in stopwatch to track work duration |
| **Report Submission** | Write activity reports with status: `PENDING` → `SUBMITTED` → `APPROVED` / `REJECTED` |
| **Late Detection** | Auto-flag late submissions with delay calculation in hours |
| **Revision Count** | Track how many times a report was revised after rejection |
| **Admin Feedback** | Managers can approve/reject with written feedback |
| **Project Management** | CRUD with status: `PLANNED` → `IN_PROGRESS` → `COMPLETED` |
| **Kanban Board** | Drag-and-drop project board (visual task management) |
| **Leaderboard** | Gamified ranking of most active employees |

---

### 7. 💬 Messaging & Social (`Sync`, `SyncMessage`)

**Controllers:** `SyncController`

| Feature | Details |
|---------|---------|
| **Real-time Chat** | Messenger-style floating widget on all pages |
| **Contact List** | Auto-populated from network circle |
| **Message Bubbles** | Styled sent/received messages with timestamps |
| **Unread Badges** | Badge counter on chat FAB button |
| **Full-screen Mode** | Expand chat to dedicated page |
| **HR Messaging** | Admin can message candidates directly |

---

### 8. 🔔 Notifications (`Notification`)

**Controllers:** `NotificationController`

| Feature | Details |
|---------|---------|
| **Real-time Bell** | Animated notification bell with unread count |
| **Panel Dropdown** | LinkedIn-style sliding notification panel |
| **Mark as Read** | Individual or bulk mark-all-as-read |
| **Auto-trigger** | Notifications for: new applications, interview scheduled, enrollment approved, etc. |
| **Dual Dashboard** | Separate notification streams for candidates and admins |

---

### 9. 🎫 Support (`SupportTicket`, `TicketReply`)

**Controllers:** `SupportController`

| Feature | Details |
|---------|---------|
| **Ticket Creation** | Category, priority (LOW/MEDIUM/HIGH/URGENT), description |
| **Status Tracking** | `OPEN` → `IN_PROGRESS` → `RESOLVED` / `CLOSED` |
| **Reply Thread** | Back-and-forth conversation between user and admin |
| **Admin Dashboard** | View all tickets, filter by status/priority |

---

### 10. 🤖 AI Chatbot

**Controllers:** `ChatbotController`

| Feature | Details |
|---------|---------|
| **Floating Widget** | AI assistant bubble on bottom-left of all pages |
| **Context-Aware** | Answers questions about the platform, job offers, profile tips |
| **Groq-Powered** | Uses Groq LPU for fast inference |
| **Rate Limited** | Configurable daily message limit |

---

## 🗃 Entity Diagram

```
User ──────┬──── Profile (1:1)
           │         └──── ProfileView (1:N)
           │
           ├──── Application (1:N) ──── Offer (N:1)
           │         └──── Interview (1:1)
           │                   └──── Meet (1:N)
           │
           ├──── Bookmark (1:N) ──── Offer (N:1)
           │
           ├──── EventParticipation (1:N) ──── Event (N:1)
           │         └──── EventFeedback (1:1)
           │
           ├──── EventLike (1:N) ──── Event (N:1)
           ├──── EventComment (1:N) ──── Event (N:1)
           │
           ├──── FormationEnrollment (1:N) ──── Formation (N:1)
           │                                       ├──── Seance (1:N)
           │                                       │        └──── Quiz (1:1)
           │                                       └──── Quiz (1:N)
           │                                                ├──── Question (1:N)
           │                                                │        └──── Choix (1:N)
           │                                                └──── QuizAttempt (1:N)
           │
           ├──── Activity (1:N) ──── Project (N:1)
           │
           ├──── SupportTicket (1:N)
           │         └──── TicketReply (1:N)
           │
           ├──── Notification (1:N)
           │
           └──── Sync (1:N)
                    └──── SyncMessage (1:N)
```

**Total: 27 mapped entities**

---

## ⚙️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Framework** | Symfony 6.4 (PHP 8.1) |
| **Database** | MySQL 8.0 + Doctrine ORM |
| **Frontend** | Twig + Bootstrap 5.3 + Bootstrap Icons |
| **Styling** | Custom CSS (glassmorphism, gradients, dark mode) |
| **AI** | Groq LPU API + OpenRouter (race pattern) |
| **Payments** | Stripe Checkout API |
| **PDF** | smalot/pdfparser (CV extraction) |
| **Auth** | Symfony Security + Google OAuth 2.0 |
| **i18n** | Symfony Translation (FR/EN) |
| **Video** | WebRTC (interview rooms) |
| **Testing** | PHPUnit 9.6 (391 tests / 617 assertions) |
| **Static Analysis** | PHPStan Level 2 (0 errors) |
| **DB Diagnostics** | Doctrine Doctor (ahmed-bhs/doctrine-doctor) |
| **Onboarding** | Driver.js guided tours |
| **Calendar** | FullCalendar.js |
| **Charts** | Chart.js |

---

## 🤖 AI Integration

### Providers

| Provider | Use Case | Model | Speed |
|----------|----------|-------|-------|
| **Groq** | Primary AI engine | llama3-70b | ~1-2s |
| **OpenRouter** | Fallback (race pattern) | google/gemini-flash | ~2-4s |

### Environment Variables
```env
GROQ_API_KEY=gsk_xxxxx
GEMINI_API_KEY=xxxxx          # Used for OpenRouter
```

### Race Architecture
Both providers are called simultaneously. The **first response wins** — ensuring sub-2 second latency for the user regardless of individual provider performance.

---

## 🛠 Installation

### Prerequisites
- PHP 8.1+
- Composer
- MySQL 8.0
- Node.js (for asset building, optional)
- Symfony CLI (recommended)

### Setup

```bash
# 1. Clone
git clone https://github.com/PI-DEV-JAVA/pi-dev-web.git
cd pi-dev-web

# 2. Install dependencies
composer install

# 3. Configure environment
cp .env .env.local
# Edit .env.local with your DATABASE_URL, API keys, etc.

# 4. Create database & run migrations
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force

# 5. Clear cache
php bin/console cache:clear

# 6. Start server
symfony server:start
# OR
php -S 127.0.0.1:8000 -t public
```

### Environment Variables

```env
DATABASE_URL="mysql://user:pass@127.0.0.1:3306/talentos?charset=utf8mb4"
GROQ_API_KEY="gsk_xxxxx"
GEMINI_API_KEY="xxxxx"
STRIPE_SECRET_KEY="sk_test_xxxxx"
STRIPE_PUBLIC_KEY="pk_test_xxxxx"
GOOGLE_CLIENT_ID="xxxxx.apps.googleusercontent.com"
GOOGLE_CLIENT_SECRET="xxxxx"
MAILER_DSN="smtp://localhost"
```

---

## 🧪 Testing

### Unit Tests (391 tests / 617 assertions)

```bash
php vendor/bin/phpunit tests/
```

| Test File | Module | Tests | Scenarios |
|-----------|--------|-------|-----------|
| `UserProfileTest` | User & Profile | 30 | Auth, roles, reset tokens, profile completeness, skills |
| `OfferApplicationTest` | Offers & Applications | 37 | Apply flow, status workflow, AI scoring, bookmarks, search |
| `ActivityProjectTest` | Activities & Projects | 22 | Timer, reports, late detection, revisions, lifecycle |
| `InterviewMeetTest` | Interviews & Meets | 27 | Scheduling, rooms, grading, code execution, calendar |
| `CourseQuizTest` | Courses & Quiz | 37 | Enrollment, scoring, anti-cheat, paid/free courses |
| `EventParticipationTest` | Events & Participation | 39 | Capacity, QR verification, likes, comments, feedback |
| `Entity/*Test` | Individual entities | 163 | All getters/setters, defaults, relations |

### Static Analysis (PHPStan)

```bash
php -d memory_limit=512M vendor/bin/phpstan analyse --memory-limit=512M
# → [OK] No errors
```

### Doctrine Validation

```bash
php bin/console doctrine:mapping:info
# → [OK] 27 entities mapped

php bin/console doctrine:schema:validate
# → Mapping: [OK] The mapping files are correct
```

### Doctrine Doctor

```bash
symfony server:start
# Open any page → Symfony Profiler Toolbar → "Doctrine Doctor" panel
# Shows: integrity issues, security warnings, configuration problems, slowest queries
```

---

## 🔌 API Endpoints

### Public (Front Office)

| Method | Route | Description |
|--------|-------|-------------|
| `GET` | `/` | Landing page |
| `GET` | `/offers` | Job listing with search & filters |
| `GET` | `/offers/{id}` | Job detail |
| `POST` | `/offers/{id}/apply` | Submit application |
| `POST` | `/offers/{id}/bookmark` | Toggle bookmark |
| `GET` | `/events` | Event listing |
| `GET` | `/events/{id}` | Event detail |
| `POST` | `/events/{id}/participate` | Register for event |
| `POST` | `/events/{id}/like` | Toggle like |
| `POST` | `/events/{id}/comment` | Post comment |
| `POST` | `/events/{id}/feedback` | Submit feedback |
| `GET` | `/courses` | Course catalog |
| `GET` | `/courses/{id}` | Course detail |
| `POST` | `/courses/{id}/apply` | Enroll in course |
| `GET` | `/courses/quiz/{id}` | Take quiz |
| `POST` | `/courses/quiz/{id}/submit` | Submit quiz answers |
| `GET` | `/interviews` | My interviews |
| `GET` | `/interviews/room/{roomId}` | Video meeting room |
| `GET` | `/activities` | My activities |
| `POST` | `/activities/{id}/report` | Submit report |
| `GET` | `/api/skills/search?q=` | Skills autocomplete |

### Admin (Back Office)

| Method | Route | Description |
|--------|-------|-------------|
| `GET` | `/admin/dashboard` | Admin stats dashboard |
| `GET/POST` | `/admin/offers/*` | Offer CRUD |
| `POST` | `/admin/offers/ai/analyze` | AI applicant analysis |
| `GET/POST` | `/admin/interviews/*` | Interview management |
| `GET/POST` | `/admin/events/*` | Event CRUD |
| `GET` | `/admin/events/presence` | Attendance tracking |
| `GET` | `/admin/events/feedback` | Feedback management |
| `GET` | `/admin/events/stats` | Event statistics |
| `GET/POST` | `/admin/courses/*` | Formation CRUD |
| `GET/POST` | `/admin/projects/*` | Project management |
| `GET` | `/admin/projects/kanban` | Kanban board |
| `GET` | `/admin/activities` | Activity monitoring |
| `GET` | `/admin/leaderboard` | Employee ranking |
| `GET` | `/admin/users` | User administration |
| `GET` | `/admin/support` | Support tickets |
| `GET` | `/admin/calendar` | Unified calendar |

---

## 📁 Project Structure

```
talentosWeb/
├── config/                    # Symfony configuration
│   ├── bundles.php            # Registered bundles (inc. DoctrineDoctor)
│   ├── packages/              # Package-specific config
│   └── routes/                # Route definitions
│
├── src/
│   ├── Controller/            # 22 controllers
│   │   ├── Admin/             # Back office controllers
│   │   ├── OfferController    # Job offers (FO)
│   │   ├── OfferAiController  # AI compatibility scoring
│   │   ├── EventController    # Events + participation
│   │   ├── CourseController   # Courses + quiz + Stripe
│   │   ├── InterviewController# Interviews + meeting rooms
│   │   ├── ActivityController # Activities + reports
│   │   ├── ProfileController  # Profile + skills API
│   │   ├── SyncController     # Messaging system
│   │   ├── ChatbotController  # AI chatbot
│   │   └── ...
│   │
│   ├── Entity/                # 27 Doctrine entities
│   ├── Repository/            # Custom query repositories
│   ├── Service/               # Business logic services
│   │   ├── StripeService      # Payment processing
│   │   ├── WorkflowEngine     # Application state machine
│   │   ├── NotificationService# Push notification dispatch
│   │   └── CourseMailer       # Email notifications
│   │
│   └── EventSubscriber/       # Symfony event listeners
│       ├── LoginSubscriber    # Post-login logic
│       └── LocaleSubscriber   # i18n locale switching
│
├── templates/
│   ├── base.html.twig         # Root layout (fonts, vars)
│   ├── front/                 # Front office templates
│   │   ├── _base.html.twig    # FO layout (navbar, footer, chat, AI bot)
│   │   ├── offers/            # Job listing & detail
│   │   ├── events/            # Event pages
│   │   ├── courses/           # Course catalog, quiz, payments
│   │   ├── account/           # Dashboard, profile, calendar
│   │   ├── meet/              # Video interview room
│   │   └── security/          # Login, register, password reset
│   │
│   └── back/                  # Back office templates
│       ├── _base.html.twig    # BO layout (sidebar, topbar)
│       ├── dashboard.html.twig
│       ├── offers/            # Offer management + AI analysis
│       ├── events/            # Event CRUD + stats
│       ├── courses/           # Formation management
│       └── ...
│
├── tests/
│   ├── Entity/                # 10 entity unit test files
│   └── Module/                # 6 module integration test files
│
├── translations/              # FR/EN translation files
├── public/                    # Web root
│   ├── uploads/               # User uploads (CVs, images)
│   └── images/                # Static assets
│
├── phpstan.neon               # Static analysis config
├── phpstan-baseline.neon      # Known issue baseline
└── phpunit.xml.dist           # Test runner config
```

---

## 🎨 Design System

### Front Office — Light Theme
- **Background:** `#ffffff` / `#f8fafc`
- **Primary:** `#4f46e5` (Indigo) with gradient to `#06b6d4` (Cyan)
- **Cards:** 16px radius, subtle shadows, hover lift animation
- **Navbar:** Glassmorphism with `backdrop-filter: blur(20px)`

### Back Office — Dark Theme
- **Background:** `#0f172a` (Slate 900)
- **Cards:** `#1e293b` (Slate 800) with border glow on hover
- **Sidebar:** Fixed 260px with icon navigation
- **Tables:** Zebra-stripe with hover highlight

### Shared Features
- **Bootstrap Icons** throughout
- **Google Fonts** (Inter)
- **Smooth transitions** on all interactive elements
- **Driver.js** guided onboarding tours
- **Responsive** breakpoints at 768px / 480px

---

## 👥 Team

**Project:** PI-DEV Web — Esprit School of Engineering  
**Academic Year:** 2025-2026  
**Branch:** `integrationFinale`  
**Repository:** [github.com/PI-DEV-JAVA/pi-dev-web](https://github.com/PI-DEV-JAVA/pi-dev-web)

---

<p align="center">
  <em>Built with ❤️ using Symfony 6.4 — Talentos Platform © 2026</em>
</p>
