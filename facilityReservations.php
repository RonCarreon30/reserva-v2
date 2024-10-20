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
if ($_SESSION['role'] !== 'Facility Head') {
    // Redirect to a page indicating unauthorized access
    header("Location: index.html");
    exit();
}

// database config
include_once 'database/config.php';

// Create connection
$conn = new mysqli($servername, $username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the user ID from the session data
$user_id = $_SESSION['user_id'];

// Fetch reservations with status "Pending" for the Pending List
$review_reservation_sql = "SELECT * FROM reservations WHERE reservation_status = 'In Review' ORDER BY created_at DESC";
$review_reservation_result = $conn->query($review_reservation_sql);

// Fetch all reservations
$all_reservations_sql = "SELECT * FROM reservations ORDER BY start_date, start_time";
$all_reservations_result = $conn->query($all_reservations_sql);

// Fetch reservations and encode them for FullCalendar
$reservations = [];
if ($all_reservations_result->num_rows > 0) {
    while ($row = $all_reservations_result->fetch_assoc()) {
        $reservations[] = [
            'title' => $row['facility_name'],
            'start' => $row['start_date'] . 'T' . $row['start_time'],
            'end' => $row['end_date'] . 'T' . $row['end_time'],
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

        
        let currentReservationId;  // Declare a variable to store the current reservation ID

        //Edit Reservation
        function editReservation(id) {
            // Store the reservation ID for future use when saving changes
            currentReservationId = id;
            // Make an AJAX request to fetch the reservation details from the server using the reservation ID
            fetch(`handlers/fetch_reservation.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    console.log(data);  // Log the retrieved data to the console for debugging

                    // Check if the reservation status is 'Rejected' or 'In Review'
                    if (data.reservation_status === 'Declined' || data.reservation_status === 'In Review') {

                        // Populate the form fields with the fetched data
                        document.getElementById('facilityName').value = data.facility_name;
                        document.getElementById('reservationDate').value = data.reservation_date;
                        document.getElementById('startTime').value = data.start_time;
                        document.getElementById('endTime').value = data.end_time;
                        document.getElementById('facultyInCharge').value = data.facultyInCharge;
                        document.getElementById('purpose').value = data.purpose;
                        document.getElementById('additionalInfo').value = data.additional_info;

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

                    } else {
                        // If status is not 'Rejected' or 'In Review', show an error or prevent editing
                        alert('You can only edit reservations that are Rejected or In Review.');
                    }
                })
                .catch(error => console.error('Error:', error));
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
            const updatedReservationStatus = 'In Review';

            // Construct the reservation data object, including the reservation ID
            const updatedReservation = {
                reservationId: currentReservationId,  // Include the ID of the reservation
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
                    alert('Reservation updated successfully!');
                    // Hide the modal and refresh the reservation list or calendar
                    closeModal();
                    location.reload();
                } else {
                    alert('Error updating reservation: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the reservation.');
            });
        }


        // Delete reservation
        function deleteReservation(reservationId) {
            // Show a confirmation dialog
            const confirmation = confirm("Are you sure you want to delete this reservation?");
            if (confirmation) {
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
                        // If deletion is successful, remove the row from the table
                        document.getElementById('reservationTableBody').innerHTML = ''; // Clear the table
                        location.reload();
                    } else {
                        alert('Failed to delete the reservation. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        }


        // Close modal
        function closeModal() {
            document.getElementById('EditReservationModal').classList.add('hidden');
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
                    <h2 class="text-lg font-semibold">Facility Reservations</h2>
                </div>
            </header>
            <!-- Main content area -->
            <main class="flex flex-1 p-4 overflow-y-auto">
                <div class="w-3/4 p-2">
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
                    <div id="reservationsList" class="min-w-full bg-white rounded-md shadow-md border border-gray-200">
                        <table id="eventsTable" class="w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-200 border-b">
                                    <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(0)">
                                        <span class="flex items-center">Facility Name
                                            <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                            </svg>
                                        </span>
                                    </th>
                                    <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(1)">
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
                                    <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(4)">
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
                                        echo '<td class="py-2 px-4">' . htmlspecialchars($row["facility_name"]) . '</td>';
                                        echo '<td class="py-2 px-4">' . htmlspecialchars($row["reservation_date"]) . '</td>';
                                        echo '<td class="py-2 px-4">' . htmlspecialchars($row["start_time"]) . '</td>';
                                        echo '<td class="py-2 px-4">' . htmlspecialchars($row["end_time"]) . '</td>';
                                        echo '<td class="py-2 px-4">' . htmlspecialchars($row["reservation_status"]) . '</td>';
                                        echo '<td class="py-2 px-4">' . htmlspecialchars($row["purpose"]) . '</td>';
                                        echo '<td class="py-2 px-4">';
                                        
                                        if ($isEditable) {
                                            echo '<button onclick="editReservation(' . $reservationId . ')" class="bg-blue-500 text-white rounded-md px-4 py-2 hover:bg-blue-600">Edit</button>';
                                        }
                                        
                                        echo '<button onclick="deleteReservation(' . $reservationId . ')" class="bg-red-500 text-white rounded-md px-4 py-2 hover:bg-red-600">Delete</button>';
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

                <div class="h-full border-l border-gray-300"></div>

                <div class="flex flex-col h-full w-1/3 space-y-4 p-2">
                    <div>
                        <h2 class="font-semibold">Pendings</h2>
                    </div>
                    <div id="PendingList" class="bg-white shadow overflow-y-auto sm:rounded-lg flex-1">
                        <ul id="PendingListUl" class="divide-y divide-gray-200 flex flex-col p-2">
                            <?php
                            if ($review_reservation_result->num_rows > 0) {
                                while ($row = $review_reservation_result->fetch_assoc()) {
                                    $reservationId = $row["id"];
                                    echo '<li class="p-4 border-gray-200 border-b reservation-item" data-reservation-id="' . $reservationId . '">';
                                    echo '<h3 class="text-lg font-bold mb-2">' . htmlspecialchars($row["facility_name"]) . '</h3>';
                                    echo '<h3 class="text-gray-600 mb-2">' . htmlspecialchars($row["user_department"]) . '</h3>';
                                    echo '<p class="text-gray-600 mb-2">Reservation Date: ' . htmlspecialchars($row["reservation_date"]) . '</p>';
                                    echo '<p class="text-gray-600 mb-2">Start Time: ' . htmlspecialchars($row["start_time"]) . ' - End Time: ' . htmlspecialchars($row["end_time"]) . '</p>';
                                    echo '<p class="italic">' . htmlspecialchars($row["reservation_status"]) . '</p>';
                                    echo '<div class="flex justify-between mt-2">';
                                    echo '<button onclick="declineReservation(' . $reservationId . ')" class="px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600">Decline</button>';
                                    echo '<button onclick="acceptReservation(' . $reservationId . ')" class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600">Approve</button>';
                                    echo '</div>';
                                    echo '</li>';
                                }
                            } else {
                                echo '<li class="flex flex-col items-center justify-center text-center space-y-4 py-8">
                                        <img class="w-1/2 h-1/2 opacity-60" src="img/undraw_winners_re_wr1l.svg" alt="No Reservations">
                                        <p class="text-gray-600 font-semibold text-lg">No pendings!</p>
                                    </li>
                                    ';
                            }
                            ?>
                        </ul>
                    </div>
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
    <!-- Reservation markup -->
    <div id="reservationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-md flex flex-col items-center">
            <div id="modalContent" class="font-bold text-xl text-blue-600 z-10">
                <!-- Reservation details will be dynamically added here -->
            </div>
            <div class="flex justify-center mt-5">
                <button onclick="closeModal()" class="mr-4 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-400">Close</button>
            </div>
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
                        <textarea id="rejectionReason" name="rejectionReason" rows="3" class="border border-gray-300 rounded-md p-2" required></textarea>
                    </div>
                </form>
                <button onclick="hideSuccessModal()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Cancel</button>
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
                <button onclick="hideSuccessModal()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">OK</button>
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

        // Function to close reservations modal
        function closeModal() {
            const modal = document.getElementById('reservationsModal');
            modal.classList.add('hidden');
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
            location.reload(); // Reload the page
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
                    showConfirmation('Are you sure you want to approve this reservation?', function() {
                        fetch('../reserva/handlers/update_reservation_status.php?id=' + reservationId + '&status=Approved', {
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
                                console.error('Error accepting reservation:', data.error);
                                showModal({ title: 'Error accepting reservation' });
                            } else {
                                showSuccessModal('Reservation Approved.');
                            }
                        })
                        .catch(error => {
                            console.error('Error accepting reservation:', error);
                            showModal({ title: 'Error accepting reservation' });
                        });
                    });
                }
            })
            .catch(error => {
                console.error('Error checking reservation overlap:', error);
                showErrorMessage('Error checking reservation overlap. Please try again.');
            });
        }


        function declineReservation(reservationId) {
            console.log("Decline button clicked");
            // Show rejection reason form
            const rejectionReasonForm = document.getElementById('rejectionReasonForm');
            rejectionReasonForm.classList.remove('hidden');

            // Handle confirmation after inputting rejection reason
            const confirmButton = document.getElementById('confirmRejectionButton');
            confirmButton.onclick = function() {
                // Get rejection reason from the form
                const rejectionReason = document.getElementById('rejectionReason').value;

                // Show confirmation message before declining
                showConfirmation('Are you sure you want to decline this reservation?', function() {
                    // Send rejection reason and decline reservation
                    fetch('handlers/update_rejected_reservation.php?id=' + reservationId + '&status=Declined', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            reason: rejectionReason
                        })
                    })
                    .then(response => {
                        if (response.ok) {
                            // Reload the page after successful decline
                            location.reload();
                        } else {
                            // Handle error
                            showModal({ title: 'Error declining reservation' });
                        }
                    });
                });
            };
        }
    </script>
</body>
</html>
