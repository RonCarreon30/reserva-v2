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
if ($_SESSION['role'] !== 'Admin') {
    // Redirect to a page indicating unauthorized access
    header("Location: unauthorized");
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
                    // Fetch facility reservations
                    const facilityReservations = fetch('handlers/fetch_events.php')
                        .then(response => response.json())
                        .catch(error => {
                            console.error('Error fetching facility reservations:', error);
                            failureCallback(error); // Handle error fetching reservations
                        });

                    // Fetch holidays from Google Calendar API
                    const holidays = fetch('https://www.googleapis.com/calendar/v3/calendars/en.philippines%23holiday%40group.v.calendar.google.com/events?key=AIzaSyCB7rRha3zbgSYH1aD5SECsRvQ3usacZHU')
                        .then(response => response.json())
                        .then(data => {
                            // Format the holiday events to fit FullCalendar format
                            return data.items.map(holiday => ({
                                title: holiday.summary,
                                start: holiday.start.date, // Use holiday date (all-day event)
                                color: '#ff0000', // Optional: set a color for holidays
                                allDay: true,
                                extendedProps: {
                                    isHoliday: true // Add a flag to identify holidays
                                }
                            }));
                        })
                        .catch(error => {
                            console.error('Error fetching holidays:', error);
                            failureCallback(error); // Handle error fetching holidays
                        });

                    // Combine both promises (facility reservations and holidays)
                    Promise.all([facilityReservations, holidays])
                        .then(results => {
                            const facilityEvents = results[0]; // Facility reservations
                            const holidayEvents = results[1];  // Holiday events
                            // Combine both event arrays
                            const allEvents = facilityEvents.concat(holidayEvents);
                            successCallback(allEvents); // Pass combined events to FullCalendar
                        })
                        .catch(error => {
                            console.error('Error combining events:', error);
                            failureCallback(error); // Handle any error in the process
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
                .fc-toolbar-title {
            font-size:large !important; /* Adjust this size as needed */
            font-weight: normal; /* Optional: adjust font weight */
        }

        /* Make navigation buttons smaller */
        .fc-prev-button,
        .fc-next-button,
        .fc-today-button,
        .fc-dayGridMonth-button,
        .fc-timeGridWeek-button,
        .fc-timeGridDay-button {
            font-size: 12px !important; /* Adjust font size */
            padding: 5px 8px !important; /* Adjust padding for size */
        }

        /* Optional: Adjust the overall toolbar padding */
        .fc-toolbar {
            padding: 5px !important; /* Adjust padding if needed */
            margin-bottom: 1px !important;
        }

    </style>
</head>
<body>
    <div class="flex h-screen bg-gray-100">
        
        <!-- Load the Sidebar here   -->
        <div id="sidebar-container">
            <?php include 'sidebar.php'; ?>
        </div>

        <div class="flex flex-col flex-1">
            <header class="bg-white shadow-lg">
                <div class="flex items-center justify-between px-6 py-3 border-b">
                    <h2 class="text-lg font-semibold">Admin's Dashboard</h2>
                </div>
            </header>

            <!-- Main content area -->
            <main class="flex flex-1 p-4 h-screen overflow-y-auto">
                <div class="w-3/4 pr-4">
                    <div id='calendar' class="h-full p-1 text-xs bg-white border border-gray-200 rounded-lg shadow-lg"></div>
                </div>

                <div class="h-full border-l border-gray-300"></div>

                <div class="flex flex-col h-full w-1/3 space-y-4">
                    <div class="h-1/2 p-2">
                        <div class="grid grid-cols-1 m-2 gap-4">
                        <!-- Widgets -->
                            <a href="accManagement" class="block">
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

                            <a href="roomManagement" class="block">
                                <!-- Total Rooms -->
                                <div class="flex items-center rounded bg-white p-6 shadow-md h-30 cursor-pointer hover:bg-gray-200">
                                    <i class="fas fa-door-closed fa-2x w-1/4 text-blue-600"></i>
                                    <div class="ml-4 w-3/4">
                                        <h2 class="text-lg font-bold">Total Rooms</h2>
                                        <?php
                                            // Fetch count of total facilities
                                            $room_count_sql = "SELECT COUNT(*) AS count FROM rooms_tbl";
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

                            <a href="facilityManagement" class="block">
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
        <!-- Modal Container -->
    <div id="eventDetailsModal" class="hidden fixed z-10 inset-0 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center border-b pb-2">
            <h2 id="eventTitle" class="text-2xl font-semibold text-gray-800">Reservation/Event Details</h2>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            </div>
            <div class="mt-4">
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-700">Purpose</h3>
                    <p id="eventPurpose" class="text-gray-600"></p>
                </div>
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-700">Facility Name:</h3>
                    <p id="eventFacility" class="text-gray-600"></p>
                </div>              
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-700">Start:</h3>
                    <p id="eventStart" class="text-gray-600"></p>
                </div>
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-700">End:</h3>
                    <p id="eventEnd" class="text-gray-600"></p>
                </div>            
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-700">Faculty In Charge:</h3>
                    <p id="eventFacultyInCharge" class="text-gray-600"></p>
                </div>
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-gray-700">Additional Info:</h3>
                    <p id="eventAdditionalInfo" class="text-gray-600"></p>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
            <button onclick="closeModal()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 focus:outline-none">Close</button>
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
    <script src="scripts/logout.js"></script>
    <script>
        function showEventDetails(event) {
            // Populate the modal with event details

            document.getElementById('eventPurpose').innerText = event.extendedProps.purpose;
            document.getElementById('eventStart').innerText = event.extendedProps.sTime;
            document.getElementById('eventEnd').innerText = event.extendedProps.eTime;
            
            // Show facility_name, FacultyInCharge, and additional_info (use event's extendedProps)
            document.getElementById('eventFacility').innerText = event.extendedProps.facility_name || 'Not specified';
            document.getElementById('eventFacultyInCharge').innerText = event.extendedProps.FacultyInCharge || 'Not specified';
            document.getElementById('eventAdditionalInfo').innerText = event.extendedProps.additional_info || 'No additional information';

            // Show the modal
                    console.log(event);
            document.getElementById('eventDetailsModal').classList.remove('hidden');
        }


        function closeModal() {
            // Hide the modal
            document.getElementById('eventDetailsModal').classList.add('hidden');
        }


    </script>
</body>
</html>
