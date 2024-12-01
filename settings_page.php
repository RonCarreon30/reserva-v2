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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
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
                                <div class="flex flex-col space-y-2 ">
                                    <label for="idNumber" class="text-gray-600">ID Number:</label>
                                    <input type="text" id="idNumber" name="idNumber" class="border border-gray-300 p-2 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500" value="<?php echo htmlspecialchars($user_id_number); ?>" disabled>

                                    <label for="name" class="text-gray-600">Name:</label>
                                    <input type="text" id="name" name="name" class="border border-gray-300 p-2 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500" value="<?php echo htmlspecialchars($user_first_name . ' ' . $user_last_name); ?>" disabled>
                                </div>
                                <div class="flex flex-col space-y-2">
                                    <label for="email" class="text-gray-600">Email Address</label>
                                    <input type="email" id="email" name="email" class="border border-gray-300 p-2 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500" value="<?php echo htmlspecialchars($user_email); ?>" required>
                                </div>
                                <!--<button type="button" class="mt-4 w-full bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 focus:outline-none">Save Changes</button>-->
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
                            <div class="flex flex-col space-y-2 relative">
                                <label for="new-password" class="text-gray-600">New Password</label>
                                <input type="password" id="new-password" name="new_password" class="border border-gray-300 p-2 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500" required>
                                <span id="toggle-new-password" class="text-gray-600" style="position: absolute; right: 10px; top: 60%; transform: translateY(-50%); cursor: pointer;">
                                    <i class=" fas fa-eye"></i>
                                </span>
                            </div>
                            <div class="flex flex-col space-y-2 relative">
                                <label for="confirm-password" class="text-gray-600">Confirm New Password</label>
                                <input type="password" id="confirm-password" name="confirm_password" class="border border-gray-300 p-2 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500" required>
                                <span id="toggle-confirm-password" class="text-gray-600" style="position: absolute; right: 10px; top: 60%; transform: translateY(-50%); cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </span>
                            </div>
                            <button type="button" id="update-pass-btn" class="mt-4 w-full bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 focus:outline-none">Update Password</button>
                        </form>
                    </section>

                    <!-- Request Account Deletion Section -->
                    <section class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-lg font-semibold text-gray-700 mb-4">Request Account Deletion</h2>
                        <p class="text-gray-600">
                            Submitting a request will notify the administrator to process your account deletion. You will be notified once the process is complete.
                        </p>
                        <button id="delete-account-btn" type="button" class="mt-4 w-full bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 focus:outline-none">
                            Request Account Deletion
                        </button>
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
                <!-- Include the FAQs section here -->
                <div class="">
                    <?php include 'faqBtn.php'; ?>
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
    <!-- Confirmation Modal -->
<div id="delete-account-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-md flex flex-col items-center">
        <img class="w-36 mb-4" src="img/undraw_warning_re_eoyh.svg" alt="Warning">
        <p class="text-lg text-slate-700 font-semibold mb-4">
            Are you sure you want to submit a deletion request? Once deletion is done it cannot be undone.
        </p>
        <div class="flex justify-center mt-5">
            <button id="cancel-delete-btn" class="mr-4 px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">
                Cancel
            </button>
            <button id="confirm-delete-btn" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                Confirm
            </button>
        </div>
    </div>
</div>
    <!-- confirmation, success, and errors modal -->
<div id="action-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-md flex flex-col items-center">
        <img id="modal-icon" class="w-36 mb-4" src="" alt="">
        <p id="modal-message" class="text-lg text-slate-700 font-semibold mb-4"></p>
        <div id="modal-actions" class="flex justify-center mt-5">
            <button id="cancel-btn" class="mr-4 px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">Cancel</button>
            <button id="confirm-btn" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Confirm</button>
        </div>
    </div>
</div>
    <!-- Add a modal for the password update confirmation -->
<div id="update-password-dialog" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-md flex flex-col items-center">
        <img class="w-36 mb-4" src="img/undraw_warning_re_eoyh.svg" alt="">
        <p class="text-lg text-slate-700 font-semibold mb-4">Are you sure you want to update your password?</p>
        <div class="flex justify-center mt-5">
            <button onclick="cancelPasswordUpdate()" class="mr-4 px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">Cancel</button>
            <button onclick="confirmPasswordUpdate()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Confirm</button>
        </div>
    </div>
</div>

<script>
    
    // Handle password visibility toggle
    document.getElementById('toggle-new-password').addEventListener('click', function () {
        const newPasswordField = document.getElementById('new-password');
        const icon = this.querySelector('i');
        if (newPasswordField.type === 'password') {
            newPasswordField.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            newPasswordField.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });

    document.getElementById('toggle-confirm-password').addEventListener('click', function () {
        const confirmPasswordField = document.getElementById('confirm-password');
        const icon = this.querySelector('i');
        if (confirmPasswordField.type === 'password') {
            confirmPasswordField.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            confirmPasswordField.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });

    // Handle password update button click
    document.getElementById('update-pass-btn').addEventListener('click', function () {
        const currentPassword = document.getElementById('current-password').value;
        const newPassword = document.getElementById('new-password').value;
        const confirmPassword = document.getElementById('confirm-password').value;

        // Validate input fields
        if (!currentPassword) {
            showModal('error', 'Current password required!');
            return;
        }
        if (!currentPassword || !newPassword || !confirmPassword) {
            showModal('error', 'Please fill in all fields!');
            return;
        }

        if (newPassword.length < 8) {
            showModal('error', 'password must be at least 8 characters long.');
            return;
        }

        if (newPassword !== confirmPassword) {
            showModal('error', "Password doesn't match!");
            return;
        }

        // Show confirmation modal
        document.getElementById('update-password-dialog').classList.remove('hidden');
    });



    // Confirm password update
    function confirmPasswordUpdate() {
        const currentPassword = document.getElementById('current-password').value;
        const newPassword = document.getElementById('new-password').value;

        // Send the request to the server to update the password
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'handlers/update_password.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showModal('success', response.message);
                } else {
                    showModal('error', response.message);
                }
            }
        };
        xhr.send('current_password=' + encodeURIComponent(currentPassword) + '&new_password=' + encodeURIComponent(newPassword));
        document.getElementById('update-password-dialog').classList.add('hidden');
    }

        // Function to show the modal with different content based on the type
    function showModal(type, message) {
        const modal = document.getElementById('action-modal');
        const modalMessage = document.getElementById('modal-message');
        const modalIcon = document.getElementById('modal-icon');
        const modalActions = document.getElementById('modal-actions');
        const cancelBtn = document.getElementById('cancel-btn');
        const confirmBtn = document.getElementById('confirm-btn');

        // Set up the modal based on type
        switch (type) {
            case 'confirmation':
                modalMessage.textContent = message;
                modalIcon.src = 'img/undraw_warning_re_eoyh.svg'; // Use a warning icon for confirmation
                confirmBtn.textContent = 'Confirm';
                confirmBtn.classList.remove('bg-blue-500', 'hover:bg-blue-600');
                confirmBtn.classList.add('bg-blue-500', 'hover:bg-blue-600');
                modalActions.classList.remove('hidden');
                cancelBtn.classList.remove('hidden');
                break;
            
            case 'success':
                modalMessage.textContent = message;
                modalIcon.src = 'img/undraw_completing_re_i7ap.svg'; // Use a success icon for success
                confirmBtn.textContent = 'OK';
                confirmBtn.classList.remove('bg-blue-600', 'hover:bg-blue-500');
                confirmBtn.classList.add('bg-green-600', 'hover:bg-green-500');
                
                // Show the confirm button and hide the cancel button
                cancelBtn.classList.add('hidden');  // Hide the cancel button
                modalActions.classList.remove('hidden');  // Ensure the modal actions are visible
                
                // Add event listener to the confirm button to reload the page
                confirmBtn.addEventListener('click', function() {
                    location.reload();  // Reload the page on confirm
                });
                break;


            case 'error':

                modalMessage.textContent = message;
                modalMessage.classList.remove('text-slate-700');
                modalMessage.classList.add('text-red-500');
                modalIcon.src = 'img/undraw_warning_re_eoyh.svg'; // Use an error icon for errors
                cancelBtn.textContent = 'Okay';
                confirmBtn.classList.add('hidden');
                modalActions.classList.remove('hidden');
                cancelBtn.classList.remove('hidden');
                break;
            }

            // Show the modal
            modal.classList.remove('hidden');
        }

        // Handle cancel button
        document.getElementById('cancel-btn').addEventListener('click', function () {
            document.getElementById('action-modal').classList.add('hidden');
        });

    </script>

    <script>
    // Show confirmation modal
    document.getElementById('delete-account-btn').addEventListener('click', () => {
        document.getElementById('delete-account-modal').classList.remove('hidden');
    });

    // Hide modal on cancel
    document.getElementById('cancel-delete-btn').addEventListener('click', () => {
        document.getElementById('delete-account-modal').classList.add('hidden');
    });

    // Handle confirmation
    document.getElementById('confirm-delete-btn').addEventListener('click', () => {
        fetch('handlers/request_deletion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showModal('success', data.message); // Notify user
                    document.getElementById('delete-account-modal').classList.add('hidden');
                } else {
                    showModal('error', data.message); // Notify error
                }
            })
            .catch(error => console.error('Error:', error));
    });
</script>

    <script src="scripts/logout.js"></script>
</body>
</html>
