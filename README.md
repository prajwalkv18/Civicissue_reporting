# 🏙️ CivicTrack — Smart Civic Issue Reporting and Resolution System

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=flat&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat&logo=javascript&logoColor=black)
![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=flat&logo=xampp&logoColor=white)

> A web-based multi-stakeholder civic complaint management platform with role-based workflows, structured assignment, and citizen-initiated two-way resolution verification.

---

## 📌 Table of Contents

- [About the Project](#about-the-project)
- [Key Features](#key-features)
- [Tech Stack](#tech-stack)
- [System Architecture](#system-architecture)
- [Database Schema](#database-schema)
- [Getting Started](#getting-started)
- [Folder Structure](#folder-structure)
- [Screenshots](#screenshots)
- [Team](#team)
- [License](#license)

---

## 📖 About the Project

CivicTrack is a full-stack web application developed as a Mini Project (24CSE48) at **New Horizon College of Engineering, Bengaluru** for the academic year 2025-26.

Urban municipalities receive thousands of civic complaints daily — potholes, garbage overflow, water leakage, broken streetlights, and drainage blockages — but most are still managed through phone calls, registers, or informal messaging groups. This leads to lost records, zero transparency, no accountability, and frequent false closures.

**CivicTrack** solves this by providing a centralized, transparent, and accountable digital platform where:
- **Residents** register complaints with evidence
- **Administrators** review, prioritize, and assign complaints
- **Engineers** resolve issues and update progress
- **Residents** verify resolution before the complaint is formally closed

---

## ✨ Key Features

- 🔐 **Role-Based Authentication** — Separate portals for Residents, Administrators, and Engineers with PHP session management and bcrypt password hashing
- 📝 **Complaint Registration** — Submit civic issues with category, location, description, photo evidence, and priority level
- 📊 **Admin Dashboard** — Filter and sort all complaints by category, status, and priority; assign to engineers with one click
- 🔧 **Engineer Portal** — View assigned complaints, update progress status, and submit completion notes
- ✅ **Two-Way Citizen Verification** — Resolved complaints are returned to residents for confirmation before closure
- 🔄 **Auto Escalation** — Complaints rejected by residents are automatically reopened with escalated priority and reassigned
- 🗄️ **Complete Audit Trail** — Every complaint stored with unique ID, timestamps, and full status history
- 📱 **Responsive Design** — Works across desktop, tablet, and mobile browsers

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, JavaScript (ES6) |
| Backend | PHP 7.4+ |
| Database | MySQL 8.0 (InnoDB) |
| Server | Apache via XAMPP |
| DB Admin | phpMyAdmin |
| Dev Tools | VS Code, Git |

---

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────┐
│            PRESENTATION LAYER               │
│         HTML5 + CSS3 + JavaScript           │
└─────────────────┬───────────────────────────┘
                  │ HTTP Requests
┌─────────────────▼───────────────────────────┐
│            APPLICATION LAYER                │
│      PHP Scripts + Session Management       │
│   (Auth, CRUD, Validation, File Upload)     │
└─────────────────┬───────────────────────────┘
                  │ MySQLi Prepared Statements
┌─────────────────▼───────────────────────────┐
│               DATA LAYER                    │
│         MySQL Database (civictrack_db)      │
│   users | complaints | assignments | feedback│
└─────────────────────────────────────────────┘
```

---

## 🗄️ Database Schema

```sql
-- Users table
CREATE TABLE users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(100) UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,  -- bcrypt hashed
    role        ENUM('resident','admin','engineer') NOT NULL,
    phone       VARCHAR(15),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Complaints table
CREATE TABLE complaints (
    complaint_id  INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    category      ENUM('pothole','garbage','water_leakage','streetlight','drainage','road_damage','other') NOT NULL,
    location      TEXT NOT NULL,
    description   TEXT NOT NULL,
    image_path    VARCHAR(255),
    priority      ENUM('low','medium','high') DEFAULT 'low',
    status        ENUM('pending','assigned','in_progress','resolved','closed','reopened') DEFAULT 'pending',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Assignments table
CREATE TABLE assignments (
    assignment_id     INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id      INT NOT NULL,
    engineer_id       INT NOT NULL,
    assigned_by       INT NOT NULL,
    assigned_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completion_notes  TEXT,
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id),
    FOREIGN KEY (engineer_id)  REFERENCES users(user_id),
    FOREIGN KEY (assigned_by)  REFERENCES users(user_id)
);

-- Feedback table
CREATE TABLE feedback (
    feedback_id   INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id  INT NOT NULL,
    user_id       INT NOT NULL,
    is_satisfied  BOOLEAN NOT NULL,
    comment       TEXT,
    submitted_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id),
    FOREIGN KEY (user_id)      REFERENCES users(user_id)
);
```

---

## 🚀 Getting Started

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP)
- A modern web browser (Chrome / Firefox)
- Git

### Installation

**1. Clone the repository**
```bash
git clone https://github.com/yourusername/civictrack.git
```

**2. Move to XAMPP htdocs**
```bash
# Windows
cp -r civictrack C:/xampp/htdocs/civictrack

# Linux/Mac
cp -r civictrack /opt/lampp/htdocs/civictrack
```

**3. Start XAMPP services**

Open the XAMPP Control Panel and start **Apache** and **MySQL**.

**4. Create the database**

- Open your browser and go to `http://localhost/phpmyadmin`
- Click **New** and create a database named `civictrack_db`
- Select `civictrack_db` and click the **Import** tab
- Upload the file `database/civictrack_db.sql` and click **Go**

**5. Configure database connection**

Open `config/db_connect.php` and update if needed:
```php
<?php
$host     = "localhost";
$username = "root";
$password = "";          // your MySQL password
$database = "civictrack_db";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
```

**6. Run the application**

Open your browser and go to:
```
http://localhost/civictrack
```

### Default Test Accounts

| Role | Email | Password |
|------|-------|----------|
| Resident | resident@test.com | Test@1234 |
| Admin | admin@test.com | Test@1234 |
| Engineer | engineer@test.com | Test@1234 |

> ⚠️ Change these credentials after first login.

---

## 📁 Folder Structure

```
civictrack/
├── config/
│   └── db_connect.php          # Database connection
├── auth/
│   ├── login.php               # Login handler
│   ├── register.php            # Registration handler
│   └── logout.php              # Session destroy
├── resident/
│   ├── dashboard.php           # Resident dashboard
│   ├── register_complaint.php  # Complaint form
│   └── verify_resolution.php   # Approve/reject resolution
├── admin/
│   ├── dashboard.php           # Admin complaint table
│   ├── assign_complaint.php    # Assign to engineer
│   └── update_priority.php     # Change priority
├── engineer/
│   ├── dashboard.php           # Engineer task list
│   └── update_status.php       # Mark in-progress/resolved
├── uploads/                    # Uploaded complaint images
├── assets/
│   ├── css/
│   │   └── style.css           # Main stylesheet
│   └── js/
│       └── validation.js       # Client-side validation
├── database/
│   └── civictrack_db.sql       # Database dump
└── index.php                   # Entry point / login page
```

---

## 📸 Screenshots

> Add screenshots of your running application here.

| Resident Dashboard | Admin Dashboard | Engineer Portal |
|:-:|:-:|:-:|
| *(screenshot)* | *(screenshot)* | *(screenshot)* |

| Complaint Form | Verification Screen | |
|:-:|:-:|:-:|
| *(screenshot)* | *(screenshot)* | |

---

## 🔒 Security Features

- Passwords hashed with **bcrypt** via `password_hash()` — never stored in plain text
- All SQL queries use **MySQLi prepared statements** — SQL injection prevention
- All user inputs sanitized with **`htmlspecialchars()`** — XSS prevention
- **Session-based role guards** at the top of every protected page
- File upload **type and size validation** before `move_uploaded_file()`

---

## 🔮 Future Enhancements

- [ ] Google Maps API integration for exact complaint location pinning
- [ ] SMS / Email notifications at each lifecycle stage
- [ ] Analytics dashboard — complaint trends, resolution time, engineer performance
- [ ] Android / iOS mobile application
- [ ] AI-based auto-categorization of complaints from description text

---

## 👨‍💻 Team

| Name | USN | Role |
|------|-----|------|
| Prajwal K V | 1NH24CS146 | Developer |
| Pranav Singh | 1NH24CS149 | Developer |

**Reviewer:** Ms. Shruthi G R, Sr. Associate Professor, Dept. of CSE, NHCE

**Institution:** New Horizon College of Engineering, Bengaluru
**Course:** 24CSE48 – Mini Project | Semester 4, Section G | AY 2025-26

---

## 📄 License

This project is submitted for academic purposes at New Horizon College of Engineering.
Feel free to use it as a reference with proper attribution.

---

<p align="center">Made with ❤️ by Prajwal K V & Pranav Singh</p>
