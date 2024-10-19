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

// Fetch reservations from the database for the current user
require_once 'database/config.php';

// Fetch the user ID from the session data
$user_id = $_SESSION['user_id'];

// Fetch the user data from the database
$user_query = "SELECT * FROM users WHERE id = '$user_id'";
$user_result = $conn->query($user_query);
$user_data = $user_result->fetch_assoc();

// Fetch user's department from the database
$head_department = '';
$head_department_sql = "SELECT department FROM users WHERE id = $user_id";
$head_department_result = $conn->query($head_department_sql);
if ($head_department_result->num_rows > 0) {
    $row = $head_department_result->fetch_assoc();
    $head_department = $row['department'];
}

// Query to fetch reservations of the student rep of same department
$all_reservations_sql = "SELECT * FROM reservations WHERE user_department = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($all_reservations_sql);
$stmt->bind_param("s", $head_department);
$stmt->execute();
$all_reservations_result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLV: RESERVA</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="flex h-screen bg-gray-100">

        <div id="sidebar-container">
            <?php include 'sidebar.php'; ?>
        </div>
        
        <div class="flex flex-col flex-1">
            <header class="bg-white shadow-lg">
                <div class="flex items-center justify-between px-6 py-3 border-b">
                    <h2 class="text-lg font-semibold">Events/Reserved Dates</h2>
                    <!-- Add any header content here -->
                </div>
            </header>
            <!-- For debugging purposes to get session data-->
            <div class="flex flex-col space-y-2 hidden">
                <label for="department" class="text-gray-700">Department:</label>
                <input type="text" id="department" name="department" class="border border-gray-300 rounded-md p-2" value="<?php echo htmlspecialchars($head_department); ?>" readonly>
            </div>
            <!-- For debugging purposes to get session data-->
            <!-- Main content area -->
            <!-- Main content area -->
            <main class="flex-1 p-6 overflow-y-auto">
                <div class="bg-white p-4 rounded-md shadow-md mb-6">
                    <div class="flex justify-between items-center m-2">
                        <input type="text" id="search" placeholder="Search..." class="border rounded-md py-2 px-4" onkeyup="filterReservations()">
                    </div>
                    <div id="eventsList" class="bg-white shadow overflow-y-auto sm:rounded-lg flex-1 m-2">
                        <table id="eventsTable" class="min-w-full bg-white rounded-md shadow-md border border-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
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
                                    <th class="py-3 px-4">Start Time</th>
                                    <th class="py-3 px-4">End Time</th>
                                    <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(4)">
                                        <span class="flex items-center">Status
                                            <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                            </svg>
                                        </span>
                                    </th>
                                    <th class="py-3 px-4">Purpose</th>
                                    <th class="py-3 px-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200" id="reservationTableBody">
                                <?php
                                while ($row = $all_reservations_result->fetch_assoc()) {
                                    $reservationId = $row["id"];
                                    $statusClass = ($row["reservation_status"] === 'Declined') ? 'text-red-600 bg-red-100' : '';
                                    echo '<tr class="' . $statusClass . '" data-id="' . $reservationId . '">';
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($row["facility_name"]) . '</td>';
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($row["reservation_date"]) . '</td>';
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($row["start_time"]) . '</td>';
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($row["end_time"]) . '</td>';
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($row["reservation_status"]) . '</td>';
                                    echo '<td class="py-2 px-4">' . htmlspecialchars($row["purpose"]) . '</td>';
                                    echo '<td class="py-2 px-4">';
                                    echo '<button onclick="editReservation(' . $reservationId . ')" class="bg-blue-500 text-white rounded-md px-4 py-2 hover:bg-blue-600">Edit</button>';
                                    echo '<button onclick="deleteReservation(' . $reservationId . ')" class="bg-red-500 text-white rounded-md px-4 py-2 hover:bg-red-600">Delete</button>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
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
                <button type="button" id="saveChangesButton" class="bg-blue-500 text-white rounded-md px-4 py-2 hover:bg-blue-600" onclick="saveReservationChanges()">Save Changes</button>
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

    <script src="scripts/logout.js"></script>
    <script src="scripts/functions.js"></script>
    <script>
//Sorting Function
function sortTable(columnIndex) {
    // Check if the columnIndex is not 2, 3, or 5 before sorting
    if (columnIndex === 2 || columnIndex === 3 || columnIndex === 5) {
        return; // Do nothing if the column is not sortable
    }

    var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
    table = document.getElementById("eventsList").getElementsByTagName("table")[0];
    switching = true;
    dir = "asc";

    // Loop until no switching is done
    while (switching) {
        switching = false;
        rows = table.rows;

        // Loop through all table rows except the first (header row)
        for (i = 1; i < (rows.length - 1); i++) {
            shouldSwitch = false;
            x = rows[i].getElementsByTagName("TD")[columnIndex];
            y = rows[i + 1].getElementsByTagName("TD")[columnIndex];

            // Check if the rows should switch based on the direction
            if (dir === "asc") {
                if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            } else if (dir === "desc") {
                if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                    shouldSwitch = true;
                    break;
                }
            }
        }

        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            switchcount++;
        } else {
            // If no switching and the direction is "asc", set direction to "desc" and run the loop again
            if (switchcount === 0 && dir === "asc") {
                dir = "desc";
                switching = true;
            }
        }
    }
}
//Sorting Function end

//Edit Reservation
function editReservation(id) {
    // Make an AJAX request to fetch the reservation details from the server using the reservation ID
    fetch(`handlers/get_reservation_details.php?id=${id}`)
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

            // Show the modal
            document.getElementById('EditReservationModal').classList.remove('hidden');
        })
        .catch(error => console.error('Error:', error));
}

function closeModal() {
    document.getElementById('EditReservationModal').classList.add('hidden');
}

    function saveReservationChanges() {
        // Collect the data from the form inputs
        const reservationId = document.getElementById('reservationForm').dataset.reservationId; // Assuming form has reservation ID attached
        const facilityName = document.getElementById('facilityName').value;
        const reservationDate = document.getElementById('reservationDate').value;
        const startTime = document.getElementById('startTime').value;
        const endTime = document.getElementById('endTime').value;
        const facultyInCharge = document.getElementById('facultyInCharge').value;
        const purpose = document.getElementById('purpose').value;

        // Create an object to store the data
        const formData = {
            reservationId: reservationId,
            facilityName: facilityName,
            reservationDate: reservationDate,
            startTime: startTime,
            endTime: endTime,
            facultyInCharge: facultyInCharge,
            purpose: purpose
        };

        // Send the data to the server using AJAX
        fetch('handlers/update_reservation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Reservation updated successfully!");
                // Optionally refresh the table or update the row to reflect changes
                closeModal();
                location.reload(); // Refreshes the page to update the table
            } else {
                alert("Failed to update reservation. Please try again.");
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("An error occurred. Please try again.");
        });
    }
    
    function deleteReservation(id) {
        // Add your delete functionality here
        if (confirm('Are you sure you want to delete reservation ID ' + id + '?')) {
            // Proceed with deletion, e.g., send a request to the server
            alert('Delete functionality not implemented for reservation ID ' + id);
        }
    }
</script>

</body>
</html>
