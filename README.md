# 🏛️ CivicLedger - Anti-Corruption Civic Platform

![Theme: Civic & Social Impact](https://img.shields.io/badge/Theme-Civic%20%26%20Social%20Impact-green?style=flat-square)
![Hackathon: TechFest 3.0](https://img.shields.io/badge/Hackathon-TechFest%203.0-blue?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat-square\&logo=php\&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat-square\&logo=mysql\&logoColor=white)

> **CivicLedger** is a civic innovation platform that empowers citizens, students, mentors, sponsors, and administrators to collaboratively solve civic problems through **transparency**, **accountability**, and **anti-corruption governance**.

**Team:** Cipher Duo
**Theme:** Civic & Social Impact

---

## 🎯 Overview

CivicLedger addresses civic challenges in Nepal by creating a transparent ecosystem where:

* Citizens report civic issues without needing an account.
* Students form teams and propose innovative solutions.
* Sponsors fund verified civic initiatives.
* Mentors validate progress and ensure accountability.
* Administrators moderate and manage platform activities.

The platform combines civic engagement, transparency, and collaborative problem-solving through a public governance framework.

---

## ✨ Key Features

### 🟢 Anti-Corruption & Transparency

| Feature                    | Description                                 |
| -------------------------- | ------------------------------------------- |
| Public Transparency Ledger | All funding activities are publicly visible |
| Escrow System              | Funds released only after verification      |
| Trust Score System         | Reputation-based accountability             |
| Proof of Progress (PoP)    | Mandatory progress updates every 14 days    |
| Mentor Validation          | Independent review and verification         |
| Public Governance Rules    | Transparent operational framework           |

---

### 🌍 Civic Empowerment

* Guest problem reporting
* Civic heatmap with location-based issues
* Community upvoting system
* Public solution browsing
* Impact tracking and reporting
* SDG-aligned civic initiatives

---

### 👥 Multi-Stakeholder Platform

#### Citizens

* Report civic issues
* Vote on community priorities
* Track issue status

#### Students

* Create teams
* Submit solutions
* Track milestones
* Collaborate through team chat

#### Sponsors

* Create civic challenges
* Fund projects
* Monitor project progress
* Access transparency reports

#### Mentors

* Review submissions
* Validate progress updates
* Guide student teams
* Resolve disputes

#### Administrators

* Manage users
* Moderate content
* Review approvals
* Maintain platform integrity

---

## 🚀 What Makes CivicLedger Different?

| Feature            | Traditional Platforms | CivicLedger               |
| ------------------ | --------------------- | ------------------------- |
| Commission         | 10–20%                | **0%**                    |
| Transparency       | Limited               | **100% Public Ledger**    |
| Reporting          | Login Required        | **Guest Reporting**       |
| Accountability     | Manual                | **Trust Score System**    |
| Funding Protection | None                  | **Escrow-Based Funding**  |
| Progress Tracking  | Optional              | **Mandatory PoP Updates** |

---

## 🏆 Governance Framework

CivicLedger operates using 11 governance principles:

1. Trust Score System
2. Proof of Progress
3. First-Look Rights
4. Dispute Resolution
5. Startup Verification
6. Intellectual Property Protection
7. Local Impact Alignment
8. Escrow Deposits
9. Mentor Validation
10. Gold Badge Recognition
11. Compliance & Ethics

---

## 🛠 Technology Stack

| Layer           | Technology                 |
| --------------- | -------------------------- |
| Backend         | PHP 7.4+                   |
| Database        | MySQL 5.7+                 |
| Frontend        | HTML5, CSS3, JavaScript    |
| Styling         | Tailwind CSS               |
| Icons           | Font Awesome               |
| Maps            | Leaflet.js                 |
| Audio           | MediaRecorder API          |
| Database Access | MySQLi Prepared Statements |

---

## 📁 Project Structure

```text
civicledger-nepal/
├── index.php
├── config.php
├── database.sql
├── dashboard.php
├── login.php
├── register.php
├── logout.php
├── profile.php
├── post_problem.php
├── problem_detail.php
├── submit_solution.php
├── public_heatmap.php
├── public_ledger.php
├── governance.php
│
├── citizen_dashboard.php
├── student_dashboard.php
├── sponsor_dashboard.php
├── mentor_dashboard.php
├── admin_dashboard.php
│
├── team_formation.php
├── team_progress.php
├── team_chat.php
├── team_leaderboard.php
├── team_public_listing.php
│
├── mentor_messages.php
├── mentor_problem_approval.php
├── mentor_solution_approval.php
│
├── sponsor_progress.php
├── create_challenge.php
├── create_sponsorship.php
├── my_sponsorships.php
│
├── chat.php
├── micro_gigs.php
├── post_gig.php
├── apply_gig.php
├── my_gigs.php
│
├── progress_update.php
├── api_*.php
│
├── assets/
├── uploads/

```

---

## 🗄 Database Overview

### Core Tables

| Table              | Purpose                       |
| ------------------ | ----------------------------- |
| users              | User accounts and roles       |
| problems           | Civic issue reports           |
| solutions          | Proposed solutions            |
| teams              | Team information              |
| team_milestones    | Team progress records         |
| mentor_assignments | Mentor allocations            |
| join_requests      | Team membership workflow      |
| chat_messages      | Platform messaging            |
| team_messages      | Team collaboration chat       |
| challenges         | Sponsor-created challenges    |
| sponsorships       | Funding records               |
| progress_updates   | Proof of Progress submissions |
| success_stories    | Community impact stories      |
| moderation_logs    | Administrative records        |

---

## ⚡ Installation

### Requirements

* PHP 7.4+
* MySQL 5.7+
* Apache/XAMPP
* PHP MySQLi Extension

---

### 1. Clone Repository

```bash
git clone https://github.com/YOUR_USERNAME/civicledger-nepal.git
cd civicledger-nepal
```

### 2. Create Database

```bash
mysql -u root -p -e "CREATE DATABASE elite4_nepal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3. Import Schema

```bash
mysql -u root -p elite4_nepal < database.sql
```

### 4. Create Upload Directories

```bash
mkdir -p uploads/profiles uploads/voice
chmod 755 uploads uploads/profiles uploads/voice
```

### 5. Configure Database

Update database credentials in:

```php
config.php
```

### 6. Run Application

```bash
php -S localhost:8000
```

Open:

```text
http://localhost:8000
```

---

## 🔑 Demo Accounts

**Default Password:** `password123`

| Role    | Email                                           |
| ------- | ----------------------------------------------- |
| Citizen | [citizen@elite4.com](mailto:citizen@elite4.com) |
| Student | [student@elite4.com](mailto:student@elite4.com) |
| Sponsor | [sponsor@elite4.com](mailto:sponsor@elite4.com) |
| Mentor  | [mentor@elite4.com](mailto:mentor@elite4.com)   |
| Admin   | [admin@elite4.com](mailto:admin@elite4.com)     |

---

## 🔄 Core Workflows

### Civic Problem Workflow

1. Citizen submits a civic issue.
2. Community upvotes the issue.
3. Moderators review and approve.
4. Student teams submit solutions.
5. Mentors validate submissions.
6. Sponsors provide funding.
7. Progress remains publicly visible.

### Team Collaboration Workflow

1. Student creates a team.
2. Members submit join requests.
3. Team leader approves members.
4. Progress milestones are tracked.
5. Mentors review updates.
6. Team chat enables collaboration.

### Sponsor Workflow

1. Sponsor creates challenge.
2. Escrow funding is deposited.
3. Teams execute projects.
4. Sponsors monitor progress.
5. Public ledger ensures transparency.

---

## 🔒 Security Features

* MySQLi prepared statements
* XSS protection through output escaping
* Session-based authentication
* Role-based access control
* Moderation workflows
* Audit and transparency records

---

## 🌱 Sustainable Development Goals (SDGs)

| SDG    | Contribution                         |
| ------ | ------------------------------------ |
| SDG 8  | Decent Work & Economic Growth        |
| SDG 11 | Sustainable Cities & Communities     |
| SDG 16 | Peace, Justice & Strong Institutions |
| SDG 17 | Partnerships for the Goals           |

---


---

## 📜 License

This project was developed for hackathon and civic innovation purposes.

---

## ❤️ Team

### Cipher Duo

Building transparent civic technology for Nepal.

> “Empowering communities through accountability, transparency, and innovation.”
