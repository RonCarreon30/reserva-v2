<?php
    // Start the session
    session_start();

    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        // Redirect to the login page
        header("Location: unauthorized");
        exit();
    }

    //connect to db
    include_once 'database/config.php';
    // Query to fetch reservations for the current user
    $user_id = $_SESSION['user_id'];

    // Fetch user's department
    $user_id = $_SESSION['user_id'];
    $user_department = $_SESSION['department'];

    // Fetch buildings for the dropdown
    $buildings_sql = "SELECT DISTINCT building FROM facilities";
    $buildings_result = $conn->query($buildings_sql);

    // Fetch all facilities
    $facility_sql = "SELECT * FROM facilities where status = 'Available'";
    $facility_result = $conn->query($facility_sql);
?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>PLV: RESERVA</title>
        <link rel="stylesheet" href="css/style.css">
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        <script src='https://cdn.jsdelivr.net/npm/fullcalendar/index.global.min.js'></script>
        <!-- Flatpickr CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <!-- Flatpickr JS -->
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

        <script>
            function filterFacilities() {
                const building = document.getElementById('buildingSelect').value.toLowerCase();
                const searchQuery = document.getElementById('searchInput').value.toLowerCase();
                const facilityRows = document.querySelectorAll('#facilityTable tbody tr');

                facilityRows.forEach(row => {
                    const facilityName = row.querySelector('.facility-name').textContent.toLowerCase();
                    const facilityBuilding = row.querySelector('.facility-building').textContent.toLowerCase();
                    
                    const matchesBuilding = building === '' || facilityBuilding.includes(building);
                    const matchesSearch = facilityName.includes(searchQuery);

                    row.style.display = matchesBuilding && matchesSearch ? '' : 'none';
                });
            }

            function showReservationForm(facilityName, facilityId) {

                document.getElementById('facilityName').value = facilityName;
                document.getElementById('facilityId').value = facilityId; // Add this line
                document.getElementById('reservationModal').classList.remove('hidden');
                console.log('Facility ID:', facilityId); // Log it to the console
            }

            function sortTable(columnIndex) {
                const table = document.getElementById('facilityTable');
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

            document.addEventListener("DOMContentLoaded", function() {
                const holidayAPIUrl = "https://www.googleapis.com/calendar/v3/calendars/en.philippines%23holiday%40group.v.calendar.google.com/events?key=AIzaSyCB7rRha3zbgSYH1aD5SECsRvQ3usacZHU"; // Your API endpoint

                fetch(holidayAPIUrl)
                    .then(response => response.json())
                    .then(data => {
                        console.log(data);
                        // Extract holiday dates from the API response
                        const holidayDates = data.items.map(holiday => holiday.start.date);

                        // Initialize Flatpickr with disabled holiday dates
                        flatpickr("#reservationDate", {
                            dateFormat: "Y-m-d",
                            enableTime: false,

                            onDayCreate: function(dObj, dStr, fp, dayElem) {
                                const date = dayElem.dateObj; // Get the date of the current day element
                                const dateString = dayElem.dateObj.toISOString().split('T')[0]; // Get date in YYYY-MM-DD format

                                // Add custom class for holidays
                                if (holidayDates.includes(dateString)) {
                                    dayElem.classList.add("holiday"); // Add class for holidays
                                }

                                // Add custom class for Sundays
                                if (date.getDay() === 0) {
                                    dayElem.classList.add("sunday"); // Add class for Sundays
                                }
                            },
                            onChange: function(selectedDates, dateStr, instance) {
                                const selectedDate = new Date(dateStr); // Convert selected date string to Date object
                                const today = new Date(); // Get today's date
                                today.setHours(0, 0, 0, 0); // Reset hours, minutes, seconds for comparison

                                // Create a new Date object for the selected date without time components
                                const selectedDateNoTime = new Date(selectedDate);
                                selectedDateNoTime.setHours(0, 0, 0, 0); // Reset hours, minutes, seconds for comparison

                                console.log("Today: ", today);
                                console.log("Selected date (no time): ", selectedDateNoTime);

                                // Check if selected date is a holiday
                                if (holidayDates.includes(dateStr)) {
                                    showToast(`${dateStr} is a holiday and cannot be selected.`); // Show toast for holiday
                                    instance.clear(); // Optionally clear the selection
                                } 
                                // Check if the selected date is today
                                else if (selectedDateNoTime.getTime() === today.getTime()) {
                                    showToast("Same day reservations are not allowed."); // Show toast for same day reservation
                                    instance.clear(); // Optionally clear the selection
                                } 
                                // Check if the selected date is in the past
                                else if (selectedDateNoTime < today) {
                                    showToast(`${dateStr} is a past date and cannot be selected.`); // Show toast for past dates
                                    instance.clear(); // Optionally clear the selection
                                }     // Check if the selected date is a Sunday
                                else if (selectedDate.getDay() === 0) { // Sunday is represented by 0
                                    showToast(`${dateStr} falls on a Sunday and cannot be selected.`); // Show toast for Sunday
                                    instance.clear(); // Optionally clear the selection
                                } else {
                                    console.log("Selected date: ", dateStr); // Handle the selected date
                                }
                            }
                        });
                    })
                    .catch(error => {
                        console.error("Error fetching holiday data:", error);
                    });

                function showToast(message) {
                    const toast = document.getElementById("toast");
                    const toastMessage = document.getElementById("toastMessage");

                    toastMessage.textContent = message; // Set the toast message
                    toast.classList.remove("hidden"); // Show the toast

                    // Hide the toast after 3 seconds
                    setTimeout(() => {
                        toast.classList.add("hidden");
                    }, 3000);
                }
            });
        </script>
        <style>
            /* Custom styles for holidays */
            .flatpickr-day.holiday {
                background-color: #ffcccc; /* Light red background */
                color: #d9534f; /* Dark red text */
            }

            /* Custom styles for Sundays */
            .flatpickr-day.sunday {
                background-color: #ccf2ff; /* Light blue background */
                color: #007bff; /* Blue text */
            }
            #custom-dialog, #toast {
                z-index: 10000; /* Ensures the logout modal appears on top of everything */
            }

        </style>
</head>
<body>
    <div class="flex h-screen bg-gray-100">
        <div id="sidebar-container">
            <?php include 'sidebar.php'; ?>
        </div>
        
        <!-- Content area -->
        <div class="flex flex-col flex-1">
            <!-- Header -->
            <header class="bg-white shadow-lg">
                <div class="flex items-center justify-between px-6 py-3 border-b">
                    <h2 class="text-lg font-semibold">Facility Reservation</h2>
                </div>
            </header>
        <!-- Main Section -->
        <main class="flex-1 p-4 overflow-y-auto">
            <div class="flex items-center space-x-4 mb-4">
                <div id="facility-reservations" title="Reservations">
                    <button id="view-reservations-btn" onclick="window.history.back()" class="px-3 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-600 transition duration-150 ease-in-out">
                        <i class="fa-solid fa-calendar"></i>
                    </button>
                </div>
                <select id="buildingSelect" class="px-4 py-2 border border-gray-300 rounded-md" onchange="filterFacilities()">
                    <option value="">All Buildings</option>
                    <?php while ($building = $buildings_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($building['building']); ?>">
                            <?php echo htmlspecialchars($building['building']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="text" id="searchInput" class="px-4 py-2 border border-gray-300 rounded-md" placeholder="Search facilities..." onkeyup="filterFacilities()">
            </div>
            <table id="facilityTable" class="min-w-full bg-white rounded-md shadow-md border border-gray-200" data-sort-order="asc">
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
                        <th class="py-3 px-4 text-left">
                            <span class="flex items-center">Status</span>
                        </th>
                        <th class="py-3 px-4 text-left">
                            <span class="flex items-center">Description</span>
                        </th>
                        <th class="py-3 px-4 text-left">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                    <?php if ($facility_result->num_rows > 0): ?>
                        <?php while ($row = $facility_result->fetch_assoc()): ?>
                            <tr class="<?php echo $row['status'] === 'Unavailable' ? 'text-red-600 bg-gray-100' : ''; ?>">
                                <td class="py-2 px-4 facility-building"><?php echo htmlspecialchars($row['building']); ?></td>                                    
                                <td class="py-2 px-4 facility-name"><?php echo htmlspecialchars($row['facility_name']); ?></td>                                    
                                <td class="py-2 px-4"><?php echo htmlspecialchars($row['status']); ?></td>
                                <td class="py-2 px-4"><?php echo htmlspecialchars($row['descri']); ?></td>
                                <td class="py-2 px-4">
                                    <?php if ($row['status'] !== 'Unavailable'): ?>
                                        <button onclick="showReservationForm('<?php echo htmlspecialchars($row['facility_name']); ?>', '<?php echo  htmlspecialchars($row['facility_id']); ?>')" class="bg-blue-500 text-white rounded-md px-4 py-2 hover:bg-blue-600">Reserve</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">No facilities found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
    <div id="toast" class="fixed top-4 right-4 bg-red-400 text-white text-sm p-3 rounded-lg hidden">
        <span id="toastMessage"></span>
    </div>
    <!-- Reservation Modal -->
    <div id="reservationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white h-5/6 overflow-y-auto py-4 px-6 rounded-md">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold">Reserve Facility</h2>
                <button id="closeModal" class="text-gray-600 hover:text-gray-800 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="reservationForm" class="space-y-4">
                <input type="hidden" id="facilityId" name="facilityId" required>
                <div class="flex mb-4 gap-2">
                    <div class="w-1/2">
                        <label for="facilityName"  class="block text-gray-700 text-xs">Facility Name:</label>
                        <input type="text" id="facilityName" name="facilityName" class="w-full border border-gray-300 bg-gray-300 rounded-md p-2" readonly required>
                    </div>
                    <div class="w-1/2">
                        <label for="reservationDate" class="block text-gray-700 text-xs">Reservation Date:<span class="text-red-500">*</span></label>
                        <input type="text" id="reservationDate" name="reservationDate" class="w-full px-3 py-2 rounded-md border border-gray-300" required onchange="validateDate()">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="purpose" class="block text-gray-700 text-xs">Purpose:<span class="text-red-500">*</span></label>
                    <input type="text" id="purpose" name="purpose" class="w-full px-3 py-2 rounded-md border border-gray-300">
                </div>
                <div class="flex flex-col space-y-2 hidden">
                    <label for="department" class="text-gray-700">Department:</label>
                    <input type="text" id="department" name="department" class="border border-gray-300 rounded-md p-2" value="<?php echo htmlspecialchars($user_department); ?>" readonly>
                </div>
                <div class="flex mb-4 gap-2">
                    <div class="w-1/2">
                        <label for="startTime" class="block text-gray-700 text-xs">Starting Time:<span class="text-red-500">*</span></label>
                        <select id="startTime" name="startTime" class="w-full px-3 py-2 rounded-md border border-gray-300" required>
                            <option value="" readonly></option>
                            <?php
                                function generateTimeOptions() {
                                    $times = [];
                                    $start = strtotime('07:00 AM');
                                    $end = strtotime('9:00 PM');
                                    $interval = 30 * 60; // 30 minutes in seconds

                                    for ($current = $start; $current <= $end; $current += $interval) {
                                        $time = date('h:i A', $current);
                                        $times[] = $time;
                                    }

                                    return $times;
                                }

                                $timeOptions = generateTimeOptions();
                            ?>
                            <?php foreach ($timeOptions as $time): ?>
                                <option value="<?php echo $time; ?>"><?php echo $time; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="w-1/2">
                        <label for="endTime" class="block text-gray-700 text-xs">End Time:<span class="text-red-500">*</span></label>
                        <select id="endTime" name="endTime" class="w-full px-3 py-2 rounded-md border border-gray-300" required>
                            <option value="" readonly></option>
                            <?php foreach ($timeOptions as $time): ?>
                                <option value="<?php echo $time; ?>"><?php echo $time; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="facultyInCharge" class="block text-gray-700 text-xs">Faculty in Charge:<span class="text-red-500">*</span></label>
                    <input type="text" id="facultyInCharge" name="facultyInCharge" class="w-full px-3 py-2 rounded-md border border-gray-300" required>
                </div>                                        
                <div class="mb-4">
                    <label for="additionalInfo" class="text-gray-700 text-xs">Additional Information:<span class="text-red-500">*</span></label>
                    <textarea id="additionalInfo" name="additionalInfo" rows="2" placeholder="Put N/A if no additional details" class="w-full px-3 py-2 rounded-md border border-gray-300"></textarea>
                </div>
                <!-- Add more form fields as needed -->
                <div class="mt-6">
                    <button type="button" id="reserveButton" class="w-full bg-plv-blue text-white rounded-md px-4 py-2 hover:bg-plv-highlight">Reserve</button>
                </div>
            </form>
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

    <!-- Error Modal -->
    <div id="errorModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-8 rounded-md shadow-md">
            <h2 class="text-xl font-semibold mb-4">Validation Errors</h2>
            <ul id="errorList" class="text-red-600">
                <!-- Validation errors will be inserted here dynamically -->
            </ul>
            <button id="closeErrorModal" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Close</button>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-8 rounded-md shadow-md">
            <h2 class="text-xl font-semibold mb-4">Success</h2>
            <p id="successMessage" class="text-green-600"></p>
            <button id="closeSuccessModal" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Close</button>
        </div>
    </div>

    <script src="scripts/logout.js"></script>
    <script>
        // Utility function to close a modal
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('hidden');
        }

        // Attach event listeners to modal close buttons
        document.getElementById('closeModal').addEventListener('click', () => closeModal('reservationModal'));
        document.getElementById('closeErrorModal').addEventListener('click', () => closeModal('errorModal'));
        document.getElementById('closeSuccessModal').addEventListener('click', () => closeModal('successModal'));

        // Validate reservation form input
        function validateReservationForm({ reservationDate, startTime, endTime, purpose }) {
            const today = new Date().toISOString().split('T')[0];

            if (!reservationDate || !startTime || !endTime || !purpose) {
                alert('Please fill in all required fields.');
                return false;
            }

            const startDateTime = new Date(`1970-01-01T${startTime}`);
            const endDateTime = new Date(`1970-01-01T${endTime}`);

            if (endDateTime <= startDateTime) {
                alert('End time must be later than start time.');
                return false;
            }

            return true;
        }

        // Handle form submission
        document.getElementById('reserveButton').addEventListener('click', function () {
            console.log('Facility ID:', facilityId); // Log it to the console
            const reservationData = {
                facilityId: document.getElementById('facilityId').value,
                facilityName: document.getElementById('facilityName').value,
                reservationDate: document.getElementById('reservationDate').value,
                startTime: document.getElementById('startTime').value,
                endTime: document.getElementById('endTime').value,
                facultyInCharge: document.getElementById('facultyInCharge').value,
                purpose: document.getElementById('purpose').value,
            };

            if (!validateReservationForm(reservationData)) {
                return;
            }

            const reservationForm = document.getElementById('reservationForm');
            const formData = new FormData(reservationForm);

            fetch('handlers/reserve_facility.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('reservationModal');
                    const successModal = document.getElementById('successModal');
                    const successMessage = document.getElementById('successMessage'); // Assuming you have an element for success message
                    successMessage.textContent = data.message; // Update the content of the success message
                    successModal.classList.remove('hidden');
                    setTimeout(() => {
                        let role = '<?php echo $_SESSION['role']; ?>';
                        console.log(department)
                        if (role === 'Student Rep') {
                            window.location.href = 'reservations-student';
                        } else if (role === 'Dept. Head') {
                            window.location.href = 'reservations-deptHead';
                        } else {
                            window.location.href = 'facilityReservations';
                        } 
                    }, 2000);
                } else {
                    const errorModal = document.getElementById('errorModal');
                    const errorList = document.getElementById('errorList');
                    errorList.innerHTML = `<li>${data.error}</li>`;
                    errorModal.classList.remove('hidden');
                }

            })
            .catch(error => {
                console.error('Error submitting reservation:', error);
            });
        });
    </script>


</body>
</html>