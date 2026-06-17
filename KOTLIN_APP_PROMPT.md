# AI Agent Prompt: Build Kotlin Mobile App for Task Management

**Role:** You are a Senior Android Developer specializing in Kotlin, Jetpack Compose, and Retrofit.

**Objective:** Develop a native Android application (Kotlin) for shop owners and staff to manage tax-related tasks. The app will consume a REST API hosted at `http://dealmybiz.in/api`.

**Technical Requirements:**
1. **Architecture:** Use MVVM (Model-View-ViewModel) pattern with StateFlow/LiveData.
2. **Networking:** Use Retrofit2 and Moshi/Gson for API communication. Use a Bearer token in the Authorization header for all authenticated requests.
3. **UI:** Build a responsive UI using Jetpack Compose.
4. **Local Storage:** Use Room for caching tasks for offline viewing.

**API Endpoints (Base URL: http://dealmybiz.in/api):**
1. `POST /login.php`:
   - Input: `{ "username": "...", "password": "..." }`
   - Output: `{ "success": true, "token": "...", "user": { "id": 1, "username": "...", "role": "..." } }`
2. `GET /tasks.php`:
   - Headers: `Authorization: Bearer <token>`
   - Output: `{ "success": true, "tasks": [ { "id": 1, "title": "...", "status": "NEW", "customer_name": "...", "customer_email": "...", "updated_at": "..." }, ... ] }`
3. `GET /tasks.php?id=<task_id>`:
   - Output: `{ "id": 1, "title": "...", "status": "...", "comments": [ { "username": "...", "comment": "...", "created_at": "..." }, ... ] }`
4. `POST /tasks.php`:
   - Headers: `Authorization: Bearer <token>`
   - Input (Status Update): `{ "task_id": 1, "action": "update_status", "status": "PROCESSING", "comment": "Optional comment" }`
   - Input (Flag Doubt): `{ "task_id": 1, "action": "flag_doubt", "comment": "Required reason for doubt" }`
   - Output: `{ "success": true }`

**Features to Implement:**
1. **Login Screen:** Authenticate users and store the Bearer token securely (EncryptedSharedPreferences).
2. **Task Dashboard:** Display a list of assigned tasks with their status and customer name. Use status-based color coding (e.g., NEW = Blue, ON_HOLD = Red).
3. **Task Detail Screen:** Show full task description, customer info, and comment history.
4. **Action Buttons:** Provide buttons to "Start Processing", "Verify", "Complete", and "Flag Doubt".
5. **Doubt Dialog:** When "Flag Doubt" is clicked, show a dialog to enter the reason.

**Constraints:**
- Ensure high security: handle sensitive data carefully.
- Responsive design for various screen sizes.
- Proper error handling for network issues (e.g., show Snackbars for API errors).

**Deliverable:** Provide the complete Kotlin source code including `build.gradle.kts`, `AndroidManifest.xml`, and all necessary Activity/ViewModel/Repository classes.
