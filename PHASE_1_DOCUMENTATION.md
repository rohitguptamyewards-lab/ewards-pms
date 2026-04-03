# eWards PMS — Phase 1 Documentation

> **Project:** eWards Project Management System
> **Phase:** 1 (Foundation & Core Modules)
> **Status:** Complete
> **Date:** April 2026

---

## Table of Contents

1. [Why This Project Exists](#1-why-this-project-exists)
2. [What It Is](#2-what-it-is)
3. [Tech Stack](#3-tech-stack)
4. [Architecture](#4-architecture)
5. [Database Schema](#5-database-schema)
6. [Roles & Access Control](#6-roles--access-control)
7. [Feature Modules](#7-feature-modules)
8. [UI / UX Design](#8-ui--ux-design)
9. [Testing](#9-testing)
10. [API Endpoints](#10-api-endpoints)
11. [Phase 2 Roadmap](#11-phase-2-roadmap)

---

## 1. Why This Project Exists

### The Problem

eWards is a merchant loyalty and engagement platform. As the product and engineering team grew, the company faced a set of operational pain points that no off-the-shelf tool adequately solved:

| Pain Point | Impact |
|---|---|
| No single place to track who is working on what | Managers had no visibility into daily team output |
| Work hours were logged in spreadsheets | Data was inconsistent, late, or missing |
| Merchant feature requests came through Slack/email | Requests got lost, duplicated, or went untriaged for weeks |
| Developers, QA, Sales, and CTO each needed different views | Generic PM tools showed the same data to everyone |
| No audit trail | No way to know who changed what, or when |
| Feature status was buried in Jira with no business context | Sales couldn't explain to merchants what was happening |

### The Solution

Build an **internal-only, role-aware project management system** that is:

- Tailored to how eWards operates (Merchants, Features, Requests)
- Opinionated — each role sees exactly what they need, nothing more
- Fast to use — daily work logging should take under 2 minutes
- Branded as eWards — consistent with the myewards.com visual identity

### Business Goals

1. **Accountability** — Every developer logs work daily. Managers see it in real time.
2. **Visibility** — CTO/CEO dashboard gives a single-pane view of team health.
3. **Merchant request tracking** — Sales team submits requests; leadership triages them.
4. **Feature lifecycle** — A feature goes from backlog → in progress → released, fully tracked.
5. **No tool fatigue** — One system, not five.

---

## 2. What It Is

**eWards PMS** is a full-stack web application that provides:

- **Role-based dashboards** — The same login renders a completely different view for a CTO vs a Developer vs a Sales rep.
- **Project & task management** — Projects with members, priorities, deadlines, and tasks with P0–P3 priority levels.
- **Daily work log system** — Team members log hours, notes, status (done/in-progress/blocked), and blockers.
- **Merchant request management** — Requests are submitted with type (bug/feature/improvement) and urgency, then triaged (accept/defer/reject) by leadership.
- **Feature pipeline** — Tracks features from backlog through release, linked to requests from merchants.
- **Team member profiles** — Each person has a profile page with their projects, tasks, work logs, and stats.
- **Reports** — Three report types: Work Log Report, Project Report, Individual Report.
- **Documents & Links** — Files and external links can be attached to any project.
- **Comments** — Threaded comments with @mention support on tasks and requests.
- **Audit log** — Every create/update/delete is recorded automatically.

---

## 3. Tech Stack

### Backend

| Layer | Technology | Version | Purpose |
|---|---|---|---|
| Language | PHP | ^8.2 | Core language with enums, readonly properties, match expressions |
| Framework | Laravel | ^12.0 | HTTP routing, ORM, queues, migrations, auth |
| SPA Bridge | Inertia.js (Laravel) | ^3.0 | Renders Vue pages server-side without a separate API |
| Auth | Laravel Session Auth | built-in | Cookie-based authentication with remember_token |
| API Auth | Laravel Sanctum | ^4.3 | Token auth for the REST API endpoints |
| Real-time | Pusher PHP Server | ^7.2 | WebSocket event broadcasting (Phase 2 ready) |
| Error Tracking | Sentry | ^4.24 | Exception monitoring |
| Route Helpers | Ziggy | ^2.6 | Generates typed route helpers for the frontend |
| Testing | PHPUnit | ^11.5 | Feature and unit tests |
| Code Style | Laravel Pint | ^1.24 | PSR-12 code formatting |
| ORM | Eloquent | built-in | Active Record with soft deletes, relationships, casts |
| Database | MySQL / SQLite | — | SQLite in testing, MySQL in production |

### Frontend

| Layer | Technology | Version | Purpose |
|---|---|---|---|
| Framework | Vue.js | ^3.5 | Reactive component-based UI |
| SPA Router | Inertia.js (Vue 3) | ^3.0 | Page transitions without full reloads |
| CSS Framework | Tailwind CSS | ^4.2 | Utility-first styling with custom eWards tokens |
| Bundler | Vite | ^7.0 | Ultra-fast HMR and production builds |
| Vite Plugin | @vitejs/plugin-vue | ^6.0 | Vue SFC compilation |
| Tailwind Plugin | @tailwindcss/vite | ^4.0 | Tailwind v4 via Vite (no config file needed) |
| Forms Plugin | @tailwindcss/forms | ^0.5 | Form reset layer |
| HTTP Client | Axios | ^1.11 | API requests (comments, documents, uploads) |
| Real-time | Laravel Echo + Pusher JS | ^2.3 / ^8.5 | WebSocket subscriptions (Phase 2 ready) |

### Infrastructure & Tooling

| Tool | Purpose |
|---|---|
| XAMPP (PHP 8.2) | Local development server (Windows) |
| Composer | PHP dependency management |
| npm | Node dependency management |
| Laravel Pail | Real-time log viewer in terminal |
| Laravel Sail | Docker wrapper for production-like dev (optional) |
| Git | Version control |

---

## 4. Architecture

### Pattern: Repository + Service + Controller

```
HTTP Request
    ↓
Controller         → validates input, calls service, returns Inertia response
    ↓
Service Layer      → business logic, orchestrates multiple repositories
    ↓
Repository Layer   → raw database queries (DB::table / Eloquent)
    ↓
Database (MySQL)
```

This separation means:
- **Controllers** are thin — they only handle HTTP concerns.
- **Services** contain all business rules — e.g., "a blocked work log must have a blocker reason".
- **Repositories** contain all queries — easy to swap or test in isolation.

### Inertia.js Hybrid Architecture

There is no traditional REST API consumed by the frontend. Instead:

- Laravel routes return `Inertia::render('PageName', $data)`.
- Vue pages receive their data as **props** — no separate fetch required.
- Form submissions use Inertia's `useForm()` and `form.post()` — these hit Laravel routes directly.
- This gives a true SPA feel (no full page reloads) while keeping routing and auth in Laravel.

### Audit Trail (Auditable Trait)

Every model that uses `HasFactory` + `Auditable` trait automatically logs:
- `event` — created / updated / deleted
- `auditable_type` / `auditable_id` — polymorphic reference
- `old_values` / `new_values` — JSON diff of changed fields
- `user_id` — who made the change

### Soft Deletes

All core models use `SoftDeletes`. Records are never physically removed — they receive a `deleted_at` timestamp and are filtered from queries automatically. This preserves referential integrity and supports audit history.

---

## 5. Database Schema

### Tables Overview

| Table | Description |
|---|---|
| `team_members` | All system users (replaces the default `users` table) |
| `projects` | Projects with owner, status, priority, dates |
| `project_members` | Many-to-many: which team members belong to which project |
| `tasks` | Tasks within projects, assigned to team members |
| `work_logs` | Daily time entries by team members |
| `features` | Product features in the pipeline |
| `modules` | Feature groupings / product areas |
| `requests` | Merchant feature/bug requests |
| `merchants` | Merchant records linked to requests |
| `documents` | Files and links attached to projects/tasks (polymorphic) |
| `comments` | Comments on tasks/requests (polymorphic) |
| `audit_logs` | Automatic change history for all core models |

### Key Table Details

#### `team_members`
```
id, name, email, password, role, is_active
department, joining_date
weekly_capacity (decimal), working_hours (decimal)
timezone, contractor_flag, freelancer_flag
git_username, notification_preferences (JSON)
cost_rate_id, remember_token
created_at, updated_at, deleted_at
```

#### `projects`
```
id, name, description
owner_id → team_members
status: planning | active | on_hold | completed | cancelled
priority: p0 | p1 | p2 | p3
start_date, end_date
created_at, updated_at, deleted_at
```

#### `project_members` (pivot)
```
project_id → projects
user_id → team_members
added_at
```

#### `tasks`
```
id, title, description
project_id → projects
assigned_to → team_members (nullable)
status: open | in_progress | blocked | done
priority: p0 | p1 | p2 | p3
deadline, estimated_hours
created_at, updated_at, deleted_at
```

#### `work_logs`
```
id
user_id → team_members
project_id → projects
task_id → tasks (nullable)
log_date, hours_spent
status: done | in_progress | blocked
note (text), blocker (text)
created_at, updated_at, deleted_at
```

#### `features`
```
id, title, description, type
priority: p0 | p1 | p2 | p3
module_id → modules
initiative, business_impact (text)
status: backlog | in_progress | in_review | in_qa | ready_for_release | released
deadline, estimated_hours
assigned_to → team_members (nullable)
qa_owner → team_members (nullable)
spec_version, tenant_id
created_at, updated_at, deleted_at
```

#### `requests`
```
id, title, description
type: bug | new_feature | improvement
urgency: merchant_blocked | merchant_unhappy | nice_to_have
status: received | under_review | accepted | deferred | rejected | completed
merchant_id → merchants (nullable)
requested_by → team_members (nullable)
linked_feature_id → features (nullable)
demand_count, revenue_impact, tenant_id
created_at, updated_at, deleted_at
```

#### `documents`
```
id
documentable_type (polymorphic)
documentable_id (polymorphic)
uploaded_by → team_members
type: file | link
file_name, file_path, file_size, mime_type
link_url, description
created_at, updated_at
```

#### `audit_logs`
```
id, user_id, event
auditable_type, auditable_id
old_values (JSON), new_values (JSON)
created_at
```

---

## 6. Roles & Access Control

### Role Definitions

| Role | Code | Description | Dashboard |
|---|---|---|---|
| CTO | `cto` | Technical leadership. Full access to everything. | Manager Dashboard |
| CEO | `ceo` | Business leadership. Full access to everything. | Manager Dashboard |
| Manager | `manager` | Project/team manager. Full management access. | Manager Dashboard |
| MC Team | `mc_team` | Merchant success / customer team. Management access. | Manager Dashboard |
| Developer | `developer` | Engineering. Projects, tasks, work logs. | Individual Dashboard |
| Tester | `tester` | QA. Projects, tasks, work logs. | Individual Dashboard |
| Analyst | `analyst` | Data/business analyst. Projects, tasks, work logs. | Individual Dashboard |
| Sales | `sales` | Sales team. Requests only (create, view, track). | Individual Dashboard |

### Access Matrix

| Module | CTO/CEO/Manager/MC | Developer/Tester/Analyst | Sales |
|---|---|---|---|
| Dashboard (Manager view) | Full | — | — |
| Dashboard (Individual view) | — | Full | Full |
| Projects | Create, View, Manage | View | — |
| Tasks | View All | View Assigned | — |
| Work Logs | View All | Own only | — |
| Requests | View, Triage | — | Create, View Own |
| Features | Full | View | — |
| Team Members | View All | View All | View All |
| Work Log Report | Full | — | — |
| Project Report | Full | — | — |
| Individual Report | Full (any user) | Own only | — |

### Triage Access

Only `cto`, `ceo`, `manager`, `mc_team` can triage (accept/defer/reject) incoming requests. This is enforced at both the controller level (PHP) and the UI level (Vue computed property).

---

## 7. Feature Modules

### 7.1 Authentication

**Login Page**
- Left panel: eWards brand gradient (`#1e0a45 → #361963 → #5e16bd`) with logo, tagline, and 3 feature highlights.
- Right panel: Email + password form with eWards purple focus states.
- Error display: inline under each field.
- Redirect on success: role-based dashboard.

**Session Auth**
- Laravel's built-in session authentication.
- `remember_token` support.
- Protected routes via `auth` middleware.
- Logout via POST `/logout`.

---

### 7.2 Role-Based Dashboard

#### Manager Dashboard (CTO / CEO / Manager / MC Team)

Displayed to all management roles on login. Shows:

**Stats Row (5 cards)**
- Active Projects count
- Open Tasks count
- Blocked Tasks count
- Overdue Tasks count
- Pending Triage (untriaged requests)

**Blocked Tasks Panel**
- All currently blocked tasks across all projects
- Shows task title, assignee, blocker reason, days blocked
- Quick link to each task

**Overdue Tasks Panel**
- All tasks past their deadline
- Shows task, project, assignee, due date, days overdue

**Team Workload Today**
- Table of all work log entries from today across the entire team
- Columns: User, Project, Task, Hours

**Header Actions**
- "Triage Requests" button (orange, only shown when untriaged requests exist)
- "Reports" quick link

#### Individual Dashboard (Developer / Tester / Analyst / Sales)

Displayed to individual contributors on login. Shows:

**Stats Row (4 cards)**
- Total hours logged this week
- My open tasks count
- My projects count
- Today's hours logged

**Today's Work Logs**
- Card list of everything the user logged today
- Status badge (done/in-progress/blocked), hours, note
- "Log Work" CTA if nothing logged yet

**My Tasks**
- All tasks assigned to the current user
- Filtered to active (not done)
- Priority badge, status badge, deadline

**My Projects**
- Projects the user is a member of
- Progress bar showing % complete

---

### 7.3 Projects

**Index Page**
- Card grid of all projects the user has access to
- Each card shows: name, status badge, priority badge, owner, progress bar, task count
- Create button (managers only)
- Filters: status, search

**Create Page**
- Fields: Name (required), Description, Owner, Start Date, End Date, Priority (P0–P3 with labels), Team Members (multi-select with count badge)
- Validation errors shown inline
- Cancel button → back to index

**Show Page (Project Detail)**
- Header card: name, status, priority, deadline, owner, progress bar
- Stats row: Open / In Progress / Blocked / Done task counts
- **Tabs:**
  - **Overview** — Description, dates, members list
  - **Tasks** — All project tasks with status/priority/assignee, filterable
  - **Work Logs** — All time entries on this project
  - **Documents** — Uploaded files and external links
  - **Comments** — Threaded comment thread with @mention support

---

### 7.4 Tasks

**Index Page (All Tasks)**
- Table of all tasks visible to the current user
- Columns: Title, Project, Assignee, Priority, Status, Deadline
- Filters: status, priority, project
- Clicking a task → show page

**Show Page (Task Detail)**
- Header: title, status badge, priority badge
- Meta: project link, assignee, deadline, estimated hours
- Description section
- Blocker reason (if status = blocked, shown in red)
- Document attachments
- Comment thread

---

### 7.5 Work Logs

**Index Page**
- Table of all work logs for the current user (paginated)
- Summary header: total entries + total hours
- Filters: date from, date to
- Columns: Date, Project, Task, Hours, Note, Blocker

**Create Page**
- Fields:
  - Project (required, select)
  - Task (optional, dynamic — populates based on selected project)
  - Date (required, defaults to today)
  - Hours Spent (required, min 0.25, max 24, step 0.25)
  - Status (required: Done / In Progress / Blocked)
  - Note (optional, textarea)
  - Blocker / Issue (required when status = Blocked, optional otherwise — textarea turns red when blocking)
- Dynamic validation: blocker field required only when status = blocked

---

### 7.6 Requests

Requests represent merchant-reported issues, feature asks, or improvement ideas.

**Index Page**
- Table of all requests
- Filters: status, type, urgency, search
- Columns: Title, Merchant, Type badge, Urgency badge, Status badge, Demand count, Date
- Create button (visible to: CTO, CEO, Manager, MC Team, Sales)

**Create Page**
- Fields:
  - Title (required)
  - Description (optional)
  - Merchant name (required)
  - Type + Urgency (side by side row):
    - Type: Bug | New Feature | Improvement
    - Urgency: Merchant Blocked | Merchant Unhappy | Nice to Have
  - Demand Count (defaults to 1)
- Cancel button → back to index

**Show Page**
- Header card: title, status, type badge, urgency badge
- Description
- Meta: Merchant, Requested By, Demand Count, Created Date
- Linked Feature (if triaged and linked to a feature)
- **Triage Panel** (visible to managers only, shown when status = received):
  - Accept / Defer / Reject buttons
  - Confirmation modal with optional/required reason field
  - Reason is required for Defer and Reject, optional for Accept
- Comment thread

**Request Status Flow:**
```
received → [triage] → accepted / deferred / rejected
accepted → [development] → completed
```

**Urgency Levels:**
- `merchant_blocked` — Critical, merchant cannot operate (red badge)
- `merchant_unhappy` — High, merchant is dissatisfied (orange badge)
- `nice_to_have` — Low, improvement request (gray badge)

---

### 7.7 Features

The features module tracks product features through their full development lifecycle.

**Index Page**
- Table of all features
- Columns: Title, Module, Type, Priority, Status, Deadline, Assignee
- Status values: Backlog → In Progress → In Review → In QA → Ready for Release → Released
- Filters: status, priority
- Visible to manager roles only

**Feature Data Model**
- Title, description, type
- Priority (P0–P3)
- Module grouping
- Initiative / theme
- Business impact description
- Status lifecycle (6 stages)
- Deadline, estimated hours
- Assigned developer
- QA owner
- Spec version
- Link to merchant requests (one feature can satisfy multiple requests)

---

### 7.8 Team Members

**Index Page**
- Card grid of all team members
- Each card: avatar with initial (color-coded by name), name, role badge, department
- Clicking a card → member profile

**Show Page (Member Profile)**
- **Header card:** Avatar, name, role badge, department, joining date, email, capacity info
- **Stats Row (4 cards):** Total Projects, Open Tasks, Hours This Month, Total Hours
- **Tabs:**
  - **Overview** — Contractor/freelancer flags, timezone, weekly capacity, working hours
  - **Tasks** — All tasks assigned to this member
  - **Work Logs** — All work log history for this member
  - **Projects** — All projects this member is part of

**Role Badges (color-coded)**

| Role | Color |
|---|---|
| CTO | Purple |
| CEO | eWards purple (#ece1ff / #5e16bd) |
| Manager | eWards purple |
| MC Team | Violet |
| Developer | Green |
| Tester | Yellow |
| Analyst | Orange |
| Sales | Pink |

---

### 7.9 Reports

Three report pages, all accessible only to manager roles.

#### Work Log Report (`/reports/work-logs`)

- **Summary card:** Total hours across all filtered results
- **Filters:** User, Project, Task, Date From, Date To
- **Table:** Date, User (with avatar initial), Project, Task, Hours, Note
- Allows managers to see exactly who worked on what, for how long, across any time range

#### Project Report (`/reports/projects`)

- **Summary badges:** Total Projects, Active count, Completed count, Total Effort (hours)
- **Filters:** Status, Owner
- **Table:** Each project with name, status, owner, progress bar, task counts, total effort hours

#### Individual Report (`/reports/individual`)

- **Summary card (gradient):** Total hours for selected user and period
- **Project Breakdown:** Side-by-side card showing hours per project with progress bars
- **Filters:** User, Date From, Date To
- **Log Table:** Chronological entries for the selected user

---

### 7.10 Documents & Links

Polymorphic attachment system — any project or task can have documents.

**Features:**
- **File upload** — drag-and-drop or click to select (max 10MB per file)
- **Link attachments** — paste any URL with a display name and optional description
- **Type badge** — "File" or "Link" shown next to each item
- **Download** — clicking a file fetches it via the API
- **Delete** — any attached document can be removed
- **Drag-over state** — drop zone highlights in eWards purple on hover

---

### 7.11 Comments

Polymorphic comment system — works on tasks and requests.

**Features:**
- Post comments on any task or request
- @mention support — `@name` is highlighted in a purple chip
- Comment list shows: user avatar initial, user name, timestamp, comment body
- Real-time-ready (Pusher/Echo wired up for Phase 2)

---

### 7.12 Audit Log

Every model change is automatically captured via the `Auditable` trait.

**Captured events:**
- `created` — new record with `new_values`
- `updated` — changed fields with `old_values` and `new_values`
- `deleted` — soft delete record

**Data stored per event:**
- Which user performed the action
- Which model was changed (polymorphic)
- Complete JSON diff of before/after values

Used for compliance, debugging, and history review.

---

## 8. UI / UX Design

### Design Language

The UI is built to match the **myewards.com** brand identity, using eWards brand colors extracted directly from the live site.

### Color Palette

| Token | Hex | Usage |
|---|---|---|
| Primary (eWards Purple) | `#5e16bd` | Buttons, links, active states, icons |
| Primary Dark | `#4c12a1` | Hover state for primary buttons |
| Primary Darker | `#361963` | Large headings, totals, key numbers |
| Sidebar Background | `#1e0a45` | Navigation sidebar |
| Sidebar Hover | `#2d1569` | Hovered nav items |
| Light Background | `#f5f0ff` | Card backgrounds, stats, gradients |
| Highlight Background | `#ece1ff` | Badge backgrounds, avatar chips |
| Brand Border | `#ddd0f7` | Card borders in branded sections |
| Accent Orange | `#f97316` | Secondary CTAs (Triage Requests) |
| Orange Dark | `#ea6a00` | Orange hover state |

### Layout Structure

```
┌──────────────────────────────────────────────────────────┐
│  Sidebar (#1e0a45)  │  Topbar (white, border-b)          │
│  ├ Logo + brand     │  ├ App name    ├ User avatar        │
│  ├ Nav links        ├─────────────────────────────────────┤
│  │  Active: #5e16bd │                                     │
│  │  Hover: #2d1569  │         Main Content Area           │
│  │  Inactive: white/60│       (bg-gray-50, p-6)          │
│  ├ REPORTS section  │                                     │
│  └ User footer      │                                     │
└──────────────────────────────────────────────────────────┘
```

### Component Library

All components are purpose-built Vue SFCs with consistent styling:

| Component | Purpose |
|---|---|
| `StatusBadge.vue` | Renders status as a colored pill (project/task/request) |
| `PriorityBadge.vue` | P0/P1/P2/P3 badge with severity colors |
| `ProgressBar.vue` | Animated fill bar showing % completion |
| `StatsCard.vue` | KPI card with label, large number, and optional icon |
| `CommentSection.vue` | Full comment thread with post form |
| `DocumentUpload.vue` | Drag-and-drop file + link uploader |
| `AppLayout.vue` | Shell with sidebar navigation + topbar |

### UX Principles Applied

1. **Role-filtered navigation** — The sidebar only shows links the current user can access. Managers see Reports; developers do not.
2. **Inline validation** — Form errors appear directly under each field, not as a toast or alert box.
3. **Dynamic form fields** — Work log's Task dropdown only populates after a Project is selected. Blocker field only requires input when status = Blocked.
4. **Conditional actions** — Triage buttons only render for manager roles. The "New Request" button only renders for roles that can create requests.
5. **Empty states** — Every table and list has a purpose-built empty state with an icon and helpful CTA.
6. **Paginated tables** — All list pages use Laravel pagination. Pagination controls render only when there are more than 3 links.
7. **Hover states** — Table rows highlight on hover (`bg-[#f5f0ff]/30`). Cards have border and shadow transitions.

### Typography

- Font: **Inter** (Google Fonts) — loaded via CSS `@theme` block
- Headings: `font-bold`, `text-gray-900` or `text-[#361963]` for key values
- Labels: `text-xs font-semibold uppercase tracking-wide text-gray-400`
- Body: `text-sm text-gray-600`

---

## 9. Testing

### Test Suite: 63 Tests, All Passing

Tests are written with PHPUnit using a dedicated `.env.testing` environment (SQLite in-memory database for speed).

### Test Files

| File | Coverage |
|---|---|
| `ProjectTest.php` | Create, list, show projects; owner/member access |
| `TaskTest.php` | Task CRUD, status transitions, assignment |
| `WorkLogTest.php` | Log creation, date filters, hours validation |
| `DocumentTest.php` | File upload, link creation, delete |
| `CommentTest.php` | Comment post, list, @mention rendering |
| `DashboardTest.php` | Role-based dashboard data, access control |
| `ReportTest.php` | Work log, project, individual report data |
| `TeamMemberProfileTest.php` | Profile stats, tabs, projects/tasks listing |
| `ExampleTest.php` | Health check / smoke test |

### Test Configuration

```
Database: SQLite in-memory (:memory:)
Runs migrations fresh for each test class (RefreshDatabase)
Factory-generated test data
Auth: actingAs() with role-specific users
```

---

## 10. API Endpoints

### Web Routes (Inertia)

All routes are protected by the `auth` middleware.

| Method | Path | Description |
|---|---|---|
| GET | `/` | Dashboard (role-based) |
| GET | `/projects` | Project list |
| GET | `/projects/create` | Create project form |
| POST | `/projects` | Store new project |
| GET | `/projects/{id}` | Project detail |
| GET | `/tasks` | All tasks |
| GET | `/tasks/{id}` | Task detail |
| GET | `/work-logs` | Work log list |
| GET | `/work-logs/create` | Log work form |
| POST | `/work-logs` | Store work log |
| GET | `/requests` | Request list |
| GET | `/requests/create` | Create request form |
| POST | `/requests` | Store request |
| GET | `/requests/{id}` | Request detail |
| PUT | `/requests/{id}/triage` | Triage action (accept/defer/reject) |
| GET | `/features` | Feature pipeline |
| GET | `/reports/work-logs` | Work log report |
| GET | `/reports/projects` | Project report |
| GET | `/reports/individual` | Individual report |
| GET | `/team-members` | Team directory |
| GET | `/team-members/{id}` | Member profile |
| GET | `/health` | Server health check |

### REST API Routes

| Method | Path | Description |
|---|---|---|
| POST | `/api/v1/comments` | Post a comment |
| GET | `/api/v1/comments` | List comments |
| POST | `/api/v1/documents` | Upload file or save link |
| GET | `/api/v1/documents/{id}` | Download document |
| DELETE | `/api/v1/documents/{id}` | Delete document |

---

## 11. Phase 2 Roadmap

The following features are **designed but not yet built** — the data model and architecture already support them.

### Notifications System
- In-app notification bell
- Push notifications via Pusher (already wired)
- Types: task assigned, request triaged, comment mention, deadline approaching

### Activity Log UI
- Browsable audit history per record and per user
- "Changed by Admin at 3:14pm — status: open → in_progress"

### Git Integration
- `git_username` field already on `team_members`
- Link PRs and commits to tasks/features
- Show PR status on task detail page

### Cost System
- `cost_rate_id` field already on `team_members`
- Hourly cost rates per person
- Work log hours × rate = cost per project
- Cost vs revenue ROI for CEO dashboard

### Feature Delivery & Business Impact
- Link released features to revenue/usage metrics
- CEO dashboard ROI view
- Merchant usage analytics per feature

### Request Management Enhancements
- Demand aggregation (multiple merchants requesting same thing)
- Auto-link requests to features during triage
- SLA tracking on untriaged requests

### Role Expansions
- QA / Tester dashboard — testing queue, bug tracker
- Sales dashboard — simplified request progress in "merchant-friendly" language

### Advanced Reports
- Export to CSV / Excel
- Scheduled email reports (weekly summary to managers)
- Burndown charts

### Multi-tenancy
- `tenant_id` already on features and requests
- Support multiple teams/organizations in one installation

---

## Appendix: Project File Structure

```
eWards PMS/
├── app/
│   ├── Enums/
│   │   ├── Role.php              — 8 roles enum
│   │   ├── ProjectPriority.php   — P0–P3
│   │   ├── ProjectStatus.php     — planning/active/on_hold/completed/cancelled
│   │   └── WorkLogStatus.php     — done/in_progress/blocked
│   ├── Http/
│   │   ├── Controllers/Api/V1/   — all page + API controllers
│   │   └── Requests/             — form request validators
│   ├── Models/                   — Eloquent models
│   ├── Repositories/             — database query layer
│   ├── Services/                 — business logic layer
│   └── Traits/
│       └── Auditable.php         — auto audit logging
│
├── database/
│   └── migrations/               — 15 migration files
│
├── resources/
│   ├── css/
│   │   └── app.css               — Tailwind v4 @theme with eWards tokens
│   └── js/
│       ├── Components/           — reusable Vue components
│       ├── Layouts/
│       │   └── AppLayout.vue     — sidebar + topbar shell
│       └── Pages/
│           ├── Auth/Login.vue
│           ├── Dashboard/Manager.vue
│           ├── Dashboard/Individual.vue
│           ├── Projects/
│           ├── Tasks/
│           ├── WorkLogs/
│           ├── Requests/
│           ├── Features/
│           ├── Reports/
│           └── TeamMembers/
│
├── routes/
│   └── web.php                   — all application routes
│
└── tests/
    └── Feature/                  — 8 feature test files (63 tests)
```

---

*Document generated for eWards internal use — April 2026*
