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

    $sql = "
        SELECT dept_id, dept_name 
        FROM dept_tbl 
        ORDER BY (dept_name = ?) DESC, dept_name ASC
    ";

    if ($stmt = $conn->prepare($sql)) {
        // Bind the parameter
        $stmt->bind_param('s', $user_department);

        // Execute the statement
        if ($stmt->execute()) {
            // Fetch the results
            $result = $stmt->get_result();
            $departments = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            echo "Error executing query: " . $stmt->error;
        }

        // Close the statement
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
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
    // Automatically filter schedules on page load
    filterSchedules();
});

function filterSchedules() {
    const searchQuery = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
    const facilityRows = document.querySelectorAll('#schedules-body tr');
    let hasVisibleRows = false;

    facilityRows.forEach(row => {
        const facilityName = row.cells[0].textContent.toLowerCase();
        const reservationStatus = row.cells[6].textContent.toLowerCase(); // Adjusted for Status column

        const matchesSearch = facilityName.includes(searchQuery);
        const matchesStatus = statusFilter === 'all' || reservationStatus === statusFilter;

        if (matchesSearch && matchesStatus) {
            row.classList.remove('hidden');
            hasVisibleRows = true;
        } else {
            row.classList.add('hidden');
        }
    });

    const noResultsMessage = document.getElementById('noResultsMessage');
    if (!hasVisibleRows) {
        noResultsMessage.classList.remove('hidden');
    } else {
        noResultsMessage.classList.add('hidden');
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
                <div class="flex items-center justify-between">
                    <div class="flex gap-2">
                        <button onclick="window.location.href='loads.php'" class="px-3 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-150 ease-in-out" title="View Room Loads">
                            <i class="fa-solid fa-calendar-check"></i>
                        </button>
                        <div class="h-10 border-2 border-gray-400"></div>
                        <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-md" onchange="filterSchedules()">
                            <option value="all">All</option>
                            <option value="pending">Pending</option>
                            <option value="assigned">Assigned</option>
                            <option value="conflicted">Conflicted</option>
                            <option value="expired">Expired</option>
                        </select>
                        <input type="text" id="searchInput" class="px-4 py-2 border border-gray-300 rounded-md" placeholder="Search..." onkeyup="filterSchedules()">
                    </div>

                    <div class="flex gap-2">
                        <button class="px-4 py-2 bg-plv-blue text-white rounded-lg hover:bg-plv-highlight transition duration-150 ease-in-out" onclick="showUploadModal()">
                            <i class="fa-solid fa-file-upload"></i> Upload Schedule
                        </button>

                        <div class="h-10 border-2 border-gray-400"></div>
                        
                        <button class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition duration-150 ease-in-out" onclick="window.location.href='handlers/download_template.php'">
                            <i class="fa-solid fa-file-download"></i> Download Template
                        </button>
                    </div>

                </div>
                <!-- Table for Schedules -->
                <div class="mt-6">
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

                                                date_default_timezone_set('Asia/Manila'); // Set to your local timezone
                                                    // Check if the schedule is newly added (within the last 30 seconds)
                                                $isNewSchedule = (strtotime($row['created_at']) >= strtotime('-30 seconds'));


                                                // Apply a different class if it's a new schedule
                                                $rowClass = $isNewSchedule ? 'bg-blue-100' : '';

                                                // Convert start_time and end_time to 12-hour format with AM/PM
                                                $startTime12hr = date("h:i A", strtotime($row['start_time']));
                                                $endTime12hr = date("h:i A", strtotime($row['end_time']));

                                                echo "<tr class='$rowClass'>"; // Add the highlight class to the row
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['subject_code']) . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['section']) . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['instructor']) . '</td>';
                                                echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($startTime12hr) . ' - ' . htmlspecialchars($endTime12hr) . '</td>'; // Updated time format
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
                                                    echo '<button onclick="editSched(' . $schedId . ')" class="text-blue-500 hover:text-blue-600" title="Edit">';
                                                    echo '<i class="fas fa-edit"></i>'; // Font Awesome Edit Icon
                                                    echo '</button>';
                                                    echo '<button onclick="deleteSched(' . $schedId . ')" class="text-red-500 hover:text-red-600" title="Delete">';
                                                    echo '<i class="fas fa-trash-alt"></i>'; // Font Awesome Delete Icon
                                                    echo '</button>';
                                                    echo '</td>';
                                                } else {
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-red-500">Unauthorized</td>';
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
    <!-- Toast Notification -->
<div id="toast" class="fixed bottom-5 right-5 z-50 flex items-center p-4 max-w-xs text-white rounded-lg shadow-lg opacity-0 transform translate-y-4 transition-all duration-300">
    <span id="toast-message"></span>
</div>

    <!-- Modal Structure -->
    <div id="message-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
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
                    <button id="close-parsed-modal" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">Close</button>
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

        document.getElementById('close-parsed-modal').addEventListener('click', function() {
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
                    console.log("Raw response from save_schedules.php:", response);
                    document.getElementById('parsed-sched-modal').classList.add('hidden');

                    if (typeof response === "string") {
                        const parsedResponse = JSON.parse(response);
                        document.getElementById('modal-title').innerText = parsedResponse.success ? 'Success' : 'Error';
                        document.getElementById('modal-message').innerText = parsedResponse.message;
                        document.getElementById('message-modal').classList.remove('hidden');

                        if (parsedResponse.success) {
                            // Display saved schedules
                            if (parsedResponse.savedSchedules.length > 0) {
                                let savedMessage = "Saved Schedules:\n" + parsedResponse.savedSchedules.map(schedule => {
                                    return `${schedule.subjectCode} - ${schedule.subject} (${schedule.section})`;
                                }).join("\n");
                                showToast(savedMessage, "bg-green-500");
                            }

                            // Display duplicate schedules if any
                            if (parsedResponse.duplicates.length > 0) {
                                let duplicateMessage = "Duplicate Schedules:\n" + parsedResponse.duplicates.map(schedule => {
                                    return `${schedule.subjectCode} - ${schedule.subject} (${schedule.section})`;
                                }).join("\n");
                                showToast(duplicateMessage, "bg-yellow-500");
                            }

                            // Display conflicting schedules if any
                            if (parsedResponse.conflicts && parsedResponse.conflicts.length > 0) {
                                let conflictMessage = "Conflicting Schedules:\n" + parsedResponse.conflicts.map(schedule => {
                                    return `${schedule.subjectCode} - ${schedule.subject} (${schedule.section}) with ${schedule.instructor} on ${schedule.day} from ${schedule.startTime} to ${schedule.endTime}`;
                                }).join("\n");
                                showToast(conflictMessage, "bg-red-500");
                            }

                            // Proceed with room assignment
                            showToast("Assigning rooms, please wait...", "bg-blue-500");

                            const scheduleIds = parsedResponse.savedSchedules.map(schedule => schedule.scheduleId); // Extract schedule IDs

                            console.log("IDs:",scheduleIds);
                            $.ajax({
                                type: 'POST',
                                url: 'handlers/fcfs_assignment.php',
                                data: { scheduleIds: JSON.stringify(scheduleIds) }, // Send only IDs
                                success: function(assignmentResponse) {
                                    console.log("Raw response from fcfs_assignment.php:", assignmentResponse);
                                    const assignmentData = typeof assignmentResponse === "string" ? JSON.parse(assignmentResponse) : assignmentResponse;

                                    if (assignmentData.success) {
                                        showToast("Room assignment successful!", "bg-green-500");
                                        setTimeout(function() {
                                            //location.reload();
                                        }, 3000);
                                    } else {
                                        showToast("Room assignment failed: " + assignmentData.message, "bg-red-500");
                                    }
                                },
                                error: function() {
                                    showToast("Error during room assignment.", "bg-red-500");
                                    document.getElementById('modal-message').innerText = "Room assignment process failed.";
                                }
                            });

                        } else {
                            if (parsedResponse.duplicates.length > 0) {
                                let duplicateMessage = "Duplicates detected:\n" + parsedResponse.duplicates.map(schedule => {
                                    return `${schedule.subjectCode} - ${schedule.subject} (${schedule.section})`;
                                }).join("\n");
                                showToast(duplicateMessage, "bg-yellow-500");
                            }

                            // Display conflicts if any
                            if (parsedResponse.conflicts && parsedResponse.conflicts.length > 0) {
                                let conflictMessage = "Conflicting Schedules Detected:\n" + parsedResponse.conflicts.map(schedule => {
                                    return `${schedule.subjectCode} - ${schedule.subject} (${schedule.section}) with ${schedule.instructor} on ${schedule.day} from ${schedule.startTime} to ${schedule.endTime}`;
                                }).join("\n");
                                showToast(conflictMessage, "bg-red-500");
                            }
                        }
                    } else {
                        console.error("Unexpected response format:", response);
                        showToast("Unexpected response format. Please try again.", "bg-red-500");
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error saving schedules:", error);
                    document.getElementById('modal-message').innerText = "An error occurred while saving schedules. Please try again.";
                    showToast("An error occurred while saving schedules.", "bg-red-500");
                }
            });
        });



        // Close modal event
        document.getElementById('close-modal').addEventListener('click', function() {
            document.getElementById('message-modal').classList.add('hidden');
        });

        function showToast(message, status) {
            let toast = document.getElementById("toast");
            let messageSpan = document.getElementById("toast-message");

            // Clear existing background color classes
            toast.classList.remove("bg-green-500", "bg-red-500", "bg-blue-500");

            // Determine the background color and icon based on status
            let bgColor, icon;
            switch (status) {
                case "success":
                    bgColor = "bg-green-500";
                    icon = "fas fa-check-circle"; // Success icon
                    break;
                case "error":
                    bgColor = "bg-red-500";
                    icon = "fas fa-exclamation-circle"; // Error icon
                    break;
                case "loading":
                    bgColor = "bg-blue-500";
                    icon = "fas fa-spinner fa-spin"; // Loading spinner icon
                    break;
                default:
                    bgColor = "bg-gray-500";
                    icon = "fas fa-info-circle"; // Default info icon
            }

            // Set the new background color class
            toast.classList.add(bgColor);

            // Set the toast message with the appropriate icon
            messageSpan.innerHTML = `<i class="${icon}"></i> ${message}`;

            // Show the toast
            toast.classList.remove("opacity-0", "translate-y-4");
            toast.classList.add("opacity-100", "translate-y-0");

            // Hide the toast automatically after 5 seconds if not in loading state
            if (status !== "loading") {
                setTimeout(() => {
                    toast.classList.remove("opacity-100", "translate-y-0");
                    toast.classList.add("opacity-0", "translate-y-4");
                }, 5000);
            }
        }

    </script>
</body>
</html>