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
            <div class="flex flex-col flex-1">
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
                                <option value="">Academic Year...</option>
                                <?php while ($building = $buildings_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($building['building']); ?>">
                                        <?php echo htmlspecialchars($building['building']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <select id="buildingSelect" class="px-4 py-2 border border-gray-300 rounded-md" onchange="filterRooms()">
                                <option value="">Semester</option>
                                <?php while ($building = $buildings_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($building['building']); ?>">
                                        <?php echo htmlspecialchars($building['building']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <select id="buildingSelect" class="px-4 py-2 border border-gray-300 rounded-md" onchange="filterRooms()">
                                <option value="">All Buildings</option>
                                <?php while ($building = $buildings_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($building['building']); ?>">
                                        <?php echo htmlspecialchars($building['building']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <select id="roomSelect" class="px-4 py-2 border border-gray-300 rounded-md" onchange="filterRooms()">
                                <option value="">All Rooms</option>
                                <?php while ($building = $buildings_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($building['building']); ?>">
                                        <?php echo htmlspecialchars($building['building']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
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