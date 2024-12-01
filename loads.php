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
    if (!in_array($_SESSION['role'], ['Dept. Head', 'Admin',  'Registrar'])) {
        // Redirect to a page indicating unauthorized access
        header("Location: unauthorized");
        exit();
    }

    // Check user role from the session
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

    // Determine the redirection URL based on the role
    $redirectUrl = ($role === 'Registrar') ? 'loads_management.php' : 'loads_upload.php';
    
    require_once 'database/config.php';

    function convertTo12HourFormat($time) {
        $timestamp = strtotime($time);
        return date('h:i A', $timestamp);
    }
    // Fetch schedules from schedules_tbl
    $query = "SELECT * FROM schedules";
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

    // Fetch unique sections
    $sectionsQuery = "SELECT DISTINCT section FROM schedules ORDER BY section";
    $sectionsResult = $conn->query($sectionsQuery);

    // Fetch unique faculty
    $facultyQuery = "SELECT DISTINCT instructor FROM schedules ORDER BY instructor";
    $facultyResult = $conn->query($facultyQuery);


// Fetch the current term and its term_id
$currentTermQuery = "SELECT term_id FROM terms_tbl WHERE term_status = 'Current' LIMIT 1"; // Add LIMIT 1 to ensure only one result
$currentTermResult = $conn->query($currentTermQuery);
$termId = ''; // Initialize termId variable

// Check if the query was successful and if we have any result
if ($currentTermResult && $currentTermResult->num_rows > 0) {
    // Fetch the row containing term_id
    $currentTermRow = $currentTermResult->fetch_assoc();
    // Assign the term_id value to $termId
    $termId = $currentTermRow['term_id'];
} else {
    // Handle the case when no current term is found
    die("No current term found.");
}

// Define the predefined order of days (Monday to Saturday)
$dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Fetch schedules from the database
$query = "SELECT ay_semester, user_id, section, GROUP_CONCAT(DISTINCT days ORDER BY FIELD(days, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') SEPARATOR ', ') AS days, subject_code, start_time, end_time
    FROM schedules
    WHERE ay_semester = '$termId' AND user_id = '{$_SESSION['user_id']}'
    GROUP BY section, subject_code, start_time, end_time";
$result = $conn->query($query);

$data = [];

while ($row = $result->fetch_assoc()) {
    $section = $row['section'];
    $daysArray = explode(',', $row['days']); // Split the comma-separated days into an array
    $daysArray = array_unique($daysArray);   // Remove duplicate days

    // Sort days based on predefined order
    usort($daysArray, function ($a, $b) use ($dayOrder) {
        return array_search($a, $dayOrder) - array_search($b, $dayOrder);
    });

    $sortedDays = implode(', ', $daysArray); // Join sorted days into a string

    // Store the data in the array
    if (!isset($data[$section])) {
        $data[$section] = [
            'days' => $sortedDays,
            'schedules' => []
        ];
    }

    // Add each schedule for that section
    foreach ($daysArray as $day) {
        $data[$section]['schedules'][] = [
            'subject_code' => $row['subject_code'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'day' => $day
        ];
    }
}

// Sort the schedules within each section by day
foreach ($data as &$sectionData) {
    usort($sectionData['schedules'], function ($a, $b) use ($dayOrder) {
        return array_search($a['day'], $dayOrder) - array_search($b['day'], $dayOrder);
    });
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

function toggleAccordion(element) {
    const bodies = document.querySelectorAll('.accordion-body'); // Select all accordion bodies
    const body = element.nextElementSibling; // The body of the clicked accordion item

    // Loop through all bodies and hide them, except the one that is clicked
    bodies.forEach(b => {
        if (b !== body) {
            b.classList.add('hidden');
        }
    });

    // Toggle the visibility of the clicked accordion body
    body.classList.toggle('hidden');
}


        function toggleFilters() {
            const filterType = document.getElementById('filterType').value;
            
            // Hide all filter dropdowns first
            document.getElementById('AYSelect').classList.add('hidden');
            document.getElementById('sectionSelect').classList.add('hidden');
            document.getElementById('facultySelect').classList.add('hidden');
            document.getElementById('buildingSelect').classList.add('hidden');
            document.getElementById('roomSelect').classList.add('hidden');

            // Show relevant dropdowns based on filter type
            if (filterType === 'section') {
                document.getElementById('AYSelect').classList.remove('hidden');
                document.getElementById('sectionSelect').classList.remove('hidden');
            } else if (filterType === 'faculty') {
                document.getElementById('AYSelect').classList.remove('hidden');
                document.getElementById('facultySelect').classList.remove('hidden');
            } else if (filterType === 'room') {
                document.getElementById('AYSelect').classList.remove('hidden');
                document.getElementById('buildingSelect').classList.remove('hidden');
                document.getElementById('roomSelect').classList.remove('hidden');
            }

            // Clear the calendar when changing filter type
            if (calendar) {
                calendar.removeAllEvents();
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
                hiddenDays: [0],
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
                    const filterType = document.getElementById('filterType').value;
                    const ayId = document.getElementById('AYSelect').value;
                    let params = `ayId=${ayId}`;

                    if (!filterType || !ayId) {
                        successCallback([]);
                        return;
                    }

                    if (filterType === 'section') {
                        const section = document.getElementById('sectionSelect').value;
                        if (!section) {
                            successCallback([]);
                            return;
                        }
                        params += `&section=${encodeURIComponent(section)}`;
                    } else if (filterType === 'faculty') {
                        const faculty = document.getElementById('facultySelect').value;
                        if (!faculty) {
                            successCallback([]);
                            return;
                        }
                        params += `&instructor=${encodeURIComponent(faculty)}`;
                    } else if (filterType === 'room') {
                        const buildingId = document.getElementById('buildingSelect').value;
                        const roomId = document.getElementById('roomSelect').value;
                        if (!buildingId || !roomId) {
                            successCallback([]);
                            return;
                        }
                        params += `&roomId=${roomId}&buildingId=${buildingId}`;
                    }

                    fetch(`handlers/fetch_all_sched.php?${params}`)
                        .then(response => response.json())
                        .then(data => {
                            successCallback(data || []);
                        })
                        .catch(error => failureCallback(error));
                },
                eventDidMount: function(info) {
                    let eventElement = info.el.querySelector('.fc-event-title');
                    if (eventElement) {
                        eventElement.innerHTML = `
                            <div><strong>${info.event.title}</strong></div>
                            <div>Section: ${info.event.extendedProps.section}</div>
                            <div>Instructor: ${info.event.extendedProps.instructor}</div>
                            <div>Room: ${info.event.extendedProps.building} - ${info.event.extendedProps.room}</div>
                        `;
                    }
                },
                eventClick: function(info) {
                    alert(`Subject: ${info.event.title}
                    \nInstructor: ${info.event.extendedProps.instructor}
                    \nSection: ${info.event.extendedProps.section}
                    \nRoom: ${info.event.extendedProps.building} - ${info.event.extendedProps.room}
                    `);
                }
            });

            calendar.render();

            // Event listeners for dropdowns
            document.getElementById('filterType').addEventListener('change', updateCalendar);
            document.getElementById('AYSelect').addEventListener('change', updateCalendar);
            document.getElementById('sectionSelect').addEventListener('change', updateCalendar);
            document.getElementById('facultySelect').addEventListener('change', updateCalendar);
            document.getElementById('buildingSelect').addEventListener('change', updateCalendar);
            document.getElementById('roomSelect').addEventListener('change', updateCalendar);
        });

        function updateCalendar() {
            if (calendar) {
                calendar.refetchEvents();
            }
        }

        function filterRooms() {
            const buildingId = document.getElementById('buildingSelect').value;
            const roomSelect = document.getElementById('roomSelect');
            const roomOptions = roomSelect.getElementsByTagName('option');

            for (let i = 0; i < roomOptions.length; i++) {
                const option = roomOptions[i];
                if (buildingId === '' || option.getAttribute('data-building-id') === buildingId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }

            const visibleOptions = Array.from(roomOptions).filter(option => option.style.display !== 'none');
            if (visibleOptions.length === 1) {
                roomSelect.value = '';
            }
        }

    </script>
    <style>
        .filter-option {
            width: 150px; /* Set fixed width for all dropdowns */
        }
        #custom-dialog {
            z-index: 10000;
        }
        #confirmation-modal {
            z-index: 10000;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen bg-gray-100">
        <!-- Sidebar -->
        <div id="sidebar-container">
            <?php include 'sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="flex flex-col flex-1">
            <header class="bg-white shadow-lg">
                <div class="flex items-center justify-between px-6 py-3 border-b">
                    <h2 class="text-lg font-semibold">Room Loads</h2>
                </div>
            </header>

            <main class="flex-1 p-4 flex h-full">
                <div class="w-3/4 rounded-lg flex flex-col h-full">
                    <div class="flex justify-between">
                        <div class="mb-1 items-center text-md">
                            <!-- Add Schedule Button -->
                            <button id="add-schedule-button" 
                                    onclick="window.location.href='<?php echo $redirectUrl; ?>'" 
                                    class="px-3 py-2 mr-4 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-150 ease-in-out" 
                                    title="Add Schedules">
                                <i class="fa-solid fa-circle-plus"></i>
                            </button>

                            <!-- Filter Type Selection -->
                            <select id="filterType" class="filter-option px-4 py-2 border border-gray-300 rounded-md mb-2" onchange="toggleFilters()">
                                <option value="">Select Filter Type...</option>
                                <option value="section">Filter by Section</option>
                                <option value="faculty">Filter by Faculty</option>
                                <option value="room">Filter by Room</option>
                            </select>

                            <!-- Academic Year Select (Always visible) -->
                            <select id="AYSelect" class="filter-option px-4 py-2 border border-gray-300 rounded-md mb-2 hidden">
                                <option value="">Academic Year...</option>
                                <?php
                                    $query = "SELECT * FROM terms_tbl";
                                    $result = $conn->query($query);
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<option value="' . $row['term_id'] . '">' . $row['academic_year'] . ' - ' . $row['semester'] . '</option>';
                                        }
                                    }
                                ?>
                            </select>

                            <!-- Section Select -->
                            <select id="sectionSelect" class="filter-option px-4 py-2 border border-gray-300 rounded-md mb-2 hidden">
                                <option value="">Select Section...</option>
                                <?php while ($section = $sectionsResult->fetch_assoc()): ?>
                                    <option value="<?php echo $section['section']; ?>"><?php echo $section['section']; ?></option>
                                <?php endwhile; ?>
                            </select>

                            <!-- Faculty Select -->
                            <select id="facultySelect" class="filter-option px-4 py-2 border border-gray-300 rounded-md mb-2 hidden">
                                <option value="">Select Faculty...</option>
                                <?php while ($faculty = $facultyResult->fetch_assoc()): ?>
                                    <option value="<?php echo $faculty['instructor']; ?>"><?php echo $faculty['instructor']; ?></option>
                                <?php endwhile; ?>
                            </select>

                            <!-- Building Select -->
                            <select id="buildingSelect" class="filter-option px-4 py-2 border border-gray-300 rounded-md mb-2 hidden">
                                <option value="">All Buildings</option>
                                <?php if ($buildingsResult->num_rows > 0): ?>
                                    <?php while ($building = $buildingsResult->fetch_assoc()): ?>
                                        <option value="<?php echo $building['building_id']; ?>"><?php echo $building['building_name']; ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>

                            <!-- Room Select -->
                            <select id="roomSelect" class="filter-option px-4 py-2 border border-gray-300 rounded-md mb-2 hidden">
                                <option value="">All Rooms</option>
                                <?php foreach ($roomsByBuilding as $buildingId => $rooms): ?>
                                    <?php foreach ($rooms as $room): ?>
                                        <option value="<?php echo $room['room_id']; ?>" data-building-id="<?php echo $buildingId; ?>">
                                            <?php echo $room['room_name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <button id="exportButton" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition duration-150 ease-in-out">
                                <i class="fa-solid fa-file-export"></i> Export Schedules
                            </button>
                        </div>
                    </div>

                    <div id="calendar" class="rounded-lg bg-white p-1 shadow-md flex-1"></div>
                </div>
                <!--Line Spacer-->
                <div class="h-full border-l ml-2 border-gray-300"></div>
                <!--Right Side-->
<div class="flex flex-col h-full w-1/4 pl-2">
    <div class="accordion">
        <h1 class="py-2 mb-2 text-lg font-semibold ">Your uploaded schedules this Semester</h1>

        <?php if (empty($data)): ?>
            <!-- Show message and image when no schedules are uploaded -->
            <div class="flex flex-col items-center justify-center text-center bg-white p-4 rounded-md">
                <img src="img/undraw_not_found_re_bh2e.svg" alt="No Schedules" class="w-1/2 h-1/2 opacity-30">
                <p class="text-md text-gray-600">No uploaded schedules yet</p>
            </div>
        <?php else: ?>
            <!-- Display the schedules when data is available -->
            <?php foreach ($data as $section => $details): ?>
                <div class="accordion-item border-b border-gray-200">
                    <!-- Accordion Header -->
                    <button onclick="toggleAccordion(this)" class="accordion-header w-full text-left py-2 px-4 bg-blue-300 hover:bg-blue-200 transition">
                        <span class="font-bold"><?php echo $section; ?></span> | 
                        <span class="text-gray-700"><?php echo count($details['schedules']); ?> Schedule/s</span> <!-- Display schedule count -->
                    </button>
                    
                    <!-- Accordion Body -->
                    <div class="accordion-body hidden bg-gray-200 ">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="text-left">
                                    <th class="border-b py-2 px-2">Subject</th>
                                    <th class="border-b py-2 px-2">Time</th>
                                    <th class="border-b py-2 px-2">Day</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($details['schedules'] as $schedule): ?>
                                <tr>
                                    <td class="border-b py-2 px-2"><?php echo $schedule['subject_code']; ?></td>
                                    <td class="border-b py-2 px-2">
                                        <?php echo date('h:iA', strtotime($schedule['start_time'])); ?> - 
                                        <?php echo date('h:iA', strtotime($schedule['end_time'])); ?>
                                    </td>
                                    <td class="border-b py-2 px-4"><?php echo $schedule['day']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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
    <!-- Add this HTML just before the closing body tag -->
<div id="export-format-dialog" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-md">
        <h3 class="text-lg font-semibold mb-4">Export Schedule</h3>
        
        <div class="space-y-4">
            <div class="flex items-start space-x-3">
                <input type="radio" id="table-format" name="export-format" value="table" checked 
                       class="mt-1 border-gray-300 rounded-full">
                <label for="table-format" class="flex-1">
                    <div class="font-medium">Tabular Format</div>
                    <div class="text-sm text-gray-500">Export as a simple table with rows for each schedule</div>
                </label>
            </div>
            
            <div class="flex items-start space-x-3">
                <input type="radio" id="timetable-format" name="export-format" value="timetable"
                       class="mt-1 border-gray-300 rounded-full">
                <label for="timetable-format" class="flex-1">
                    <div class="font-medium">Timetable Format</div>
                    <div class="text-sm text-gray-500">Export as a visual timetable with time blocks</div>
                </label>
            </div>
        </div>
        
        <div class="flex justify-end space-x-3 mt-6">
            <button onclick="hideExportDialog()" 
                    class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                Cancel
            </button>
            <button onclick="handleExport()" 
                    class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                Export
            </button>
        </div>
    </div>
</div>
    <script src="scripts/logout.js"></script>
    <script>
// Update the exportButton click handler
document.getElementById("exportButton").onclick = showExportDialog;

function showExportDialog() {
  document.getElementById("export-format-dialog").classList.remove("hidden");
}

function hideExportDialog() {
  document.getElementById("export-format-dialog").classList.add("hidden");
}

function handleExport() {
  const format = document.querySelector(
    'input[name="export-format"]:checked'
  ).value;
  hideExportDialog();

  if (format === "table") {
    exportTableFormat();
  } else {
    exportTimetableFormat();
  }
}

// Your existing export function renamed to exportTableFormat
function exportTableFormat() {
  const filterType = document.getElementById("filterType").value;
  const ayId = document.getElementById("AYSelect").value;

  // Validate basic requirements
  if (!filterType || !ayId) {
    alert("Please select a Filter Type and Academic Year.");
    return;
  }

  // Build the query parameters based on filter type
  let params = `ayId=${ayId}`;
  let filterDescription = "";
  let exportIdentifier = "";

  switch (filterType) {
    case "section":
      const section = document.getElementById("sectionSelect").value;
      if (!section) {
        alert("Please select a Section.");
        return;
      }
      params += `&section=${encodeURIComponent(section)}`;
      filterDescription = `Section: ${section}`;
      exportIdentifier = section;
      break;

    case "faculty":
      const faculty = document.getElementById("facultySelect").value;
      if (!faculty) {
        alert("Please select a Faculty member.");
        return;
      }
      params += `&instructor=${encodeURIComponent(faculty)}`;
      filterDescription = `Faculty: ${faculty}`;
      exportIdentifier = faculty;
      break;

    case "room":
      const buildingId = document.getElementById("buildingSelect").value;
      const roomId = document.getElementById("roomSelect").value;
      if (!buildingId || !roomId) {
        alert("Please select both Building and Room.");
        return;
      }
      params += `&roomId=${roomId}&buildingId=${buildingId}`;
      const buildingName =
        document.getElementById("buildingSelect").options[
          document.getElementById("buildingSelect").selectedIndex
        ].text;
      const roomName =
        document.getElementById("roomSelect").options[
          document.getElementById("roomSelect").selectedIndex
        ].text;
      filterDescription = `Building/Room: ${buildingName} - ${roomName}`;
      exportIdentifier = `${buildingName}_${roomName}`;
      break;

    default:
      alert("Please select a valid filter type.");
      return;
  }

  // Define day order for sorting
  const dayOrder = {
    Monday: 1,
    Tuesday: 2,
    Wednesday: 3,
    Thursday: 4,
    Friday: 5,
    Saturday: 6,
  };

  // Function to get the sort value for a day string
  const getDayValue = (dayString) => {
    const days = dayString.split("/");
    return Math.min(...days.map((day) => dayOrder[day.trim()] || 999));
  };

  // Fetch the data
  fetch(`handlers/export_sched.php?${params}`)
    .then((response) => response.json())
    .then((data) => {
      if (data && data.length > 0) {
        // Get the academic year and semester info from the first event
        const academicYear = data[0].extendedProps.AcademicYear;
        const semester = data[0].extendedProps.semester;

        // Prepare and sort the worksheet data
        const worksheetData = data
          .map((event) => ({
            Subject: event.title,
            Instructor: event.instructor,
            Section: event.section,
            Day: event.extendedProps.days,
            Time: `${formatTime(event.startTime)} - ${formatTime(
              event.endTime
            )}`,
            Room: `${event.extendedProps.building} - ${event.extendedProps.room}`,
            _dayValue: getDayValue(event.extendedProps.days),
          }))
          .sort((a, b) => {
            if (a._dayValue !== b._dayValue) {
              return a._dayValue - b._dayValue;
            }
            return a.Time.localeCompare(b.Time);
          });

        // Remove the _dayValue property used for sorting
        worksheetData.forEach((row) => delete row._dayValue);

        // Create workbook and worksheet
        const wb = XLSX.utils.book_new();

        // Create worksheet with headers and data
        const ws = XLSX.utils.aoa_to_sheet([
          [`Academic Year: ${academicYear} - ${semester}`, "", "", "", "", ""],
          [filterDescription, "", "", "", "", ""],
          [""], // Blank row for spacing
          ["Subject", "Instructor", "Section", "Day", "Time", "Room"],
        ]);

        // Append the data rows
        XLSX.utils.sheet_add_aoa(
          ws,
          worksheetData.map((row) => [
            row.Subject,
            row.Instructor,
            row.Section,
            row.Day,
            row.Time,
            row.Room,
          ]),
          { origin: -1 }
        );

        // Set column widths
        const cols = [
          { wch: 15 }, // Subject
          { wch: 20 }, // Instructor
          { wch: 15 }, // Section
          { wch: 20 }, // Day
          { wch: 20 }, // Time
          { wch: 30 }, // Room
        ];
        ws["!cols"] = cols;

        // Define merge ranges for headers
        ws["!merges"] = [
          { s: { r: 0, c: 0 }, e: { r: 0, c: 5 } }, // Merge first row
          { s: { r: 1, c: 0 }, e: { r: 1, c: 5 } }, // Merge second row
        ];

        // Add the worksheet to workbook
        XLSX.utils.book_append_sheet(wb, ws, "Schedule");

        // Generate descriptive filename based on filter type and academic info
        const cleanAcademicInfo = `${academicYear}_${semester}`.replace(
          /\s+/g,
          "_"
        );
        const cleanIdentifier = exportIdentifier
          .replace(/[^\w\s-]/g, "_")
          .replace(/\s+/g, "_");
        const filename = `${cleanIdentifier}_${cleanAcademicInfo}.xlsx`;

        // Write the file
        XLSX.writeFile(wb, filename);
      } else {
        alert("No schedules found for the selected filters.");
      }
    })
    .catch((error) => {
      console.error("Error exporting schedules:", error);
      alert(
        "An error occurred while exporting the schedules. Please try again."
      );
    });
  // Helper function to format time
  function formatTime(timeString) {
    if (!timeString) return "";
    const [hours, minutes] = timeString.split(":");
    const time = new Date();
    time.setHours(parseInt(hours), parseInt(minutes));
    return time.toLocaleTimeString("en-US", {
      hour: "numeric",
      minute: "2-digit",
      hour12: true,
    });
  }
}

function exportTimetableFormat() {
  // Validate input selections
  const filterType = document.getElementById("filterType").value;
  const ayId = document.getElementById("AYSelect").value;

  if (!filterType || !ayId) {
    alert("Please select a Filter Type and Academic Year.");
    return;
  }

  // Build query parameters based on filter type
  let params = `ayId=${ayId}`;
  let filterDescription = "";
  let exportIdentifier = "";

  try {
    // Filter type selection (similar to previous implementation)
    switch (filterType) {
      case "section":
        const section = document.getElementById("sectionSelect").value;
        if (!section) {
          alert("Please select a Section.");
          return;
        }
        params += `&section=${encodeURIComponent(section)}`;
        filterDescription = `Section: ${section}`;
        exportIdentifier = section;
        break;

      case "faculty":
        const faculty = document.getElementById("facultySelect").value;
        if (!faculty) {
          alert("Please select a Faculty member.");
          return;
        }
        params += `&instructor=${encodeURIComponent(faculty)}`;
        filterDescription = `Faculty: ${faculty}`;
        exportIdentifier = faculty;
        break;

      case "room":
        const buildingId = document.getElementById("buildingSelect").value;
        const roomId = document.getElementById("roomSelect").value;
        if (!buildingId || !roomId) {
          alert("Please select both Building and Room.");
          return;
        }
        params += `&roomId=${roomId}&buildingId=${buildingId}`;
        const buildingName = document.getElementById("buildingSelect").options[
          document.getElementById("buildingSelect").selectedIndex
        ].text;
        const roomName = document.getElementById("roomSelect").options[
          document.getElementById("roomSelect").selectedIndex
        ].text;
        filterDescription = `Building/Room: ${buildingName} - ${roomName}`;
        exportIdentifier = `${buildingName}_${roomName}`;
        break;

      default:
        alert("Please select a valid filter type.");
        return;
    }

    // Fetch schedule data
    fetch(`handlers/export_sched.php?${params}`)
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        if (!data || data.length === 0) {
          alert("No schedules found for the selected filters.");
          return;
        }

        // Create workbook and worksheet
        const wb = XLSX.utils.book_new();
        const days = [
          "Monday",
          "Tuesday", 
          "Wednesday",
          "Thursday",
          "Friday",
          "Saturday",
        ];

        // Generate time slots from 7:00 AM to 10:00 PM with 30-minute intervals in 12-hour format
        const timeSlots = generateTimeSlots();

        // Prepare worksheet data with time slots
        const wsData = [
          [
            `Schedule - ${data[0].extendedProps.AcademicYear} - ${data[0].extendedProps.semester}`,
            "",
            "",
            "",
            "",
            "",
            "",
          ],
          [filterDescription, "", "", "", "", "", ""],
          [""], // Spacing row
          ["Time", ...days], // Column headers
        ];

        // Add time slots as rows
        timeSlots.forEach((timeSlot) => {
          const row = [timeSlot, "", "", "", "", "", ""];
          wsData.push(row);
        });

        // Create worksheet first
        const ws = XLSX.utils.aoa_to_sheet(wsData);

        // Prepare merge ranges
        const mergeRanges = [
          { s: { r: 0, c: 0 }, e: { r: 0, c: 6 } }, // Title row
          { s: { r: 1, c: 0 }, e: { r: 1, c: 6 } }, // Filter description row
        ];

        // Process and map schedules to appropriate cells
        data.forEach((schedule) => {
          const dayIndex = days.indexOf(schedule.extendedProps.days) + 1;
          if (dayIndex === 0) return; // Skip if day not found

          // Convert start and end times to 30-minute interval indices
          const startTimeIndex = findTimeSlotIndex(
            timeSlots,
            convertTo12HourFormat(schedule.startTime)
          );
          const endTimeIndex = findTimeSlotIndex(
            timeSlots,
            convertTo12HourFormat(schedule.endTime)
          );

          // Adjust end index to be the cell BEFORE the end time
          const adjustedEndTimeIndex = Math.max(0, endTimeIndex - 1);

          if (startTimeIndex !== -1 && adjustedEndTimeIndex !== -1) {
            // Prepare schedule details with 12-hour format times
            const startTime12hr = convertTo12HourFormat(schedule.startTime);
            const endTime12hr = convertTo12HourFormat(schedule.endTime);
            const scheduleDetails =
              `${startTime12hr} - ${endTime12hr}\n` +
              `${schedule.title}\n` +
              `${schedule.section}\n` +
              `${schedule.instructor}\n` +
              `${schedule.extendedProps.building} - ${schedule.extendedProps.room}`;

            // Merge cells for the entire schedule duration
            const mergeRange = {
              s: { r: startTimeIndex + 4, c: dayIndex },
              e: { r: adjustedEndTimeIndex + 4, c: dayIndex },
            };

            // Add schedule details to the first cell of the merged range
            ws[XLSX.utils.encode_cell({ r: startTimeIndex + 4, c: dayIndex })] =
              {
                v: scheduleDetails,
                t: "s", // string type
              };

            // Add merge range
            mergeRanges.push(mergeRange);
          }
        });

        // Set merge ranges
        ws["!merges"] = mergeRanges;

        // Set column widths
        ws["!cols"] = [
          { wch: 10 },   // Time column
          { wch: 35 },   // Monday
          { wch: 35 },   // Tuesday
          { wch: 35 },   // Wednesday
          { wch: 35 },   // Thursday
          { wch: 35 },   // Friday
          { wch: 35 }    // Saturday
        ];

        // Styling
        const range = XLSX.utils.decode_range(ws["!ref"]);
        for (let R = range.s.r; R <= range.e.r; R++) {
          for (let C = range.s.c; C <= range.e.c; C++) {
            const cell_ref = XLSX.utils.encode_cell({ r: R, c: C });
            
            // Initialize style if not exists
            if (!ws[cell_ref]) ws[cell_ref] = {};
            if (!ws[cell_ref].s) ws[cell_ref].s = {};

            ws[cell_ref].s = {
              font: { 
                sz: R < 2 ? 14 : (R === 3 ? 12 : 11),
                bold: R < 4
              },
              alignment: { 
                horizontal: R === 3 || C === 0 ? "center" : "left", 
                vertical: "center", 
                wrapText: true 
              },
              border: (R > 2 && R <= range.e.r) ? {
                top: { style: "thin" },
                bottom: { style: "thin" },
                left: { style: "thin" },
                right: { style: "thin" }
              } : undefined
            };

            // Additional styling for headers and schedule cells
            if (R === 3) { // Day headers
              ws[cell_ref].s.fill = {
                fgColor: { rgb: "4F81BD" },
                patternType: "solid"
              };
              ws[cell_ref].s.font.color = { rgb: "FFFFFF" };
            }

            // Background for scheduled slots
            if (R > 3 && C > 0 && ws[cell_ref] && ws[cell_ref].v !== "") {
              ws[cell_ref].s.fill = {
                fgColor: { rgb: "E8F0FE" },
                patternType: "solid"
              };
            }
          }
        }

        // Set row heights
        ws["!rows"] = Array(range.e.r + 1).fill({ hpt: 45 });

        // Add worksheet to workbook
        XLSX.utils.book_append_sheet(wb, ws, "Schedule");

        // Generate filename
        const cleanAcademicInfo = `${data[0].extendedProps.AcademicYear}_${data[0].extendedProps.semester}`
          .replace(/\s+/g, "_")
          .replace(/[^a-zA-Z0-9_-]/g, "");
        const cleanIdentifier = exportIdentifier
          .replace(/[^\w\s-]/g, "_")
          .replace(/\s+/g, "_");
        const filename = `${cleanIdentifier}_${cleanAcademicInfo}_timetable.xlsx`;

        // Export the file
        XLSX.writeFile(wb, filename);
      })
      .catch((error) => {
        console.error("Error exporting schedules:", error);
        alert(
          `An error occurred while exporting the schedules: ${error.message}`
        );
      });
  } catch (error) {
    console.error("Unexpected error in export function:", error);
    alert("An unexpected error occurred during export.");
  }
}

// Helper function to generate time slots in 12-hour format
function generateTimeSlots() {
  const timeSlots = [];
  for (let hour = 7; hour <= 22; hour++) {
    const amPm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour > 12 ? hour - 12 : (hour === 0 ? 12 : hour);
    
    timeSlots.push(`${displayHour}:00 ${amPm}`);
    timeSlots.push(`${displayHour}:30 ${amPm}`);
  }
  return timeSlots;
}

// Helper function to convert 24-hour time to 12-hour format
function convertTo12HourFormat(time24hr) {
  const [hours, minutes] = time24hr.split(':').map(Number);
  const amPm = hours >= 12 ? 'PM' : 'AM';
  const displayHour = hours % 12 || 12;
  return `${displayHour}:${minutes.toString().padStart(2, '0')} ${amPm}`;
}

// Helper function to find the index of a time slot
function findTimeSlotIndex(timeSlots, targetTime) {
  return timeSlots.findIndex((slot) => slot === targetTime);
}

    </script>
</body>
</html>