<?php
// ফাইলটির নাম functions.php করা হয়েছে
require 'functions.php';

// অ্যাডমিন প্যানেলে যাওয়ার জন্য চেক
if (isset($_GET['admin']) && $_GET['admin'] == '7316439041') {
    header("Location: admin.php");
    exit;
}

// ইউজার আইডি আছে কি না এবং সেটি একটি সংখ্যা কি না, তা চেক করা হচ্ছে
if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
     die("A valid User ID is required.");
}

$userId = $_GET['id'];
$user = getUserById($userId);

// যদি ইউজার موجود না থাকে, তাহলে নতুন ইউজার তৈরি করা হবে
if (!$user) {
    // এখানে টেলিগ্রাম থেকে নাম ও অন্যান্য তথ্য নেওয়া যেতে পারে, আপাতত ডিফল্ট নাম দেওয়া হলো
    $created = createUser($userId, 'Guest', 'User', '');
    if ($created) {
        $user = getUserById($userId);
    } else {
        // যদি ইউজার তৈরি করতেও সমস্যা হয়
        die("Could not create or find the user profile. Please try again.");
    }
}

// ব্যবহারকারীর তথ্যগুলোকে ভ্যারিয়েবলে রাখা হচ্ছে
$balance = isset($user['balance']) ? (float)$user['balance'] : 0.00;
$today_earnings = isset($user['today_earnings']) ? (float)$user['today_earnings'] : 0.00;
$total_earnings = isset($user['total_earnings']) ? (float)$user['total_earnings'] : 0.00;

// ব্যবহারকারী ব্যানড কি না, তা চেক করা হচ্ছে
if ($user['is_banned']) {
    // ব্যানড মেসেজ দেখানো হচ্ছে
    die('
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Account Banned</title>
        <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
        <script src="https://unpkg.com/lucide@latest"></script>
        <script src="https://cdn.tailwindcss.com"></script>
        <style> body { font-family: "Google Sans", sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f3f4f6; } </style>
    </head>
    <body>
        <div class="text-center p-6 max-w-md mx-auto">
            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="ban" class="w-10 h-10 text-red-600"></i>
            </div>
            <h2 class="text-2xl font-bold text-red-600 mb-2">Account Banned</h2>
            <p class="text-gray-600 mb-4">Your account has been suspended. Reason: ' . htmlspecialchars($user['ban_reason']) . '</p>
            <p class="text-sm text-gray-500">Please contact support if you believe this is a mistake.</p>
        </div>
        <script>lucide.createIcons();</script>
    </body>
    </html>');
}

// কুকি থেকে দেখা অ্যাড-এর হিসাব রাখা হচ্ছে
$cookieName = 'ads_completed_' . $userId;
$completedAds = isset($_COOKIE[$cookieName]) ? json_decode($_COOKIE[$cookieName], true) : ['count' => 0, 'last_completed' => null];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Watch Ad & Earn Money</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        body { font-family: 'Google Sans', sans-serif; -webkit-tap-highlight-color: transparent; }
        .nav-btn.active::before { content: ''; position: absolute; top: -10px; left: 50%; transform: translateX(-50%); width: 50px; height: 3px; background: #2563eb; border-radius: 3px; }
        .popup { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; max-width: 90%; animation: popIn 0.3s ease-out; }
        @keyframes popIn { 0% { transform: translate(-50%, -50%) scale(0.8); opacity: 0; } 100% { transform: translate(-50%, -50%) scale(1); opacity: 1; } }
        .overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999; }
        .countdown-timer { font-family: monospace; font-weight: bold; color: #2563eb; }
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

    <!-- Main App Content -->
    <div id="app-content" class="max-w-md mx-auto min-h-screen flex flex-col pb-16 bg-white relative">
        <!-- Header -->
        <header class="bg-blue-600 text-white p-4 shadow-md">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-bold">Earn Money</h1>
                <button id="user-btn" class="p-2 rounded-full">
                    <i data-lucide="user" class="w-5 h-5"></i>
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 p-4 overflow-y-auto">
            <!-- Home Page Content -->
            <div id="home-page">
                <div class="bg-white rounded-lg shadow-md p-6 mb-4">
                    <div class="flex items-center mb-4">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                            <i data-lucide="user" class="text-blue-600 w-8 h-8"></i>
                        </div>
                        <div>
                            <h2 id="user-name" class="text-lg font-semibold"><?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])); ?></h2>
                            <p id="user-id" class="text-gray-500">ID: <?php echo htmlspecialchars($user['user_id']); ?></p>
                        </div>
                    </div>

                    <div class="bg-blue-50 rounded-lg p-4 mb-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-500 text-sm">Available Balance</p>
                                <p id="user-balance" class="text-2xl font-bold">$<?php echo number_format($balance, 2); ?></p>
                            </div>
                            <button id="withdraw-btn" class="nav-btn bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center" data-page="withdraw-page">
                                <i data-lucide="banknote" class="w-4 h-4 mr-1"></i> Withdraw
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-green-50 p-3 rounded-lg">
                            <p class="text-gray-500 text-sm">Today Earnings</p>
                            <p id="today-earnings" class="text-lg font-semibold">$<?php echo number_format($today_earnings, 2); ?></p>
                        </div>
                        <div class="bg-purple-50 p-3 rounded-lg">
                            <p class="text-gray-500 text-sm">Total Earnings</p>
                            <p id="total-earnings" class="text-lg font-semibold">$<?php echo number_format($total_earnings, 2); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-4">
                    <h3 class="font-semibold mb-3">Available Ads (<span id="remaining-ads"><?php echo max(0, 3 - $completedAds['count']); ?></span>/3)</h3>
                    <div class="space-y-3" id="ads-container">
                        <!-- Ads will be loaded by JavaScript -->
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Next ads in: <span id="next-ads-time" class="countdown-timer">00:00:00</span></p>
                </div>
            </div>

            <!-- Other Pages (Hidden by default) -->
            <div id="withdraw-page" class="hidden">
                <div class="bg-white rounded-lg shadow-md p-4">
                    <h2 class="text-lg font-semibold mb-4">Withdraw Earnings</h2>
                    <div class="space-y-4">
                         <div class="border border-gray-200 rounded-lg p-4">
                            <h3 class="font-medium mb-2">Payment Methods</h3>
                            <div class="space-y-3">
                                <label class="flex items-center p-2 bg-gray-50 rounded-lg cursor-pointer">
                                    <input type="radio" name="payment-method" value="Bkash" class="mr-3" checked>
                                    <span>Bkash</span>
                                </label>
                                <label class="flex items-center p-2 bg-gray-50 rounded-lg cursor-pointer">
                                    <input type="radio" name="payment-method" value="Nagad" class="mr-3">
                                    <span>Nagad</span>
                                </label>
                                 <label class="flex items-center p-2 bg-gray-50 rounded-lg cursor-pointer">
                                    <input type="radio" name="payment-method" value="Rocket" class="mr-3">
                                    <span>Rocket</span>
                                </label>
                            </div>
                        </div>

                        <div class="border border-gray-200 rounded-lg p-4">
                            <h3 class="font-medium mb-2">Withdrawal Details</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-sm text-gray-500">Amount ($)</label>
                                    <input type="number" id="withdraw-amount" class="w-full p-2 border border-gray-300 rounded-lg mt-1" placeholder="1.00" min="1" step="0.01">
                                </div>
                                <div>
                                    <label class="text-sm text-gray-500">Account Number</label>
                                    <input type="text" id="account-details" class="w-full p-2 border border-gray-300 rounded-lg mt-1" placeholder="Enter your account number">
                                </div>
                                <button id="request-withdraw-btn" class="bg-blue-600 text-white w-full py-2 rounded-lg mt-2">
                                    Request Withdrawal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Bottom Navigation -->
        <nav class="fixed bottom-0 left-0 right-0 bg-white shadow-lg border-t border-gray-200 max-w-md mx-auto">
            <div class="flex justify-around py-2">
                <button class="nav-btn active w-full py-2 flex flex-col items-center" data-page="home-page">
                    <i data-lucide="home" class="w-5 h-5"></i><span class="text-xs mt-1">Home</span>
                </button>
                <button class="nav-btn w-full py-2 flex flex-col items-center" data-page="withdraw-page">
                    <i data-lucide="banknote" class="w-5 h-5"></i><span class="text-xs mt-1">Withdraw</span>
                </button>
            </div>
        </nav>
    </div>

    <!-- Monetag Ad Script -->
    <!-- !!! গুরুত্বপূর্ণ: নিচের YOUR_ZONE_ID পরিবর্তন করে আপনার নিজের Monetag Zone ID বসান !!! -->
    <script src='//libtl.com/sdk.js' data-zone='YOUR_ZONE_ID' data-sdk='show_YOUR_ZONE_ID'></script>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Navigation logic
        document.querySelectorAll('.nav-btn').forEach(button => {
            button.addEventListener('click', function() {
                const pageId = this.getAttribute('data-page');

                // ব্যালেন্স ১ ডলারের কম হলে উইথড্র পেজ ব্লক করা
                if (pageId === 'withdraw-page' && parseFloat(document.getElementById('user-balance').textContent.replace('$', '')) < 1) {
                    showPopup('Info', 'This page will be unlocked once you reach $1.');
                    return;
                }

                document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                document.querySelectorAll('main > div').forEach(page => page.classList.add('hidden'));
                document.getElementById(pageId).classList.remove('hidden');
            });
        });

        // Popup logic
        function showPopup(title, message, type = 'info') {
            const popup = document.getElementById('popup-modal');
            const iconContainer = document.getElementById('popup-icon-container');
            iconContainer.innerHTML = '';
            let iconHtml = '';

            if (type === 'success') {
                iconHtml = `<div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center"><i data-lucide="check" class="w-8 h-8 text-green-600"></i></div>`;
            } else if (type === 'error') {
                iconHtml = `<div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center"><i data-lucide="x" class="w-8 h-8 text-red-600"></i></div>`;
            } else {
                iconHtml = `<div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center"><i data-lucide="info" class="w-8 h-8 text-blue-600"></i></div>`;
            }
            iconContainer.innerHTML = iconHtml;
            document.getElementById('popup-title').textContent = title;
            document.getElementById('popup-message').textContent = message;
            popup.classList.remove('hidden');
            document.getElementById('overlay').classList.remove('hidden');
            lucide.createIcons();
        }

        document.getElementById('close-popup').addEventListener('click', () => {
            document.getElementById('popup-modal').classList.add('hidden');
            document.getElementById('overlay').classList.add('hidden');
        });

        // Cookie functions
        const userId = '<?php echo $userId; ?>';
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }

        function setCookie(name, value, days) {
            let expires = "";
            if (days) {
                const date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "") + expires + "; path=/";
        }

        function getCompletedAds() {
            const cookieValue = getCookie(`ads_completed_${userId}`);
            return cookieValue ? JSON.parse(cookieValue) : { count: 0, last_completed: null };
        }

        function updateCompletedAds(count, lastCompleted) {
            const data = JSON.stringify({ count: count, last_completed: lastCompleted });
            setCookie(`ads_completed_${userId}`, data, 30);
        }

        // Ad watching logic
        function watchAd(button) {
            const adContainer = button.closest('.ad-container');
            button.disabled = true;
            button.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin mx-auto"></i> Loading...';
            lucide.createIcons();

            // !!! গুরুত্বপূর্ণ: নিচের show_YOUR_ZONE_ID() ফাংশনটি আপনার নিজের Zone ID দিয়ে পরিবর্তন করুন
            show_YOUR_ZONE_ID().then(() => {
                fetch('update_balance.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `user_id=${userId}&amount=0.01`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showPopup('Success', 'You earned $0.01!', 'success');
                        
                        // সার্ভার থেকে পাওয়া সঠিক তথ্য দিয়ে UI আপডেট করা হচ্ছে
                        document.getElementById('user-balance').textContent = '$' + data.new_balance.toFixed(2);
                        document.getElementById('today-earnings').textContent = '$' + data.new_today_earnings.toFixed(2);
                        document.getElementById('total-earnings').textContent = '$' + data.new_total_earnings.toFixed(2);

                        const completed = getCompletedAds();
                        const newCount = completed.count + 1;
                        updateCompletedAds(newCount, new Date().toISOString());

                        if (newCount >= 3) {
                            startCountdown();
                        }
                        loadAds(); // অ্যাড লিস্ট আবার লোড করা হচ্ছে
                    } else {
                        showPopup('Error', data.message || 'Failed to update balance.', 'error');
                        button.disabled = false;
                        button.innerHTML = 'Watch';
                    }
                });
            }).catch((error) => {
                console.error('Ad load error:', error);
                showPopup('Error', 'Ad failed to load. Please try again.', 'error');
                button.disabled = false;
                button.innerHTML = 'Watch';
            });
        }
        
        // Load ads
        function loadAds() {
            const adsContainer = document.getElementById('ads-container');
            adsContainer.innerHTML = '';

            const completed = getCompletedAds();
            const remainingAds = Math.max(0, 3 - completed.count);
            document.getElementById('remaining-ads').textContent = remainingAds;

            if (remainingAds > 0) {
                for (let i = 0; i < remainingAds; i++) {
                    const adHtml = `
                        <div class="ad-container flex items-center p-3 border border-gray-200 rounded-lg">
                            <div class="bg-blue-100 p-2 rounded-lg mr-3"><i data-lucide="video" class="w-5 h-5 text-blue-600"></i></div>
                            <div class="flex-1">
                                <h4 class="font-medium">Video Ad #${3 - remainingAds + i + 1}</h4>
                                <p class="text-sm text-gray-500">Earn $0.01</p>
                            </div>
                            <button onclick="watchAd(this)" class="watch-ad-btn bg-blue-600 text-white px-3 py-1 rounded-lg text-sm">Watch</button>
                        </div>
                    `;
                    adsContainer.innerHTML += adHtml;
                }
            } else {
                adsContainer.innerHTML = `
                    <div class="text-center py-10">
                        <i data-lucide="clock" class="w-12 h-12 mx-auto text-gray-400 mb-3"></i>
                        <p class="text-gray-500">You've watched all ads. Come back after the timer ends.</p>
                    </div>`;
            }
            lucide.createIcons();
        }

        // Countdown timer for ad refresh
        let countdownInterval;
        function startCountdown() {
            clearInterval(countdownInterval);
            const completed = getCompletedAds();
            if (!completed.last_completed) return;

            const resetTime = new Date(completed.last_completed).getTime() + (24 * 60 * 60 * 1000);

            countdownInterval = setInterval(() => {
                const now = new Date().getTime();
                const diff = resetTime - now;

                if (diff <= 0) {
                    clearInterval(countdownInterval);
                    document.getElementById('next-ads-time').textContent = '00:00:00';
                    updateCompletedAds(0, null); // রিসেট
                    loadAds();
                    return;
                }

                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                const timeString = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                document.getElementById('next-ads-time').textContent = timeString;
            }, 1000);
        }

        // Withdrawal request logic
        document.getElementById('request-withdraw-btn').addEventListener('click', function() {
            const amount = parseFloat(document.getElementById('withdraw-amount').value);
            const accountDetails = document.getElementById('account-details').value.trim();
            const paymentMethod = document.querySelector('input[name="payment-method"]:checked').value;
            const btn = this;

            if (isNaN(amount) || amount < 1) {
                showPopup('Error', 'Minimum withdrawal amount is $1.00', 'error');
                return;
            }
            if (!accountDetails) {
                showPopup('Error', 'Please enter your account number', 'error');
                return;
            }
            
            btn.disabled = true;
            btn.textContent = 'Processing...';

            fetch('process_withdrawal.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}&amount=${amount}&method=${paymentMethod}&account_details=${encodeURIComponent(accountDetails)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showPopup('Success', 'Withdrawal request submitted successfully!', 'success');
                    document.getElementById('user-balance').textContent = '$' + parseFloat(data.new_balance).toFixed(2);
                    document.getElementById('withdraw-amount').value = '';
                    document.getElementById('account-details').value = '';
                } else {
                    showPopup('Error', data.message || 'Failed to process withdrawal', 'error');
                }
            })
            .catch(error => {
                console.error('Withdrawal Error:', error);
                showPopup('Error', 'An error occurred while processing your request.', 'error');
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = 'Request Withdrawal';
            });
        });

        // Initialize Telegram WebApp user details
        document.addEventListener('DOMContentLoaded', () => {
            if (window.Telegram && Telegram.WebApp.initDataUnsafe && Telegram.WebApp.initDataUnsafe.user) {
                const user = Telegram.WebApp.initDataUnsafe.user;
                const userName = (user.first_name || '') + ' ' + (user.last_name || '');
                if (userName.trim()) {
                    document.getElementById('user-name').textContent = userName.trim();
                }
            }
            
            // Load ads initially and start countdown if needed
            loadAds();
            if (getCompletedAds().count >= 3) {
                startCountdown();
            }
        });
    </script>
</body>
</html>
