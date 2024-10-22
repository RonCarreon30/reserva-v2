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

    require_once 'database/config.php';

        function convertTo12HourFormat($time) {
            $timestamp = strtotime($time);
            return date('h:i A', $timestamp);
        }
    // Fetch schedules from schedules_tbl
    $query = "SELECT * FROM schedules_tbl";
    $result = mysqli_query($conn, $query);



// Fetch buildings
$buildingsQuery = "SELECT * FROM buildings_tbl";
$buildingsResult = $conn->query($buildingsQuery);

// Fetch all rooms
$roomsQuery = "SELECT * FROM rooms_tbl";
$roomsResult = $conn->query($roomsQuery);

// Organize rooms by building ID
$roomsByBuilding = [];
while ($room = $roomsResult->fetch_assoc()) {
    $roomsByBuilding[$room['building_id']][] = $room;
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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>
    <script>
        function filterRooms() {
            const buildingId = document.getElementById('buildingSelect').value;
            const roomSelect = document.getElementById('roomSelect');
            const roomOptions = roomSelect.getElementsByTagName('option');

            // Show all room options if no building is selected
            for (let i = 0; i < roomOptions.length; i++) {
                const option = roomOptions[i];
                if (buildingId === '' || option.getAttribute('data-building-id') === buildingId) {
                    option.style.display = ''; // Show this option
                } else {
                    option.style.display = 'none'; // Hide this option
                }
            }
            
            // Optionally, reset roomSelect if no rooms are visible
            const visibleOptions = Array.from(roomOptions).filter(option => option.style.display !== 'none');
            if (visibleOptions.length === 1) { // Only the default "All Rooms" option is visible
                roomSelect.value = ''; // Reset to "All Rooms"
            }
        }

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        headerToolbar: {
            start: '',
            center: '',
            end: ''
        },
        height: '100%',
        hiddenDays: [0], // Hide Sunday
        slotMinTime: '07:00:00',
        slotMaxTime: '22:00:00',
        slotDuration: '00:30:00',
        nowIndicator: false,
        allDaySlot: false,
        dayHeaderFormat: { weekday: 'short' },
        views: {
            timeGridWeek: {
                dayHeaderFormat: { weekday: 'short' }
            }
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            const ayId = document.getElementById('AYSelect').value;
            const buildingId = document.getElementById('buildingSelect').value;
            const roomId = document.getElementById('roomSelect').value;

            fetch(`handlers/fetch_all_sched.php?roomId=${roomId}&ayId=${ayId}&buildingId=${buildingId}`)
                .then(response => response.json())
                .then(data => successCallback(data))
                .catch(error => failureCallback(error));
        },
        eventDidMount: function(info) {
            let eventElement = info.el.querySelector('.fc-event-title');
            if (eventElement) {
                eventElement.innerHTML = `
                    <div><strong>${info.event.title}</strong></div>
                    <div>Section: ${info.event.extendedProps.section}</div>
                    <div>Instructor: ${info.event.extendedProps.instructor}</div>
                `;
            }
        },
        eventClick: function(info) {
            alert(`Event: ${info.event.title}\nInstructor: ${info.event.extendedProps.instructor}\nSection: ${info.event.extendedProps.section}`);
        }
    });

    calendar.render();

    // Update calendar when dropdowns change
    document.getElementById('AYSelect').addEventListener('change', updateCalendar);
    document.getElementById('buildingSelect').addEventListener('change', updateCalendar);
    document.getElementById('roomSelect').addEventListener('change', updateCalendar);
});

// Function to refresh calendar events based on selected filters
function updateCalendar() {
    calendar.refetchEvents();
}

    </script>
    <style>
        #custom-dialog {
            z-index: 10000; /* Ensures the logout modal appears on top of everything */
        }
        #confirmation-modal {
            z-index: 10000; /* Ensures the logout modal appears on top of everything */
        }
    </style>
</head>
    <body class=" bg-gray-50">
        <div class="flex h-screen bg-gray-100">

            <!-- Sidebar -->
            <div id="sidebar-container">
                <?php include 'sidebar.php'; ?>
            </div>

            <!-- Room List -->
            <div class="flex flex-col flex-1">
                <header class="bg-white shadow-lg">
                    <div class="flex items-center justify-between px-6 py-3 border-b">
                        <h2 class="text-lg font-semibold">Room Loads</h2>
                    </div>
                </header>

                <main class="flex-1 p-4 overflow-y-auto flex ">
                    
                    <div class="w-3/4 rounded-lg flex flex-col">
                        <div class="flex mb-1 space-x-4 items-center ">
                            <button id="add-schedule-button" onclick="window.location.href='loads_upload.php'" class="px-3 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-150 ease-in-out" title="Add Schedules">
                                <i class="fa-solid fa-circle-plus"></i>
                            </button>

                            <select id="AYSelect" class="px-4 py-2 border border-gray-300 rounded-md" onchange="filterRooms()">
                                <option value="">Academic Year...</option>
                                <?php
                                    // Query to get terms values
                                    $query = "SELECT * FROM terms_tbl";
                                    $result = $conn->query($query);
                                    // Loop through the result and create an <option> element for each building
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<option value="' . $row['terms_id'] . '">' . $row['academic_year'] . ' - ' . $row['semester'] . '</option>';
                                        }
                                    } else {
                                        echo '<option value="">No Data available</option>';
                                    }
                                
                                ?>
                            </select>
                            <select id="buildingSelect" class="px-4 py-2 border border-gray-300 rounded-md" onchange="filterRooms()">
                                <option value="">All Buildings</option>
                                <?php if ($buildingsResult->num_rows > 0): ?>
                                    <?php while ($building = $buildingsResult->fetch_assoc()): ?>
                                        <option value="<?php echo $building['building_id']; ?>"><?php echo $building['building_name']; ?></option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="">No buildings available</option>
                                <?php endif; ?>
                            </select>

                            <select id="roomSelect" class="px-4 py-2 border border-gray-300 rounded-md" onchange="filterRooms()">
                                <option value="">All Rooms</option>
                                <?php foreach ($roomsByBuilding as $buildingId => $rooms): ?>
                                    <?php foreach ($rooms as $room): ?>
                                        <option value="<?php echo $room['room_id']; ?>" data-building-id="<?php echo $buildingId; ?>">
                                            <?php echo $room['room_number']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </select>

                            <!-- Export button -->
                            <button id="exportButton" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-150 ease-in-out" onclick="exportSchedule()">
                                <i class="fa-solid fa-file-export"></i> Export Schedules
                            </button>
                        </div>

                        <div id="calendar" class="rounded-lg bg-white p-1 overflow-hidden shadow-md flex-grow h-[500px]">
                            <!-- Calendar/timetable here -->
                        </div>
                    </div>
                </main>

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
</body>
</html>