
<div id="sidebar" class="flex flex-col items-center w-16 h-full overflow-hidden text-blue-200 bg-plv-blue rounded-r-lg">
    <a class="flex items-center justify-center mt-3" href="#">
        <img class="w-8 h-8" src="img/PLV Logo.png" alt="Logo">
    </a>
    <?php
    // Determine user role here
    $userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'default';

    // Dynamically generate sidebar links based on user role
    switch ($userRole) {
        case "Registrar":
            ?>
            <!-- Registrar Sidebar Links -->
            <div class="flex flex-col items-center mt-3 border-t border-gray-700">
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="dashboard-reg" title="Dashboard">
                    <i class="fa-solid fa-home"></i>
                </a>
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="accManagement" title="Account Management">
                    <i class="fa-solid fa-user"></i>
                </a>
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="dataSetup" title="Data Setup">
                    <i class="fa-solid fa-database"></i>
                </a>
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="loads" title="Room Loading">
                    <i class="fa-solid fa-table"></i>
                </a>
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="roomManagement" title="Room Management">
                    <i class="fa-solid fa-door-open"></i>
                </a>

            </div>
            <?php
            break;

        case "Admin":
            ?>
            <!-- Admin Sidebar Links -->
            <div class="flex flex-col items-center mt-3 border-t border-gray-700">
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="dashboard-admin" title="Dashboard">
                    <i class="fa-solid fa-home"></i>
                </a>
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="accManagement" title="Account Management">
                    <i class="fa-solid fa-user"></i>
                </a>
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="dataSetup" title="Data Setup">
                    <i class="fa-solid fa-school"></i>
                </a>
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="loads" title="Room Loading">
                    <i class="fa-solid fa-table"></i>
                </a>                
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="facilityReservations" title="Facility Reservations">
                    <i class="fa-solid fa-calendar"></i>
                </a>
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="facilityManagement" title="Facility Management">
                    <i class="fa-solid fa-building"></i>
                </a>
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="roomManagement" title="Room Management">
                    <i class="fa-solid fa-door-open"></i>
                </a>
            </div>
            <?php
            break;

        case "Facility Head":
            ?>
            <!-- Facility Head Sidebar Links -->
            <div class="flex flex-col items-center mt-3 border-t border-gray-700">
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="dashboard-gso" title="Dashboard">
                    <i class="fa-solid fa-home"></i>
                </a>
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="facilityManagement" title="Facility Management">
                    <i class="fa-solid fa-building"></i>
                </a>
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="facilityReservations" title="Facility Reservations">
                    <i class="fa-solid fa-calendar"></i>
                </a>
            </div>
            <?php
            break;

        case "Dept. Head":
            ?>
            <!-- Dept. Head Sidebar Links -->
            <div class="flex flex-col items-center mt-3 border-t border-gray-700">
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="dashboard-deptHead" title="Dashboard">
                    <i class="fa-solid fa-home"></i>
                </a>
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="loads" title="Room Loading">
                    <i class="fa-solid fa-table"></i>
                </a>
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="reservations-deptHead" title="Facility Reservations">
                    <i class="fa-solid fa-calendar"></i>
                </a>
            </div>
            <?php
            break;

        case "Student Rep":
            ?>
            <!-- Student Rep Sidebar Links -->
            <div class="flex flex-col items-center mt-3 border-t border-gray-700">
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="dashboard-student" title="Home">
                    <i class="fa-solid fa-home"></i>
                </a>
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="reservations-student" title="Reservations">
                    <i class="fa-solid fa-calendar"></i>
                </a>
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="facilityReservation" title="Facility Reservation">
                    <i class="fa-solid fa-building"></i>
                </a>
            </div>
            <?php
            break;

        // Add cases for other user roles as needed
        default:
            // Default sidebar links for unrecognized roles
            ?>
            <div class="flex flex-col items-center mt-3 border-t border-gray-700">
                <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="#" title="Placeholder Link">
                    <i class="fa-solid fa-link"></i>
                </a>
            </div>
            <?php
            break;
    }
    ?>
    <div class="flex flex-col items-center mt-2 border-t border-gray-700">
        <a class="flex items-center justify-center w-12 h-12 mt-2 rounded hover:bg-persian-blue" href="settings_page" title="Account Settings">
            <i class="fa-solid fa-gear"></i>
        </a>
    </div>
    <!-- Trigger the custom confirmation dialog -->
    <a class="flex items-center justify-center w-16 h-16 mt-auto bg-persian-blue hover:bg-plv-highlight" onclick="showCustomDialog()">
        <i class="fa-solid fa-sign-out-alt"></i>
    </a>
</div>

<!-- Font Awesome CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" />
