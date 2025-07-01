<?php
require_once 'config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$toolManager = new ToolManager($pdo);

// Handle credit update
if (isset($_POST['update_credits'])) {
    $userId = (int)$_POST['user_id'];
    $credits = (int)$_POST['credits'];
    
    $stmt = $pdo->prepare("UPDATE users SET credits = ? WHERE id = ?");
    $stmt->execute([$credits, $userId]);
    
    $_SESSION['message'] = "Credits updated successfully";
    header("Location: admin.php");
    exit;
}

// Handle tool management
if (isset($_POST['add_tool'])) {
    $name = $_POST['name'];
    $baseUrl = $_POST['base_url'];
    $cookieData = $_POST['cookie_data'];
    $userAgent = $_POST['user_agent'];
    
    // Validate JSON
    if (!json_decode($cookieData)) {
        $_SESSION['error'] = "Invalid JSON data for cookies";
        header("Location: admin.php");
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO tools (name, base_url, cookie_data, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $baseUrl, $cookieData, $userAgent]);
    
    $_SESSION['message'] = "Tool added successfully";
    header("Location: admin.php");
    exit;
}

// Get all users
$users = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);

// Get all tools
$tools = $pdo->query("SELECT * FROM tools")->fetchAll(PDO::FETCH_ASSOC);

// Get recent activity
$activity = $pdo->query("
    SELECT u.username, t.name as tool_name, a.* 
    FROM user_activity a
    JOIN users u ON a.user_id = u.id
    JOIN tools t ON a.tool_id = t.id
    ORDER BY a.activity_time DESC
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <h1>Admin Panel</h1>
            <a href="logout.php" class="btn btn-outline-danger">Logout</a>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="row mb-5">
            <div class="col-md-6">
                <h3>User Management</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Credits</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= $user['credits'] ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editUserModal" 
                                            data-user-id="<?= $user['id'] ?>" data-username="<?= htmlspecialchars($user['username']) ?>" 
                                            data-credits="<?= $user['credits'] ?>">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="col-md-6">
                <h3>Tool Management</h3>
                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addToolModal">
                    Add New Tool
                </button>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Subdomain</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tools as $tool): ?>
                            <tr>
                                <td><?= htmlspecialchars($tool['name']) ?></td>
                                <td><?= sprintf(TOOLS_SUBDOMAIN_PATTERN, $tool['id']) ?></td>
                                <td><?= $tool['is_active'] ? 'Active' : 'Inactive' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <h3>Recent Activity</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Tool</th>
                            <th>IP Address</th>
                            <th>Screen</th>
                            <th>Timezone</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activity as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['username']) ?></td>
                                <td><?= htmlspecialchars($log['tool_name']) ?></td>
                                <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                <td><?= htmlspecialchars($log['screen_resolution']) ?></td>
                                <td><?= htmlspecialchars($log['timezone']) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($log['activity_time'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User Credits</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="editUserId">
                        <div class="mb-3">
                            <label for="editUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="editUsername" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="editCredits" class="form-label">Credits</label>
                            <input type="number" class="form-control" id="editCredits" name="credits" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_credits" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Add Tool Modal -->
    <div class="modal fade" id="addToolModal" tabindex="-1" aria-labelledby="addToolModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addToolModalLabel">Add New Tool</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="toolName" class="form-label">Tool Name</label>
                            <input type="text" class="form-control" id="toolName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="baseUrl" class="form-label">Base URL</label>
                            <input type="url" class="form-control" id="baseUrl" name="base_url" required>
                        </div>
                        <div class="mb-3">
                            <label for="cookieData" class="form-label">Cookie Data (JSON)</label>
                            <textarea class="form-control" id="cookieData" name="cookie_data" rows="5" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="userAgent" class="form-label">User Agent</label>
                            <input type="text" class="form-control" id="userAgent" name="user_agent" required 
                                   value="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_tool" class="btn btn-primary">Add Tool</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle edit user modal
        var editUserModal = document.getElementById('editUserModal');
        editUserModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var userId = button.getAttribute('data-user-id');
            var username = button.getAttribute('data-username');
            var credits = button.getAttribute('data-credits');
            
            var modal = this;
            modal.querySelector('#editUserId').value = userId;
            modal.querySelector('#editUsername').value = username;
            modal.querySelector('#editCredits').value = credits;
        });
    </script>
</body>
</html>