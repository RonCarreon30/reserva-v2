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
    if (!in_array($_SESSION['role'], ['Registrar', 'Admin'])) {
        // Redirect to a page indicating unauthorized access
    header("Location: unauthorized");
        exit();
    }
    // Fetch reservations from the database for the current user
    require_once "database/config.php";

    // Fetch the user ID from the session data
    $user_id = $_SESSION['user_id'];

    // Fetch buildings for the dropdown
    $buildings_sql = "SELECT DISTINCT building FROM facilities";
    $buildings_result = $conn->query($buildings_sql);

    //Query to fetch facility data from the database
    $sql = "SELECT * FROM facilities";
    $result = $conn->query($sql);
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

        <div class="flex flex-col flex-1">
            <header class="bg-white shadow-lg">
                <div class="flex items-center justify-between px-6 py-3 border-b">
                    <h2 class="text-lg font-semibold">Data Setup</h2>
                </div>
            </header>
            <main class="flex flex-1 p-4 overflow-y-auto">
                <!-- Left Side -->
                <div class=" flex flex-col h-full w-1/4 pr-4 ">
                    <!-- Clickable Options -->
                     <div class="bg-white flex flex-col w-full h-auto divide-y-2 rounded-lg">
                        <button id="academicYearBtn" class=" text-blue-500 hover:text-blue-700 font-semibold text-left p-2">
                            Academic Year & Semester
                        </button>
                        <!-- Academic Year & Semester Form -->
                        <form id="academicYearForm" class="bg-gray-200 hidden p-2">
                            <h3 class="text-md font-semibold mb-2">Add Academic Year & Sem.</h3>
                            <div class="mb-2">
                                <label for="academicYear" class="block text-xs font-medium text-gray-700 ml-1">Academic Year</label>
                                <input type="text" id="academicYear" class=" p-1 border border-gray-300 rounded-md w-full" placeholder="2023-2024">
                            </div>
                            <div class="mb-2">
                                <label for="semester" class="block text-xs font-medium text-gray-700 ml-1">Semester</label>
                                <input type="text" id="semester" class="p-1 border border-gray-300 rounded-md w-full" placeholder="1st Semester">
                            </div>
                            <div class="mb-2">
                                <label for="ayStatus" class="block text-xs font-medium text-gray-700 ml-1">Status:</label>
                                <select id="ayStatus" name="ayStatus" required class="w-full px-2 py-1 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500">
                                    <option value="Current">Current</option>
                                    <option value="Upcoming">Upcoming</option>
                                </select>
                            </div>
                            <button type="button" id="saveAcademicYearBtn" class="px-2 py-1 bg-blue-500 text-white rounded-md">Save</button>
                        </form>

                        <button id="buildingBtn" class="text-blue-500 hover:text-blue-700 font-semibold text-left p-2">
                            Building
                        </button>
                        <!-- Building Form -->
                        <form id="buildingForm" class="bg-gray-200 hidden p-2">
                            <h3 class="text-md font-semibold mb-2">Add Building</h3>
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 ml-1">Building Name</label>
                                <input type="text" id="building_name" class="p-1 border border-gray-300 rounded-md w-full" placeholder="CEIT">
                            </div>
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 ml-1">Description</label>
                                <input type="text" id="building_desc" class="p-1 border border-gray-300 rounded-md w-full" placeholder="Civil Engineer and Information Technology Building">
                            </div>
                            <button type="button" id="saveBuildingBtn" class="px-2 py-1 bg-blue-500 text-white rounded-md">Save</button>
                        </form>

                        <button id="departmentBtn" class="text-blue-500 hover:text-blue-700 font-semibold text-left p-2">
                            Department
                        </button>
                        <!-- Department Form -->
                        <form id="departmentForm" class="bg-gray-200 hidden p-2">
                            <h3 class="text-md font-semibold mb-2">Add Department</h3>
                            <div class="mb-2">
                                <label class="block text-xs font-medium text-gray-700 ml-1">Department Name:</label>
                                <input type="text" id="departmentName" class="p-1 border border-gray-300 rounded-md w-full" placeholder="Information Technology">
                            </div>
                            <div class="mb-2">
                                <label for="building" class="block text-xs font-medium text-gray-700 ml-1">Building:</label>
                                <select id="building" name="building" required class="w-full px-2 py-1 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500">
                                    <option value="" disabled selected>Select Building</option> <!-- Default empty option -->
                                    <?php
                                    // Query to get distinct dept_building values
                                    $query = "SELECT * FROM buildings_tbl";
                                    $result = $conn->query($query);
                                    // Loop through the result and create an <option> element for each building
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<option value="' . $row['building_id'] . '">' . $row['building_name'] . '</option>';
                                        }
                                    } else {
                                        echo '<option value="">No buildings available</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="button" id="saveDepartmentBtn" class="px-2 py-1 bg-blue-500 text-white rounded-md">Save</button>
                        </form>
                     </div>
                    <!-- Image at the bottom part -->
                    <div class=" mt-auto opacity-50">
                        <img src="img/undraw_new_entries_re_cffr.svg" alt="Data Setup" class="w-full h-auto object-cover">
                    </div>

                </div>

                <div class="h-full border-l border-gray-300"></div>                    

                <!-- Right Side -->
                <div class="w-3/4 pl-4">
                    <div id="toast-container" class="fixed top-0 right-0 m-4 flex flex-col gap-2"></div>

                    <!-- Forms will be dynamically displayed here -->
                    <div id='forms-container' class="h-full p-4 text-sm bg-white border border-gray-200 rounded-lg shadow-lg overflow-auto">
                        <!-- Table to display Academic Year & Semester data -->
                        <div id="AYTableContainer" class="hidden">
                            <h3 class="text-lg font-semibold mb-4">Saved Academic Year & Semester</h3>
                            <table class="w-full table-auto border-collapse border border-gray-300">
                                <thead>
                                    <tr class="bg-gray-200">
                                        <th class="px-4 py-2 border">Academic Year</th>
                                        <th class="px-4 py-2 border">Semester</th>
                                        <th class="px-4 py-2 border">Set Status</th>
                                        <th class="px-4 py-2 border">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="academicYearTableBody">
                                    <!-- Data will be dynamically inserted here -->
                                    <?php
                                        $query = "SELECT * FROM terms_tbl";
                                        $result = $conn->query($query);

                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr data-id='{$row['term_id']}'>";
                                            echo "<td class='border px-4 py-2 academic-year-cell'>{$row['academic_year']}</td>";
                                            echo "<td class='border px-4 py-2 semester-cell'>{$row['semester']}</td>";
                                            echo "<td class='border px-4 py-2 status-cell'>{$row['term_status']}</td>";
                                            echo "<td class='border px-4 py-2'>
                                                    <button class='edit-btn px-2 py-1 bg-blue-500 text-white rounded'>Edit</button>
                                                    <button class='delete-btn px-2 py-1 bg-red-500 text-white rounded' data-id='{$row['term_id']}'>Delete</button>
                                                </td>";
                                            echo "</tr>";
                                        }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Table to display Building data -->
                        <div id="buildingTableContainer" class="hidden">
                            <h3 class="text-lg font-semibold mb-4">Saved Buildings</h3>
                            <table class="w-full table-auto border-collapse border border-gray-300">
                                <thead>
                                    <tr class="bg-gray-200">
                                        <th class="px-4 py-2 border">Building Name</th>
                                        <th class="px-4 py-2 border">Description</th>
                                        <th class="px-4 py-2 border">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="buildingTableBody">
                                    <?php
                                        $query = "SELECT * FROM buildings_tbl";
                                        $result = $conn->query($query);

                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr data-id='{$row['building_id']}'>";
                                            echo "<td class='border px-4 py-2'>{$row['building_name']}</td>";
                                            echo "<td class='border px-4 py-2'>{$row['building_desc']}</td>";
                                            echo "<td class='border px-4 py-2'>
                                                    <button class='edit-building-btn px-2 py-1 bg-blue-500 text-white rounded'>Edit</button>
                                                    <button class='delete-building-btn px-2 py-1 bg-red-500 text-white rounded' data-id='{$row['building_id']}'>Delete</button></td>";
                                            echo "</tr>";
                                        }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Table to display Department data -->
                        <div id="deptTableContainer" class="hidden">
                            <h3 class="text-lg font-semibold mb-4">Saved Departments</h3>
                            <table class="w-full table-auto border-collapse border border-gray-300">
                                <thead>
                                    <tr class="bg-gray-200">
                                        <th class="px-4 py-2 border">Department Name</th>
                                        <th class="px-4 py-2 border">Building</th>
                                        <th class="px-4 py-2 border">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="departmentTableBody">
                                    <?php
                                        $query = "SELECT d.dept_id, d.dept_name, b.building_name FROM dept_tbl d JOIN buildings_tbl b ON d.building_id = b.building_id";
                                        $result = $conn->query($query);

                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr data-id='{$row['dept_id']}'>";
                                            echo "<td class='border px-4 py-2'>{$row['dept_name']}</td>";
                                            echo "<td class='border px-4 py-2'>{$row['building_name']}</td>";
                                            echo "<td class='border px-4 py-2'>
                                                    <button class='edit-department-btn px-2 py-1 bg-blue-500 text-white rounded'>Edit</button>
                                                    <button class='delete-department-btn px-2 py-1 bg-red-500 text-white rounded' data-id='{$row['dept_id']}'>Delete</button></td>";
                                            echo "</tr>";
                                        }
                                    ?>
                                </tbody>
                            </table>
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

    <script>
        // Get references to the buttons and forms
        const academicYearBtn = document.getElementById('academicYearBtn');
        const buildingBtn = document.getElementById('buildingBtn');
        const departmentBtn = document.getElementById('departmentBtn');

        // State variables to track visibility of each section
        let isAcademicYearVisible = false;
        let isBuildingVisible = false;
        let isDepartmentVisible = false;

        // On page load, restore visibility states
        window.addEventListener('load', function () {
            isAcademicYearVisible = localStorage.getItem('isAcademicYearVisible') === 'true';
            isBuildingVisible = localStorage.getItem('isBuildingVisible') === 'true';
            isDepartmentVisible = localStorage.getItem('isDepartmentVisible') === 'true';

            // Restore visibility of sections
            updateVisibility();
        });

        // Toggle visibility for the Academic Year form and table
        academicYearBtn.addEventListener('click', function() {
            isAcademicYearVisible = !isAcademicYearVisible;
            isBuildingVisible = false;
            isDepartmentVisible = false;
            updateVisibility();
        });

        // Toggle visibility for the Building form and table
        buildingBtn.addEventListener('click', function() {
            isBuildingVisible = !isBuildingVisible;
            isAcademicYearVisible = false;
            isDepartmentVisible = false;
            updateVisibility();
        });

        // Toggle visibility for the Department form and table
        departmentBtn.addEventListener('click', function() {
            isDepartmentVisible = !isDepartmentVisible;
            isAcademicYearVisible = false;
            isBuildingVisible = false;
            updateVisibility();
        });

        // Function to update visibility based on state variables
        function updateVisibility() {
            document.getElementById('academicYearForm').classList.toggle('hidden', !isAcademicYearVisible);
            document.getElementById('AYTableContainer').classList.toggle('hidden', !isAcademicYearVisible);
            
            document.getElementById('buildingForm').classList.toggle('hidden', !isBuildingVisible);
            document.getElementById('buildingTableContainer').classList.toggle('hidden', !isBuildingVisible);
            
            document.getElementById('departmentForm').classList.toggle('hidden', !isDepartmentVisible);
            document.getElementById('deptTableContainer').classList.toggle('hidden', !isDepartmentVisible);

            // Store the visibility states in localStorage
            localStorage.setItem('isAcademicYearVisible', isAcademicYearVisible);
            localStorage.setItem('isBuildingVisible', isBuildingVisible);
            localStorage.setItem('isDepartmentVisible', isDepartmentVisible);
        }

        // Toast notification function
        function showToast(message, type) {
            const toastContainer = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.classList.add('p-4', 'rounded-md', 'shadow-md', 'text-white', 'font-semibold');
            
            // Success or error
            if (type === 'success') {
                toast.classList.add('bg-green-500');
            } else {
                toast.classList.add('bg-red-500');
            }
            
            toast.textContent = message;
            toastContainer.appendChild(toast);

            // Remove after 3 seconds
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        /*/ Save Building
        document.getElementById('saveBuildingBtn').addEventListener('click', async function () {
            const building_name = document.getElementById('building_name').value; // Corrected variable name
            const building_desc = document.getElementById('building_desc').value; // Corrected variable name

            const formData = new FormData();
            formData.append('building_name', building_name); // Use the correct variable
            formData.append('building_desc', building_desc); // Use the correct variable

            try {
                const response = await fetch('handlers/save_building.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') { // Corrected property check
                    showToast('Building saved successfully!', 'success');
                    setTimeout(() => {
                        location.reload(); // Reload the page after 3 seconds
                    }, 2000); // 3000 milliseconds = 3 seconds
                } else {
                    showToast('Failed to save Building!', 'error');
                }
            } catch (error) {
                showToast('Error occurred while saving data!', 'error');
            }
        });

        // Save Department
        document.getElementById('saveDepartmentBtn').addEventListener('click', async function () {
            const departmentName = document.getElementById('departmentName').value;
            const building = document.getElementById('building').value;

            const formData = new FormData();
            formData.append('departmentName', departmentName);
            formData.append('building', building);

            try {
                const response = await fetch('handlers/save_department.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    showToast('Department saved successfully!', 'success');
                    setTimeout(() => {
                        location.reload(); // Reload the page after 3 seconds
                    }, 3000);
                } else {
                    showToast('Failed to save Department!', 'error');
                }
            } catch (error) {
                showToast('Error occurred while saving data!', 'error');
            }
        });*/

    </script>

    <!--edit and delete AY-->
    <script>
        // Get references to buttons and the form
        const saveAcademicYearBtn = document.getElementById('saveAcademicYearBtn');

        // Function to handle editing
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function () {
                const row = button.closest('tr');
                const rowId = row.getAttribute('data-id'); // Assuming each row has a data-id attribute with the academic year ID

                // Fetch the academic year data from the server using the ID
                fetch(`handlers/fetch_ay.php?id=${rowId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Populate form fields with the fetched data
                        document.getElementById('academicYear').value = data.academic_year;
                        document.getElementById('semester').value = data.semester;
                        document.getElementById('ayStatus').value = data.term_status;

                        // Show the form and change the button label to "Update"
                        document.getElementById('academicYearForm').classList.remove('hidden');
                        document.getElementById('AYTableContainer').classList.remove('hidden');
                        saveAcademicYearBtn.textContent = 'Update';

                        // Store the row ID for later updates
                        document.getElementById('academicYearForm').setAttribute('data-edit-id', rowId);
                    })
                    .catch(error => {
                        console.error('Error fetching academic year data:', error);
                    });
            });

        });

        // Function to handle saving or updating
        saveAcademicYearBtn.addEventListener('click', async function () {
            const form = document.getElementById('academicYearForm');
            const editId = form.getAttribute('data-edit-id');

            const academicYear = document.getElementById('academicYear').value;
            const semester = document.getElementById('semester').value;
            const ayStatus = document.getElementById('ayStatus').value;

            // Prepare data for request
            const formData = new FormData();
            formData.append('academicYear', academicYear);
            formData.append('semester', semester);
            formData.append('ayStatus', ayStatus);

            if (editId) {
                formData.append('id', editId); // Attach ID for update
                fetch('handlers/update_ay.php', {
                    method: 'POST',
                    body: formData
                }).then(response => response.json()).then(data => {
                    if (data.status === 'success') { // Change this line to check for 'status'
                        showToast(data.message, 'success'); // Use the message from the response
                        updateVisibility(); // Update visibility after a few seconds
                        setTimeout(() => {
                            location.reload(); // Reload the page after 3 seconds
                        }, 3000);
                    } else {
                        showToast(data.message, 'error');
                    }
                });
            } else {
                try {
                    const response = await fetch('handlers/save_academic_year.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        showToast('Academic Year & Semester saved successfully!', 'success');
                        updateVisibility(); // Update visibility after a few seconds
                        setTimeout(() => {
                            location.reload(); // Reload the page after 3 seconds
                        }, 3000);
                    } else {
                        showToast('Failed to save Academic Year & Semester!', 'error');
                    }
                } catch (error) {
                    showToast('Error occurred while saving data!', 'error');
                }
            }
        });

        // Function to handle deletion
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function () {
                const rowId = button.getAttribute('data-id');
                if (confirm("Are you sure you want to delete this entry?")) {
                    fetch('handlers/delete_ay.php', {
                        method: 'POST',
                        body: JSON.stringify({ id: rowId }),
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    }).then(response => response.json()).then(data => {
                        if (data.status === 'success') {
                            showToast(data.message, 'success'); // Use the message from the response
                            updateVisibility(); // Update visibility after a few seconds
                            setTimeout(() => {
                                location.reload(); // Reload the page after 3 seconds
                            }, 3000);
                        } else {
                            showToast(data.message, 'error');
                        }
                    });
                }
            });
        });

    </script>
    <script>
        // Function to handle editing building
document.querySelectorAll('.edit-building-btn').forEach(button => {
    button.addEventListener('click', function () {
        const row = button.closest('tr');
        const rowId = row.getAttribute('data-id');

        fetch(`handlers/fetch_building.php?id=${rowId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('building_name').value = data.building_name;
                document.getElementById('building_desc').value = data.building_desc;
                document.getElementById('buildingForm').classList.remove('hidden');
                saveBuildingBtn.textContent = 'Update';
                document.getElementById('buildingForm').setAttribute('data-edit-id', rowId);
            })
            .catch(error => console.error('Error fetching building data:', error));
    });
});

// Function to handle saving or updating building
saveBuildingBtn.addEventListener('click', async function () {
    const form = document.getElementById('buildingForm');
    const editId = form.getAttribute('data-edit-id');

    const buildingName = document.getElementById('building_name').value;
    const buildingDesc = document.getElementById('building_desc').value;

    const formData = new FormData();
    formData.append('building_name', buildingName);
    formData.append('building_desc', buildingDesc);

    if (editId) {
        formData.append('id', editId);
        fetch('handlers/update_building.php', {
            method: 'POST',
            body: formData
        }).then(response => response.json()).then(data => {
            if (data.status === 'success') {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 3000);
            } else {
                showToast(data.message, 'error');
            }
        });
    } else {
        try {
            const response = await fetch('handler/save_building.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.status === 'success') {
                showToast('Building saved successfully!', 'success');
                setTimeout(() => location.reload(), 3000);
            } else {
                showToast('Failed to save building!', 'error');
            }
        } catch (error) {
            showToast('Error occurred while saving building data!', 'error');
        }
    }
});

// Function to handle deleting building
document.querySelectorAll('.delete-building-btn').forEach(button => {
    button.addEventListener('click', function () {
        const rowId = button.getAttribute('data-id');
        if (confirm("Are you sure you want to delete this building?")) {
            fetch('handlers/delete_building.php', {
                method: 'POST',
                body: JSON.stringify({ id: rowId }),
                headers: { 'Content-Type': 'application/json' }
            }).then(response => response.json()).then(data => {
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 3000);
                } else {
                    showToast(data.message, 'error');
                }
            });
        }
    });
});

// Function to handle editing department
document.querySelectorAll('.edit-department-btn').forEach(button => {
    button.addEventListener('click', function () {
        const row = button.closest('tr');
        const rowId = row.getAttribute('data-id');

        fetch(`handlers/fetch_department.php?id=${rowId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('departmentName').value = data.dept_name;
                document.getElementById('building').value = data.building_id; // Assuming this is a dropdown
                document.getElementById('departmentForm').classList.remove('hidden');
                saveDepartmentBtn.textContent = 'Update';
                document.getElementById('departmentForm').setAttribute('data-edit-id', rowId);
            })
            .catch(error => console.error('Error fetching department data:', error));
    });
});

// Function to handle saving or updating department
saveDepartmentBtn.addEventListener('click', async function () {
    const form = document.getElementById('departmentForm');
    const editId = form.getAttribute('data-edit-id');

    const departmentName = document.getElementById('departmentName').value;
    const buildingId = document.getElementById('building').value;

    const formData = new FormData();
    formData.append('departmentName', departmentName);
    formData.append('buildingId', buildingId);

    if (editId) {
        formData.append('id', editId);
        fetch('handlers/update_department.php', {
            method: 'POST',
            body: formData
        }).then(response => response.json()).then(data => {
            if (data.status === 'success') {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 3000);
            } else {
                showToast(data.message, 'error');
            }
        });
    } else {
        try {
            const response = await fetch('handlers/save_department.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.status === 'success') {
                showToast('Department saved successfully!', 'success');
                setTimeout(() => location.reload(), 3000);
            } else {
                showToast('Failed to save department!', 'error');
            }
        } catch (error) {
            showToast('Error occurred while saving department data!', 'error');
        }
    }
});

// Function to handle deleting department
document.querySelectorAll('.delete-department-btn').forEach(button => {
    button.addEventListener('click', function () {
        const rowId = button.getAttribute('data-id');
        if (confirm("Are you sure you want to delete this department?")) {
            fetch('handlers/delete_department.php', {
                method: 'POST',
                body: JSON.stringify({ id: rowId }),
                headers: { 'Content-Type': 'application/json' }
            }).then(response => response.json()).then(data => {
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 3000);
                } else {
                    showToast(data.message, 'error');
                }
            });
        }
    });
});

    </script>
</body>

</html>