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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@^2.2.15/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <div id="sidebar-container">
            <?php include 'sidebar.php'; ?>
        </div>
        <!-- Main Content -->
        <div class="flex flex-col flex-1">
            <header class="bg-white shadow-lg">
                <div class="flex items-center justify-between px-6 py-3 border-b">
                    <h2 class="text-lg font-semibold">Room Loading</h2>
                </div>
            </header>
            <main class="flex-1 p-4 overflow-y-auto">
                <div class="flex items-center space-x-4 justify-between">
                    <div class="space-x-12">
                        <button onclick="window.location.href='loads-deptHead.php'" class="px-3 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-150 ease-in-out" title="View Room Loads">
                            <i class="fa-solid fa-calendar-check"></i>
                        </button>
                        <button class="px-4 py-2 bg-plv-blue text-white rounded-lg hover:bg-plv-highlight transition duration-150 ease-in-out" onclick="showUploadModal()">
                            <i class="fa-solid fa-file-upload"></i> Upload Schedule
                        </button>
                    </div>
                    <button class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition duration-150 ease-in-out" onclick="window.location.href='handlers/download_template.php'">
                        <i class="fa-solid fa-file-download"></i> Download Template
                    </button>
                </div>
                                            <!-- Toast Notification -->
                            <div id="toast" class="fixed right-4 text-white p-4 rounded-lg shadow-lg opacity-0 transform translate-y-4 transition-opacity transition-transform duration-300">
                                <span id="toast-message"></span>
                            </div>
                            <div id="loading-spinner" class="hidden flex justify-center items-center">
                                <div class="animate-spin h-8 w-8 border-4 border-t-transparent border-blue-500 rounded-full"></div>
                            </div>
                                <!-- Table for Pending Schedules -->
                <div class="mt-6">
                    <h3 class="text-lg font-semibold mb-2">Uploaded Schedules</h3>
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

                
            </main>
        </div>
    </div>
    <!-- Upload Modal -->
    <div id="uploadModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-md flex flex-col">
            <h1 class="text-xl font-bold mb-4">File Upload</h1>

            <div class="mb-4">
                <label for="aySemester" class="block text-sm font-medium text-gray-700">Academic Year & Semester</label>
                <select id="aySemester" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="" disabled selected>Select Academic Year & Semester</option>
                    <option value="2024-2025-1">2024-2025 - Semester 1</option>
                    <option value="2024-2025-2">2024-2025 - Semester 2</option>
                    <option value="2023-2024-1">2023-2024 - Semester 1</option>
                    <option value="2023-2024-2">2023-2024 - Semester 2</option>
                    <!-- Add more options as needed -->
                </select>
            </div>

            <div class="mb-4">
                <label for="department-dropdown" class="block text-sm font-medium text-gray-700">Department</label>
                <select id="department-dropdown" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= htmlspecialchars($department['dept_id']); ?>" 
                            <?= ($department['dept_name'] == $user_department) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($department['dept_name']); ?>
                        </option>

                    <?php endforeach; ?>
                </select>
            </div>

            <div id="upload-container">
                <input type="file" id="file-input" accept=".xlsx, .xls"
                    class="w-full text-gray-400 font-semibold bg-white border file:cursor-pointer cursor-pointer file:border-0 file:py-3 file:px-4 file:mr-4 file:bg-gray-100 file:hover:bg-gray-200 file:text-gray-500 rounded" />
                <p class="text-sm text-gray-400 mt-2">Only the provided template is allowed.</p>
            </div>

            <div class="flex justify-between mt-5">
                <button id="cancel-upload" onclick="cancelUpload()" class="mr-4 px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">Cancel</button>
                <button id="confirm-upload" class="px-4 py-2 bg-plv-blue text-white rounded-lg hover:bg-plv-highlight">Upload</button>
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

    <!-- Logout confirmation modal -->
    <div id="custom-dialog" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-md flex flex-col items-center">
            <img class="w-36 mb-4" src="img\undraw_warning_re_eoyh.svg" alt="">
            <p class="text-lg text-slate-700 font-semibold mb-4">Are you sure you want to logout?</p>
            <div class="flex justify-center mt-5">
                <button onclick="cancelLogout()" class="mr-4 px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">Cancel</button>
                <button onclick="confirmLogout()" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-500">Logout</button>
            </div>
        </div>
    </div> 
    <script src="scripts/logout.js"></script>
    <script src="scripts/GA.js"></script>
    <script>
        function showUploadModal() {
            document.getElementById('uploadModal').classList.remove('hidden');
        }

        function cancelUpload() {
            // Handle cancelation if needed
            document.getElementById('uploadModal').classList.add('hidden');
        }
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