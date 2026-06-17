# REST API Documentation (Mobile App Integration)

This document provides technical details for the REST API endpoints used by the mobile application. All APIs are located under `http://dealmybiz.in/api/`.

## Authentication

### Login
`POST /login.php`

**Request Body:**
```json
{
  "username": "staff_username",
  "password": "staff_password"
}
```

**Response (Success):**
```json
{
  "success": true,
  "token": "a1b2c3d4e5f6...",
  "user": {
    "id": 1,
    "username": "staff_username",
    "role": "staff"
  }
}
```

**Response (Error):**
```json
{
  "error": "Invalid credentials"
}
```

---

## Task Management

All task endpoints require a `Authorization: Bearer <token>` header.

### List Assigned Tasks
`GET /tasks.php`

**Response:**
```json
{
  "success": true,
  "tasks": [
    {
      "id": 1,
      "title": "Tax Return 2023",
      "description": "...",
      "status": "PROCESSING",
      "customer_name": "John Doe",
      "customer_email": "john@example.com",
      "assigned_staff": "staff1",
      "created_at": "2026-06-16 10:15:21",
      "updated_at": "2026-06-16 10:20:38"
    }
  ]
}
```

### Get Task Details & Comments
`GET /tasks.php?id=<task_id>`

**Response:**
```json
{
  "id": 1,
  "title": "...",
  "status": "...",
  "comments": [
    {
      "id": 1,
      "username": "admin",
      "comment": "Task assigned to staff1",
      "created_at": "2026-06-16 10:30:32"
    }
  ]
}
```

### Update Task Status
`POST /tasks.php`

**Request Body (Update Status):**
```json
{
  "task_id": 1,
  "action": "update_status",
  "status": "COMPLETED",
  "comment": "All documents processed."
}
```

**Request Body (Flag Doubt):**
```json
{
  "task_id": 1,
  "action": "flag_doubt",
  "comment": "Customer provided incorrect PAN card."
}
```

**Response:**
```json
{
  "success": true
}
```

---

## Status Values
- `NEW`
- `PROCESSING`
- `ON_HOLD`
- `VERIFYING`
- `COMPLETED`

## Security Notes
- All requests must use HTTPS in production.
- Bearer tokens should be stored securely on the device.
- Ensure the `Content-Type: application/json` header is set for all POST requests.
