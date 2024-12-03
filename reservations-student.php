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
if ($_SESSION['role'] !== 'Student Rep') {
    // Redirect to a page indicating unauthorized access
        header("Location: unauthorized");
    exit();
}

// Fetch reservations from the database for the current user
include_once 'database/config.php';

// Fetch the user ID from the session data
$user_id = $_SESSION['user_id'];

// Fetch all reservations
$all_reservations_sql = "
    SELECT 
        r.*, 
        f.facility_name, 
        f.building 
    FROM 
        reservations r 
    JOIN 
        facilities f 
    ON 
        r.facility_id = f.facility_id
    WHERE 
        r.user_id = $user_id 
    ORDER BY 
        r.created_at DESC";
$all_reservations_result = $conn->query($all_reservations_sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLV: RESERVA</title>
        <link rel="stylesheet" href="css/style.css">
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        <!-- Flatpickr CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <!-- Flatpickr JS -->
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Automatically filter reservations on page load
            filterReservations();
        });


        function filterReservations() {
            const searchQuery = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
            const facilityRows = document.querySelectorAll('#eventsTable tbody tr');

            facilityRows.forEach(row => {
                const facilityName = row.cells[1].textContent.toLowerCase(); // Facility name in the first column
                const reservationStatus = row.cells[5].textContent.toLowerCase(); // Reservation status in the fifth column
                
                // Determine if the row should be shown based on search and status filters
                const matchesSearch = facilityName.includes(searchQuery);
                let matchesStatus = false;

                // Exact matching for statusFilter
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
                    matchesStatus = reservationStatus === 'expired';
                } else {
                    // Default to showing 'In Review' and 'Reserved' statuses
                    matchesStatus = (reservationStatus === 'in review' || reservationStatus === 'approved');
                }

                // Show or hide the row based on matches
                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
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

        document.addEventListener("DOMContentLoaded", function() {
            const holidayAPIUrl = "https://www.googleapis.com/calendar/v3/calendars/en.philippines%23holiday%40group.v.calendar.google.com/events?key=AIzaSyCB7rRha3zbgSYH1aD5SECsRvQ3usacZHU"; // Your API endpoint

            fetch(holidayAPIUrl)
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                    // Extract holiday dates from the API response
                    const holidayDates = data.items.map(holiday => holiday.start.date);

                    // Initialize Flatpickr with disabled holiday dates
                    flatpickr("#reservationDate", {
                        dateFormat: "Y-m-d",
                        enableTime: false,

                        onDayCreate: function(dObj, dStr, fp, dayElem) {
                            const date = dayElem.dateObj; // Get the date of the current day element
                            const dateString = dayElem.dateObj.toISOString().split('T')[0]; // Get date in YYYY-MM-DD format

                            // Add custom class for holidays
                            if (holidayDates.includes(dateString)) {
                                dayElem.classList.add("holiday"); // Add class for holidays
                            }

                            // Add custom class for Sundays
                            if (date.getDay() === 0) {
                                dayElem.classList.add("sunday"); // Add class for Sundays
                            }
                        },
                        onChange: function(selectedDates, dateStr, instance) {
                            const selectedDate = new Date(dateStr); // Convert selected date string to Date object
                            const today = new Date(); // Get today's date
                            today.setHours(0, 0, 0, 0); // Reset hours, minutes, seconds for comparison

                            // Create a new Date object for the selected date without time components
                            const selectedDateNoTime = new Date(selectedDate);
                            selectedDateNoTime.setHours(0, 0, 0, 0); // Reset hours, minutes, seconds for comparison

                            console.log("Today: ", today);
                            console.log("Selected date (no time): ", selectedDateNoTime);

                            // Check if selected date is a holiday
                            if (holidayDates.includes(dateStr)) {
                                showToast(`${dateStr} is a holiday and cannot be selected.`); // Show toast for holiday
                                instance.clear(); // Optionally clear the selection
                            } 
                            // Check if the selected date is today
                            else if (selectedDateNoTime.getTime() === today.getTime()) {
                                showToast("Same day reservations are not allowed."); // Show toast for same day reservation
                                instance.clear(); // Optionally clear the selection
                            } 
                            // Check if the selected date is in the past
                            else if (selectedDateNoTime < today) {
                                showToast(`${dateStr} is a past date and cannot be selected.`); // Show toast for past dates
                                instance.clear(); // Optionally clear the selection
                            }     // Check if the selected date is a Sunday
                            else if (selectedDate.getDay() === 0) { // Sunday is represented by 0
                                showToast(`${dateStr} falls on a Sunday and cannot be selected.`); // Show toast for Sunday
                                instance.clear(); // Optionally clear the selection
                            } else {
                                console.log("Selected date: ", dateStr); // Handle the selected date
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error("Error fetching holiday data:", error);
                });

            function showToast(message) {
                const toast = document.getElementById("toast");
                const toastMessage = document.getElementById("toastMessage");

                toastMessage.textContent = message; // Set the toast message
                toast.classList.remove("hidden"); // Show the toast

                // Hide the toast after 3 seconds
                setTimeout(() => {
                    toast.classList.add("hidden");
                }, 3000);
            }
        });
    </script>
    <style>
        /* Custom styles for holidays */
        .flatpickr-day.holiday {
            background-color: #ffcccc; /* Light red background */
            color: #d9534f; /* Dark red text */
        }

        /* Custom styles for Sundays */
        .flatpickr-day.sunday {
            background-color: #ccf2ff; /* Light blue background */
            color: #007bff; /* Blue text */
        }
        #custom-dialog, #toast {
            z-index: 10000; /* Ensures the logout modal appears on top of everything */
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
                    <h2 class="text-lg font-semibold">My Reservations</h2>
                </div>
            </header>
            <!-- Main content area -->
            <main class="flex-1 p-4 overflow-y-auto">
                <div class="w-full" class="overflow-y-auto max-h-[calc(100vh-200px)]">
                    <div class="flex items-center space-x-4 mb-4">
                        <select id="buildingSelect" class="px-4 py-2 border border-gray-300 rounded-md" onchange="filterReservations()">
                            <option value="" disabled selected>Buildings</option>
                            <option value="">All Buildings</option>
                            <?php    // Fetch buildings for the dropdown
                                $buildings_sql = "SELECT DISTINCT building FROM facilities";
                                $buildings_result = $conn->query($buildings_sql);
                                while ($building = $buildings_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($building['building']); ?>">
                                    <?php echo htmlspecialchars($building['building']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-md" onchange="filterReservations()">
                            <option value="" disabled selected>Select Status</option> <!-- Placeholder option -->
                            <option value="all">All</option>
                            <option value="in review">In Review</option>
                            <option value="approved">Approved</option>
                            <option value="declined">Declined</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="expired">Expired</option>
                        </select>
                        <input type="text" id="searchInput" class="px-4 py-2 border border-gray-300 rounded-md" placeholder="Search..." onkeyup="filterReservations()">
                    </div>
                    <table id="eventsTable" class="min-w-full overflow-y-auto max-h-[calc(100vh-200px)] bg-white rounded-md shadow-md border border-gray-200">
                        <thead>
                            <tr class="bg-gray-200 border-b">
                                <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(0)">
                                    <span class="flex items-center">Building
                                        <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                        </svg>
                                    </span>
                                </th>
                                <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(1)">
                                    <span class="flex items-center">Facility Name
                                        <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                        </svg>
                                    </span>
                                </th>
                                <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(2)">
                                    <span class="flex items-center">Reservation Date
                                        <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                        </svg>
                                    </span>
                                </th>
                                <th class="py-3 px-4">
                                    <span class="flex items-center">Time</span>
                                </th>
                                <th class="py-3 px-4">
                                    <span class="flex items-center">Purpose</span>
                                </th>
                                <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(4)">
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
                            $all_reservations_result->data_seek(0);
                            $today = date('Y-m-d');
                            while ($row = $all_reservations_result->fetch_assoc()) {
                                $reservationId = $row["id"];
                                $reservationStatus = $row["reservation_status"];
                                $reservationDate = $row["reservation_date"];
                                $isExpired = strtotime($reservationDate) < strtotime($today);

                                // Convert times to 12-hour format
                                $startTime = new DateTime($row["start_time"]);
                                $formattedStartTime = $startTime->format('g:i A');
                                $endTime = new DateTime($row["end_time"]);
                                $formattedEndTime = $endTime->format('g:i A');
                                

                                // Determine status classes for highlighting
                                    $statusClass = match($reservationStatus) {
                                        'In Review' => 'bg-blue-50 text-blue-800',
                                        'Cancelled' => 'bg-yellow-50 text-yellow-800',
                                        'Declined' => 'bg-red-50 text-red-800',
                                        'Approved' => 'bg-green-50 text-green-800',
                                        default => ''
                                    };
                                ?>
                                <tr>
                                    <td class="border py-2 px-4"><?php echo htmlspecialchars($row["building"]); ?></td>
                                    <td class="border py-2 px-4"><?php echo htmlspecialchars($row["facility_name"]); ?></td>
                                    <td class="border py-2 px-4"><?php echo htmlspecialchars($row["reservation_date"]); ?></td>
                                    <td class="border py-2 px-4"><?php echo htmlspecialchars($formattedStartTime) . ' - ' . htmlspecialchars($formattedEndTime); ?></td>
                                    <td class="border py-2 px-4"><?php echo htmlspecialchars($row["purpose"]); ?></td>                                    
                                    <td class="border py-2 px-4"><?php 
                                        switch ($row["reservation_status"]) {
                                            case "In Review":
                                                echo '<span class="inline-block px-3 py-1 text-sm font-medium text-black bg-yellow-400 rounded-full">In Review</span>';
                                                break;
                                            case "Approved":
                                                echo '<span class="inline-block px-3 py-1 text-sm font-medium text-white bg-green-600 rounded-full">Approved</span>';
                                                break;
                                            case "Declined":
                                                echo '<span class="inline-block px-3 py-1 text-sm font-medium text-white bg-red-500 rounded-full">Declined</span>';
                                                break;
                                            case "Cancelled":
                                                echo '<span class="inline-block px-3 py-1 text-sm font-medium text-black bg-gray-400 rounded-full">Cancelled</span>';
                                                break;
                                            default:
                                                echo '<span class="inline-block px-3 py-1 text-sm font-medium text-black bg-blue-300 rounded-full">' . htmlspecialchars($row["reservation_status"]) . '</span>';
                                                break;
                                        }
                                    ?></td>
                                    <td class="py-2 px-4 space-x-2">
                                        <?php
                                    // Define button states based on conditions
                                    $canEdit = $reservationStatus === 'In Review' || $reservationStatus === 'Declined'|| $reservationStatus === 'Cancelled' ;
                                    $canDelete = $reservationStatus === 'In Review' || $isExpired || $reservationStatus === 'Declined'|| $reservationStatus === 'Cancelled';
                                    $canCancel = $reservationStatus === 'Approved';

                                        // Helper function to generate button classes
                                        $getButtonClasses = function($isEnabled, $baseColor) {
                                            return $isEnabled 
                                                ? "text-{$baseColor}-500 hover:text-{$baseColor}-600 cursor-pointer" 
                                                : "text-gray-300 cursor-not-allowed";
                                        };
                                        ?>

                                        <!-- Cancel Button -->
                                        <button 
                                            onclick="<?php echo $canCancel ? "cancelReservation($reservationId)" : ''; ?>"
                                            class="<?php echo $getButtonClasses($canCancel, 'yellow'); ?>"
                                            <?php echo !$canCancel ? 'title="Unauthorized Action"' : 'title="Cancel Reservation"'; ?>
                                        >
                                            <i class="fas fa-times-circle"></i>
                                        </button>

                                        <!-- Edit Button -->
                                        <button 
                                            onclick="<?php echo $canEdit ? "editReservation($reservationId)" : ''; ?>"
                                            class="<?php echo $getButtonClasses($canEdit, 'blue'); ?>"
                                            <?php echo !$canEdit ? 'title="Unauthorized Action"' : 'title="Edit"'; ?>
                                        >
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <!-- Delete Button -->
                                        <button 
                                            onclick="<?php echo $canDelete ? "deleteReservation($reservationId)" : ''; ?>"
                                            class="<?php echo $getButtonClasses($canDelete, 'red'); ?>"
                                            <?php echo !$canDelete ? 'title="Unauthorized Action"' : 'title="Delete"'; ?>
                                        >
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>

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
    <div id="toast" class="fixed top-4 right-4 bg-red-400 text-white text-sm p-3 rounded-lg hidden">
        <span id="toastMessage"></span>
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
                <button id="closeModal" class="text-gray-600 hover:text-gray-800 focus:outline-none" onclick="hideModal('EditReservationModal')">
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
                        <label for="reservationDate" class="block text-gray-700 text-xs">Reservation Date:<span class="text-red-500">*</span></label>
                        <input type="text" id="reservationDate" name="reservationDate" class="w-full px-3 py-2 rounded-md border border-gray-300" required onchange="validateDate()">
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
                    <textarea id="additionalInfo" name="additionalInfo" class="border border-gray-300 rounded-md p-2" required></textarea>
                </div>
                <div class="flex justify-between">
                    <button type="button" id="saveChangesButton" class="bg-blue-500 text-white rounded-md px-4 py-2 hover:bg-blue-600" onclick="saveChanges()">Save Changes</button>
                </div>
            </form>
        </div>
    </div>


        <!-- HTML for custom confirmation dialog -->
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
                    <button onclick="hideModal('errorMessageModal')" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">OK</button>
                </div>
            </div>
        </div>


    <script src="scripts/logout.js"></script>
    <script src="scripts/functions.js"></script>
    <script>
        let currentReservationId;  // Declare a variable to store the current reservation ID

        // Utility functions to show/hide modals
        function showModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        function hideModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        // Edit Reservation
        function editReservation(id) {
            currentReservationId = id;
            fetch(`handlers/fetch_reservation.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                    if (data.reservation_status === 'Declined' || data.reservation_status === 'In Review') {
                        document.getElementById('facilityName').value = data.facility_name;
                        document.getElementById('reservationDate').value = data.reservation_date;
                        document.getElementById('startTime').value = data.start_time;
                        document.getElementById('endTime').value = data.end_time;
                        document.getElementById('facultyInCharge').value = data.facultyInCharge;
                        document.getElementById('purpose').value = data.purpose;
                        document.getElementById('additionalInfo').value = data.additional_info;
                         // Add facilityId to a hidden input field or another appropriate way to capture it
                        document.getElementById('facilityId').value = data.facility_id;

                        if (data.reservation_status === 'Declined') {
                            document.getElementById('rejectionReasonContainer').style.display = 'block';
                            document.getElementById('rejectionReason').textContent = data.rejection_reason;
                        } else {
                            document.getElementById('rejectionReasonContainer').style.display = 'none';
                        }

                        // Show the modal
                        showModal('EditReservationModal');
                    } else {
                        showErrorMessage('You can only edit reservations that are Rejected or In Review.');
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Save changes to reservation
        function saveChanges() {
            const facilityName = document.getElementById('facilityName').value;
            const reservationDate = document.getElementById('reservationDate').value;
            const startTime = document.getElementById('startTime').value;
            const endTime = document.getElementById('endTime').value;
            const facultyInCharge = document.getElementById('facultyInCharge').value;
            const purpose = document.getElementById('purpose').value;
            const additionalInfo = document.getElementById('additionalInfo').value;
            const rejectionReason = document.getElementById('rejectionReason').textContent;

            const updatedReservationStatus = 'In Review';
            const facilityId = document.getElementById('facilityId').value; // Get facilityId from the hidden input

            const updatedReservation = {
                reservationId: currentReservationId,
                facilityId: facilityId,                
                facilityName: facilityName,
                reservationDate: reservationDate,
                startTime: startTime,
                endTime: endTime,
                facultyInCharge: facultyInCharge,
                purpose: purpose,
                additionalInfo: additionalInfo,
                rejectionReason: rejectionReason,
                reservationStatus: updatedReservationStatus
            };

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
                    showSuccessMessage('Reservation updated successfully!');
                    closeModal('EditReservationModal'); // Close the edit modal
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

        // Delete reservation
        function deleteReservation(reservationId) {
            // Show confirmation modal
            document.getElementById('confirmationMessage').textContent = "Are you sure you want to delete this reservation?";
            showModal('confirmationModal');

            // Set action for confirmation button
            window.confirmAction = function() {
                fetch('handlers/delete_reservation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: reservationId })
                })
                .then(response => {
                    if (response.ok) {
                        showSuccessMessage('Reservation deleted successfully!');
                        setTimeout(() => {
                            location.reload(); // Reload the current page after success
                        }, 3000); // 3000 milliseconds = 3 seconds // Reload the page if deletion is successful
                    } else {
                        showErrorMessage('Failed to delete the reservation. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
                hideModal('confirmationModal'); // Hide confirmation modal after action
            };

            window.cancelAction = function() {
                hideModal('confirmationModal'); // Hide confirmation modal if canceled
            };
        }
        // Cancel Reservation
function cancelReservation(reservationId) {
    // Show confirmation modal with the message
    document.getElementById('confirmationMessage').textContent = "Are you sure you want to cancel this reservation?";
    showModal('confirmationModal');

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


        // Show success modal
        function showSuccessMessage(message) {
            document.getElementById('successMessage').textContent = message;
            showModal('successModal');
        }

        // Show error modal
        function showErrorMessage(message) {
            document.getElementById('errorMessageContent').textContent = message;
            showModal('errorMessageModal');
        }

        // Close modal
        function closeModal(modalId) {
            hideModal(modalId);
        }
    </script>
</body>
</html>

