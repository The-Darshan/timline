<?php
session_start();
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['unsubscribe_email'])) {
        $email = $_POST['unsubscribe_email'];
        $code = generateVerificationCode();

        $_SESSION['email'] = $email;
        $_SESSION['verification_code'] = $code;
        $_SESSION['action'] = 'unsubscribe';

        if (sendVerificationEmail($email, $code, "Confirm Unsubscription", 'unsubscribe')) {
            echo "<p>Verification code sent to $email for unsubscription.</p>";
            showCodeForm();
            exit;
        } else {
            echo "<p style='color:red;'>Failed to send verification email.</p>";
        }
    } elseif (isset($_POST['unsubscribe_verification_code'])) {
        $entered = $_POST['unsubscribe_verification_code'];
        $actual = $_SESSION['verification_code'] ?? '';
        $email = $_SESSION['email'] ?? '';

        if ($entered == $actual && $email) {
            if (unsubscribeEmail($email)) {
                echo "<p style='color:green;'>You have been unsubscribed successfully!</p>";
            } else {
                echo "<p style='color:orange;'>Email not found or already unsubscribed.</p>";
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

showEmailForm();

function showEmailForm() {
    echo '
    <h2>Unsubscribe</h2>
    <form method="POST">
        <label>Email:</label>
        <input type="email" name="unsubscribe_email" required>
        <button type="submit-unsubscribe">Unsubscribe</button>
    </form>';
}

function showCodeForm() {
    echo '
    <form method="POST">
        <label>Enter 6-digit Code:</label>
        <input type="text" name="unsubscribe_verification_code" required >
        <button type="submit" id="verify-unsubscribe">Verify</button>
    </form>';
}
?>
