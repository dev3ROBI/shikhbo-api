# 🚀 Shikhbo API + Admin Panel

Modern REST API for the **Shikhbo Android Application**  
plus a secure **Web Admin Dashboard** for managing exams, students, and results.

Built with PHP + MySQL • Deployed on Render • Database on Railway

---

## 🌐 URLs

| Service      | URL                                     |
| ------------ | --------------------------------------- |
| **API**      | `https://shikhbo-api.onrender.com/api/` |
| **Admin**    | `https://shikhbo-api.onrender.com/`     |

---

## ✨ Features

### 📱 Mobile API
- 🔐 Google Authentication (OAuth 2.0)
- 👤 User Registration & Login
- 🎁 Referral System (Auto Rewards)
- 🖼 Profile Image Handling
- 🔑 Token-based Authentication
- 🌍 Multi-language Support (EN / BN)

### 🖥 Admin Panel (Web)
- 📊 Dashboard with real‑time stats
- 👥 Student Management (view, suspend, delete)
- 📝 Exam & Question Management
- 📈 Result Analytics with progress bars
- 👤 Admin Management (add/remove admins)
- 🔒 High‑security session handling
- 🛡 CSRF Protection, Rate Limiting, SQL Injection Prevention
- 📱 Fully responsive (Tailwind CSS)

---

## 🧩 Tech Stack

| Layer     | Technology                     |
| --------- | ------------------------------ |
| Backend   | PHP (Custom API + Web Router)  |
| Database  | MySQL (Railway)                |
| Hosting   | Render                         |
| Auth      | Google OAuth / Admin Login     |
| Frontend  | Tailwind CSS + Font Awesome    |

---

## 🔐 Authentication Flow

### Mobile (Google)
1. Android app gets Google ID Token
2. Send token to API
3. API verifies with Google
4. User created / logged in
5. Token returned

### Admin Panel
1. Navigate to `/pages/admin_login.php`
2. Enter email & password
3. Session created with strict security (HttpOnly, Secure, SameSite)
4. Role verified – only `admin` role can access

---

## 📌 API Endpoints

### 🔑 Google Login

**POST** `/api/google_login.php`

#### Request Body
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

---

#### ✅ Success Response

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

#### ❌ Error Response

```json
{
  "status": "error",
  "message": "Invalid Google token"
}
```

---

## 🗄 Database Setup

Run:

```
/setup_database.php
```

✔ Creates all required tables
✔ Auto-adds missing columns
✔ Safe to run multiple times

---

## 🧪 Test API

```
/connection.php?test=1
```

---

## 📁 Project Structure

```
/
├── index.php                  # Main admin router (requires auth)
├── pages/
│   ├── admin_login.php        # Admin login page
│   ├── dashboard.php          # Dashboard (dynamic stats)
│   ├── students.php           # Students management
│   ├── exams.php              # Exams management
│   ├── questions.php          # Question bank
│   ├── results.php            # Exam results
│   ├── admins.php             # Admin management
│   ├── settings.php           # Admin settings
│   └── logout.php             # Destroy session
├── includes/
│   ├── auth.php               # Authentication & role checks
│   └── security.php           # CSRF, rate limiting, headers
├── css/
│   └── custom.css             # Custom styles
├── js/
│   └── custom.js              # Sidebar, dropdowns, timer
└── api/
    ├── config.php
    ├── connection.php
    ├── google_login.php
    ├── setup_database.php
    └── uploads/
```

---

## 🔒 Security Notes

* Tokens expire after 30 days
* Google token verified via official endpoint
* Prepared statements used to prevent SQL injection
* Sensitive data should be moved to `.env` (recommended)

---

## ⚡ Deployment

### Render

* Auto deploy from GitHub
* Docker supported

### Railway

* MySQL Database
* TCP Proxy connection

---

## 🧠 Future Improvements

* JWT Authentication
* Rate Limiting
* Admin Panel
* Email Verification
* API Versioning

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
