# 🚀 Shikhbo API + Admin Panel

Modern REST API for the **Shikhbo Android Application**  
plus a secure **Web Admin Dashboard** for managing exams, students, and results.

Built with PHP + MySQL • Deployed on Render • Database on Railway

---

## 🌐 URLs

| Service      | URL                                     |
| ------------ | --------------------------------------- |
| **API**      | `https://shikhbo-api.onrender.com/api/`   |
| **Admin**    | `https://shikhbo-api.onrender.com/`      |

---

## ✨ Features

### 📱 Mobile API
- 🔐 Google Authentication (OAuth 2.0)
- 👤 User Registration & Login
- 🎁 Referral System (Auto Rewards)
- 🖼 Profile Image Handling
- 🔑 Token-based Authentication
- 🌍 Multi-language Support (EN / BN)
- 📝 Exam Attempt & Result Submission
- 📚 Question Bank Management

### 🖥 Admin Panel (Web)
- 📊 Dashboard with real-time stats & live clock
- 👥 Student Management (search, view, suspend, delete)
- 📝 Exam & Question Management
- 📚 Category Management (multi-level hierarchy)
- 📈 Result Analytics with progress bars
- 👤 Admin Management (add/remove admins)
- 📱 App Control (maintenance mode, force update)
- 🖥 Database Console (terminal-style interface)
- 🔒 High-security session handling
- 🛡 CSRF Protection, Rate Limiting, SQL Injection Prevention
- 🌙 Dark Mode Support
- 📱 Fully responsive (Tailwind CSS)

---

## 🧩 Tech Stack

| Layer     | Technology                     |
| --------- | ------------------------------ |
| Backend   | PHP (Custom API + Web Router)   |
| Database  | MySQL (Railway)               |
| Hosting   | Render                        |
| Auth      | Google OAuth / Token / Session |
| Frontend  | Tailwind CSS + Font Awesome  |

---

## 🔐 Authentication Flow

### Mobile (Google)
1. Android app gets Google ID Token
2. Send token to API
3. API verifies with Google
4. User created / logged in
5. Token returned

### Mobile (Email/Password)
1. User submits email & password
2. API validates credentials
3. Token generated if valid
4. Return token for API access

### Admin Panel
1. Navigate to `/` (redirects to login)
2. Enter email & password
3. Session created with strict security (HttpOnly, Secure, SameSite)
4. Role verified – only `admin` role can access

---

## 📌 API Endpoints

### 🔑 Google Login

**POST** `/api/google_login.php`

```json
{
  "google_token": "GOOGLE_ID_TOKEN",
  "email": "user@gmail.com",
  "name": "User Name",
  "device_info": {
    "device_id": "unique_id",
    "device_model": "Android Device",
    "os_version": "11",
    "app_version": "1.0"
  }
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Login successful",
  "user_id": 1,
  "token": "generated_token",
  "email": "user@gmail.com",
  "name": "User Name",
  "referral_code": "REF12345",
  "profile_image": "",
  "login_method": "google"
}
```

---

### 📝 Email Login

**POST** `/api/login.php`

```json
{
  "email": "user@example.com",
  "password": "securepassword"
}
```

---

### 📝 Registration

**POST** `/api/signup.php`

```json
{
  "name": "User Name",
  "email": "user@example.com",
  "password": "securepassword",
  "referral_code": "REF12345" 
}
```

---

### 📤 Profile Update

**POST** `/api/update_profile.php`

```json
{
  "token": "user_token",
  "name": "New Name"
}
```

---

### 🎓 Exam Categories

**GET** `/api/get_categories.php?parent_id=1`

**Response:**
```json
{
  "status": "success",
  "categories": [
    {
      "id": 1,
      "name": "Academic",
      "slug": "academic",
      "slug": "academic",
      "level": 1,
      "category_type": "academic",
      "icon": "fa-graduation-cap",
      "is_active": 1
    }
  ]
}
```

---

### 📋 Get Exams by Category

**GET** `/api/get_exams_by_category.php?category_id=1&direct=1`

**Response:**
```json
{
  "status": "success",
  "exams": [
    {
      "id": 1,
      "title": "English for Beginners",
      "duration_minutes": 30,
      "total_marks": 100,
      "passing_percentage": 40,
      "is_free": 1,
      "status": "active"
    }
  ]
}
```

---

### ❓ Get Exam Questions

**GET** `/api/get_exam_questions.php?exam_id=1&page=1&per_page=25`

**Response:**
```json
{
  "status": "success",
  "questions": [
    {
      "id": 1,
      "question_text": "What is the capital of Bangladesh?",
      "option_a": "Dhaka",
      "option_b": "Chittagong",
      "option_c": "Sylhet",
      "option_d": "Khulna",
      "correct_answer": "a",
      "marks": 1
    }
  ]
}
```

---

### 📤 Submit Exam

**POST** `/api/submit_exam.php`

```json
{
  "exam_id": 1,
  "user_id": 1,
  "answers": [
    {"question_id": 1, "selected_option": "a"},
    {"question_id": 2, "selected_option": "b"}
  ]
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Exam submitted successfully",
  "score": 15,
  "total_marks": 20,
  "percentage": 75,
  "exam_status": "PASSED"
}
```

---

### ⚙️ App Settings

**GET** `/api/get_app_settings.php`

**Response:**
```json
{
  "status": "success",
  "settings": {
    "app_notice": "Welcome to Shikhbo!",
    "maintenance_mode": "off",
    "latest_version": "1.0.0",
    "force_update": "0"
  }
}
```

---

## 🖥 Admin Pages

| Page              | URL                           | Description                      |
| ---------------- | ---------------------------- | -------------------------------- |
| Dashboard        | `?page=dashboard`            | Stats, quick actions, live clock    |
| Categories       | `?page=categories`          | Multi-level category tree        |
| Exams           | `?page=exams`               | Exam management                 |
| Questions       | `?page=questions`           | Question bank with filters       |
| Exam Attempt    | `?page=exam_attempt`         | Browse & attempt exams          |
| Students        | `?page=students`             | Student management              |
| Results         | `?page=results`             | Exam results with analytics    |
| Admins          | `?page=admins`             | Admin management               |
| App Control     | `?page=app_control`        | Maintenance, force update    |
| Database       | `?page=database`           | Terminal-style console        |
| Settings       | `?page=settings`           | Admin profile settings        |

---

## 🗄 Database Setup

Run database console in Admin Panel or visit:

```
/api/setup_database.php
```

✔ Creates all required tables  
✔ Auto-adds missing columns (migration)  
✔ Seeds default categories  
✔ Creates default admin account  

**Default Admin:**
- Email: `admin@shikhbo.com`
- Password: `Admin@123#Secure` (change immediately!)

---

## 🧪 Test API

```
/api/connection.php?test=1
```

---

## 📁 Project Structure

```
/
├── index.php                    # Main admin router (requires auth)
├── pages/
│   ├── admin_login.php      # Admin login page
│   ├── dashboard.php      # Dashboard with stats & live clock
│   ├── categories.php   # Category management (tree)
│   ├── exams.php       # Exam management
│   ├── questions.php   # Question bank
│   ├── exam_attempt.php # Exam browser & attempt
│   ├── students.php   # Student management
│   ├── results.php   # Exam results
│   ├── admins.php    # Admin management
│   ├── app_control.php # App settings & maintenance
│   ├── database.php # Database console
│   ├── settings.php # Admin profile
│   └── logout.php  # Destroy session
├── includes/
│   ├── auth.php    # Authentication & role checks
│   └── security.php # CSRF, rate limiting, headers
├── css/
│   └── custom.css  # Custom styles & animations
├── js/
│   └── custom.js  # Sidebar, dropdowns, timer, toasts
├── api/
│   ├── config.php
│   ├── connection.php
│   ├── login.php
│   ├── logout.php
│   ├── signup.php
│   ├── google_login.php
│   ├── update_profile.php
│   ├── get_categories.php
│   ├── get_exams_by_category.php
│   ├── get_exam_questions.php
│   ├── submit_exam.php
│   ├── get_app_settings.php
│   └── setup_database.php
└── database/
    └── config.php  # Database credentials
```

---

## 🔒 Security Notes

- Tokens expire after 30 days
- Google token verified via official endpoint
- Prepared statements used to prevent SQL injection
- HttpOnly, Secure, SameSite cookies
- CSRF tokens on all forms
- Rate limiting on login attempts
- Sensitive data should be moved to `.env` (recommended)

---

## ⚡ Deployment

### Render
- Auto deploy from GitHub
- Build command: `php artisan serve` (not needed for PHP)

### Railway
- MySQL Database
- TCP Proxy connection
- Get credentials from Railway dashboard

---

## 🧠 Future Improvements

- JWT Authentication
- Rate Limiting
- Email Verification
- API Versioning
- Push Notifications
- Analytics Dashboard

---

## 👨‍💻 Developer

**Robiul Islam**  
Android & Backend Developer  

---

## 📜 License

This project is licensed for personal & educational use.

---

## 💬 Support

If you face any issue, open an issue or contact the developer.

---

🔥 Built for performance. Designed for scalability.