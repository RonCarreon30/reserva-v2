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

        // Fetch buildings for the dropdown
        $buildings_sql = "SELECT DISTINCT building FROM rooms";
        $buildings_result = $conn->query($buildings_sql);

        // Fetch all rooms
        $get_rooms_sql = "SELECT * FROM rooms WHERE room_status = 'Available'ORDER BY building";
        $get_rooms_result = $conn->query($get_rooms_sql);
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
                const selectedBuilding = document.getElementById('buildingSelect').value.toLowerCase();
                const searchQuery = document.getElementById('searchInput').value.toLowerCase();
                const roomRows = document.querySelectorAll('#roomTable tbody tr');

                roomRows.forEach(row => {
                    const roomName = row.querySelector('.room_number').textContent.toLowerCase();
                    const building = row.querySelector('.building').textContent.toLowerCase();

                    const matchesBuilding = selectedBuilding === '' || building.includes(selectedBuilding);
                    const matchesSearch = roomName.includes(searchQuery);

                    row.style.display = matchesBuilding && matchesSearch ? '' : 'none';
                });
            }

            function sortTable(columnIndex) {
                const table = document.getElementById('roomTable');
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
        <style>
            #custom-dialog {
                z-index: 10000; /* Ensures the logout modal appears on top of everything */
            }
            #confirmation-modal {
                z-index: 10000; /* Ensures the logout modal appears on top of everything */
            }
        </style>
    </head>
    <body class="bg-gray-50">
        <div class="flex h-screen bg-gray-100">

            <!-- Sidebar -->
            <div id="sidebar-container">
                <?php include 'sidebar.php'; ?>
            </div>

            <!-- Room List -->
            <div id="roomList" class="flex flex-col flex-1">
                <header class="bg-white shadow-lg">
                    <div class="flex items-center justify-between px-6 py-3 border-b">
                        <h2 class="text-lg font-semibold">Room Loads</h2>
                    </div>
                </header>

                <main class="flex-1 p-4 overflow-y-auto">
                    <div class="rounded-md">
                        <div class="flex items-center space-x-4 mb-4">
                            <div id="back-to-roomloading-button" class="">
                                <button id="add-schedule-button" onclick="window.location.href='room_select.php'" class="px-3 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-150 ease-in-out">
                                    <i class="fa-solid fa-circle-plus"></i>
                                </button>
                            </div>
                            <select id="buildingSelect" class="px-4 py-2 border border-gray-300 rounded-md" onchange="filterRooms()">
                                <option value="">All Buildings</option>
                                <?php while ($building = $buildings_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($building['building']); ?>">
                                        <?php echo htmlspecialchars($building['building']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <input type="text" id="searchInput" class="px-4 py-2 border border-gray-300 rounded-md" placeholder="Search rooms..." onkeyup="filterRooms()">
                        </div>
                        <table id="roomTable" class="min-w-full bg-white rounded-md shadow-md border border-gray-200" data-sort-order="asc">
                            <thead>
                                <tr class="bg-gray-200 border-b">
                                    <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(0)">
                                        <span class="flex items-center">Room Name</span>
                                    </th>
                                    <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(1)">
                                        <span class="flex items-center">Type</span>
                                    </th>
                                    <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(2)">
                                        <span class="flex items-center">Building</span>
                                    </th>
                                    <th class="py-3 px-4 text-left">Action</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200">
                                <?php if ($get_rooms_result->num_rows > 0): ?>
                                    <?php while ($row = $get_rooms_result->fetch_assoc()): ?>
                                        <tr class="<?php echo $row['room_status'] === 'Unavailable' ? 'text-red-600 bg-gray-100' : ''; ?>">
                                            <td class="py-2 px-4 room_number"><?php echo htmlspecialchars($row['room_number']); ?></td>
                                            <td class="py-2 px-4 room_type"><?php echo htmlspecialchars($row['room_type']); ?></td>
                                            <td class="py-2 px-4 building"><?php echo htmlspecialchars($row['building']); ?></td>
                                            <td class="py-2 px-4">
                                                <?php if ($row['room_status'] !== 'Unavailable'): ?>
                                                    <button onclick="showRoomLoads('<?php echo htmlspecialchars($row['room_number']); ?>', '<?php echo $row['room_id']; ?>')" class="bg-blue-500 text-white rounded-md px-4 py-2 hover:bg-blue-600">View Schedules</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">No rooms found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </main>
            </div>

            <!-- Room Loads -->
            <div id="roomLoads" class="flex flex-col flex-1 hidden">
                <!-- Header -->
                <header class="bg-white shadow-lg">
                    <div class="flex items-center justify-between px-6 py-3 border-b">
                        <h2 class="text-lg font-semibold">Room Schedules</h2>
                    </div>
                </header>

                <main class="flex-1 p-4 overflow-y-auto flex space-x-6">
                    
                    <div class="w-3/4 bg-white p-4 rounded-lg shadow-lg flex flex-col">
                        <div class="flex justify-between items-center mb-4">
                            <!-- Back button -->
                            <button id="backButton" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-150 ease-in-out" onclick="goBackToRoomList()">
                                <i class="fa-solid fa-arrow-left"></i> Back
                            </button>

                            <!-- Filter for schedules (example: filter by days or instructors) -->
                            <select id="filterSelect" class="px-4 py-2 border border-gray-300 rounded-md" onchange="filterCalendarEvents()">
                                <option value="">All Schedules</option>
                                <option value="monday">Monday</option>
                                <option value="tuesday">Tuesday</option>
                                <option value="instructor1">Instructor 1</option>
                                <option value="instructor2">Instructor 2</option>
                            </select>

                            <!-- Export button -->
                            <button id="exportButton" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-150 ease-in-out" onclick="exportSchedule()">
                                <i class="fa-solid fa-file-export"></i> Export Schedules
                            </button>
                        </div>

                        <div id="calendar" class="rounded-lg overflow-hidden shadow-md flex-grow h-[500px]">
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
    <script>
        let calendar;
        let selectedRoomId = null;

        function showRoomLoads(roomNumber, roomId) {
            selectedRoomId = roomId; // Store roomId globally for later use
            console.log("Selected roomId:", selectedRoomId); // Debugging line
            document.getElementById('roomList').classList.add('hidden');
            document.getElementById('roomLoads').classList.remove('hidden');

            if (calendar) {
                calendar.destroy();
            }

            calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
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
                    fetch(`handlers/fetch_all_sched.php?roomId=${roomId}`)
                        .then(response => response.json())
                        .then(data => successCallback(data))
                        .catch(error => failureCallback(error));
                },

                // Customize how events are displayed
                eventDidMount: function(info) {
                    // Modify event title to include section and instructor
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
        }

        // Ensure this script runs after the DOM is fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Other initialization code if needed

        });
        // Function to handle the back button click
        function goBackToRoomList() {
            document.getElementById('roomLoads').classList.add('hidden');
            document.getElementById('roomList').classList.remove('hidden');
        }

// Adjust the export button click handler to use the selected roomId
document.getElementById("exportButton").addEventListener("click", function () {
  if (selectedRoomId) {
    exportSchedule(selectedRoomId); // Call the export function with the selected roomId
  } else {
    alert("Please select a room first by clicking 'View Schedules'.");
  }
});

function exportSchedule(roomId) {
  console.log("Exporting schedule for roomId:", roomId); // Debugging line
  fetch(`handlers/export_schedule.php?room_id=${roomId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.length === 0) {
        // No schedules found
        showToast("No schedules found for this room.");
      } else {
        // Create Excel file
        generateExcel(data);
      }
    })
    .catch((error) => console.error("Error fetching schedule:", error));
}

function showToast(message) {
  const toast = document.createElement("div");
  toast.className = "fixed top-0 right-0 m-4 p-4 bg-red-600 text-white rounded";
  toast.innerText = message;
  document.body.appendChild(toast);
  setTimeout(() => {
    document.body.removeChild(toast);
  }, 3000);
}

function generateExcel(data) {
  // Prepare the timetable format
  const timetable = [];

  // Create headers for the timetable
  const headers = [
    "Time",
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
  ];
  timetable.push(headers);

  // Create an array to hold time slots with 30-minute intervals
  const timeSlots = [];
  for (let hour = 7; hour <= 22; hour++) {
    // Add full hour
    timeSlots.push(`${hour % 12 || 12}:00 ${hour < 12 ? 'AM' : 'PM'}`);
    // Add half hour
    timeSlots.push(`${hour % 12 || 12}:30 ${hour < 12 ? 'AM' : 'PM'}`);
  }

  // Create a mapping of occupied cells
  const occupiedCells = new Set(); // Track occupied time slots to avoid duplicate entries
  const displayCells = {}; // Store the content for display

  // Fill in the time slots
  timeSlots.forEach((time) => {
    const row = [time, "", "", "", "", "", ""]; // Initialize row with time

    // Iterate through data to find matching schedules for each day
    data.forEach((schedule) => {
      const startTime = new Date("1970-01-01 " + schedule.start_time);
      const endTime = new Date("1970-01-01 " + schedule.end_time);
      const timeSlot = new Date("1970-01-01 " + time);

      // Check if the time falls within the schedule's time range
      if (timeSlot >= startTime && timeSlot < endTime) {
        // Determine which column to place the schedule in
        const dayIndex = getDayIndex(schedule.days); // Implement this function based on your days
        if (dayIndex !== -1) {
          const key = `${time}-${dayIndex}`;
          // Check if this cell is already filled
          if (!occupiedCells.has(key)) {
            // Fill the cell with the schedule information
            row[dayIndex + 1] = `${schedule.subject_code}\nSection: ${schedule.year_section}\nInstructor: ${schedule.instructor}`;
            occupiedCells.add(key); // Mark this cell as occupied
            // Store the display information
            displayCells[key] = { subject: schedule.subject_code, section: schedule.year_section, instructor: schedule.instructor, startTime: time, endTime: schedule.end_time, dayIndex: dayIndex };
          } else {
            // Leave it blank for subsequent rows
            row[dayIndex + 1] = "";
          }
        }
      }
    });

    timetable.push(row);
  });

  // Convert the timetable array to worksheet
  const ws = XLSX.utils.aoa_to_sheet(timetable);
  const wb = XLSX.utils.book_new();

  // Adjust cell merging based on displayCells
  Object.keys(displayCells).forEach((key) => {
    const { startTime, endTime, dayIndex } = displayCells[key];
    const startRow = timeSlots.indexOf(startTime) + 1; // +1 for headers
    const endRow = timeSlots.indexOf(endTime) + 1; // +1 for headers

    // Merge cells in the Excel sheet
    const cellRange = XLSX.utils.encode_range({
      s: { r: startRow, c: dayIndex + 1 }, // Start row, column (day index + 1 for time column)
      e: { r: endRow, c: dayIndex + 1 },   // End row, same column
    });

    if (!ws[cellRange]) {
      ws[cellRange] = { v: displayCells[key].subject + '\nSection: ' + displayCells[key].section + '\nInstructor: ' + displayCells[key].instructor, t: 's' }; // Add the merged cell value
    }

    // Mark the merged cells as blank
    for (let r = startRow + 1; r <= endRow; r++) {
      ws[XLSX.utils.encode_cell({ r, c: dayIndex + 1 })] = { v: "", t: 's' }; // Leave cells blank
    }
  });

  XLSX.utils.book_append_sheet(wb, ws, "Timetable");

  // Generate Excel file and download
  XLSX.writeFile(wb, "schedule_timetable.xlsx");
}

// Helper function to get day index
function getDayIndex(days) {
  const dayMap = {
    Monday: 0,
    Tuesday: 1,
    Wednesday: 2,
    Thursday: 3,
    Friday: 4,
    Saturday: 5,
  };

  // Split the days and return the index of the first matched day
  const daysArray = days.split(","); // Adjust based on how days are formatted
  for (const day of daysArray) {
    const trimmedDay = day.trim();
    if (dayMap[trimmedDay] !== undefined) {
      return dayMap[trimmedDay];
    }
  }
  return -1; // No matching day found
}

    </script>
    </body>
    </html>
