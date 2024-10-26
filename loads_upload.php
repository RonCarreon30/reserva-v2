<?php
    // Start the session
    session_start();

    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        // Redirect to the login page
        header("Location: unauthorized");
        exit();
    }

    // Check if the user has the required role
    if (!in_array($_SESSION['role'], ['Dept. Head', 'Admin',  'Registrar'])) {
        // Redirect to a page indicating unauthorized access
        header("Location: unauthorized");
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>
    <script>
                document.addEventListener('DOMContentLoaded', function() {
            // Automatically filter reservations on page load
            filterSchedules();
        });

        function filterSchedules() {
            const searchQuery = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
            const facilityRows = document.querySelectorAll('#eventsTable tbody tr');
            let hasVisibleRows = false; // Flag to check if any rows are visible

            facilityRows.forEach(row => {
                const facilityName = row.cells[0].textContent.toLowerCase(); // Facility name in the first column
                const reservationStatus = row.cells[4].textContent.toLowerCase(); // Reservation status in the fifth column
                
                // Determine if the row should be shown based on search and status filters
                const matchesSearch = facilityName.includes(searchQuery);
                let matchesStatus = false;

                // Default status filter behavior
                if (statusFilter === 'all') {
                    matchesStatus = true; // Show all
                } else if (statusFilter === 'in review') {
                    matchesStatus = reservationStatus === 'in review';
                } else if (statusFilter === 'approved') {
                    matchesStatus = reservationStatus === 'approved';                    
                } else if (statusFilter === 'declined') {
                    matchesStatus = reservationStatus === 'declined';                    
                } else if (statusFilter === 'expired') {
                    matchesStatus = reservationStatus === 'expired'; // Show only expired
                } else {
                    // Default to showing 'In Review' and 'Reserved' statuses
                    matchesStatus = (reservationStatus === 'in review' || reservationStatus === 'approved');
                }

                // Show or hide the row based on matches
                if (matchesSearch && matchesStatus) {
                    row.classList.remove('hidden'); // Show row
                    hasVisibleRows = true; // At least one row is visible
                } else {
                    row.classList.add('hidden'); // Hide row
                }
            });

            // Show or hide the "no results found" message based on visibility of rows
            const noResultsMessage = document.getElementById('noResultsMessage');
            if (!hasVisibleRows) {
                noResultsMessage.classList.remove('hidden'); // Show the message
            } else {
                noResultsMessage.classList.add('hidden'); // Hide the message
            }
        }

        function sortTable(columnIndex) {
            const table = document.getElementById('eventsTable');
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const isAscending = table.dataset.sortOrder === 'asc';

            rows.sort((rowA, rowB) => {
                const cellA = rowA.children[columnIndex].textContent.trim();
                const cellB = rowB.children[columnIndex].textContent.trim();

                if (isAscending) {
                    return cellA.localeCompare(cellB);
                } else {
                    return cellB.localeCompare(cellA);
                }
            });

            table.querySelector('tbody').append(...rows);
            table.dataset.sortOrder = isAscending ? 'desc' : 'asc';
        }
    </script>
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
                    <div class="space-x-4">
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
                <!-- Table for Schedules -->
                <div class="mt-6">
                    <h3 class="text-lg font-semibold mb-2">Uploaded Schedules</h3>
                        <div class="flex items-center space-x-4 mb-2">
                        <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-md" onchange="filterReservations()">
                            <option value="" disabled selected>Select Status</option> <!-- Placeholder option -->
                            <option value="all">All</option>
                            <option value="in review">In Review</option>
                            <option value="approved">Approved</option>
                            <option value="declined">Declined</option>
                            <option value="expired">Expired</option>
                        </select>
                        <input type="text" id="searchInput" class="px-4 py-2 border border-gray-300 rounded-md" placeholder="Search..." onkeyup="filterReservations()">
                    </div>
                    <div id="schedulesList" class="overflow-x-auto max-h-[calc(100vh-200px)] bg-white rounded-md shadow-md border border-gray-200">
                        <table id="schedules-table" class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-200 border-b">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instructor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Room Assignment</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody id="schedules-body" class="bg-white divide-y divide-gray-200">
                                <?php
                                // Query to fetch schedules, ordered by created_at
                                    $query = "
                                        SELECT 
                                            schedules.*, 
                                            room_assignments_tbl.assignment_id, 
                                            rooms_tbl.room_name, 
                                            rooms_tbl.room_type, 
                                            rooms_tbl.building_id, 
                                            buildings_tbl.building_name, 
                                            buildings_tbl.building_desc
                                        FROM schedules
                                        LEFT JOIN room_assignments_tbl ON schedules.schedule_id = room_assignments_tbl.schedule_id
                                        LEFT JOIN rooms_tbl ON room_assignments_tbl.room_id = rooms_tbl.room_id
                                        LEFT JOIN buildings_tbl ON rooms_tbl.building_id = buildings_tbl.building_id
                                        WHERE schedules.user_id = ?
                                        ORDER BY schedules.created_at DESC
                                    ";

                                    // Prepare the statement
                                    if ($stmt = $conn->prepare($query)) {
                                        // Bind the current user ID to the query
                                        $stmt->bind_param('i', $_SESSION['user_id']);

                                        // Execute the query
                                        $stmt->execute();

                                        // Get the result
                                        $result = $stmt->get_result();

                                        if ($result->num_rows > 0) {
                                            // Output data for each row
                                            while ($row = $result->fetch_assoc()) {
                                                $schedId = $row["schedule_id"];
                                                $schedStatus = $row["sched_status"];
                                                $isEditable = (strtolower($schedStatus) === 'pending' || strtolower($schedStatus) === 'conflicted');
                                                $isAssigned = (strtolower($schedStatus) === 'assigned');

                                                echo '<tr>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['subject_code']) . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['section']) . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['instructor']) . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['start_time']) . ' - ' . htmlspecialchars($row['end_time']) . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['days']) . '</td>';
                                                
                                                if ($isAssigned) {
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['building_name']) . ' - ' . htmlspecialchars($row['room_name']) .'</td>';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-green-500">' . htmlspecialchars($row['sched_status']) . '</td>';
                                                } else {
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-red-500">Not Assigned</td>';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['sched_status']) . '</td>';
                                                }

                                                if ($isEditable) {
                                                    echo '<td class="py-2 px-4 space-x-2">';
                                                    echo '<button onclick="editSched(' . $schedId . ')" class="bg-blue-500 text-white rounded-md px-4 py-2 hover:bg-blue-600">Edit</button>';
                                                    echo '<button onclick="deleteSched(' . $schedId . ')" class="bg-red-500 text-white rounded-md px-4 py-2 hover:bg-red-600">Delete</button>';
                                                    echo '</td>';
                                                } else {
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-red-500">Not Editable</td>';
                                                }
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">No schedules found.</td></tr>';
                                        }
                                    }
                                    ?>
                            </tbody>
                        </table>
                        <div id="noResultsMessage" class="text-center py-4 hidden">
                            <img src="img/undraw_not_found_re_bh2e.svg" alt="No Reservations Found" class="mx-auto mb-2 opacity-40" style="max-width: 250px;">
                            <p class="text-gray-500">No results found for the selected filters.</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <!-- Modal Structure -->
    <div id="message-modal" class="fixed inset-0 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg p-5">
            <h2 id="modal-title" class="text-lg font-semibold mb-4">Message</h2>
            <p id="modal-message" class="text-gray-700"></p>
            <button id="close-modal" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded">Close</button>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="upload-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-md flex flex-col">
            <h1 class="text-xl font-bold mb-4">File Upload</h1>
            <div class="mb-4">
                <label for="aySemester" class="block text-sm font-medium text-gray-700">Academic Year & Semester</label>
                <select id="aySemester" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="" disabled selected>Select Academic Year & Semester</option>
                    <?php
                        // Query to get terms values
                        $query = "SELECT * FROM terms_tbl";
                        $result = $conn->query($query);
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . $row['term_id'] . '">' . $row['academic_year'] . ' - ' . $row['semester'] . '</option>';
                            }
                        } else {
                            echo '<option value="">No data available</option>';
                        }  
                    ?>
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
                <button id="parse-upload" class="px-4 py-2 bg-plv-blue text-white rounded-lg hover:bg-plv-highlight">Upload</button>
            </div>
        </div>
    </div>
    <!-- Modal Background -->
    <div id="parsed-sched-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center h-full">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-4xl mx-4 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Parsed Schedules</h2>
                </div>

                <div id="schedule-table-container" class="overflow-x-auto mb-4">
                    <div id="selected-info" class="mb-4 text-md text-gray-600">
                        <!-- Selected values will be populated here -->
                    </div>
                    <table id="schedule-table" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instructor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day/s</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class Type</th>
                            </tr>
                        </thead>
                        <tbody id="schedule-table-body" class="bg-white divide-y divide-gray-200">
                            <!-- Rows will be added here dynamically -->
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end space-x-4">
                    <button id="close-modal" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">Close</button>
                    <button id="save-schedule" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Save Schedule</button>
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
    <script>
        function showUploadModal() {
            document.getElementById('upload-modal').classList.remove('hidden');
        }

        function cancelUpload() {
            document.getElementById('upload-modal').classList.add('hidden');
            document.getElementById('file-input').value = ''; // Clear the file input
        }

        document.getElementById('parse-upload').addEventListener('click', function() {
            const fileInput = document.getElementById('file-input');
            const file = fileInput.files[0];

            if (!file) {
                alert('Please upload an Excel file.');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(event) {
                const data = new Uint8Array(event.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                const jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });

                // Remove headers
                jsonData.shift();

                // Initialize an array for parsed schedules
                const parsedSchedules = []; 

                jsonData.forEach(row => {
                    parsedSchedules.push({
                        subjectCode: row[0],
                        subject: row[1],
                        section: row[2],
                        instructor: row[3],
                        startTime: row[4], // Capture Start Time
                        endTime: row[5],   // Capture End Time
                        days: row[6],      // Days column
                        classType: row[7]  // Class Type
                    });
                });

                populateScheduleTable(parsedSchedules);
                document.getElementById('upload-modal').classList.add('hidden');
                document.getElementById('parsed-sched-modal').classList.remove('hidden');
            };
            reader.readAsArrayBuffer(file);
        });

        function populateScheduleTable(parsedSchedules) {
            const scheduleTableBody = document.getElementById('schedule-table-body');
            scheduleTableBody.innerHTML = '';

            parsedSchedules.forEach(schedule => {
                const daysArray = schedule.days.split(',').map(day => day.trim()); // Split days by comma and trim spaces
                daysArray.forEach(day => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap">${schedule.subjectCode}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${schedule.subject}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${schedule.section}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${schedule.instructor}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${schedule.startTime}</td> <!-- Start Time -->
                        <td class="px-6 py-4 whitespace-nowrap">${schedule.endTime}</td> <!-- End Time -->
                        <td class="px-6 py-4 whitespace-nowrap">${day}</td> <!-- Individual Day -->
                        <td class="px-6 py-4 whitespace-nowrap">${schedule.classType}</td>
                    `;
                    scheduleTableBody.appendChild(row);
                });
            });
        }

        document.getElementById('close-modal').addEventListener('click', function() {
            document.getElementById('parsed-sched-modal').classList.add('hidden');
        });

        document.getElementById('save-schedule').addEventListener('click', function() {
            const schedules = Array.from(document.querySelectorAll('#schedule-table-body tr')).map(row => {
                return {
                    subjectCode: row.cells[0].innerText,
                    subject: row.cells[1].innerText,
                    section: row.cells[2].innerText,
                    instructor: row.cells[3].innerText,
                    startTime: row.cells[4].innerText,
                    endTime: row.cells[5].innerText,
                    days: row.cells[6].innerText,
                    classType: row.cells[7].innerText,
                };
            });

            const aySemester = document.getElementById('aySemester').value;
            const departmentId = document.getElementById('department-dropdown').value;

            $.ajax({
                type: 'POST',
                url: 'handlers/save_schedules.php',
                data: { schedules: JSON.stringify(schedules), aySemester, departmentId },
                success: function(response) {
                    const parsedResponse = JSON.parse(response);
                    
                    // Set modal title based on response success
                    document.getElementById('modal-title').innerText = parsedResponse.success ? 'Success' : 'Error';
                    
                    if (parsedResponse.success) {
                        // Function to convert time from 24-hour format to 12-hour format with AM/PM
                        function convertTo12HourFormat(time24) {
                            const [hours, minutes] = time24.split(':');
                            const period = hours >= 12 ? 'PM' : 'AM';
                            const adjustedHours = hours % 12 || 12; // Convert to 12-hour format
                            return `${adjustedHours}:${minutes} ${period}`;
                        }
                        // Show saved schedules
                        if (parsedResponse.savedSchedules.length > 0) {
                            document.getElementById('modal-message').innerText = 
                                parsedResponse.message + '\nSaved Schedules:\n' + 
                                parsedResponse.savedSchedules.map(schedule => {
                                    const startTime12 = convertTo12HourFormat(schedule.startTime);
                                    const endTime12 = convertTo12HourFormat(schedule.endTime);
                                    return `${schedule.subjectCode} (${schedule.day}): ${startTime12} - ${endTime12}`;
                                }).join('\n');
                        } else {
                            document.getElementById('modal-message').innerText = parsedResponse.message; // No new schedules saved
                        }

                        // Show duplicates if any
                        if (parsedResponse.duplicates.length > 0) {
                            document.getElementById('modal-message').innerText += 
                                '\n\nSome schedules were not saved due to duplicates:\n' + 
                                parsedResponse.duplicates.map(schedule => {
                                    const startTime12 = convertTo12HourFormat(schedule.startTime);
                                    const endTime12 = convertTo12HourFormat(schedule.endTime);
                                    return `${schedule.subjectCode} (${schedule.day}): ${startTime12} - ${endTime12}`;
                                }).join('\n');
                        }
                    } else {
                        document.getElementById('modal-message').innerText = parsedResponse.message; // Handle errors
                    }

                    // Show the modal
                    document.getElementById('message-modal').classList.remove('hidden');
                    
                    document.getElementById('parsed-sched-modal').classList.add('hidden');
                },
                error: function(xhr, status, error) {
                    document.getElementById('modal-title').innerText = 'Error';
                    document.getElementById('modal-message').innerText = 'An error occurred while saving the schedules. Please try again.';
                    document.getElementById('message-modal').classList.remove('hidden');
                }
            });

        });

        // Close modal event
        document.getElementById('close-modal').addEventListener('click', function() {
            document.getElementById('message-modal').classList.add('hidden');
        });



        /*function loadPendingSchedules() {
            // Logic to fetch and display pending schedules (if needed)
        }*/

        function showToast(message, bgColor) {
        let toast = document.getElementById("toast");
        let messageSpan = document.getElementById("toast-message");

        // Ensure the toast starts hidden
        toast.classList.add("opacity-0", "translate-y-4");
        toast.classList.remove("opacity-100", "translate-y-0");

        // Clear existing background color classes
        toast.classList.remove("bg-green-500", "bg-red-500", "bg-blue-500");

        // Set the new background color class
        toast.classList.add(bgColor);

        // Determine the icon based on bgColor
        let icon;
        if (bgColor === "bg-green-500") {
            icon = "fas fa-check-circle"; // Success icon
        } else if (bgColor === "bg-red-500") {
            icon = "fas fa-exclamation-circle"; // Error icon
        } else if (bgColor === "bg-blue-500") {
            icon = "fas fa-spinner fa-spin"; // Loading icon
        } else {
            icon = "fas fa-info-circle"; // Default info icon
        }

        // Set the toast message with the appropriate icon
        messageSpan.innerHTML = `<i class="${icon}"></i> ${message}`;

        // Show the toast
        toast.classList.remove("opacity-0", "translate-y-4");
        toast.classList.add("opacity-100", "translate-y-0");

        // Hide the toast after 3 seconds (or keep it for loading until manually closed)
        if (bgColor !== "bg-blue-500") {
            // Only auto-hide if not a loading message
            setTimeout(() => {
            toast.classList.remove("opacity-100", "translate-y-0");
            toast.classList.add("opacity-0", "translate-y-4");
            }, 5000);
        }
        }
    
        //For displaying Scheds on table
        /*$(document).ready(function() {
            // Fetch pending schedules
            fetchPendingSchedules();

            function fetchPendingSchedules() {
                $.ajax({
                    url: 'handlers/fetch_pending_schedules.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        const $tbody = $('#schedules-body');
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
        });*/
    </script>
</body>
</html>