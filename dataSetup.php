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

            #toastContainer {
                position: fixed;
                top: 1rem;
                right: 1rem;
                z-index: 10000;
                max-width: 300px;
            }

            .toast {
                transition: opacity 0.3s ease;
                opacity: 1;
            }

            .fade {
                opacity: 0;
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
                        <button id="buildingBtn" class="text-blue-500 hover:text-blue-700 font-semibold text-left p-2">
                            Building
                        </button>
                        <button id="departmentBtn" class="text-blue-500 hover:text-blue-700 font-semibold text-left p-2">
                            Department
                        </button>
                     </div>
                    <!-- Image at the bottom part -->
                    <div class="mt-auto opacity-50">
                        <img src="img/undraw_new_entries_re_cffr.svg" alt="Data Setup" class="w-full h-auto object-cover">
                    </div>

                </div>

                <div class="h-full border-l border-gray-300"></div>                    

                <!-- Right Side -->
                <div class="w-3/4 pl-4">
                    <div id="toast-container" class="fixed top-0 right-0 m-4 flex flex-col gap-2"></div>

                    <!-- Forms will be dynamically displayed here -->
                    <div id='forms-container' class="h-full p-4 text-sm bg-white border border-gray-200 rounded-lg shadow-lg">
                        <!-- Academic Year & Semester Form -->
                        <form id="academicYearForm" class="hidden mb-6">
                            <h3 class="text-lg font-semibold mb-4">Add Academic Year & Semester</h3>
                            <div class="mb-4">
                                <label for="academicYear" class="block text-sm font-medium text-gray-700">Academic Year</label>
                                <input type="text" id="academicYear" class="mt-1 p-2 border border-gray-300 rounded-md w-full" placeholder="2023-2024">
                            </div>
                            <div class="mb-4">
                                <label for="semester" class="block text-sm font-medium text-gray-700">Semester</label>
                                <input type="text" id="semester" class="mt-1 p-2 border border-gray-300 rounded-md w-full" placeholder="1st Semester">
                            </div>
                            <div class="mb-4">
                                <label for="ayStatus" class="block text-sm font-medium text-gray-700">Status:</label>
                                <select id="ayStatus" name="ayStatus" required class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500">
                                    <option value="Current">Current</option>
                                    <option value="Upcoming">Upcoming</option>
                                </select>
                            </div>
                            <button type="button" id="saveAcademicYearBtn" class="px-4 py-2 bg-blue-500 text-white rounded-md">Save</button>
                        </form>

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
                                            echo "<tr>";
                                            echo "<tr data-term-id='{$row['term_id']}'>";
                                            echo "<td class='border px-4 py-2'>{$row['academic_year']}</td>";
                                            echo "<td class='border px-4 py-2'>{$row['semester']}</td>";
                                            echo "<td class='border px-4 py-2'>{$row['term_status']}</td>";
                                            echo "<td class='border px-4 py-2'><button class='px-2 py-1 bg-blue-500 text-white rounded'>Edit</button>
                                            <button class='px-2 py-1 bg-red-500 text-white rounded'>Delete</button></td>";
                                            echo "</tr>";
                                        }
                                    ?>

                                </tbody>
                            </table>
                        </div>

                        <!-- Building Form -->
                        <form id="buildingForm" class="hidden mb-6">
                            <h3 class="text-lg font-semibold mb-4">Add Building</h3>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Building Name</label>
                                <input type="text" id="building_name" class="mt-1 p-2 border border-gray-300 rounded-md w-full" placeholder="CEIT">
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <input type="text" id="building_desc" class="mt-1 p-2 border border-gray-300 rounded-md w-full" placeholder="Civil Engineer and Information Technology Building">
                            </div>
                            <button type="button" id="saveBuildingBtn" class="px-4 py-2 bg-blue-500 text-white rounded-md">Save</button>
                        </form>

                        <!-- Table to display Department data -->
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
                                    <!-- Data will be dynamically inserted here -->
                                    <?php
                                        $query = "SELECT * FROM buildings_tbl";
                                        $result = $conn->query($query);

                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<tr data-building-id='{$row['building_id']}'>";
                                            echo "<td class='border px-4 py-2'>{$row['building_name']}</td>";
                                            echo "<td class='border px-4 py-2'>{$row['building_desc']}</td>";
                                            echo "<td class='border px-4 py-2'><button class='px-2 py-1 bg-blue-500 text-white rounded'>Edit</button>
                                            <button class='px-2 py-1 bg-red-500 text-white rounded'>Delete</button></td>";
                                            echo "</tr>";
                                        }
                                    ?>

                                </tbody>
                            </table>
                        </div>                        

                        <!-- Department Form -->
                        <form id="departmentForm" class="hidden mb-6">
                            <h3 class="text-lg font-semibold mb-4">Add Department</h3>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Department Name</label>
                                <input type="text" id="departmentName" class="mt-1 p-2 border border-gray-300 rounded-md w-full" placeholder="Information Technology">
                            </div>
                            <div class="mb-4">
                                <label for="building" class="block text-sm font-medium text-gray-700">Building:</label>
                                <select id="building" name="building" required class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500">
                                    <option value="" readonly>Select Building</option> <!-- Default empty option -->
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
                            <button type="button" id="saveDepartmentBtn" class="px-4 py-2 bg-blue-500 text-white rounded-md">Save</button>
                        </form>

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
                                    <!-- Data will be dynamically inserted here -->
                                    <?php
                                        $query = "SELECT d.dept_id, d.dept_name, b.building_name FROM dept_tbl d JOIN buildings_tbl b ON d.building_id = b.building_id";

                                        $result = $conn->query($query);

                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<tr data-dept-id='{$row['dept_id']}'>";
                                            echo "<td class='border px-4 py-2'>{$row['dept_name']}</td>";
                                            echo "<td class='border px-4 py-2'>{$row['building_name']}</td>";
                                            echo "<td class='border px-4 py-2'><button class='px-2 py-1 bg-blue-500 text-white rounded'>Edit</button>
                                            <button class='px-2 py-1 bg-red-500 text-white rounded'>Delete</button></td>";
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


    <!-- Edit Modal for Academic Year & Semester -->
<div id="editAYModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white rounded shadow-lg p-4 w-1/3">
        <h2 class="text-lg font-semibold mb-2">Edit Academic Year & Semester</h2>
        <form id="editAYForm">
            <input type="hidden" id="editAYTermId" name="term_id">
            <div class="mb-4">
                <label for="editAYAcademicYear" class="block mb-1">Academic Year</label>
                <input type="text" id="editAYAcademicYear" name="academic_year" class="border w-full px-2 py-1" required>
            </div>
            <div class="mb-4">
                <label for="editAYSemester" class="block mb-1">Semester</label>
                <input type="text" id="editAYSemester" name="semester" class="border w-full px-2 py-1" required>
            </div>
            <div class="mb-4">
                <label for="editAYTermStatus" class="block mb-1">Set Status</label>
                <input type="text" id="editAYTermStatus" name="term_status" class="border w-full px-2 py-1" required>
            </div>
            <div class="flex justify-end">
                <button type="button" id="closeAYModal" class="mr-2 px-4 py-2 bg-gray-300 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal for Buildings -->
<div id="editBuildingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white rounded shadow-lg p-4 w-1/3">
        <h2 class="text-lg font-semibold mb-2">Edit Building</h2>
        <form id="editBuildingForm">
            <input type="hidden" id="editBuildingId" name="building_id">
            <div class="mb-4">
                <label for="editBuildingName" class="block mb-1">Building Name</label>
                <input type="text" id="editBuildingName" name="building_name" class="border w-full px-2 py-1" required>
            </div>
            <div class="mb-4">
                <label for="editBuildingDesc" class="block mb-1">Description</label>
                <input type="text" id="editBuildingDesc" name="building_desc" class="border w-full px-2 py-1" required>
            </div>
            <div class="flex justify-end">
                <button type="button" id="closeBuildingModal" class="mr-2 px-4 py-2 bg-gray-300 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal for Departments -->
<div id="editDeptModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
    <div class="bg-white rounded shadow-lg p-4 w-1/3">
        <h2 class="text-lg font-semibold mb-2">Edit Department</h2>
        <form id="editDeptForm">
            <input type="hidden" id="editDeptId" name="dept_id">
            <div class="mb-4">
                <label for="editDeptName" class="block mb-1">Department Name</label>
                <input type="text" id="editDeptName" name="dept_name" class="border w-full px-2 py-1" required>
            </div>
            <div class="mb-4">
                <label for="editDeptBuilding" class="block mb-1">Building</label>
                <select id="editDeptBuilding" name="building_id" required class="border w-full px-2 py-1">
                    <option value="" readonly>Select Building</option> <!-- Default empty option -->
                    <?php
                    // Query to get distinct buildings
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
            <div class="flex justify-end">
                <button type="button" id="closeDeptModal" class="mr-2 px-4 py-2 bg-gray-300 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">Save Changes</button>
            </div>
        </form>
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
    document.addEventListener('DOMContentLoaded', function () {
        // Academic Year & Semester Modals
        const editAYModal = document.getElementById('editAYModal');
        const closeAYModal = document.getElementById('closeAYModal');
        const editAYForm = document.getElementById('editAYForm');

        // Buildings Modals
        const editBuildingModal = document.getElementById('editBuildingModal');
        const closeBuildingModal = document.getElementById('closeBuildingModal');
        const editBuildingForm = document.getElementById('editBuildingForm');

        // Departments Modals
        const editDeptModal = document.getElementById('editDeptModal');
        const closeDeptModal = document.getElementById('closeDeptModal');
        const editDeptForm = document.getElementById('editDeptForm');

        // Function to open the Academic Year & Semester edit modal
        function openAYEditModal(termId, academicYear, semester, termStatus) {
            document.getElementById('editAYTermId').value = termId;
            document.getElementById('editAYAcademicYear').value = academicYear;
            document.getElementById('editAYSemester').value = semester;
            document.getElementById('editAYTermStatus').value = termStatus;
            editAYModal.classList.remove('hidden');
        }

        // Function to open the Building edit modal
        function openBuildingEditModal(buildingId, buildingName, buildingDesc) {
            document.getElementById('editBuildingId').value = buildingId;
            document.getElementById('editBuildingName').value = buildingName;
            document.getElementById('editBuildingDesc').value = buildingDesc;
            editBuildingModal.classList.remove('hidden');
        }

            // Function to open the Department edit modal
            function openDeptEditModal(deptId, deptName, buildingId) {
                document.getElementById('editDeptId').value = deptId;
                document.getElementById('editDeptName').value = deptName;
                document.getElementById('editDeptBuilding').value = buildingId; // Change this to use buildingId
                editDeptModal.classList.remove('hidden');
            }


        // Event listeners for edit buttons in Academic Year & Semester table
        document.querySelectorAll('#academicYearTableBody .bg-blue-500').forEach(button => {
            button.addEventListener('click', function () {
                const row = this.closest('tr');
                const termId = row.dataset.termId;
                const academicYear = row.children[0].textContent;
                const semester = row.children[1].textContent;
                const termStatus = row.children[2].textContent;

                openAYEditModal(termId, academicYear, semester, termStatus);
            });
        });

        // Event listeners for edit buttons in Buildings table
        document.querySelectorAll('#buildingTableBody .bg-blue-500').forEach(button => {
            button.addEventListener('click', function () {
                const row = this.closest('tr');
                const buildingId = row.dataset.buildingId; // Add data attributes
                const buildingName = row.children[0].textContent;
                const buildingDesc = row.children[1].textContent;

                openBuildingEditModal(buildingId, buildingName, buildingDesc);
            });
        });

        // Event listeners for edit buttons in Departments table
        document.querySelectorAll('#departmentTableBody .bg-blue-500').forEach(button => {
            button.addEventListener('click', function () {
                const row = this.closest('tr');
                const deptId = row.dataset.deptId; // Add data attributes
                const deptName = row.children[0].textContent;
                const building = row.children[1].textContent;

                openDeptEditModal(deptId, deptName, building);
            });
        });

        // Event listener for delete buttons in Academic Year & Semester table
        document.querySelectorAll('#academicYearTableBody .bg-red-500').forEach(button => {
            button.addEventListener('click', function () {
                const row = this.closest('tr');
                const termId = row.dataset.termId; // Add data attributes

                if (confirm('Are you sure you want to delete this entry?')) {
                    // Call your delete function via AJAX
                    deleteEntry('handlers/delete_ay.php', { term_id: termId });
                }
            });
        });

        // Event listener for delete buttons in Buildings table
        document.querySelectorAll('#buildingTableBody .bg-red-500').forEach(button => {
            button.addEventListener('click', function () {
                const row = this.closest('tr');
                const buildingId = row.dataset.buildingId; // Add data attributes

                if (confirm('Are you sure you want to delete this entry?')) {
                    // Call your delete function via AJAX
                    deleteEntry('handlers/delete_building.php', { building_id: buildingId });
                }
            });
        });

        // Event listener for delete buttons in Departments table
        document.querySelectorAll('#departmentTableBody .bg-red-500').forEach(button => {
            button.addEventListener('click', function () {
                const row = this.closest('tr');
                const deptId = row.dataset.deptId; // Add data attributes

                if (confirm('Are you sure you want to delete this entry?')) {
                    // Call your delete function via AJAX
                    deleteEntry('handlers/delete_department.php', { dept_id: deptId });
                }
            });
        });

        // Close modals
        closeAYModal.addEventListener('click', () => editAYModal.classList.add('hidden'));
        closeBuildingModal.addEventListener('click', () => editBuildingModal.classList.add('hidden'));
        closeDeptModal.addEventListener('click', () => editDeptModal.classList.add('hidden'));

        // Submit forms for editing entries
        editAYForm.addEventListener('submit', function (e) {
            e.preventDefault();
            // Implement AJAX to submit the form
            submitForm('handlers/edit_ay.php', new FormData(this));
        });

        editBuildingForm.addEventListener('submit', function (e) {
            e.preventDefault();
            // Implement AJAX to submit the form
            submitForm('handlers/edit_building.php', new FormData(this));
        });

        editDeptForm.addEventListener('submit', function (e) {
            e.preventDefault();
            // Implement AJAX to submit the form
            submitForm('handlers/edit_department.php', new FormData(this));
        });
    });

    function deleteEntry(url, data) {
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                editAYModal.classList.add('hidden');
                editBuildingModal.classList.add('hidden');
                editDeptModal.classList.add('hidden');
                showToast('Entry deleted successfully!', 'success');
                // Refresh the table or remove the row
                setTimeout(() => {
                    location.reload(); // Reloads the current page
                }, 3000); // 3000 milliseconds = 3 seconds
            } else {
                showToast('Error deleting entry: ' + data.error, 'error');
            }
        })
        .catch(error => showToast('Error deleting entry: ' + error.message, 'error'));
    }

    function submitForm(url, formData) {
        fetch(url, {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                editAYModal.classList.add('hidden');
                editBuildingModal.classList.add('hidden');
                editDeptModal.classList.add('hidden');
                showToast('Changes saved successfully!', 'success');
                setTimeout(() => {
                    location.reload(); // Reloads the current page
                }, 3000); // 3000 milliseconds = 3 seconds
            } else {
                showToast('Error saving changes: ' + data.error, 'error');
            }
        })
        .catch(error => showToast('Error saving changes: ' + error.message, 'error'));
    }

    function showToast(message, type) {
        const toastContainer = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white p-4 rounded-md shadow-md mb-4`;
        toast.textContent = message;

        // Append toast to container
        toastContainer.appendChild(toast);

        // Automatically hide the toast after a few seconds
        setTimeout(() => {
            toast.classList.add('fade');
            setTimeout(() => {
                toastContainer.removeChild(toast);
            }, 300); // Delay for fade-out effect
        }, 3000); // Display duration
    }
</script>

    <script>
        // Get references to the buttons and forms
        const academicYearBtn = document.getElementById('academicYearBtn');
        const buildingBtn = document.getElementById('buildingBtn');
        const departmentBtn = document.getElementById('departmentBtn');
   
        // Event listeners to show the respective forms
        academicYearBtn.addEventListener('click', function() {
            document.getElementById('academicYearForm').classList.remove('hidden');
            document.getElementById('AYTableContainer').classList.remove('hidden');
            document.getElementById('departmentForm').classList.add('hidden');
            document.getElementById('deptTableContainer').classList.add('hidden');
            document.getElementById('buildingForm').classList.add('hidden');
            document.getElementById('buildingTableContainer').classList.add('hidden');
        });

        buildingBtn.addEventListener('click', function() {
            document.getElementById('buildingForm').classList.remove('hidden');
            document.getElementById('buildingTableContainer').classList.remove('hidden');
            document.getElementById('academicYearForm').classList.add('hidden');
            document.getElementById('AYTableContainer').classList.add('hidden');
            document.getElementById('departmentForm').classList.add('hidden');
            document.getElementById('deptTableContainer').classList.add('hidden');
        });

        departmentBtn.addEventListener('click', function() {
            document.getElementById('departmentForm').classList.remove('hidden');
            document.getElementById('deptTableContainer').classList.remove('hidden');
            document.getElementById('academicYearForm').classList.add('hidden');
            document.getElementById('AYTableContainer').classList.add('hidden');
            document.getElementById('buildingForm').classList.add('hidden');
            document.getElementById('buildingTableContainer').classList.add('hidden');
        });

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

        // Save Academic Year & Semester
        document.getElementById('saveAcademicYearBtn').addEventListener('click', async function () {
            const academicYear = document.getElementById('academicYear').value;
            const semester = document.getElementById('semester').value;
            const term_status = document.getElementById('ayStatus').value;

            const formData = new FormData();
            formData.append('academicYear', academicYear);
            formData.append('semester', semester);
            formData.append('ayStatus', term_status);

            try {
                const response = await fetch('handlers/save_academic_year.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    showToast('Academic Year & Semester saved successfully!', 'success');
                    setTimeout(() => {
                        location.reload(); // Reload the page after 3 seconds
                    }, 3000);
                } else {
                    showToast('Failed to save Academic Year & Semester!', 'error');
                }
            } catch (error) {
                showToast('Error occurred while saving data!', 'error');
            }
        });

        // Save Building
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
        });

    </script>
</body>

</html>