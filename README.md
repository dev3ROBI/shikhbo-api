# 🚀 Shikhbo API

Modern REST API for the **Shikhbo Android Application**
Built with PHP + MySQL • Deployed on Render • Database on Railway

---

## 🌐 Base URL

```
https://shikhbo-api.onrender.com/api/
```

---

## ✨ Features

* 🔐 Google Authentication (OAuth 2.0)
* 👤 User Registration & Login
* 🎁 Referral System (Auto Rewards)
* 🖼 Profile Image Handling
* 🔑 Token-based Authentication
* 🌍 Multi-language Support (EN / BN)
* ⚡ Fast & Scalable API Architecture

---

## 🧩 Tech Stack

| Layer    | Technology       |
| -------- | ---------------- |
| Backend  | PHP (Custom API) |
| Database | MySQL (Railway)  |
| Hosting  | Render           |
| Auth     | Google OAuth     |

---

## 🔐 Authentication Flow

1. Android app gets Google ID Token
2. Send token to API
3. API verifies with Google
4. User created / logged in
5. Token returned

---

## 📌 API Endpoints

### 🔑 Google Login

**POST** `/google_login.php`

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
/api
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
