<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page
    header("Location: index.html");
    exit();
}

// Check if the user has the required role
if ($_SESSION['role'] !== 'Dept. Head') {
    // Redirect to a page indicating unauthorized access
    header("Location: index.html");
    exit();
}

// Assuming user_department is stored in the session
$user_department = isset($_SESSION['department']) ? $_SESSION['department'] : 'Unknown';

require 'database/config.php'; 

try {
    // Connect to the database
    $pdo = new PDO('mysql:host=localhost;dbname=reservadb', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch all departments, sorting to place the user's department at the top
    $sql = "
        SELECT dept_id, dept_name 
        FROM dept_tbl 
        ORDER BY (dept_name = :user_department) DESC, dept_name ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_department', $user_department, PDO::PARAM_STR);
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLV: RESERVA</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@^2.2.15/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* Ensures that the file name container has a fixed width */
        #file-name-container {
            min-width: 200px; /* Adjust the width as needed */
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <div id="sidebar-container">
            <?php include 'sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div id="roomList" class="flex flex-col flex-1">
            <header class="bg-white shadow-lg">
                <div class="flex items-center justify-between px-6 py-3 border-b">
                    <h2 class="text-lg font-semibold">Room Loading</h2>
                </div>
            </header>
            <main class="flex-1 p-4 overflow-y-auto">
                <!-- Schedule upload -->
                <div id="schedule-upload-page" class="">
                    <!-- File Upload Section -->
                    <div class="flex flex-col mb-4">
                        <div class="flex items-center space-x-4">
                            <div id="sched-button-container" class="flex items-center">
                                <button id="redirect-button" onclick="window.location.href='room_loads.php'" class="px-3 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-150 ease-in-out">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </button>
                            </div>
                            <div id="upload-container">
                                <label class="flex items-center px-3 py-2 bg-white text-blue-500 rounded-lg shadow-md cursor-pointer hover:bg-blue-500 hover:text-white transition duration-150 ease-in-out">
                                    <svg class="w-6 h-6" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path d="M16.88 9.1A4 4 0 0 1 16 17H5a5 5 0 0 1-1-9.9V7a3 3 0 0 1 4.52-2.59A4.98 4.98 0 0 1 17 8c0 .38-.04.74-.12 1.1zM11 11h3l-4-4-4 4h3v3h2v-3z" />
                                    </svg>
                                    <span class="ml-2 text-sm">Select a file</span>
                                    <input type="file" id="file-input" class="hidden" name="file" accept=".xlsx, .xls" />
                                </label>
                            </div>
                            
                            <div id="file-info" class=" flex items-center space-x-2">
                                <div class="w-40 overflow-hidden">
                                    <p id="file-name-container" class="text-sm text-gray-700"></p>
                                    <p id="file-size" class="text-sm text-gray-500"></p>
                                </div>
                                

                                <button id="confirm-upload" class="hidden p-1 bg-green-500 text-white rounded-full hover:bg-green-600 transition duration-150 ease-in-out">
                                    <svg class="w-6 h-6" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path d="M7 10l3 3 5-5-1.41-1.41L10 11.17 8.41 9.59 7 10z" />
                                    </svg>
                                </button>
                                <button id="cancel-upload" class="hidden p-1 bg-red-500 text-white rounded-full hover:bg-red-600 transition duration-150 ease-in-out">
                                    <svg class="w-6 h-6" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path d="M14.29 15.29a1 1 0 0 1-1.41 0L10 12.41l-2.88 2.88a1 1 0 0 1-1.41-1.41L8.59 11 5.71 8.12a1 1 0 0 1 1.41-1.41L10 9.59l2.88-2.88a1 1 0 0 1 1.41 1.41L11.41 11l2.88 2.88a1 1 0 0 1 0 1.41z" />
                                    </svg>
                                </button>
                            </div>
                            <div id="dept-dropdown">
                                <div class="flex items-center space-x-4">
                                    <label for="department-dropdown" class="text-sm text-gray-600">Preferred Department:</label>
                                    <select id="department-dropdown" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring focus:border-blue-300">
                                        <?php foreach ($departments as $department): ?>
                                            <option value="<?= htmlspecialchars($department['dept_id']); ?>" 
                                                <?= ($department['dept_name'] == $user_department) ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($department['dept_name']); ?>
                                            </option>

                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <!-- Toast Notification -->
                            <div id="toast" class="fixed right-4 text-white p-4 rounded-lg shadow-lg opacity-0 transform translate-y-4 transition-opacity transition-transform duration-300">
                                <span id="toast-message"></span>
                            </div>
                            <div id="loading-spinner" class="hidden flex justify-center items-center">
                                <div class="animate-spin h-8 w-8 border-4 border-t-transparent border-blue-500 rounded-full"></div>
                            </div>

                        </div>
                    </div>

                    <!-- Modal Background -->
                    <div id="parsed-sched-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden">
                        <div class="flex items-center justify-center h-full">
                            <!-- Modal Content -->
                            <div class="bg-white rounded-lg shadow-lg w-full max-w-4xl mx-4 p-6">
                                <!-- Modal Header -->
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-lg font-semibold text-gray-800">Parsed Schedules</h2>
                                    <button id="cancel-action" class="text-gray-400 hover:text-gray-600">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Table to Display Uploaded Schedules -->
                                <div id="schedule-table-container" class="overflow-x-auto mb-4">
                                    <table id="schedule-table" class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject Code</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instructor</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day/s</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class Type</th>
                                            </tr>
                                        </thead>
                                        <tbody id="schedule-table-body" class="bg-white divide-y divide-gray-200">
                                            <!-- Rows will be added here dynamically -->
                                             <span id="user-department" class="hidden"><?php echo htmlspecialchars($user_department); ?></span>

                                        </tbody>
                                    </table>
                                </div>

                                <!-- Buttons for Actions -->
                                <div class="flex justify-end space-x-4">
                                    <button id="cancel-action" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-150 ease-in-out">
                                        Cancel
                                    </button>
                                    <button id="save-action" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition duration-150 ease-in-out">
                                        Save
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

        <!-- Table for Pending Schedules -->
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-2">Pending Schedules</h3>
            <div class="overflow-x-auto">
                <table id="pending-schedules-table" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instructor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day/s</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody id="pending-schedules-body" class="bg-white divide-y divide-gray-200">
                        <!-- Rows will be populated here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>             

            </main>
        </div>
    </div>




    <script src="scripts/logout.js"></script>
    <script src="scripts/GA.js"></script>
    <script>
        $(document).ready(function() {
            // Tabs functionality
            $('#formTabs a').on('click', function(e) {
                e.preventDefault();
                var tab = $(this).attr('href');
                $('#formTabs a').removeClass('border-blue-500 text-blue-600');
                $(this).addClass('border-blue-500 text-blue-600');
                // Hide all form sections except the one corresponding to the clicked tab
                $('#schedule-upload-page, #room-assign-page, #room_scheds').addClass('hidden');
                $(tab).removeClass('hidden');
            });
        });
        $(document).ready(function() {
            // Fetch pending schedules
            fetchPendingSchedules();

            function fetchPendingSchedules() {
                $.ajax({
                    url: 'handlers/fetch_pending_schedules.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        const $tbody = $('#pending-schedules-body');
                        $tbody.empty(); // Clear existing rows
                        data.forEach(schedule => {
                            const row = `<tr>
                                <td class="px-6 py-4 whitespace-nowrap">${schedule.subject_code}</td>
                                <td class="px-6 py-4 whitespace-nowrap">${schedule.section}</td>
                                <td class="px-6 py-4 whitespace-nowrap">${schedule.instructor}</td>
                                <td class="px-6 py-4 whitespace-nowrap">${schedule.time}</td>
                                <td class="px-6 py-4 whitespace-nowrap">${schedule.days}</td>
                                <td class="px-6 py-4 whitespace-nowrap">${schedule.schedule_status}</td>
                            </tr>`;
                            $tbody.append(row);
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching schedules:', error);
                    }
                });
            }
        });

    </script>
</body>
</html>

