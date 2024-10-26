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
        r.facility_id = f.id
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
                } else if (statusFilter === 'expired') {
                    matchesStatus = reservationStatus === 'expired';
                } else {
                    // Default to showing 'In Review' and 'Reserved' statuses
                    matchesStatus = (reservationStatus === 'in review' || reservationStatus === 'approved' || reservationStatus === 'declined');
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
    </script>
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
            <main class="flex-1 p-6 overflow-y-auto">
                <div class="bg-white p-4 rounded-md shadow-md mb-6" class="overflow-y-auto max-h-[calc(100vh-200px)]">
                    <div class="flex items-center space-x-4 mb-4">
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
                    <table id="eventsTable" class="min-w-full bg-white rounded-md shadow-md border border-gray-200">
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
                                    <span class="flex items-center">Start Time</span>
                                </th>
                                <th class="py-3 px-4">
                                    <span class="flex items-center">End Time</span>
                                </th>
                                <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(5)">
                                    <span class="flex items-center">Status
                                        <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                        </svg>
                                    </span>
                                </th>
                                <th class="py-3 px-4">
                                    <span class="flex items-center">Purpose</span>
                                </th>
                                <th class="py-3 px-4">
                                    <span class="flex items-center">Actions</span>
                                </th>
                            </tr>
                        </thead>
                            <tbody class="divide-y divide-gray-200" id="reservationTableBody">
                                <?php
                                // Output reservations for the events list table
                                $all_reservations_result->data_seek(0); // Reset result pointer
                                $today = date('Y-m-d');
                                while ($row = $all_reservations_result->fetch_assoc()) {
                                    $reservationId = $row["id"];
                                    $reservationStatus = $row["reservation_status"];
                                    $reservationDate = $row["reservation_date"];
                                    $isEditable = ($reservationDate >= $today) || ($reservationStatus === 'In Review' || $reservationStatus === 'Declined');
                                    
                                    $statusClass = ($reservationStatus === 'Declined') ? 'text-red-600 bg-red-100' : '';
                                    echo '<tr class="' . $statusClass . '">';
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($row["building"]) . '</td>'; // Display the building
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($row["facility_name"]) . '</td>'; // Display the facility name
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($row["reservation_date"]) . '</td>';
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($row["start_time"]) . '</td>';
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($row["end_time"]) . '</td>';
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($row["reservation_status"]) . '</td>';
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($row["purpose"]) . '</td>';                                   
                                    if ($isEditable) {
                                        echo '<td class="py-2 px-4">';
                                        echo '<button onclick="editReservation(' . $reservationId . ')" class="bg-blue-500 text-white rounded-md px-4 py-2 hover:bg-blue-600">Edit</button>';
                                        echo '<button onclick="deleteReservation(' . $reservationId . ')" class="bg-red-500 text-white rounded-md px-4 py-2 hover:bg-red-600">Delete</button>';
                                        echo '</td>';
                                    } else {
                                        echo '<td class="py-2 px-4 text-red-500" title="Admin Access Only">Unauthorized</td>';  
                                    }
                                
                                    echo '</tr>';
                                }

                                ?>
                            </tbody>
                    </table>

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
                    <button onclick="hideErrorMessage()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">OK</button>
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

            const updatedReservation = {
                reservationId: currentReservationId,
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

