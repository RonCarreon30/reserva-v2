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
if ($_SESSION['role'] !== 'Facility Head' && $_SESSION['role'] !== 'Admin') {
    // Redirect to a page indicating unauthorized access
    header("Location: unauthorized");
    exit();
}

// database config
include_once 'database/config.php';


// Fetch the user ID from the session data
$user_id = $_SESSION['user_id'];

// Fetch reservations with status "Pending" for the Pending List
$review_reservation_sql = "SELECT 
    r.*,         -- Select all columns from the reservations table
    u.first_name,
    u.last_name,  -- Example of a column from the users table
    u.email,     -- Example of another column from the users table
    f.facility_name, -- Example of a column from the facilities table
    f.building,
    f.descri    -- Example of another column from the facilities table
FROM 
    reservations r
JOIN 
    users u ON r.user_id = u.id
JOIN 
    facilities f ON r.facility_id = f.facility_id
WHERE 
    r.reservation_status = 'In Review'
ORDER BY 
    r.created_at DESC;
";
$review_reservation_result = $conn->query($review_reservation_sql);

$all_reservations_sql = "SELECT 
    r.*,
    u.first_name,
    u.last_name,  -- Example of a column from the users table
    f.building,
    f.facility_name
FROM
    reservations r
JOIN 
    users u ON r.user_id = u.id
JOIN
    facilities f ON r.facility_id = f.facility_id
ORDER BY 
    CASE WHEN r.reservation_status = 'In Review' THEN 0 ELSE 1 END,
    r.created_at DESC";
$all_reservations_result = $conn->query($all_reservations_sql);
/* Check if there are results
if ($all_reservations_result && $all_reservations_result->num_rows > 0) {
    // Start the output (you could also build an HTML table)
    echo "<table border='1'>
            <tr>
                <th>Reservation ID</th>
                <th>User First Name</th>
                <th>User Last Name</th>
                <th>Building</th>
                <th>Facility Name</th>
                <th>Reservation Status</th>
                <th>Created At</th>
            </tr>";

    // Loop through the results
    while ($row = $all_reservations_result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['first_name']}</td>
                <td>{$row['last_name']}</td>
                <td>{$row['building']}</td>
                <td>{$row['facility_name']}</td>
                <td>{$row['reservation_status']}</td>
                <td>{$row['created_at']}</td>
              </tr>";
    }

    echo "</table>";
} else {
    echo "No reservations found.";
}*/


// Fetch reservations and encode them for FullCalendar
$reservations = [];
if ($all_reservations_result->num_rows > 0) {
    while ($row = $all_reservations_result->fetch_assoc()) {
        $reservations[] = [
            'title' => $row['purpose'].' @'.$row['facility_name'],
            'start' => $row['reservation_date'] . 'T' . $row['start_time'],
            'end' => $row['reservation_date'] . 'T' . $row['end_time'],
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLV: RESERVA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Automatically filter reservations on page load
            filterReservations();
        });

        function filterReservations() {
            const searchQuery = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
            const facilityRows = document.querySelectorAll('#eventsTable tbody tr');
            let hasVisibleRows = false; // Flag to check if any rows are visible
    
            facilityRows.forEach(row => {
                const facilityName = row.cells[0].textContent.toLowerCase(); // Facility name in the first column
                const reservationStatus = row.cells[6].textContent.toLowerCase(); // Reservation status in the fifth column
                
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
                } else if (statusFilter === 'cancelled') {
                    matchesStatus = reservationStatus === 'cancelled';                    
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
            const columnHeader = table.querySelector(`th:nth-child(${columnIndex + 1})`);
            const isAscending = columnHeader.dataset.sortOrder === 'asc';
        
            // Check if the column contains dates (for the Reservation Date column)
            const isDateColumn = columnIndex === 3;
        
            rows.sort((rowA, rowB) => {
                let cellA = rowA.children[columnIndex].textContent.trim();
                let cellB = rowB.children[columnIndex].textContent.trim();
        
                // Parse dates if it’s a date column
                if (isDateColumn) {
                    cellA = new Date(cellA);
                    cellB = new Date(cellB);
                    return isAscending ? cellA - cellB : cellB - cellA;
                } else {
                    return isAscending ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
                }
            });
        
            // Append sorted rows and update the sort order for the next click
            table.querySelector('tbody').append(...rows);
            columnHeader.dataset.sortOrder = isAscending ? 'desc' : 'asc';
        }

    </script>
    <style>
            /* Simple spinning animation */
        .loader {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

    </style>
</head>
<body>
    <div class="flex h-screen bg-gray-100">
        <div id="sidebar-container">
            <?php include 'sidebar.php'; ?>
        </div>
        
        <div class="flex flex-col flex-1">
            <!-- Header -->
            <header class="bg-white shadow-lg">
                <div class="flex items-center justify-between px-6 py-3 border-b">
                    <h2 class="text-lg font-semibold">Reservations Management</h2>
                </div>
            </header>
            <!-- Main content area -->
            <main class="flex flex-1 p-4 overflow-y-auto">
                <div class="w-full">
                    <div class="flex items-center space-x-4 mb-2">
                        <div id="facility-reservation" title="Facility Reservation">
                            <button id="add-schedule-button" onclick="window.location.href='facilityReservation'" class="px-3 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-150 ease-in-out">
                                <i class="fa-solid fa-circle-plus"></i>
                            </button>
                        </div>
                        <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-md" onchange="filterReservations()">
                            <option value="" disabled selected>Select Status</option> <!-- Placeholder option -->
                            <option value="all">All</option>
                            <option value="in review">In Review</option>
                            <option value="approved">Approved</option>
                            <option value="declined">Declined</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="expired">Expired</option>
                        </select>
                        <input type="text" id="searchInput" class="px-4 py-2 border border-gray-300 rounded-md" placeholder="Search..." onkeyup="filterReservations()">
                    </div>
                    <div id="reservationsList" class="overflow-y-auto max-h-[calc(100vh-200px)] bg-white rounded-md shadow-md border border-gray-200">
                        <table id="eventsTable" class="w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-200 border-b">
                                    <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(0)">
                                        <span class="flex items-center">Facility
                                            <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                            </svg>
                                        </span>
                                    </th>
                                    <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100">
                                        <span class="flex items-center">Purpose</span>
                                    </th>
                                    <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100">
                                        <span class="flex items-center">Faculty In Charge</span>
                                    </th>       
                                    <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100">
                                        <span class="flex items-center">Reserved By</span>
                                    </th>                                
                                    <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(4)">
                                        <span class="flex items-center">Reservation Date
                                            <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                            </svg>
                                        </span>
                                    </th>
                                    <th class="py-3 px-4">
                                        <span class="flex items-center">Time</span>
                                    </th>
                                    <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(6)">
                                        <span class="flex items-center">Status
                                            <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                            </svg>
                                        </span>
                                    </th>
                                    <th class="py-3 px-4">
                                        <span class="flex items-center">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200" id="reservationTableBody">
                                <?php
                                $all_reservations_result->data_seek(0); // Reset result pointer
                                $today = date('Y-m-d');
                                $hasInReview = false;
                                $hasOtherReservations = false;

                                // Preliminary check for "In Review" reservations
                                while ($row = $all_reservations_result->fetch_assoc()) {
                                    if ($row["reservation_status"] === 'In Review') {
                                        $hasInReview = true;
                                    } else {
                                        $hasOtherReservations = true;
                                    }
                                }

                                // Reset pointer for actual display loop
                                $all_reservations_result->data_seek(0);
                                $inReviewShown = false; // Flag to check if "In Review" header has been shown
                                $otherShown = false;    // Flag to check if "Other Reservations" header has been shown

                                while ($row = $all_reservations_result->fetch_assoc()) {
                                    $reservationId = $row["id"];
                                    $reservationStatus = $row["reservation_status"];
                                    $isPending = ($reservationStatus === 'In Review');

                                    // Convert start and end times to 12-hour format with AM/PM
                                    $startTime = new DateTime($row["start_time"]);
                                    $formattedStartTime = $startTime->format('g:i A');

                                    $endTime = new DateTime($row["end_time"]);
                                    $formattedEndTime = $endTime->format('g:i A');

                                    $statusClass = ($reservationStatus === 'Declined') ? 'text-red-600 bg-red-100' : '';

                                    // Show "In Review" header only if there are "In Review" reservations
                                    if ($isPending && $hasInReview && !$inReviewShown) {
                                        echo '<tr><td colspan="8" class="bg-yellow-200 text-yellow-800 font-bold py-2 px-4">Pending Reservations</td></tr>';
                                        $inReviewShown = true;
                                    }

                                    // Show "Other Reservations" header only if there are other reservations and at least one "In Review" reservation
                                    if (!$isPending && $hasInReview && $hasOtherReservations && !$otherShown) {
                                        echo '<tr><td colspan="8" class="bg-green-100 text-gray-800 font-bold py-2 px-4 mt-4">Other Reservations</td></tr>';
                                        $otherShown = true;
                                    }

                                    echo '<tr class="' . $statusClass . '">';
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($row["building"]) . ' ' . htmlspecialchars($row["facility_name"]) . '</td>';
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($row["purpose"]) . '</td>';
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($row["facultyInCharge"]) . '</td>';
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($row["first_name"]) . ' ' . htmlspecialchars($row["last_name"]) .  '</td>';
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($row["reservation_date"]) . '</td>';
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($formattedStartTime) . ' - ' . htmlspecialchars($formattedEndTime) . '</td>';
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($row["reservation_status"]) . '</td>';
                                    echo '<td class="py-2 px-4 space-x-3">';

                                    // Always show all buttons with different states based on reservation status
                                    if ($isPending) {
                                        // Approve and Reject buttons for In Review reservations
                                        echo '<button onclick="acceptReservation(' . $reservationId . ')" class="text-blue-500 hover:text-blue-600" title="Approve"><i class="fa-regular fa-circle-check"></i></button>';
                                        echo '<button onclick="declineReservation(' . $reservationId . ')"  class="text-red-500 hover:text-red-600" title="Reject"><i class="fa-solid fa-ban"></i></button>';
                                        
                                    } else {
                                        
                                        // Active buttons for other statuses
                                        if ($reservationStatus === 'Expired') {
                                            echo '<button disabled class="text-gray-300 cursor-not-allowed" title="Cancel Reservation"><i class="fas fa-times-circle"></i></button>';
                                            echo '<button disabled class="text-gray-300 cursor-not-allowed" title="Edit"><i class="fas fa-edit"></i></button>';
                                            echo '<button onclick="deleteReservation(' . $reservationId . ')" class="text-red-500 hover:text-red-600" title="Delete"><i class="fas fa-trash-alt"></i></button>';
                                        } elseif ($reservationStatus === 'Approved') {
                                            echo '<button onclick="cancelReservation(' . $reservationId . ')" class="text-yellow-500 hover:text-yellow-600" title="Cancel Reservation"><i class="fas fa-times-circle"></i></button>';
                                            echo '<button disabled class="text-gray-300 cursor-not-allowed" title="Edit"><i class="fas fa-edit"></i></button>';
                                            echo '<button disabled class="text-gray-300 cursor-not-allowed" title="Delete"><i class="fas fa-trash-alt"></i></button>';
                                        } elseif ($reservationStatus === 'Declined' || $reservationStatus === 'Cancelled') {
                                            echo '<button disabled class="text-gray-300 cursor-not-allowed" title="Cancel Reservation"><i class="fas fa-times-circle"></i></button>';
                                            echo '<button onclick="editReservation(' . $reservationId . ')" class="text-blue-500 hover:text-blue-600" title="Edit"><i class="fas fa-edit"></i></button>';
                                            echo '<button onclick="deleteReservation(' . $reservationId . ')" class="text-red-500 hover:text-red-600" title="Delete"><i class="fas fa-trash-alt"></i></button>';
                                        } else {
                                            echo '<button onclick="cancelReservation(' . $reservationId . ')" class="text-gray-500 hover:text-gray-600" title="Cancel Reservation"><i class="fas fa-times-circle"></i></button>';
                                            echo '<button onclick="editReservation(' . $reservationId . ')" class="text-blue-500 hover:text-blue-600" title="Edit"><i class="fas fa-edit"></i></button>';
                                            echo '<button onclick="deleteReservation(' . $reservationId . ')" class="text-red-500 hover:text-red-600" title="Delete"><i class="fas fa-trash-alt"></i></button>';
                                        }
                                    }

                                    echo '</td>';
                                    echo '</tr>';
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

    <!-- HTML for custom confirmation dialog -->
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
    <!-- Edit Reservation Modal -->
    <div id="EditReservationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white p-8 rounded-md shadow-md">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold">Edit Reservation</h2>
                <button id="closeModal" class="text-gray-600 hover:text-gray-800 focus:outline-none" onclick="closeModal()">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <?php
                function generateTimeOptions() {
                    $times = [];
                    $start = strtotime('07:00 AM');
                    $end = strtotime('10:00 PM');
                    $interval = 30 * 60; // 30 minutes in seconds

                    for ($current = $start; $current <= $end; $current += $interval) {
                        $time = date('h:i A', $current);
                        $times[] = $time;
                    }

                    return $times;
                }

                $timeOptions = generateTimeOptions();
            ?>
            <form id="reservationForm" class="space-y-4">
                <!-- Rejection reason container (hidden by default) -->
                <div id="rejectionReasonContainer" class="flex items-center space-x-2" style="display:none;">
                    <label for="rejectionReason" class="font-semibold text-red-600 flex items-center">
                        Rejection Reason: 
                        <span id="rejectionReason" class="ml-1 text-red-600"></span>
                    </label>
                </div>
                <input type="hidden" id="facilityId" />

                <div class="flex mb-4 gap-2">
                    <div class="w-1/2">
                        <div class="flex flex-col space-y-2">
                            <label for="facilityName" class="text-gray-700">Facility Name:</label>
                            <input type="text" id="facilityName" name="facilityName" class="border border-gray-300 bg-gray-300 rounded-md p-2" readonly required>
                        </div>
                    </div>
                    <div class="w-1/2">
                        <div class="flex flex-col space-y-2">
                            <label for="reservationDate" class="text-gray-700">Reservation Date:</label>
                            <input type="date" id="reservationDate" name="reservationDate" class="border border-gray-300 rounded-md p-2" required>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col space-y-2 hidden">
                    <label for="department" class="text-gray-700">Department:</label>
                    <input type="text" id="department" name="department" class="border border-gray-300 rounded-md p-2" readonly>
                </div>
                <div class="flex mb-4 gap-2">
                    <div class="w-1/2">
                        <div class="flex flex-col space-y-2">
                            <label for="startTime" class="text-gray-700">Starting Time:</label>
                            <select id="startTime" name="startTime" class="border border-gray-300 rounded-md p-2" required>
                                <?php foreach ($timeOptions as $time): ?>
                                    <option value="<?php echo $time; ?>" <?php echo (isset($row['start_time']) && $time == $row['start_time']) ? 'selected' : ''; ?>>
                                        <?php echo $time; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="w-1/2">
                        <div class="flex flex-col space-y-2">
                            <label for="endTime" class="text-gray-700">End Time:</label>
                            <select id="endTime" name="endTime" class="border border-gray-300 rounded-md p-2" required>
                                <?php foreach ($timeOptions as $time): ?>
                                    <option value="<?php echo $time; ?>" <?php echo (isset($row['end_time']) && $time == $row['end_time']) ? 'selected' : ''; ?>>
                                        <?php echo $time; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col space-y-2">
                    <label for="facultyInCharge" class="text-gray-700">Faculty in Charge:</label>
                    <input type="text" id="facultyInCharge" name="facultyInCharge" class="border border-gray-300 rounded-md p-2" required>
                </div>
                <div class="flex flex-col space-y-2">
                    <label for="purpose" class="text-gray-700">Purpose:</label>
                    <input type="text" id="purpose" name="purpose" class="border border-gray-300 rounded-md p-2">
                </div>
                <div class="flex flex-col space-y-2">
                    <label for="additionalInfo" class="text-gray-700">Additional Information:</label>
                    <textarea id="additionalInfo" name="additionalInfo" class="border border-gray-300 rounded-md p-2"></textarea>
                </div>
                <div class="flex justify-between">
                    <button type="button" id="saveChangesButton" class="bg-blue-500 text-white rounded-md px-4 py-2 hover:bg-blue-600" onclick="saveChanges()">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reservations Modal -->
    <div id="reservationsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-md">
            <div class="font-bold text-xl mb-4">Reservation Details</div>
            <div id="modalContent" class="text-gray-800">
                <p><strong>Facility Name:</strong> <span id="facilityName"></span></p>
                <p><strong>Reservation Date:</strong> <span id="reservationDate"></span></p>
                <p><strong>Start Time:</strong> <span id="startTime"></span></p>
                <p><strong>End Time:</strong> <span id="endTime"></span></p>
                <!-- Add more details as needed -->
            </div>
            <div class="flex justify-center mt-5">
                <button onclick="closeModal()" class="mr-4 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-400">Close</button>
            </div>
        </div>
    </div>

    <!--Rejection reason modal-->
    <div id="rejectionReasonForm" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white p-8 rounded-md shadow-md">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold">Enter reason for rejection:</h2>
            </div>
            <div>
                <form id="reservationForm" class="space-y-4">
                    <div class="flex flex-col space-y-2">
                        <textarea id="rejectionReasonText" name="rejectionReasonText" rows="3" class="border border-gray-300 rounded-md p-2" required></textarea>
                    </div>
                </form>
                <button onclick="location.reload();" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Cancel</button>
                <button id="confirmRejectionButton" class="px-4 py-2 mt-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Okay</button>
            </div>
        </div>
    </div>

    <!-- confirmation modal -->
    <div id="confirmationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-md flex flex-col items-center">
            <p id="confirmationMessage" class="text-lg text-slate-700 font-semibold mb-4"></p>
            <div class="flex justify-center mt-5">
                <button onclick="cancelAction()" class="mr-4 px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">Cancel</button>
                <button onclick="confirmAction()" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">Confirm</button>
            </div>
        </div>
    </div>

    <!-- HTML for success modal -->
    <div id="successModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-md flex flex-col items-center">
            <p id="successMessage" class="text-lg text-slate-700 font-semibold mb-4"></p>
            <div class="flex justify-center mt-5">
                <button onclick="location.reload()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">OK</button>
            </div>
        </div>
    </div>

    <!-- HTML for error message modal -->
    <div id="errorMessageModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-md flex flex-col items-center">
            <p id="errorMessageContent" class="text-lg text-red-700 font-semibold mb-4"></p>
            <div class="flex justify-center mt-5">
                <button onclick="hideErrorMessage()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">OK</button>
            </div>
        </div>
    </div>
    <div id="loadingIcon" class="hidden fixed inset-0 flex justify-center items-center bg-gray-800 bg-opacity-50">
        <div class="loader"></div>
    </div>



    <script src="scripts/logout.js"></script>
    <script src="scripts/functions.js"></script>
    <script>
        let currentReservationId;  // Declare a variable to store the current reservation ID

        // Edit Reservation
        function editReservation(id) {
            // Store the reservation ID for future use when saving changes
            currentReservationId = id;

            // Make an AJAX request to fetch the reservation details from the server using the reservation ID
            fetch(`handlers/fetch_reservation.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    console.log(data);  // Log the retrieved data to the console for debugging

                    // Populate the form fields with the fetched data
                    document.getElementById('facilityName').value = data.facility_name;
                    document.getElementById('reservationDate').value = data.reservation_date;
                    document.getElementById('startTime').value = data.start_time;
                    document.getElementById('endTime').value = data.end_time;
                    document.getElementById('facultyInCharge').value = data.facultyInCharge;
                    document.getElementById('purpose').value = data.purpose;
                    document.getElementById('additionalInfo').value = data.additional_info;
                        document.getElementById('facilityId').value = data.facility_id;
                    // If the status is 'Rejected', show the rejection reason
                    if (data.reservation_status === 'Declined') {
                        document.getElementById('rejectionReasonContainer').style.display = 'block';
                        document.getElementById('rejectionReason').textContent = data.rejection_reason;
                    } else {
                        // Hide the rejection reason if it's not rejected
                        document.getElementById('rejectionReasonContainer').style.display = 'none';
                    }

                    // Show the modal
                    document.getElementById('EditReservationModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorMessage('An error occurred while fetching reservation details.');
                });
        }

        // Save changes to reservation
        function saveChanges() {
            // Gather the updated form values
            const facilityName = document.getElementById('facilityName').value;
            const reservationDate = document.getElementById('reservationDate').value;
            const startTime = document.getElementById('startTime').value;
            const endTime = document.getElementById('endTime').value;
            const facultyInCharge = document.getElementById('facultyInCharge').value;
            const purpose = document.getElementById('purpose').value;
            const additionalInfo = document.getElementById('additionalInfo').value;
            const rejectionReason = document.getElementById('rejectionReason').textContent;

            // Update reservation status to 'In Review'
            const updatedReservationStatus = 'Approved';
            const facilityId = document.getElementById('facilityId').value; // Get facilityId from the hidden input

            // Construct the reservation data object, including the reservation ID
            const updatedReservation = {
                reservationId: currentReservationId,  // Include the ID of the reservation
                facilityId: facilityId,     
                facilityName: facilityName,
                reservationDate: reservationDate,
                startTime: startTime,
                endTime: endTime,
                facultyInCharge: facultyInCharge,
                purpose: purpose,
                additionalInfo: additionalInfo,
                rejectionReason: rejectionReason,
                reservationStatus: updatedReservationStatus // Set reservation status to 'In Review'
            };

            // Make an AJAX request to update the reservation on the server
            fetch('handlers/update_reservation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(updatedReservation),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal('Reservation updated successfully!');
                    // Hide the modal and refresh the reservation list or calendar
                    closeModal();
                    setTimeout(() => {
                        location.reload(); // Reload the current page after success
                    }, 3000); // 3000 milliseconds = 3 seconds
                } else {
                    showErrorMessage('Error updating reservation: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('An error occurred while saving the reservation.');
            });
        }
        // Utility functions to show/hide modals
        function showConfirmModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }
                // Close modal
        function closeModal(modalId) {
            hideModal(modalId);
        }
        // Delete reservation
        function deleteReservation(reservationId) {
            // Show confirmation dialog
            showConfirmation("Are you sure you want to delete this reservation?", function() {
                // Make an AJAX request to delete the reservation
                fetch('handlers/delete_reservation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: reservationId })
                })
                .then(response => {
                    if (response.ok) {
                        showSuccessModal('Reservation deleted successfully!');
                        setTimeout(() => {
                            location.reload(); // Reload the current page after success
                        }, 3000); // 3000 milliseconds = 3 seconds
                    } else {
                        showErrorMessage('Failed to delete the reservation. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorMessage('An error occurred while deleting the reservation.');
                });
            });
        }
        

        // Cancel Reservation
        function cancelReservation(reservationId) {
            // Show confirmation modal with the message
            document.getElementById('confirmationMessage').textContent = "Are you sure you want to cancel this reservation?";
            showConfirmModal('confirmationModal');

            // Set action for confirmation button
            window.confirmAction = function() {
                // Send request to the server to cancel the reservation
                fetch('handlers/cancel_reservation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: reservationId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message and reload the page
                        showSuccessMessage('Reservation cancelled successfully!');
                        setTimeout(() => {
                            location.reload(); // Reload the current page after success
                        }, 3000);
                    } else {
                        // Show error message if cancellation fails
                        showErrorMessage(data.message || 'Failed to cancel the reservation. Please try again.');
                    }
                })
                .catch(error => {
                    // Handle network or server errors
                    console.error('Error:', error);
                    showErrorMessage('An error occurred while cancelling the reservation.');
                })
                .finally(() => {
                    hideModal('confirmationModal'); // Always hide the modal after an action
                });
            };

            // Set action for cancel button
            window.cancelAction = function() {
                hideModal('confirmationModal'); // Simply hide the modal if canceled
            };
        }

        // Close modal
        function closeModal() {
            document.getElementById('EditReservationModal').classList.add('hidden');
        }

        // Function to show modal with reservation details
        function showModal(event) {
            console.log('Showing modal for event:', event);
            const modal = document.getElementById('reservationsModal');
            const modalContent = modal.querySelector('#modalContent');

            // Convert start and end dates to local time
            const startDate = new Date(event.start);
            const endDate = new Date(event.end);

            // Format options for date and time
            const dateOptions = {
                weekday: 'short', 
                year: 'numeric', 
                month: 'numeric', 
                day: 'numeric',
            };

            const timeOptions = {
                hour: 'numeric',
                minute: 'numeric',
                hour12: true
            };

            modalContent.innerHTML = `
                <p><strong>Facility Name:</strong> ${event.title}</p>
                <p><strong>Reservation Date:</strong> ${startDate.toLocaleDateString(undefined, dateOptions)}</p>
                <p><strong>Start Time:</strong> ${startDate.toLocaleTimeString(undefined, timeOptions)}</p>
                <p><strong>End Time:</strong> ${endDate.toLocaleTimeString(undefined, timeOptions)}</p>
                <!-- Add more details as needed -->
            `;

            modal.classList.remove('hidden');
        }

        // Function to show success modal
        function showSuccessModal(message) {
            const successModal = document.getElementById('successModal');
            const successMessage = document.getElementById('successMessage');
            successMessage.innerText = message;
            successModal.classList.remove('hidden');
        }

        // Function to hide success modal
        function hideSuccessModal() {
            const successModal = document.getElementById('successModal');
            successModal.classList.add('hidden');
            setTimeout(() => {
                location.reload(); // Reload the current page after success
            }, 3000); // 3000 milliseconds = 3 seconds
        }

        // Function to show confirmation modal
        function showConfirmation(message, callback) {
            const confirmationModal = document.getElementById('confirmationModal');
            const confirmationMessage = document.getElementById('confirmationMessage');
            confirmationMessage.innerText = message;
            confirmationModal.classList.remove('hidden');
            confirmActionCallback = callback;
        }

        // Function to hide confirmation modal
        function hideConfirmation() {
            const confirmationModal = document.getElementById('confirmationModal');
            confirmationModal.classList.add('hidden');
        }

        // Function to handle confirmation action
        function confirmAction() {
            const rejectionReasonForm = document.getElementById('rejectionReasonForm');
            rejectionReasonForm.classList.add('hidden');
            hideConfirmation();
            if (confirmActionCallback) {
                confirmActionCallback();
            }
        }

        // Function to handle cancellation of action
        function cancelAction() {
            hideConfirmation();
        }

        let confirmActionCallback;

        // Function to show error message in a modal
        function showErrorMessage(message) {
            const errorMessageModal = document.getElementById('errorMessageModal');
            const errorMessageContent = document.getElementById('errorMessageContent');
            
            // Set the error message content
            errorMessageContent.innerText = message;
            
            // Show the error message modal
            errorMessageModal.classList.remove('hidden');
        }

        // Function to hide the error message modal
        function hideErrorMessage() {
            const errorMessageModal = document.getElementById('errorMessageModal');
            errorMessageModal.classList.add('hidden');
        }

        // Function to handle accepting reservation
// Function to handle accepting reservation
function acceptReservation(reservationId) {
    console.log('Accept reservation:', reservationId);
    fetch('handlers/check_reservation_overlap.php?id=' + reservationId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            console.error('Error checking reservation overlap:', data.error);
            showErrorMessage('Error checking reservation overlap. Please try again.');
        } else if (data.overlap) {
            showErrorMessage('There is a reservation conflict. Please select another time slot.');
        } else {
            showConfirmation('Are you sure you want to accept this reservation?', function() {
                // Send AJAX request to accept reservation
                    // Show the loading icon
                document.getElementById('loadingIcon').classList.remove('hidden');
                fetch('handlers/update_reservation_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: reservationId, // Include reservation ID
                        status: 'Approved' // Set the desired status here
                    })
                })
                .then(response => response.json())
                .then(data => {
                    // Hide loading icon after success or failure
                    document.getElementById('loadingIcon').classList.add('hidden');
                    
                    if (data.success) {
                        showSuccessModal('Reservation Approved!');
                        setTimeout(() => {
                            location.reload(); // Reload the current page after success
                        }, 3000); // 3000 milliseconds = 3 seconds
                    } else {
                        showErrorMessage('Failed to approve reservation. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Hide loading icon on error
                    document.getElementById('loadingIcon').classList.add('hidden');
                    showErrorMessage('An error occurred while accepting the reservation.');
                });
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Hide loading icon on error
        document.getElementById('loadingIcon').classList.add('hidden');
        showErrorMessage('An error occurred while checking the reservation.');
    });
}

// Function to decline reservation
function declineReservation(reservationId) {
    console.log("Decline button clicked", reservationId);
    const rejectionReasonForm = document.getElementById('rejectionReasonForm');
    rejectionReasonForm.classList.remove('hidden');

    const confirmButton = document.getElementById('confirmRejectionButton');
    confirmButton.onclick = function() {
        const rejectionReason = document.getElementById('rejectionReasonText').value;
        console.log('Sending rejection reason:', rejectionReason);

        showConfirmation('Are you sure you want to decline this reservation?', function() {
            // Show the loading icon before sending the request
            document.getElementById('loadingIcon').classList.remove('hidden');
            
            // Send reservation ID, status, and rejection reason in the request body
            fetch('handlers/update_reservation_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: reservationId,           // Include reservation ID
                    status: 'Declined',          // Include status as Declined
                    reason: rejectionReason       // Include rejection reason
                })
            })
            .then(response => response.json())
            .then(data => {
                // Hide loading icon after success or failure
                document.getElementById('loadingIcon').classList.add('hidden');
                
                if (data.success) {
                        location.reload(); // Reload the current page after success
                } else {
                    showModal({ title: 'Error declining reservation' });
                }
            })
            .catch(error => {
                // Hide loading icon on error
                document.getElementById('loadingIcon').classList.add('hidden');
                console.error('Error:', error);
                showModal({ title: 'Error declining reservation' });
            });
        });
    };
}



    </script>

</body>
</html>
