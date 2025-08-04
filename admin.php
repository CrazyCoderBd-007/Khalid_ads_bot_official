<?php
require 'functions.php';
// NEW: Start session to handle CSRF tokens
session_start();

// NEW: Generate CSRF token for security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$users = getAllUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Google Sans', sans-serif; }
        .popup {
            position: fixed; top: 50%; left: 50%;
            transform: translate(-50%, -50%); z-index: 1000;
            background: white; padding: 20px; border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center;
            width: 90%; max-width: 400px;
            animation: popIn 0.3s ease-out;
        }
        @keyframes popIn {
            0% { transform: translate(-50%, -50%) scale(0.8); opacity: 0; }
            100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
        }
        .overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5); z-index: 999;
        }
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border-left-color: #09f;
            animation: spin 1s ease infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Popup Modal -->
    <div id="popup-modal" class="popup hidden">
        <div id="popup-icon-container" class="flex justify-center mb-4"></div>
        <h3 id="popup-title" class="text-lg font-bold mb-2">Info</h3>
        <p id="popup-message" class="text-gray-600 mb-4">Message goes here.</p>
        <button id="close-popup" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">OK</button>
    </div>
    <div id="overlay" class="overlay hidden"></div>

    <div class="container mx-auto p-4 md:p-6">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">Admin Dashboard</h1>

        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">User Management</h2>
            <!-- NEW: Add CSRF token to your page -->
            <input type="hidden" id="csrf-token" value="<?php echo $csrfToken; ?>">
            <div class="space-y-4 md:space-y-0 md:flex md:space-x-2">
                <input type="text" id="user-id-input" class="w-full md:w-1/3 p-2 border border-gray-300 rounded-lg" placeholder="Enter User ID">
                <input type="text" id="ban-reason-input" class="w-full md:w-1/3 p-2 border border-gray-300 rounded-lg" placeholder="Reason (for banning)">
                <div class="flex space-x-2">
                    <button id="ban-btn" class="flex-1 justify-center bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 flex items-center space-x-2">
                        <i data-lucide="user-x" class="w-5 h-5"></i>
                        <span>Ban</span>
                    </button>
                    <button id="unban-btn" class="flex-1 justify-center bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center space-x-2">
                         <i data-lucide="user-check" class="w-5 h-5"></i>
                        <span>Unban</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User ID</th>
                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody id="user-table-body">
                        <?php if (is_array($users) && !empty($users)): foreach ($users as $user): ?>
                        <!-- FIXED: All outputs are now sanitized with htmlspecialchars -->
                        <tr id="user-row-<?php echo htmlspecialchars($user['user_id']); ?>">
                            <td class="py-4 px-4 border-b border-gray-200 text-sm"><?php echo htmlspecialchars($user['user_id']); ?></td>
                            <td class="py-4 px-4 border-b border-gray-200 text-sm"><?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])); ?></td>
                            <td class="py-4 px-4 border-b border-gray-200 text-sm">$<?php echo htmlspecialchars(number_format($user['balance'], 2)); ?></td>
                            <td class="py-4 px-4 border-b border-gray-200 text-sm status-cell">
                                <?php if ($user['is_banned']): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Banned: <?php echo htmlspecialchars($user['ban_reason']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Active
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="4" class="py-4 px-4 text-center text-gray-500">No users found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        const popupModal = document.getElementById('popup-modal');
        const overlay = document.getElementById('overlay');
        const popupIconContainer = document.getElementById('popup-icon-container');
        const popupTitle = document.getElementById('popup-title');
        const popupMessage = document.getElementById('popup-message');

        const successIcon = `<div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center"><i data-lucide="check" class="w-8 h-8 text-green-600"></i></div>`;
        const errorIcon = `<div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center"><i data-lucide="x" class="w-8 h-8 text-red-600"></i></div>`;
        const infoIcon = `<div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center"><i data-lucide="info" class="w-8 h-8 text-blue-600"></i></div>`;

        function showPopup(title, message, type = 'info') {
            popupTitle.textContent = title;
            popupMessage.textContent = message;
            
            if (type === 'success') popupIconContainer.innerHTML = successIcon;
            else if (type === 'error') popupIconContainer.innerHTML = errorIcon;
            else popupIconContainer.innerHTML = infoIcon;
            
            lucide.createIcons();
            popupModal.classList.remove('hidden');
            overlay.classList.remove('hidden');
        }

        document.getElementById('close-popup').addEventListener('click', () => {
            popupModal.classList.add('hidden');
            overlay.classList.add('hidden');
        });

        const userIdInput = document.getElementById('user-id-input');
        const reasonInput = document.getElementById('ban-reason-input');
        const banBtn = document.getElementById('ban-btn');
        const unbanBtn = document.getElementById('unban-btn');
        // FIXED: Get CSRF token from the hidden input
        const csrfToken = document.getElementById('csrf-token').value;
        
        async function handleUserAction(endpoint, body) {
            banBtn.disabled = true;
            unbanBtn.disabled = true;
            const originalBanBtnHtml = banBtn.innerHTML;
            const originalUnbanBtnHtml = unbanBtn.innerHTML;
            banBtn.innerHTML = `<div class="spinner mx-auto"></div>`;
            unbanBtn.innerHTML = `...`;
            
            // FIXED: Add CSRF token to the request body
            body.csrf_token = csrfToken;

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(body).toString()
                });

                const data = await response.json();

                if (!response.ok) {
                    showPopup('Error', data.message || 'An unexpected error occurred.', 'error');
                } else {
                    showPopup('Success', data.message, 'success');
                    updateTableRow(body.user_id, endpoint.includes('ban_user.php'));
                }
            } catch (error) {
                console.error('Fetch Error:', error);
                showPopup('Network Error', 'Could not connect to the server.', 'error');
            } finally {
                banBtn.disabled = false;
                unbanBtn.disabled = false;
                banBtn.innerHTML = originalBanBtnHtml;
                unbanBtn.innerHTML = originalUnbanBtnHtml;
                lucide.createIcons();
            }
        }

        banBtn.addEventListener('click', () => {
            const userId = userIdInput.value.trim();
            const reason = reasonInput.value.trim();

            if (!userId) {
                showPopup('Info', 'Please enter a User ID.', 'info');
                return;
            }
            if (!reason) {
                showPopup('Info', 'A reason is required to ban a user.', 'info');
                return;
            }

            handleUserAction('ban_user.php', { user_id: userId, reason: reason });
        });

        unbanBtn.addEventListener('click', () => {
            const userId = userIdInput.value.trim();

            if (!userId) {
                showPopup('Info', 'Please enter a User ID.', 'info');
                return;
            }

            handleUserAction('unban_user.php', { user_id: userId });
        });

        function updateTableRow(userId, isBanned) {
            const row = document.getElementById(`user-row-${userId}`);
            if (!row) return;

            const statusCell = row.querySelector('.status-cell');
            const reason = reasonInput.value.trim();
            let newStatusHtml;

            if (isBanned) {
                newStatusHtml = `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                Banned: ${reason.replace(/</g, "<").replace(/>/g, ">")}
                               </span>`;
            } else {
                newStatusHtml = `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Active
                               </span>`;
            }
            statusCell.innerHTML = newStatusHtml;
        }
    </script>
</body>
</html>```

---
### `ban_user.php`
*Rewritten to include CSRF validation and proper HTTP responses.*

```php
<?php
header('Content-Type: application/json');
require 'functions.php';
session_start(); // NEW: Start session to access CSRF token

// --- 1. Check Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// --- 2. CSRF Token Validation ---
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token. Request denied.']);
    exit;
}

// --- 3. Validate Input ---
if (empty(trim($_POST['user_id'])) || empty(trim($_POST['reason']))) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields: user_id and reason.']);
    exit;
}

// --- 4. Process Data ---
$userId = trim($_POST['user_id']);
$reason = trim($_POST['reason']);

if (banUser($userId, $reason)) {
    echo json_encode(['status' => 'success', 'message' => 'User ' . htmlspecialchars($userId) . ' has been banned.']);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Failed to ban the user. The user may not exist or an internal error occurred.']);
}
?>
