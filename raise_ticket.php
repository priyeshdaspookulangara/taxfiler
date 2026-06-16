<?php
// raise_ticket.php
require_once 'includes/functions.php';
require_once 'includes/auth.php'; // For CSRF

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid request security token";
    } else {
        $name = sanitizeInput($_POST['customer_name'] ?? '');
        $email = filter_var($_POST['customer_email'] ?? '', FILTER_VALIDATE_EMAIL);
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');

        if (!$name || !$email || !$title || !$description) {
            $error = "All fields are required and email must be valid.";
        } else {
            try {
                $db = getDbConnection();
                $stmt = $db->prepare("INSERT INTO tasks (title, description, customer_name, customer_email, status) VALUES (?, ?, ?, ?, 'NEW')");
                $stmt->execute([$title, $description, $name, $email]);
                $success = "Your request has been submitted successfully. A staff member will be assigned shortly.";
            } catch (Exception $e) {
                $error = "There was an error submitting your request. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raise a Ticket - Tax Firm</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <h2>Submit Service Request</h2>
        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
            <p><a href="raise_ticket.php">Submit another request</a></p>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="form-group">
                    <label for="customer_name">Your Name</label>
                    <input type="text" name="customer_name" id="customer_name" required>
                </div>
                <div class="form-group">
                    <label for="customer_email">Your Email</label>
                    <input type="email" name="customer_email" id="customer_email" required>
                </div>
                <div class="form-group">
                    <label for="title">Subject / Service Type</label>
                    <input type="text" name="title" id="title" required placeholder="e.g. 2023 Tax Return">
                </div>
                <div class="form-group">
                    <label for="description">Details</label>
                    <textarea name="description" id="description" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn">Submit Request</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
