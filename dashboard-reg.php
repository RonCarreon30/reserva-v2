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
if ($_SESSION['role'] !== 'Registrar') {
    // Redirect to a page indicating unauthorized access
    header("Location: index.html");
    exit();
}


// connect to db
include_once 'database/config.php';

// Fetch the user ID from the session data
$user_id = $_SESSION['user_id'];


// Fetch the user data from the database
$user_query = "SELECT * FROM users WHERE id = '$user_id'";
$user_result = $conn->query($user_query);
$user_data = $user_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLV: RESERVA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css" />
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
</head>
<body>
    <div class="flex h-screen bg-gray-100">
      
        <!-- Load the Sidebar here   -->
        <div id="sidebar-container">
          <?php include 'sidebar.php'; ?>
      </div>
        
        <!-- Content area -->
        <div class="flex flex-col flex-1">
            <!-- Header -->
            <header class="bg-white shadow-lg">
                <div class="flex items-center justify-between px-6 py-3 border-b">
                    <h2 class="text-lg font-semibold">Registrar Dashboard</h2>
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
                        <div class="grid grid-cols-1 m-2 gap-4">
                        <!-- Widgets -->
                            <a href="accManagement.php" class="block">
                                <!-- Total Users -->
                                <div class="flex items-center rounded bg-white p-6 shadow-md h-30 cursor-pointer hover:bg-gray-200">
                                    <i class="fas fa-users fa-2x w-1/4 text-blue-600"></i>
                                    <div class="ml-4 w-3/4">
                                        <h2 class="text-lg font-bold">Total Users</h2>
                                        <?php
                                            // Fetch count of total facilities
                                            $user_count_sql = "SELECT COUNT(*) AS count FROM users";
                                            $user_count_result = $conn->query($user_count_sql);

                                            if ($user_count_result) {
                                                $row = $user_count_result->fetch_assoc();
                                                $user_count = $row['count'];
                                                echo '<p class="text-2xl">' . $user_count . '</p>';
                                            } else {
                                                echo '<p class="text-2xl">0</p>';
                                            }
                                        ?>
                                    </div>
                                </div>
                            </a>

                            <a href="roomManagement.php" class="block">
                                <!-- Total Rooms -->
                                <div class="flex items-center rounded bg-white p-6 shadow-md h-30 cursor-pointer hover:bg-gray-200">
                                    <i class="fas fa-door-closed fa-2x w-1/4 text-blue-600"></i>
                                    <div class="ml-4 w-3/4">
                                        <h2 class="text-lg font-bold">Total Rooms</h2>
                                        <?php
                                            // Fetch count of total facilities
                                            $room_count_sql = "SELECT COUNT(*) AS count FROM rooms";
                                            $room_count_result = $conn->query($room_count_sql);

                                            if ($room_count_result) {
                                                $row = $room_count_result->fetch_assoc();
                                                $room_count = $row['count'];
                                                echo '<p class="text-2xl">' . $room_count . '</p>';
                                            } else {
                                                echo '<p class="text-2xl">0</p>';
                                            }
                                        ?>
                                    </div>
                                </div>
                            </a>

                            <a href="facilityManagement.php" class="block">
                                <!-- Total Facilities -->
                                <div class="flex items-center rounded bg-white p-6 shadow-md h-30 cursor-pointer hover:bg-gray-200">
                                    <i class="fas fa-building fa-2x w-1/4 text-blue-600"></i>
                                    <div class="ml-4 w-3/4">
                                        <h2 class="text-lg font-bold">Total Facilities</h2>
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
                        </div>
                    </div>
                </div>
            </main>     
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
</body>
</html>