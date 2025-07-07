<?php
session_start();
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // SUBSCRIBE REQUEST
    if (isset($_POST['action']) && $_POST['action'] === 'subscribe' && isset($_POST['email'])) {
        $email = $_POST['email'];
        $code = generateVerificationCode();

        $_SESSION['email'] = $email;
        $_SESSION['verification_code'] = $code;
        $_SESSION['action'] = 'subscribe';

        if (sendVerificationEmail($email, $code, "Your Verification Code" , 'subscribe')) {
            echo "<p>Verification code sent to $email for subscription.</p>";
            showCodeForm();
            exit;
        } else {
            echo "<p style='color:red;'>Failed to send verification email.</p>";
        }
    }

    // VERIFICATION CODE CHECK
    elseif (isset($_POST['verification_code'])) {
        $entered = $_POST['verification_code'];
        $actual = $_SESSION['verification_code'] ?? null;
        $email = $_SESSION['email'] ?? null;
        $action = $_SESSION['action'] ?? null;

        if ($entered == $actual && $email && $action === 'subscribe') {
            if (registerEmail($email)) {
                echo "<p style='color:green;'>You have been subscribed successfully!</p>";
            } else {
                echo "<p style='color:orange;'>You are already subscribed.</p>";
            }
            session_destroy();
            exit;
        } else {
            echo "<p style='color:red;'>Invalid verification code. Please try again.</p>";
            showCodeForm();
            exit;
        }
    }
}

// SHOW INITIAL FORMS
showForms();

// ---------- FORM HELPERS ----------

function showForms() {
    echo '
    <h2>Subscribe</h2>
    <form method="POST">
        <input type="hidden" name="action" value="subscribe">
        <label>Email:</label>
        <input type="email" name="email" required>
        <button type="submit" id="submit-email">Subscribe</button>
    </form>';
}

function showCodeForm() {
    echo '
    <form method="POST">
        <label>Enter 6-digit Code:</label>
        <input type="text" name="verification_code" required maxlength="6">
        <button type="submit" id="submit-verification">Verify</button>
    </form>';
}
?>
