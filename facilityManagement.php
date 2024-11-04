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
if (!in_array($_SESSION['role'], ['Facility Head', 'Admin'])) {
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
$sql = "SELECT * FROM facilities ORDER BY facility_id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLV: RESERVA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script>
        function filterFacilities() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const selectedBuilding = document.getElementById('buildingSelect').value.toLowerCase();
            const table = document.getElementById('facilitiesTable');
            const tr = table.getElementsByTagName('tr');
            const noResultsMessage = document.getElementById('noResultsMessage'); // The "nothing found" message element

            let visibleRows = 0; // Counter for visible rows

            for (let i = 1; i < tr.length; i++) { // Assuming first row is the header
                let td = tr[i].getElementsByTagName('td');
                let rowContainsSearchTerm = false;
                let rowMatchesBuilding = false;

                // Check if the row matches the search input
                for (let j = 0; j < td.length; j++) {
                    if (td[j]) {
                        const textValue = td[j].textContent || td[j].innerText;
                        if (textValue.toLowerCase().indexOf(searchInput) > -1) {
                            rowContainsSearchTerm = true;
                        }
                    }
                }

                // Check if the room belongs to the selected building
                const buildingCell = td[0]; // Assuming Building is the first column (index 0)
                if (buildingCell && (selectedBuilding === '' || buildingCell.textContent.toLowerCase() === selectedBuilding)) {
                    rowMatchesBuilding = true;
                }

                // Show the row if it matches both the search input and the building filter
                if (rowContainsSearchTerm && rowMatchesBuilding) {
                    tr[i].style.display = ''; // Show the row
                    visibleRows++; // Increment the counter for visible rows
                } else {
                    tr[i].style.display = 'none'; // Hide the row
                }
            }

            // Show or hide the "nothing found" message based on visible rows
            if (visibleRows === 0) {
                noResultsMessage.classList.remove('hidden'); // Show the message
            } else {
                noResultsMessage.classList.add('hidden'); // Hide the message
            }
        }

        function sortTable(columnIndex) {
            const table = document.getElementById('facilitiesTable');
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

        <!-- Sidebar here -->
        <div id="sidebar-container">
            <?php include 'sidebar.php'; ?>
        </div>

        <!-- Content area -->
        <div class="flex flex-col flex-1">
            <header class="bg-white shadow-lg">
                <div class="flex items-center justify-between px-6 py-3 border-b">
                    <h2 class="text-lg font-semibold">
                        Facility Management
                    </h2>
                </div>
            </header>
            
            <!-- Main content area -->
            <main class="flex-1 p-4 h-screen">
                <div class="flex items-center justify-between p-1 rounded-md">
                    <div class="flex items-center space-x-4 mb-4">
                    <select id="buildingSelect" class="px-4 py-2 border border-gray-300 rounded-md" onchange="filterFacilities()">
                        <option value="">All Buildings</option>
                        <?php while ($building = $buildings_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($building['building']); ?>">
                                <?php echo htmlspecialchars($building['building']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                        <input type="text" id="searchInput" class="px-4 py-2 border border-gray-300 rounded-md" placeholder="Search..." onkeyup="filterFacilities()">
                    </div>
                    <div>
                        <button onclick="showFacilityForm()" class="ml-auto px-4 py-2 bg-plv-blue text-white rounded-md flex items-center justify-center hover:bg-plv-highlight focus:outline-none focus:ring focus:ring-plv-highlight">
                            <img src="img/icons8-plus-24.png" alt="Add Account Icon" class="w-4 h-4 mr-2">
                            Add Facility
                        </button>
                    </div>
                </div>

                <div class="min-w-full bg-white rounded-md shadow-md border border-gray-200 overflow-y-auto max-h-[calc(100vh-200px)]">
                    <div id="error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Error!</strong>
                        <span class="block sm:inline">Something went wrong.</span>
                    </div>
                    <table id="facilitiesTable" class="min-w-full bg-white rounded-md shadow-md border border-gray-200">
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
                                    <span class="flex items-center">Facility Name
                                        <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                        </svg>
                                    </span>
                                </th>
                                <th class="py-3 px-4 text-left">Status</th>
                                <th class="py-3 px-4 text-left">Description</th>
                                <th class="py-3 px-4 text-left">Action</th>
                            </tr>
                        </thead>
                        <tbody id="facilityList" class="divide-y divide-gray-200">
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="py-3 px-4"><?php echo $row['building']; ?></td>
                                        <td class="py-3 px-4"><?php echo $row['facility_name']; ?></td>
                                        <td class="py-3 px-4"><?php echo $row['status']; ?></td>
                                        <td class="py-3 px-4"><?php echo $row['descri']; ?></td>
                                        <td class="py-3 px-4">
                                            <button onclick="editFacility(<?php echo $row['facility_id']; ?>)" class="bg-blue-500 text-white rounded-md px-4 py-2 hover:bg-blue-600">Edit</button>
                                            <button onclick="deleteFacility(<?php echo $row['facility_id']; ?>)" class="bg-red-500 text-white rounded-md px-4 py-2 hover:bg-red-600">Delete</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-4 px-4 text-center">
                                        <img src="img/undraw_not_found_re_bh2e.svg" alt="No Reservations Found" class="mx-auto mb-2 opacity-40" style="max-width: 250px;">
                                        <p class="text-gray-500">No results found for the selected filters.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div id="noResultsMessage" class="text-center py-4 hidden">
                        <img src="img/undraw_not_found_re_bh2e.svg" alt="No Reservations Found" class="mx-auto mb-2 opacity-40" style="max-width: 250px;">
                        <p class="text-gray-500">No results found for the selected filters.</p>
                    </div>
                </div>
                
                <!--Facility Form-->
                <div id="facility-form" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                    <div class="bg-white py-4 px-6 rounded-md">
                        <!-- Modal Header with Close Button -->
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-2xl font-semibold">Add Facility</h2>
                            <button onclick="closeFacilityForm()" class="text-gray-600 hover:text-gray-800 focus:outline-none">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <form method="POST" action="handlers/create_facility.php" >
                            <div class="mb-4">
                                <label for="facilityName" class="block text-gray-700 font-semibold mb-2">Facility Name:</label>
                                <input type="text" id="facilityName" name="facilityName" required class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500">
                            </div>
                            <div class="flex mb-4 gap-2">
                                <div class="w-1/2">
                                    <label for="building" class="block text-gray-700 font-semibold mb-2">Building:</label>
                                    <select id="building" name="building" required
                                        class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500">
                                        <option value="SC/MAIN">SC/MAIN</option>
                                        <option value="CABA">CABA</option>
                                        <option value="CAS">CAS</option>
                                        <option value="CEIT">CEIT</option>
                                        <option value="COED">COED</option>
                                        <option value="CPA">CPA</option>
                                    </select>
                                </div>
                                <div class="w-1/2">
                                    <label for="status" class="block text-gray-700 font-semibold mb-2">Status:</label>
                                    <select id="status" name="status" required
                                        class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500">
                                        <option value="Available">Available</option>
                                        <option value="Unavailable">Unavailable</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="descri" class="block text-gray-700 font-semibold mb-2">Description:</label>
                                <textarea id="descri" name="descri" rows="4" required
                                    class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500"></textarea>
                            </div>
                            <div class="mt-6">
                                <button type="submit"
                                    class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:bg-blue-600">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
            <div id="footer-container">
                <?php include 'footer.php' ?>
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

    <!--Edit Facility Form-->
    <div id="editFacilityModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white py-4 px-6 rounded-md">
            <h1 class="text-2xl font-semibold">Edit Facility</h1>
            <form id="ediFacilityForm" class="space-y-10" >
                <input type="hidden" id="editUserId" name="facilityId"><!-- Hidden field for facility ID -->
                <div class="mb-4">
                    <div>
                        <label for="editFacilityName" class="block text-gray-700 font-semibold mb-2">Facility Name:</label>
                        <input type="text" id="editFacilityName" name="facilityName" required
                            class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500">
                    </div>
                </div>
                <div class="flex mb-4 gap-2">
                    <div class="w-1/2">
                        <label for="editBuilding" class="block text-gray-700 font-semibold mb-2">Building:</label>
                        <select id="editBuilding" name="building" required
                            class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500">
                            <option value="SC/MAIN">SC/MAIN</option>
                            <option value="CABA">CABA</option>
                            <option value="CAS">CAS</option>
                            <option value="CEIT">CEIT</option>
                            <option value="COED">COED</option>
                            <option value="CPA">CPA</option>
                        </select>
                    </div>
                    <div class="w-1/2">
                        <label for="editStatus" class="block text-gray-700 font-semibold mb-2">Status:</label>
                        <select id="editStatus" name="status" required
                            class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500">
                            <option value="Available">Available</option>
                            <option value="Unavailable">Unavailable</option>
                        </select>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="editDescri" class="block text-gray-700 font-semibold mb-2">Description:</label>
                    <textarea id="editDescri" name="descri" rows="4" required
                        class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:border-blue-500"></textarea>
                </div>
            <div class="grid grid-cols-2 gap-4">
                <button type="button" onclick="closeModal()" class="col-span-1 px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">Cancel</button>
                <button type="button" onclick="saveFacilityChanges()" class="col-span-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600" >Update Facility</button>
            </div>
            </form>
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
                <h2 id="successMessage" class="text-lg font-semibold mb-4">Facility Added Successfully!</h2>
                <!-- Close Button -->
                <button onclick="closeSuccessModal()" class="absolute top-0 right-0 mt-2 mr-2 focus:outline-none">
                    <svg class="w-6 h-6 text-gray-500 hover:text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>


    
    <script src="scripts/logout.js"></script>
    <script src="scripts/functions.js"></script>
    <script> 
        let currentFacilityId;  // Declare a variable to store the current user ID

        // Edit User
        function editFacility(id) {
            // Store the user ID for future use when saving changes
            currentFacilityId = id;

            // Make an AJAX request to fetch the user details from the server using the user ID
            fetch(`handlers/fetch_facility.php?facility_id=${id}`)
                .then(response => response.json())
                .then(data => {
                    // Populate the form fields with the fetched data
                    document.getElementById('editFacilityName').value = data.facility_name;
                    document.getElementById('editBuilding').value = data.building;
                    document.getElementById('editStatus').value = data.status;
                    document.getElementById('editDescri').value = data.descri;

                    // Show the form for editing
                    // Show the modal
                    document.getElementById('editFacilityModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error fetching facility details:', error);
                    showError('Error fetching facility details.');
                });
        }

        // Save Changes to the User Account
        function saveFacilityChanges() {

            // Get the updated data from the form fields
            const updatedFacilityData = {
                id: currentFacilityId, // Make sure currentUserId is defined
                facility_name: document.getElementById('editFacilityName').value,
                building: document.getElementById('editBuilding').value,
                status: document.getElementById('editStatus').value,
                descri: document.getElementById('editDescri').value,
            };

            // Make an AJAX request to save the updated user data to the server
            fetch('handlers/update_facility.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(updatedFacilityData),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success modal or message
                        showSuccessModal('Facility updated successfully!');
                        setTimeout(() => {
                            closeSuccessModal();
                            location.reload(); // Reloads the current page
                        }, 3000); // 3000 milliseconds = 3 seconds
                // Reload the user list or update the table row
                        closeModal();
                    } else {
                        // Show error message
                        showError(data.error || 'Failed to update Facility.');
                    }
                })
                .catch(error => {
                    console.error('Error updating Facility:', error);
                    showError('Error updating Facility.');
                });
        }

        // Delete User Account
        function deleteFacility(facilityId) {
            // Confirm before deleting
            if (!confirm('Are you sure you want to delete this facility?')) {
                return;
            }

            // Make an AJAX request to delete the user from the server
            fetch(`handlers/delete_facility.php?id=${facilityId}`, {
                method: 'DELETE',
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the user row from the table
                        showSuccessModal('Facility deleted successfully!');
                        setTimeout(() => {
                            closeSuccessModal();
                            location.reload(); // Reloads the current page
                        }, 3000); // 3000 milliseconds = 3 seconds
                    } else {
                        // Show error message
                        showError(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error deleting Facility:', error);
                    showError('Error deleting Facility.');
                });
        }

        // Close modal
        function closeModal() {
            document.getElementById('editFacilityModal').classList.add('hidden');
        }

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
                showError("Facility already exists on the list!");
            } else if (error) {
                // Handle general error
                showError("Unknown Error Occurred: Facility not saved.");
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

        function showSuccessModal(message) {
            const successMessageElement = document.getElementById('successMessage');
            successMessageElement.textContent = message; // Set the dynamic success message
            const successModal = document.getElementById('successModal');
            successModal.classList.remove('hidden'); // Show the modal
            setTimeout(() => {
                closeSuccessModal();
                location.reload(); // Reloads the current page
            }, 3000); // 3000 milliseconds = 3 seconds
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