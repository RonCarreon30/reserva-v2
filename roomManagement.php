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
if (!in_array($_SESSION['role'], ['Registrar', 'Admin'])) {
    // Redirect to a page indicating unauthorized access
    header("Location: index.html");
    exit();
}

require_once "database/config.php";

// Create connection
$conn = new mysqli($servername, $username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the user ID from the session data
$user_id = $_SESSION['user_id'];

//Query to fetch facility data from the database
$sql = "SELECT * FROM rooms";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLV: RESERVA</title>
    <link rel="stylesheet" href="css/style.css">
        <script>
        function filterReservations() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('roomsTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let td = tr[i].getElementsByTagName('td');
                let rowContainsSearchTerm = false;

                for (let j = 0; j < td.length; j++) {
                    if (td[j]) {
                        const textValue = td[j].textContent || td[j].innerText;
                        if (textValue.toLowerCase().indexOf(filter) > -1) {
                            rowContainsSearchTerm = true;
                            break;
                        }
                    }
                }

                tr[i].style.display = rowContainsSearchTerm ? '' : 'none';
            }
        }

        function sortTable(columnIndex) {
            const table = document.getElementById('roomsTable');
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const isAscending = table.dataset.sortOrder === 'asc';

            rows.sort((rowA, rowB) => {
                const cellA = rowA.children[columnIndex].textContent.trim().toLowerCase();
                const cellB = rowB.children[columnIndex].textContent.trim().toLowerCase();

                return isAscending ? cellA.localeCompare(cellB) : cellB.localeCompare(cellA);
            });

            table.querySelector('tbody').append(...rows);
            table.dataset.sortOrder = isAscending ? 'desc' : 'asc';
        }

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
            <header class="bg-white shadow-lg">
                <div class="flex items-center justify-between px-6 py-3 border-b">
                    <h2 class="text-lg font-semibold">
                        Room Management
                    </h2>
                </div>
            </header>

            <!-- Main content area -->
            <main class="flex-1 p-4 h-screen">
                <div class="flex items-center justify-between p-1 rounded-md">
                    <div class="flex items-center space-x-4 mb-4">
                        <input type="text" id="searchInput" class="px-4 py-2 border border-gray-300 rounded-md" placeholder="Search..." onkeyup="filterReservations()">
                    </div>
                    <div>
                        <button onclick="showRoomForm()" class="ml-auto px-4 py-2 bg-plv-blue text-white rounded-md flex items-center justify-center hover:bg-plv-highlight focus:outline-none focus:ring focus:ring-plv-highlight">
                            <img src="img/icons8-plus-24.png" alt="Add Account Icon" class="w-4 h-4 mr-2">
                            Add Room
                        </button>
                    </div>
                </div>                           
                <!--Room List -->
                <div class=" overflow-y-auto max-h-[calc(100vh-200px)]">
                    <div id="error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Error!</strong>
                        <span class="block sm:inline">Something went wrong.</span>
                    </div>
                    <table id="roomsTable" class="min-w-full bg-white rounded-md shadow-md border border-gray-200">
                        <thead>
                            <tr class="bg-gray-200 border-b">
                                <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(0)">
                                    <span class="flex items-center">Building
                                        <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                        </svg>
                                    </span>
                                </th>
                                <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(1)">
                                    <span class="flex items-center">Room
                                        <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                        </svg>                                        
                                    </span>
                                </th>
                                <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(2)">
                                    <span class="flex items-center">Type
                                        <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                        </svg>                                        
                                    </span>
                                </th>
                                <th class="py-3 px-4 text-left">Action</th>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="roomList" class="bg-white divide-y divide-gray-200">
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="py-3 px-4"><?php echo $row["building"]; ?></td>
                                        <td class="py-3 px-4"><?php echo $row["room_number"]; ?></td>
                                        <td class="py-3 px-4"><?php echo $row["room_type"]; ?></td>
                                        <td class="py-3 px-4">
                                            <button onclick="showEditFacilityForm(<?php echo $row['room_id']; ?>)" class="bg-blue-500 text-white rounded-md px-4 py-2 hover:bg-blue-600">Edit</button>
                                            <button onclick="deleteFacility(<?php echo $row['room_id']; ?>)" class="bg-red-500 text-white rounded-md px-4 py-2 hover:bg-red-600">Delete</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-4 px-4 text-center">No facilities found</td>
                                </tr>
                            <?php endif; ?> 
                        </tbody>
                    </table>
                </div>

                <!--Room Form-->
                <div id="room-form" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                    <div class="bg-white py-4 px-6 rounded-md">
                        <!-- Modal Header with Close Button -->
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-2xl font-semibold">Add Room</h2>
                            <button onclick="closeRoomForm()" class="text-gray-600 hover:text-gray-800 focus:outline-none">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <form method="POST" action="handlers/create_room.php">
                            <div class="flex mb-4 gap-2">
                                <div class="w-1/2">
                                    <label for="roomNumber" class="block text-sm font-medium text-gray-700">Room Number:</label>
                                    <input type="text" id="roomNumber" name="roomNumber" required class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500">
                                </div>
                                <div class="w-1/2">
                                    <label for="building" class="block text-sm font-medium text-gray-700">Building:</label>
                                    <select id="building" name="building" required class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500">
                                        <option value="">Select Building</option> <!-- Default empty option -->
                                        <?php
                                        // Query to get distinct dept_building values
                                        $query = "SELECT DISTINCT dept_building FROM dept_tbl";
                                        $result = $conn->query($query);
                                        // Loop through the result and create an <option> element for each building
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo '<option value="' . $row['dept_building'] . '">' . $row['dept_building'] . '</option>';
                                            }
                                        } else {
                                            echo '<option value="">No buildings available</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="flex mb-4 gap-2">
                                <div class="w-1/2">
                                    <label for="type" class="block text-sm font-medium text-gray-700">Type:</label>
                                    <select id="type" name="type" required class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500">
                                        <option value="">Select Type</option> <!-- Default empty option -->
                                        <option value="Classroom">Classroom</option>
                                        <option value="Laboratory">Laboratory</option>
                                    </select>
                                </div>
                                <div class="w-1/2">
                                    <label for="roomStatus" class="block text-sm font-medium text-gray-700">Status:</label>
                                    <select id="roomStatus" name="roomStatus" required class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500">
                                        <option value="Available">Available</option>
                                        <option value="Unavailable">Unavailable</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-6">
                                <button type="submit" class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:bg-blue-600">
                                    Submit
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <!-- Success Modal -->
    <div id="successModal" class="fixed z-10 inset-0 bg-black bg-opacity-50 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background Overlay -->
            <div class="fixed inset-0 bg-gray-500 opacity-75"></div>
            <!-- Modal Content -->
            <div class="bg-white rounded-lg p-8 max-w-sm mx-auto relative">
                <!-- Green Check Icon -->
                <img src="img/undraw_completing_re_i7ap.svg" alt="Success Image" class="mx-auto mb-4 w-16 h-20">
                <!-- Modal Header -->
                <h2 class="text-lg font-semibold mb-4">Room Added Successfully!</h2>
                <!-- Close Button -->
                <button onclick="closeSuccessModal()" class="absolute top-0 right-0 mt-2 mr-2 focus:outline-none">
                    <svg class="w-6 h-6 text-gray-500 hover:text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
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

    <script src="scripts/functions.js"></script>
    <script src="scripts/logout.js"></script>
    <script> 
    


    document.addEventListener("DOMContentLoaded", function () {
        // Check if the parameters are present in the URL
        var urlParams = new URLSearchParams(window.location.search);
        var success = urlParams.has('success') && urlParams.get('success') === 'true'; 
        var duplicate = urlParams.has('duplicate') && urlParams.get('duplicate') === 'true';
        var error = urlParams.has('error') && urlParams.get('error') === 'true';

        if (success) {
            var successModal = document.getElementById('successModal');
            // Show success modal
            successModal.classList.remove('hidden');
        } else if (duplicate) {
            // Handle duplicate room error
            showError("Duplicate room error occurred");
        } else if (error) {
            // Handle general error
            showError("General error occurred");
        }
    });

    function showError(message) {
        var errorMessageDiv = document.getElementById('error-message');
        // Set error message text
        errorMessageDiv.innerHTML = `
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">${message}</span>
        `;
        // Show error message
        errorMessageDiv.classList.remove('hidden');
        // Hide error message after 5 seconds
        setTimeout(function() {
            errorMessageDiv.classList.add('hidden');
            // Clear the referrer
            window.history.replaceState({}, document.title, window.location.pathname);
        }, 5000);
    }

    function closeSuccessModal() {
        var successModal = document.getElementById('successModal');
        // Hide modal
        successModal.classList.add('hidden');

        // Remove success parameter from URL
        var urlParams = new URLSearchParams(window.location.search);
        urlParams.delete('success');
        var newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        window.history.replaceState({}, '', newUrl);
    }

    </script>
</body>
</html>