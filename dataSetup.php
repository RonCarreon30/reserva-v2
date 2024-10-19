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
                <main class="flex-1 p-4 overflow-y-auto">
            </div>
        </div>
    </body>
</body>
</html>