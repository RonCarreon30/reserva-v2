    <?php
    // Start the session
    session_start();

    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        // Redirect to the login page
    header("Location: unauthorized");
        exit();
    }

    // Fetch reservations from the database for the current user
    require_once 'database/config.php';

    // Query to fetch reservations for the current user
    $user_id = $_SESSION['user_id'];
    
    // Fetch user's department from session
    $user_department = $_SESSION['department'];

    // Fetch user data from the database
$sql = "SELECT first_name, last_name, email, id_number, department_id, userRole FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch data for display
$user_first_name = $user['first_name'];
$user_last_name = $user['last_name'];
$user_email = $user['email'];
$user_id_number = $user['id_number'];
$user_department_id = $user['department_id'];
$user_role = $user['userRole'];

// Close the database connection
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLV: RESERVA</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        #custom-dialog {
            z-index: 10000; /* Ensures the logout modal appears on top of everything */
        }
                .fc-toolbar-title {
            font-size:large !important; /* Adjust this size as needed */
            font-weight: normal; /* Optional: adjust font weight */
        }

    </style>
</head>
<body>
    
    <div class="flex h-screen bg-gray-100">
        
        <!-- Load the Sidebar here   -->
        <div id="sidebar-container">
            <?php include 'sidebar.php'; ?>
        </div>
        
        <!-- Content area -->
        <div class="flex flex-col flex-1">
            <header class="bg-white shadow-lg">
                <div class="flex items-center justify-between px-6 py-3 border-b">
                    <h2 class="text-lg font-semibold">Settings</h2>
                </div>
            </header>

            <!-- Main Content goes here-->
            <main class="flex flex-1 p-6 mb-2 h-screen overflow-y-auto">
                <div class="w-full max-w-4xl mx-auto space-y-6">
                    <!-- Account Information -->
                        <section class="bg-white p-6 rounded-lg shadow-md">
                            <h2 class="text-lg font-semibold text-gray-700 mb-4">Account Information</h2>
                            <form id="account-info-form" class="space-y-4">
                                <div class="flex flex-col space-y-2">
                                    <label for="email" class="text-gray-600">Name:</label>
                                    <input type="email" id="email" name="email" class="border border-gray-300 p-2 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500" value="<?php echo htmlspecialchars($user_first_name . ' ' . $user_last_name); ?>" disabled>
                                </div>
                                <div class="flex flex-col space-y-2">
                                    <label for="email" class="text-gray-600">Email Address</label>
                                    <input type="email" id="email" name="email" class="border border-gray-300 p-2 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500" value="<?php echo htmlspecialchars($user_email); ?>" required>
                                </div>
                                <button type="button" class="mt-4 w-full bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 focus:outline-none">Save Changes</button>
                            </form>
                        </section>

                    <!-- Password Management -->
                    <section class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-lg font-semibold text-gray-700 mb-4">Password Management</h2>
                        <form id="password-form" class="space-y-4">
                            <div class="flex flex-col space-y-2">
                                <label for="current-password" class="text-gray-600">Current Password</label>
                                <input type="password" id="current-password" name="current_password" class="border border-gray-300 p-2 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500" required>
                            </div>
                            <div class="flex flex-col space-y-2">
                                <label for="new-password" class="text-gray-600">New Password</label>
                                <input type="password" id="new-password" name="new_password" class="border border-gray-300 p-2 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500" required>
                            </div>
                            <div class="flex flex-col space-y-2">
                                <label for="confirm-password" class="text-gray-600">Confirm New Password</label>
                                <input type="password" id="confirm-password" name="confirm_password" class="border border-gray-300 p-2 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500" required>
                            </div>
                            <button type="button" class="mt-4 w-full bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 focus:outline-none">Update Password</button>
                        </form>
                    </section>

                    <!-- Request Account Deletion -->
                    <section class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-lg font-semibold text-gray-700 mb-4">Request Account Deletion</h2>
                        <p class="text-gray-600">Once your account is deleted, it cannot be recovered. Please confirm your decision carefully.</p>
                        <button type="button" class="mt-4 w-full bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 focus:outline-none">Request Account Deletion</button>
                    </section>

                    <!-- About -->
                    <section class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-lg font-semibold text-gray-700 mb-4">About</h2>
                        <p class="text-gray-600 mb-4">
                            <strong>PLV: RESERVA</strong> is a facility reservation and room loading system, developed by BSIT students, designed to streamline facility management and room loading for educational institutions and organizations. This project was initiated in 2023 and is expected to be continuously developed through 2025.
                        </p>

                            <h3 class="text-md font-semibold text-gray-700 mb-2">Version</h3>
                        <p class="text-gray-600 mb-4">
                            <strong>Version 1.0.0</strong><br>
                            This version includes core features such as facility reservations, room loading, user role management, and Excel(.xlsx) upload for schedules. Planned updates will enhance these features with additional options for notifications, reporting, and personalized dashboards.
                        </p>
                    </section>

                    
                </div>
            </main>


            <div id="footer-container">
                <?php include 'footer.php' ?>
            </div>        
        </div>
    </div>

    <!-- Logout Modal -->
    <div id="custom-dialog" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-md flex flex-col items-center">
            <img class="w-36 mb-4" src="img/undraw_warning_re_eoyh.svg" alt="">
            <p class="text-lg text-slate-700 font-semibold mb-4">Are you sure you want to logout?</p>
            <div class="flex justify-center mt-5">
                <button onclick="cancelLogout()" class="mr-4 px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">Cancel</button>
                <button onclick="confirmLogout()" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-500">Logout</button>
            </div>
        </div>
    </div> 
    <script src="scripts/logout.js"></script>
</body>
</html>