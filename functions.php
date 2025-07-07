<?php

/**
 * Generate a 6-digit numeric verification code.
 */
function generateVerificationCode(): string{
   return strval(rand(100000, 999999));  
}

/**
 * Send a verification code to an email.
 */
function sendVerificationEmail(string $email, string $code, string $subject, string $type = 'subscribe'): bool {

    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $baseUrl .= "://" . $_SERVER['HTTP_HOST'];
    $baseUrl .= dirname($_SERVER['PHP_SELF']); 

    $unsubscribeUrl = $baseUrl . "/unsubscribe.php";

    if ($type === 'subscribe') {
        $message = "
        <html>
        <head>
            <title>$subject</title>
        </head>
        <body>
            <p>Thank you for subscribing!</p>
            <p>Your verification code is: <strong>$code</strong></p>
            <p>To unsubscribe from future emails, click here:<br>
            <a href='$unsubscribeUrl'>
                Unsubscribe
            </a></p>
        </body>
        </html>";
    } elseif ($type === 'unsubscribe') {
        $message = "
        <html>
        <head>
            <title>$subject</title>
        </head>
        <body>
            <p>You requested to unsubscribe.</p>
            <p>Use this verification code to confirm: <strong>$code</strong></p>
            <p>If this wasn't you, ignore this email.</p>
        </body>
        </html>";
    } else {
        return false; // unknown type
    }

    // Set content-type for HTML email
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: no-reply@example.com\r\n";

    return mail($email, $subject, $message, $headers);
}

/**
 * Register an email by storing it in a file.
 */
function registerEmail(string $email): bool {
    $file = __DIR__ . '/registered_emails.txt';

    $email = trim(strtolower($email)); // Normalize

    // Check if file exists and read lines
    if (file_exists($file)) {
        $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (in_array($email, $emails)) {
            return false; // Already registered
        }
    }

    // Append email to the file
    file_put_contents($file, $email . PHP_EOL, FILE_APPEND);
    return true;
}

/**
 * Unsubscribe an email by removing it from the list.
 */
function unsubscribeEmail(string $email): bool {
  $file = __DIR__ . '/registered_emails.txt';

   $file = __DIR__ . '/registered_emails.txt';
    $email = trim(strtolower($email));

    if (!file_exists($file)) return false;

    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!in_array($email, $emails)) return false;

    $updated = array_filter($emails, fn($e) => strtolower(trim($e)) !== $email);
    file_put_contents($file, implode(PHP_EOL, $updated) . PHP_EOL);
    return true;

}

/**
 * Fetch GitHub timeline.
 */
function fetchGitHubTimeline() {
    // TODO: Implement this function
      $context = stream_context_create([
        "http" => [
            "user_agent" => "PHP script"
        ]
    ]);

    $html = @file_get_contents("https://github.com/timeline", false, $context);
    return $html ?: "<p>Unable to fetch timeline.</p>";
}

/**
 * Format GitHub timeline data. Returns a valid HTML sting.
 */
function formatGitHubData(array $data): string {
    if (empty($data)) {
        return "<p>No GitHub events found.</p>";
    }

    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $baseUrl .= "://" . $_SERVER['HTTP_HOST'];
    $baseUrl .= dirname($_SERVER['PHP_SELF']); 

    $unsubscribeUrl = $baseUrl . "/unsubscribe.php";

    $html = "<h2>GitHub Timeline Updates</h2>\n";
    $html .= "<table border=\"1\">\n";
    $html .= "  <tr><th>Event</th><th>User</th></tr>\n";

    foreach ($data as $event) {
        $type = htmlspecialchars($event['type'] ?? 'Unknown Event');
        $user = htmlspecialchars($event['actor']['login'] ?? 'Unknown User');
        $html .= " <tr><td>$type</td><td>$user</td></tr>\n";
    }

    $html .= "</table>\n";
    $html .= "<p><a href='$unsubscribeUrl' id=\"unsubscribe-button\">Unsubscribe</a></p>\n";

    return $html;
}

function sendGitHubUpdatesToSubscribers(): void {
    $file = __DIR__ . '/registered_emails.txt';

    if (!file_exists($file)) {
        echo "Email list file not found.\n";
        return;
    }

    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (empty($emails)) {
        echo "No registered emails found.\n";
        return;
    }

    // Fetch JSON data
    $json = @file_get_contents('https://api.github.com/events', false, stream_context_create([
        "http" => [
            "user_agent" => "PHP script"  // GitHub API requires User-Agent
        ]
    ]));

    if ($json === false) {
        echo "Failed to fetch GitHub data.\n";
        return;
    }

    $data = json_decode($json, true);
    if (!is_array($data)) {
        echo "Invalid JSON format.\n";
        return;
    }

    $html = formatGitHubData($data);

    foreach ($emails as $email) {
         $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: github-timeline@yourdomain.com\r\n";

        mail($email, "Latest GitHub Updates", $html, $headers);
    }

    echo "Emails sent to subscribers.\n";
}
