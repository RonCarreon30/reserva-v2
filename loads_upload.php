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

    // Assuming user_department is stored in the session
    $user_department = isset($_SESSION['department']) ? $_SESSION['department'] : 'Unknown';

    require 'database/config.php'; 

    try {
        // Connect to the database
        $pdo = new PDO('mysql:host=localhost;dbname=reservadb', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch all departments, sorting to place the user's department at the top
        $sql = "
            SELECT dept_id, dept_name 
            FROM dept_tbl 
            ORDER BY (dept_name = :user_department) DESC, dept_name ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_department', $user_department, PDO::PARAM_STR);
        $stmt->execute();
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLV: RESERVA</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@^2.2.15/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                    <h2 class="text-lg font-semibold">Room Loading</h2>
                </div>
            </header>
            <main class="flex-1 p-4 overflow-y-auto">
                <div class="flex items-center space-x-4 justify-between">
                    <div class="space-x-12">
                        <button onclick="window.location.href='loads-deptHead.php'" class="px-3 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-150 ease-in-out" title="View Room Loads">
                            <i class="fa-solid fa-calendar-check"></i>
                        </button>
                        <button class="px-4 py-2 bg-plv-blue text-white rounded-lg hover:bg-plv-highlight transition duration-150 ease-in-out" onclick="showUploadModal()">
                            <i class="fa-solid fa-file-upload"></i> Upload Schedule
                        </button>
                    </div>
                    <button class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition duration-150 ease-in-out" onclick="window.location.href='handlers/download_template.php'">
                        <i class="fa-solid fa-file-download"></i> Download Template
                    </button>
                </div>
                <!-- Toast Notification -->
                <div id="toast" class="fixed right-4 text-white p-4 rounded-lg shadow-lg opacity-0 transform translate-y-4 transition-opacity transition-transform duration-300">
                    <span id="toast-message"></span>
                </div>
                <div id="loading-spinner" class="hidden flex justify-center items-center">
                    <div class="animate-spin h-8 w-8 border-4 border-t-transparent border-blue-500 rounded-full"></div>
                </div>
                <!-- Table for Pending Schedules -->
                <div class="mt-6">
                    <h3 class="text-lg font-semibold mb-2">Uploaded Schedules</h3>
                    <div class="overflow-x-auto">
                        <table id="pending-schedules-table" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instructor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day/s</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody id="pending-schedules-body" class="bg-white divide-y divide-gray-200">
                                <!-- Rows will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div> 

                
            </main>
        </div>
    </div>
    <!-- Upload Modal -->
    <div id="uploadModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-md flex flex-col">
            <h1 class="text-xl font-bold mb-4">File Upload</h1>

            <div class="mb-4">
                <label for="aySemester" class="block text-sm font-medium text-gray-700">Academic Year & Semester</label>
                <select id="aySemester" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="" disabled selected>Select Academic Year & Semester</option>
                        <?php
                            // Query to get terms values
                            $query = "SELECT * FROM terms_tbl";
                            $result = $conn->query($query);
                            // Loop through the result and create an <option> element for each building
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="' . $row['term_id'] . '">' . $row['academic_year'] . ' - ' . $row['semester'] . '</option>';
                                }
                            } else {
                                echo '<option value="">No data available</option>';
                            }  
                        ?>
                    <!-- Add more options as needed -->
                </select>
            </div>

            <div class="mb-4">
                <label for="department-dropdown" class="block text-sm font-medium text-gray-700">Department</label>
                <select id="department-dropdown" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= htmlspecialchars($department['dept_id']); ?>" 
                            <?= ($department['dept_name'] == $user_department) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($department['dept_name']); ?>
                        </option>

                    <?php endforeach; ?>
                </select>
            </div>

            <div id="upload-container">
                <input type="file" id="file-input" accept=".xlsx, .xls"
                    class="w-full text-gray-400 font-semibold bg-white border file:cursor-pointer cursor-pointer file:border-0 file:py-3 file:px-4 file:mr-4 file:bg-gray-100 file:hover:bg-gray-200 file:text-gray-500 rounded" />
                <p class="text-sm text-gray-400 mt-2">Only the provided template is allowed.</p>
            </div>

            <div class="flex justify-between mt-5">
                <button id="cancel-upload" onclick="cancelUpload()" class="mr-4 px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">Cancel</button>
                <button id="confirm-upload" class="px-4 py-2 bg-plv-blue text-white rounded-lg hover:bg-plv-highlight">Upload</button>
            </div>
        </div>
    </div>
        <!-- Modal Background -->
<div id="parsed-sched-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center h-full">
        <!-- Modal Content -->
        <div class="bg-white rounded-lg shadow-lg w-full max-w-4xl mx-4 p-6">
            <!-- Modal Header -->
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Parsed Schedules</h2>
            </div>

            <!-- Display Academic Year & Semester and Department -->
            <div id="schedule-table-container" class="overflow-x-auto mb-4">
                <div id="selected-info" class="mb-4 text-md text-gray-600">
                    <!-- Selected values will be populated here -->
                </div>
                <table id="schedule-table" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instructor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day/s</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class Type</th>
                        </tr>
                    </thead>
                    <tbody id="schedule-table-body" class="bg-white divide-y divide-gray-200">
                        <!-- Rows will be added here dynamically -->
                        <span id="user-department" class="hidden"><?php echo htmlspecialchars($user_department); ?></span>
                    </tbody>
                </table>
            </div>

            <!-- Buttons for Actions -->
            <div class="flex justify-end space-x-4">
                <button id="cancel-action" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-150 ease-in-out">
                    Cancel
                </button>
                <button id="save-action" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition duration-150 ease-in-out">
                    Save
                </button>
            </div>
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
    <script src="scripts/logout.js"></script>

    <script>
        function showUploadModal() {
            document.getElementById('uploadModal').classList.remove('hidden');
        }

        function cancelUpload() {
            // Handle cancelation if needed
            document.getElementById('uploadModal').classList.add('hidden');
        }

        document.getElementById("cancel-action").addEventListener("click", function () {
            document.getElementById("schedule-table-body").innerHTML = "";
            document.getElementById("selected-info").innerHTML = ""; // Clear the selected info
            document.getElementById("parsed-sched-modal").classList.add("hidden");
        });

        document.getElementById("file-input").addEventListener("change", function () {
            let fileInput = document.getElementById("file-input");
            let file = fileInput.files[0];

            if (file) {
                document.getElementById("confirm-upload").addEventListener("click", function () {
                    // Get the selected values from the dropdowns
                    const aySemester = document.getElementById("aySemester").options[document.getElementById("aySemester").selectedIndex].text;
                    const department = document.getElementById("department-dropdown").options[document.getElementById("department-dropdown").selectedIndex].text;

                    // Clear previous entries if any
                    document.getElementById("schedule-table-body").innerHTML = ""; 
                    document.getElementById("selected-info").innerHTML = ""; // Clear the selected info

                    // Update the modal with selected values
                    const parsedModalHeader = document.createElement('div');
                    parsedModalHeader.innerHTML = `
                        <p class="text-sm text-gray-600">Academic Year & Semester: ${aySemester}</p>
                        <p class="text-sm text-gray-600">Preferred Department: ${department}</p>
                    `;
                    document.getElementById("selected-info").appendChild(parsedModalHeader);

                    document.getElementById("parsed-sched-modal").classList.remove("hidden");
                    
                    let formData = new FormData();
                    formData.append("file", file);

                    fetch("handlers/parse.php", {
                        method: "POST",
                        body: formData,
                    })
                    .then((response) => response.text())
                    .then((text) => {
                        try {
                            let data = JSON.parse(text); // Then attempt to parse it as JSON
                            console.log("Parsed JSON:", data);

                            if (data.success) {
                                let schedules = data.schedules;

                                displayTable(schedules);
                                showToast("Parsed successfully!", "bg-green-500");
                            } else {
                                document.getElementById("parsed-sched-modal").classList.add("hidden");
                                showToast("Failed to upload file. " + (data.error || ""), "bg-red-500");
                            }
                        } catch (e) {
                            console.error("Parsing error:", e);
                            document.getElementById("parsed-sched-modal").classList.add("hidden");
                            showToast("An error occurred while processing the file.", "bg-red-500");
                        }
                    })
                    .catch((error) => {
                        document.getElementById("parsed-sched-modal").classList.add("hidden");
                        console.error("Error:", error);
                        showToast("An error occurred.", "bg-red-500");
                    });
                });
            }
        });


        function showToast(message, bgColor) {
        let toast = document.getElementById("toast");
        let messageSpan = document.getElementById("toast-message");

        // Ensure the toast starts hidden
        toast.classList.add("opacity-0", "translate-y-4");
        toast.classList.remove("opacity-100", "translate-y-0");

        // Clear existing background color classes
        toast.classList.remove("bg-green-500", "bg-red-500", "bg-blue-500");

        // Set the new background color class
        toast.classList.add(bgColor);

        // Determine the icon based on bgColor
        let icon;
        if (bgColor === "bg-green-500") {
            icon = "fas fa-check-circle"; // Success icon
        } else if (bgColor === "bg-red-500") {
            icon = "fas fa-exclamation-circle"; // Error icon
        } else if (bgColor === "bg-blue-500") {
            icon = "fas fa-spinner fa-spin"; // Loading icon
        } else {
            icon = "fas fa-info-circle"; // Default info icon
        }

        // Set the toast message with the appropriate icon
        messageSpan.innerHTML = `<i class="${icon}"></i> ${message}`;

        // Show the toast
        toast.classList.remove("opacity-0", "translate-y-4");
        toast.classList.add("opacity-100", "translate-y-0");

        // Hide the toast after 3 seconds (or keep it for loading until manually closed)
        if (bgColor !== "bg-blue-500") {
            // Only auto-hide if not a loading message
            setTimeout(() => {
            toast.classList.remove("opacity-100", "translate-y-0");
            toast.classList.add("opacity-0", "translate-y-4");
            }, 5000);
        }
        }

        function displayTable(schedules) {
        let tableBody = document.getElementById("schedule-table-body");
        tableBody.innerHTML = "";

        schedules.forEach((schedule) => {
            let row = document.createElement("tr");
            row.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap">${schedule.subject_code}</td>
                            <td class=" px-6 py-4 whitespace-nowrap">${schedule.subject}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${schedule.section}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${schedule.instructor}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${schedule.start_time} - ${schedule.end_time}</td>
                            <td class="hidden px-6 py-4 whitespace-nowrap">${schedule.start_time} - ${schedule.end_time}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${schedule.days}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${schedule.type}</td>
                            
                        `;
            tableBody.appendChild(row);
        });
        }
    
        //For displaying Scheds on table
        $(document).ready(function() {
            // Fetch pending schedules
            fetchPendingSchedules();

            function fetchPendingSchedules() {
                $.ajax({
                    url: 'handlers/fetch_pending_schedules.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        const $tbody = $('#pending-schedules-body');
                        $tbody.empty(); // Clear existing rows
                        data.forEach(schedule => {
                            const row = `<tr>
                                <td class="px-6 py-4 whitespace-nowrap">${schedule.subject_code}</td>
                                <td class="px-6 py-4 whitespace-nowrap">${schedule.section}</td>
                                <td class="px-6 py-4 whitespace-nowrap">${schedule.instructor}</td>
                                <td class="px-6 py-4 whitespace-nowrap">${schedule.time}</td>
                                <td class="px-6 py-4 whitespace-nowrap">${schedule.days}</td>
                                <td class="px-6 py-4 whitespace-nowrap">${schedule.schedule_status}</td>
                            </tr>`;
                            $tbody.append(row);
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching schedules:', error);
                    }
                });
            }
        });


        //Not yet done so continue fix
        document.getElementById("save-action").addEventListener("click", () => {
            let rows = Array.from(document.querySelectorAll("#schedule-table-body tr"));

            let schedules = rows.map((row) => {
                let cells = row.children;
                return {
                subject_code: cells[0] ? cells[0].textContent : "",
                subject: cells[1] ? cells[1].textContent : "",
                section: cells[2] ? cells[2].textContent : "",
                instructor: cells[3] ? cells[3].textContent : "",
                start_time: cells[4] ? cells[4].textContent.split(" - ")[0] : "",
                end_time: cells[5] ? cells[5].textContent.split(" - ")[1] : "",
                days: cells[6] ? cells[6].textContent : "",
                type: cells[7] ? cells[7].textContent : "",
                user_department: document.getElementById("user-department").textContent,
                };
            });

            let selectedDepartmentId = document.getElementById(
                "department-dropdown"
            ).value;
            let data = JSON.stringify({ schedules: schedules, selectedDepartmentId });

            // Show loading spinner
            const loader = document.getElementById("loading-spinner");
            loader.classList.remove("hidden");

            fetch("handlers/save.php", {
                method: "POST",
                headers: {
                "Content-Type": "application/json",
                },
                body: data,
            })
                .then((response) => response.json())
                .then((result) => {
                loader.classList.add("hidden"); // Hide loading spinner after fetching data

                if (result.success) {
                    document.getElementById("parsed-sched-modal").classList.add("hidden");
                    document.getElementById("uploadModal").classList.add("hidden");
                    showToast("Schedules saved successfully!", "bg-green-500");
                    document.getElementById("schedule-table-body").innerHTML = "";

                    // Run Genetic Algorithm after saving
                    showToast("Running genetic algorithm...", "bg-blue-500");
                    // Trigger the schedule assignment process
                    runGeneticAlgorithm();
                } else {
                    document.getElementById("parsed-sched-modal").classList.add("hidden");
                    showToast(
                    "Failed to save schedules. " + (result.error || ""),
                    "bg-red-500"
                    );
                }
                })
                .catch((error) => {
                loader.classList.add("hidden"); // Hide loading spinner on error
                document.getElementById("parsed-sched-modal").classList.add("hidden");
                console.error("Error:", error);
                showToast("An error occurred while saving the schedules.", "bg-red-500");
            });
        });

        // Function to fetch data from the PHP backend
async function fetchData() {
  try {
    const response = await fetch("handlers/fetch_data.php");
    if (!response.ok) {
      throw new Error(`HTTP error! Status: ${response.status}`);
    }
    const data = await response.json();
    return data;
  } catch (error) {
    console.error("Fetch error:", error);
    showToast("Failed to fetch data from server.", "bg-red-500");
    return null; // Return null if there's an error
  }
}

async function runGeneticAlgorithm() {
  const data = await fetchData();
  if (!data) {
    return; // Exit if fetching data failed
  }

  // Use the fetched data in your genetic algorithm
  const assignedRooms = data.assignedRooms;
  const schedules = data.schedules;
  const rooms = data.rooms;
  const departments = data.departments;

  // Function to check if a room assignment overlaps with existing assignments
  function isOverlap(newSchedule, roomAssignments) {
    if (!roomAssignments || roomAssignments.length === 0) {
      return false; // No overlap if there are no existing assignments
    }
    return roomAssignments.some((assignment) => {
      return (
        assignment.room_id === newSchedule.room_id && // Check same room
        assignment.day === newSchedule.days && // Check same day
        !(
          newSchedule.end_time <= assignment.start_time || // No overlap
          newSchedule.start_time >= assignment.end_time
        )
      );
    });
  }

  // Function to evaluate fitness of an assignment
  // Function to evaluate fitness of an assignment
  function evaluateFitness(assignments) {
    let fitness = 0;

    assignments.forEach((assignment) => {
      const schedule = schedules.find(
        (s) => s.schedule_id === assignment.schedule_id
      );

      if (!schedule) {
        console.error(`Schedule not found for assignment:`, assignment);
        return; // Skip this assignment if no schedule is found
      }

      console.log(`Evaluating fitness for schedule ${schedule.schedule_id}`);

      if (assignment.room_id === null) {
        console.warn(
          `No room assigned for schedule ID ${schedule.schedule_id}`
        );
        return; // Skip if no room is assigned
      }

      const room = rooms.find((r) => r.room_id === assignment.room_id);
      if (room && room.room_type === schedule.type) {
        fitness += 2; // Extra points for matching room type
      }

      console.log("Departments:", departments); // Log all departments
      console.log(
        `Checking for preferred department ID: ${schedule.pref_dept}`
      );

      // Ensure pref_dept is being compared correctly
      const preferredDept = departments.find(
        (dept) => dept.dept_id === schedule.pref_dept.toString()
      );

      if (!preferredDept) {
        console.warn(
          `Preferred department not found for schedule ID ${schedule.schedule_id} with pref_dept ${schedule.pref_dept}`
        );
        return; // Skip if preferred department is not found
      }

      console.log(`Found preferred department:`, preferredDept);

      if (
        room &&
        preferredDept &&
        room.building === preferredDept.dept_building
      ) {
        fitness += 3; // Extra points for department match
      }
    });

    console.log(`Total fitness score: ${fitness}`);
    return fitness;
  }

  // Function to create initial random population
  function createInitialPopulation(size, departments) {
    const population = [];

    for (let i = 0; i < size; i++) {
      const assignments = schedules
        .map((schedule) => {
          const availableRooms = rooms.filter(
            (room) =>
              room.room_type === schedule.type &&
              room.room_status === "Available" &&
              !isOverlap(schedule, assignedRooms) // Ensure no overlap
          );

          const preferredDept = departments.find(
            (dept) => dept.dept_id === schedule.pref_dept
          );

          const preferredDeptRooms = availableRooms.filter(
            (room) => room.building === preferredDept?.dept_building
          );

          const roomToAssign =
            preferredDeptRooms.length > 0
              ? preferredDeptRooms[
                  Math.floor(Math.random() * preferredDeptRooms.length)
                ]
              : availableRooms.length > 0
                ? availableRooms[
                    Math.floor(Math.random() * availableRooms.length)
                  ]
                : rooms[Math.floor(Math.random() * rooms.length)]; // Fallback to any room

          return {
            schedule_id: schedule.schedule_id,
            room_id: roomToAssign ? roomToAssign.room_id : null,
            days: schedule.days, // Include additional properties
            start_time: schedule.start_time,
            end_time: schedule.end_time,
            type: schedule.type,
            instructor: schedule.instructor, // Add more as needed
            section: schedule.section,
          };
        })
        .filter(Boolean); // Filter out null assignments

      population.push(assignments);
    }

    return population;
  }

  // Mutation function to randomly alter an assignment
  function mutate(assignments, mutationRate) {
    assignments.forEach((assignment) => {
      if (Math.random() < mutationRate) {
        const availableRooms = rooms.filter(
          (room) =>
            room.room_type ===
              schedules.find((s) => s.schedule_id === assignment.schedule_id)
                .type &&
            room.room_status === "Available" &&
            !isOverlap(assignment, assignedRooms)
        );

        const newRoom =
          availableRooms.length > 0
            ? availableRooms[Math.floor(Math.random() * availableRooms.length)]
            : rooms[Math.floor(Math.random() * rooms.length)];

        assignment.room_id = newRoom.room_id;
      }
    });
  }

  // Function to select parents based on fitness
  function selectParents(population) {
    const fitnessScores = population.map((assignments) =>
      evaluateFitness(assignments)
    );
    const totalFitness = fitnessScores.reduce((a, b) => a + b, 0);

    const selectionProbabilities = fitnessScores.map(
      (fitness) => fitness / totalFitness
    );
    const parents = [];

    while (parents.length < population.length) {
      const randomValue = Math.random();
      let cumulativeProbability = 0;

      for (let j = 0; j < selectionProbabilities.length; j++) {
        cumulativeProbability += selectionProbabilities[j];
        if (randomValue < cumulativeProbability) {
          parents.push(population[j]);
          break;
        }
      }
    }
    return parents;
  }

  // Crossover function to create new offspring
  function crossover(parent1, parent2) {
    const crossoverPoint = Math.floor(Math.random() * parent1.length);
    const child = parent1
      .slice(0, crossoverPoint)
      .concat(parent2.slice(crossoverPoint));
    return child;
  }

  // Main Genetic Algorithm function
  function geneticAlgorithm(generations, populationSize, mutationRate) {
    let population = createInitialPopulation(populationSize, departments);
    let bestSolution = null;
    let bestFitness = 0;

    for (let generation = 0; generation < generations; generation++) {
      const parents = selectParents(population);
      const newPopulation = [];

      for (let i = 0; i < parents.length; i += 2) {
        const parent1 = parents[i];
        const parent2 = parents[i + 1];

        if (parent2) {
          const child = crossover(parent1, parent2);
          mutate(child, mutationRate);
          newPopulation.push(child);
        }
      }

      population = newPopulation;

      population.forEach((assignments) => {
        const fitness = evaluateFitness(assignments);
        if (fitness > bestFitness) {
          bestFitness = fitness;
          bestSolution = assignments;
        }
      });
    }

    return { bestSolution, bestFitness };
  }

  // Run the Genetic Algorithm
  // After running the genetic algorithm
  try {
    const result = geneticAlgorithm(100, 50, 0.1);
    console.log("Best Assignments: ", result.bestSolution);
    console.log("Best Fitness: ", result.bestFitness);
    showToast("Genetic Algorithm completed successfully!", "bg-green-500");

    // Now save the best assignments to the database
    await saveAssignments(result.bestSolution, schedules); // Pass schedules along with assignments
  } catch (error) {
    console.error("Algorithm error:", error);
    showToast("An error occurred while running the algorithm.", "bg-red-500");
  }
}

// Function to save the best assignments to the database
async function saveAssignments(assignments) {
  try {
    const response = await fetch("handlers/save_assignments.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(assignments), // Send full assignments as JSON
    });

    const result = await response.json();

    if (result.success) {
      showToast("Assignments saved successfully!", "bg-green-500");
        setTimeout(() => {
            location.reload(); // Reloads the current page
        }, 3000); // 3000 milliseconds = 3 seconds
    } else {
      console.error("Failed to save assignments:", result.error);
      showToast(
        "Failed to save assignments. " + (result.error || ""),
        "bg-red-500"
      );
    }
  } catch (error) {
    console.error("Save assignments error:", error);
    showToast("An error occurred while saving assignments.", "bg-red-500");
  }
}

    </script>
</body>
</html>