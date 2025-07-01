<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$toolManager = new ToolManager($pdo);
$user = $toolManager->getCurrentUser();

// Get all available tools
$stmt = $pdo->query("SELECT * FROM tools WHERE is_active = 1");
$tools = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle tool access request
if (isset($_GET['request_access']) && isset($_GET['tool_id'])) {
    $toolId = (int)$_GET['tool_id'];
    // print_r($toolId);
    // die;
    $result = $toolManager->validateAccess($toolId);
// print_r($result);
// die;
    if ($result['success']) {
        $toolData = $toolManager->getToolData($toolId);
        $subdomain = sprintf(TOOLS_SUBDOMAIN_PATTERN, $toolId);
        $redirectUrl = "https://$subdomain/?access_token=" . $result['access_token'];
        header("Location: $redirectUrl");
        exit;
    } else {
        $error = $result['error'];
        if (isset($result['wait_time'])) {
            $error .= " (" . $result['wait_time'] . " seconds)";
        }
        $_SESSION['error'] = $error;
        header("Location: dashboard.php");
        exit;
    }
}

// Display dashboard
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Tools Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .tool-card {
            transition: transform 0.3s;
            margin-bottom: 20px;
        }

        .tool-card:hover {
            transform: translateY(-5px);
        }

        .credits-display {
            font-size: 1.2rem;
            font-weight: bold;
            color: #0d6efd;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h1>SEO Tools Dashboard</h1>
            <div>
                <span class="credits-display">Credits: <?= $user['credits'] ?></span>
                <a href="logout.php" class="btn btn-outline-danger ms-3">Logout</a>
            </div>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="row">
            <?php foreach ($tools as $tool): ?>
                <div class="col-md-4">
                    <div class="card tool-card">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($tool['name']) ?></h5>
                            <p class="card-text">Access the <?= htmlspecialchars($tool['name']) ?> tool with your credits.</p>
                            <a href="dashboard.php?request_access=1&tool_id=<?= $tool['id'] ?>" class="btn btn-primary">
                                Access Tool
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Collect browser fingerprint data
        document.addEventListener('DOMContentLoaded', function() {
            const screenRes = window.screen.width + 'x' + window.screen.height;
            const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

            // Store in localStorage to be sent with each tool request
            localStorage.setItem('screen_res', screenRes);
            localStorage.setItem('timezone', timezone);
        });

        // Collect detailed fingerprint data
        function collectFingerprint() {
            const fingerprint = {
                screen_width: window.screen.width,
                screen_height: window.screen.height,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                hardware_concurrency: navigator.hardwareConcurrency || 0,
                plugins: Array.from(navigator.plugins).map(p => p.name).join(','),
                // Additional fingerprinting data can be added here
            };

            // Send to server with important requests
            return fingerprint;
        }

        // Send fingerprint on page load
        document.addEventListener('DOMContentLoaded', function() {
            const fingerprint = collectFingerprint();

            // Store in localStorage for subsequent requests
            localStorage.setItem('fingerprint', JSON.stringify(fingerprint));

            // Send to server
            fetch('/api/store_fingerprint', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(fingerprint)
            });
        });

        // Include fingerprint in all tool requests
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            if (url.startsWith('/tools/')) {
                const fingerprint = JSON.parse(localStorage.getItem('fingerprint') || '{}');
                options.headers = options.headers || {};
                options.headers['X-Fingerprint'] = JSON.stringify(fingerprint);
            }
            return originalFetch(url, options);
        };
    </script>
</body>

</html>