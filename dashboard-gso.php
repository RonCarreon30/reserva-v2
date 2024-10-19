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

// Fetch reservations from the database for the current user
include_once 'database/config.php';

// Fetch the user ID from the session data
$user_id = $_SESSION['user_id'];

// Fetch the user data from the database
$user_query = "SELECT * FROM users WHERE id = '$user_id'";
$user_result = $conn->query($user_query);
$user_data = $user_result->fetch_assoc();

// Fetch the total number of facilities
$count_query = "SELECT COUNT(*) as total FROM facilities"; // Adjust the table name as per your database
$count_result = $conn->query($count_query);

$total_facilities = 0;
if ($count_result->num_rows > 0) {
    $row = $count_result->fetch_assoc();
    $total_facilities = $row['total'];
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLV: RESERVA</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');

            var calendar = new FullCalendar.Calendar(calendarEl, {
                headerToolbar: {
                    left  : 'prev,next today',
                    center: 'title',
                    right : 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                initialView: 'dayGridMonth',
                  views: {
                        dayGridMonth: {
                        eventTimeFormat: { // Hide time in Month View
                            hour: '2-digit', 
                            minute: '2-digit', 
                            omitZeroMinute: false,
                            meridiem: 'short', 
                            hour12: true
                        }
                        }
                    },
                    eventTimeFormat: { // Format for Week/Day views
                        hour: '2-digit', 
                        minute: '2-digit',
                        omitZeroMinute: false,
                        meridiem: 'short',
                        hour12: true
                    },
                hiddenDays: [0], // Hide Sunday
                selectable: true,
                eventDisplay: 'block', // Forces events to display as blocks
                eventClick: function(info) {
                    // You can show the event details in a modal or alert here
                    showEventDetails(info.event); // Function to handle showing event details
                },
                events: function(fetchInfo, successCallback, failureCallback) {
                    fetch('handlers/fetch_events.php')
                        .then(response => response.json())
                        .then(data => {
                            console.log(data); // Add this to inspect the fetched data
                            successCallback(data);
                        })
                        .catch(error => {
                            console.error('Error fetching events:', error);
                            failureCallback(error);
                        });
                },


            });

            calendar.render();
        });
    </script>
    <style>
        #custom-dialog {
            z-index: 10000; /* Ensures the logout modal appears on top of everything */
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
                    <h2 class="text-lg font-semibold">Dashboard</h2>
                </div>
            </header>

            <!-- Main Content goes here-->
            <main class="flex flex-1 p-4 h-screen overflow-y-auto">

                <div class="w-3/4 pr-4">
                    <div id='calendar' class="h-full p-1 text-xs bg-white border border-gray-200 rounded-lg shadow-lg"></div>
                </div>

                <div class="h-full border-l border-gray-300"></div>

                <div class="flex flex-col h-full w-1/3 space-y-4">
                    <div class="h-1/2 p-2">
                        <!--Widgets-->
                        <div class="grid grid-cols-1 m-2 gap-4">
                            <a href="facilityManagement.php" class="block">
                                <div class="flex items-center rounded bg-white p-6 shadow-md h-30 cursor-pointer hover:bg-gray-200">
                                    <i class="fas fa-building fa-2x w-1/4 text-blue-600"></i>
                                    <div class="w-3/4">
                                        <h2 class="text-m font-bold">Total Number Of Facilities</h2>
                                        <?php
                                        // Fetch count of total facilities
                                        $facility_count_sql = "SELECT COUNT(*) AS count FROM facilities";
                                        $facility_count_result = $conn->query($facility_count_sql);

                                        if ($facility_count_result) {
                                            $row = $facility_count_result->fetch_assoc();
                                            $facility_count = $row['count'];
                                            echo '<p class="text-2xl">' . $facility_count . '</p>';
                                        } else {
                                            echo '<p class="text-2xl">0</p>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </a>

                            <a href="FacilityReservations.php" class="block">
                                <div class="flex items-center rounded bg-white p-6 shadow-md h-30 cursor-pointer hover:bg-gray-200">
                                    <i class="fas fa-calendar-check fa-2x w-1/4 text-green-600"></i>
                                    <div class="w-3/4">
                                        <h2 class="text-m font-bold">Facilities with Reservation</h2>
                                        <?php
                                        // Fetch count of distinct facilities that have a "Reserved" reservation status
                                        $facilities_with_reservation_sql = "
                                            SELECT COUNT(DISTINCT facility_id) AS count 
                                            FROM reservations 
                                            WHERE reservation_status = 'Reserved'
                                        ";
                                        $facilities_with_reservation_result = $conn->query($facilities_with_reservation_sql);

                                        if ($facilities_with_reservation_result) {
                                            $row = $facilities_with_reservation_result->fetch_assoc();
                                            $facilities_with_reservation_count = $row['count'];
                                            echo '<p class="text-2xl">' . $facilities_with_reservation_count . '</p>';
                                        } else {
                                            echo '<p class="text-2xl">0</p>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </a>

                            <div class="flex items-center rounded bg-white p-6 shadow-md h-30">
                                <i class="fas fa-calendar-alt fa-2x w-1/4"></i>
                                <div class="w-3/4">
                                    <h2 class="text-m font-bold">Reservations This Month</h2>
                                    <?php
                                    // Fetch count of reservations for the current month with status 'Reserved'
                                    $current_year = date('Y');
                                    $current_month = date('m');
                                    
                                    $monthly_reservations_sql = "SELECT COUNT(*) AS count FROM reservations WHERE YEAR(reservation_date) = '$current_year' AND MONTH(reservation_date) = '$current_month' AND reservation_status = 'Reserved'";
                                    $monthly_reservations_result = $conn->query($monthly_reservations_sql);

                                    if ($monthly_reservations_result) {
                                        $row = $monthly_reservations_result->fetch_assoc();
                                        $monthly_reservations_count = $row['count'];
                                        echo '<p class="text-2xl">' . $monthly_reservations_count . '</p>';
                                    } else {
                                        echo '<p class="text-2xl">0</p>';
                                    }
                                    ?>
                                </div>
                            </div>

                            <div class="flex items-center rounded bg-white p-6 shadow-md h-30">
                                <i class="fas fa-calendar-times fa-2x w-1/4 text-red-600"></i>
                                <div class="w-3/4">
                                    <h2 class="text-m font-bold">Facilities with No Reservations</h2>
                                    <?php
                                    // Fetch count of facilities with no reservations
                                    $no_reservations_sql = "
                                        SELECT COUNT(*) AS count 
                                        FROM facilities f 
                                        LEFT JOIN reservations r ON f.facility_name = r.facility_name 
                                        WHERE r.facility_name IS NULL";
                                    $no_reservations_result = $conn->query($no_reservations_sql);

                                    if ($no_reservations_result) {
                                        $row = $no_reservations_result->fetch_assoc();
                                        $no_reservations_count = $row['count'];
                                        echo '<p class="text-2xl">' . $no_reservations_count . '</p>';
                                    } else {
                                        echo '<p class="text-2xl">0</p>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <div id="footer-container">
                <?php include 'footer.php' ?>
            </div>            
        </div>
    </div>
    <!-- Modal Container -->
    <div id="eventDetailsModal" class="hidden fixed z-10 inset-0 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center border-b pb-2">
            <h2 id="eventTitle" class="text-2xl font-semibold text-gray-800"></h2>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            </div>
            <div class="mt-4">
            <div class="mb-4">
                <h3 class="text-lg font-medium text-gray-700">Time:</h3>
                <p id="eventTime" class="text-gray-600"></p>
            </div>
            <div class="mb-4">
                <h3 class="text-lg font-medium text-gray-700">Facility Name:</h3>
                <p id="eventFacility" class="text-gray-600"></p>
            </div>
            <div class="mb-4">
                <h3 class="text-lg font-medium text-gray-700">Faculty In Charge:</h3>
                <p id="eventFacultyInCharge" class="text-gray-600"></p>
            </div>
            <div class="mb-4">
                <h3 class="text-lg font-medium text-gray-700">Additional Info:</h3>
                <p id="eventAdditionalInfo" class="text-gray-600"></p>
            </div>
            <div>
                <h3 class="text-lg font-medium text-gray-700">Description:</h3>
                <p id="eventDescription" class="text-gray-600"></p>
            </div>
            </div>
            <div class="mt-6 flex justify-end">
            <button onclick="closeModal()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 focus:outline-none">Close</button>
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
    <script src="scripts/logout.js"></script>
    <script src="scripts/functions.js"></script>
    <script>
        function showEventDetails(event) {
            // Populate the modal with event details
            document.getElementById('eventTitle').innerText = event.title;
            
            // Format the start and end time, show both if available
            var eventTime = event.start.toLocaleString() + 
                            (event.end ? ' - ' + event.end.toLocaleString() : '');
            document.getElementById('eventTime').innerText = eventTime;
            
            // Show facility_name, FacultyInCharge, and additional_info (use event's extendedProps)
            document.getElementById('eventFacility').innerText = event.extendedProps.facility_name || 'Not specified';
            document.getElementById('eventFacultyInCharge').innerText = event.extendedProps.FacultyInCharge || 'Not specified';
            document.getElementById('eventAdditionalInfo').innerText = event.extendedProps.additional_info || 'No additional information';

            // Show event description if available
            document.getElementById('eventDescription').innerText = event.extendedProps.description || 'No description provided';

            // Show the modal
            document.getElementById('eventDetailsModal').classList.remove('hidden');
        }

        function closeModal() {
            // Hide the modal
            document.getElementById('eventDetailsModal').classList.add('hidden');
        }


    </script>
</body>
</html>