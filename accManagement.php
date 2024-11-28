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
if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Registrar') {
    // Redirect to a page indicating unauthorized access
    header("Location: unauthorized");
    exit();
}

// Fetch reservations from the database for the current user
require_once "database/config.php";

// Fetch the user ID from the session data
$user_id = $_SESSION['user_id'];

// Query to fetch user data and department name from the database
$sql = "SELECT users.*, dept_tbl.dept_name 
        FROM users 
        LEFT JOIN dept_tbl ON users.department_id = dept_tbl.dept_id";
$result = $conn->query($sql);

// Fetch roles for the dropdown
$role_sql = "SELECT DISTINCT userRole FROM users";
$role_result = $conn->query($role_sql);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLV: RESERVA</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        function filterUser() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const roleFilter = document.getElementById('roleSelect').value.toLowerCase();
            const table = document.getElementById('usersTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let td = tr[i].getElementsByTagName('td');
                let textMatch = false;
                let roleMatch = false;

                // Text search filter
                for (let j = 0; j < td.length - 1; j++) { // Ignore the last 'Action' column
                    if (td[j]) {
                        const textValue = td[j].textContent || td[j].innerText;
                        if (textValue.toLowerCase().indexOf(input) > -1) {
                            textMatch = true;
                        }
                    }
                }

                // Role filter
                if (td[4] && (roleFilter === "" || td[4].textContent.toLowerCase().indexOf(roleFilter) > -1)) {
                    roleMatch = true;
                }

                // Show row only if both text and role match
                tr[i].style.display = (textMatch && roleMatch) ? '' : 'none';
            }
        }
        

        function sortTable(columnIndex) {
            const table = document.getElementById('usersTable');
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
                        Account Management
                    </h2>
                </div>
            </header>

            <!-- Main content area -->
            <main class="flex-1 p-4 h-screen">
                <div class="flex items-center justify-between p-1 rounded-md">
                    <div class="flex items-center space-x-4 mb-4">
                        <select id="roleSelect" class="px-4 py-2 border border-gray-300 rounded-md" onchange="filterUser()">
                            <option value="">All Roles</option>
                            <?php while ($role = $role_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($role['userRole']); ?>">
                                    <?php echo htmlspecialchars($role['userRole']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <input type="text" id="searchInput" class="px-4 py-2 border border-gray-300 rounded-md" placeholder="Search..." onkeyup="filterUser()">
                    </div>
                    <div>
                        <button onclick="showUserForm()" class="ml-auto px-4 py-2 bg-plv-blue text-white rounded-md flex items-center justify-center hover:bg-plv-highlight focus:outline-none focus:ring focus:ring-plv-highlight">
                            <img src="img/icons8-add-user-30.png" alt="Add Account Icon" class="w-4 h-4 mr-2">
                            Add User
                        </button>
                    </div>
                </div>               
            
                <!-- User List -->
                <div class=" overflow-y-auto max-h-[calc(100vh-200px)]">
                    <div id="toast-container" class="fixed top-5 right-5 z-50 space-y-2"></div>
                    <div id="error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Error!</strong>
                        <span class="block sm:inline">Something went wrong.</span>
                    </div>
                    <table id="usersTable" class="min-w-full bg-white rounded-md shadow-md border border-gray-200">
                        <thead class="">
                            <tr class="bg-gray-200 border-b">
                                <th class=" border-r border-white py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(0)">
                                    <span class="flex items-center">First Name
                                        <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                        </svg>
                                    </span>
                                    
                                </th>
                                <th class="border-r border-white py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(1)">
                                    <span class="flex items-center">Last Name
                                        <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                        </svg>
                                    </span>
                                </th>
                                <th class="border-r border-white py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(2)">
                                    <span class="flex items-center">Email
                                        <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                        </svg>
                                    </span>
                                </th>
                                <th class="border-r border-white py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(3)">
                                    <span class="flex items-center">Department
                                        <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                        </svg>
                                    </span>
                                </th>
                                <th class="border-r border-white py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(4)">
                                    <span class="flex items-center">Role
                                        <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                        </svg>
                                    </span>
                                </th>
                                <th class="py-3 px-4 text-left">Action</th>
                            </tr>
                        </thead>
                        <tbody id="userList" class="bg-white divide-y divide-gray-200">
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="border py-3 px-4"><?php echo $row['first_name']; ?></td>
                                        <td class="border py-3 px-4"><?php echo $row['last_name']; ?></td>
                                        <td class="border py-3 px-4"><?php echo $row['email']; ?></td>
                                        <td class="border py-3 px-4"><?php echo $row['dept_name']; ?></td>
                                        <td class="border py-3 px-4"><?php echo $row['userRole']; ?></td>
                                        <td class="border py-2 px-4 space-x-2">
                                            <button onclick="editUser(<?php echo $row['id']; ?>)" class="text-blue-500 hover:text-blue-600" title='Edit User'><i class="fas fa-edit"></i></button>
                                            <button onclick="deleteUser(<?php echo $row['id']; ?>)" class="text-red-500 hover:text-red-600" title="Delete"><i class="fas fa-trash-alt"></i></button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="py-4 px-4 text-center">No user found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>

                    </table>
                </div>

                <!--Add User Form-->      
                <div id="addUserForm" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                    <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-md flex flex-col items-center">
                        <h1 class="text-center mb-10 text-slate-700 font-semibold text-xl">ADD USER ACCOUNT</h1>
                        <form id="createUserForm" class="space-y-10">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="firstName" class="block text-sm font-medium text-gray-700">First Name</label>
                                    <input id="firstName" name="firstName" type="text" required class="shadow-sm p-1 focus:ring-plv-blue focus:border-plv-blue block w-full border border-gray-300 rounded-md">
                                </div>
                                <div>
                                    <label for="lastName" class="block text-sm font-medium text-gray-700">Last Name</label>
                                    <input id="lastName" name="lastName" type="text" required class="shadow-sm p-1 focus:ring-blue-500 focus:border-blue-500 block w-full border border-gray-300 rounded-md">
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                    <input id="email" name="email" type="email" required class="shadow-sm p-1 focus:ring-blue-500 focus:border-blue-500 block w-full border border-gray-300 rounded-md">
                                </div>
                                <div>
                                    <label for="idNumber" class="block text-sm font-medium text-gray-700">ID Number</label>
                                    <input id="idNumber" name="idNumber" type="text" required class="shadow-sm p-1 focus:ring-blue-500 focus:border-blue-500 block w-full border border-gray-300 rounded-md">
                                </div>
                                <div>
                                    <label for="department" class="block text-sm font-medium text-gray-700">Department</label>
                                    <select id="department" name="department" required class="shadow-sm p-1 focus:ring-blue-500 focus:border-blue-500 block w-full border border-gray-300 rounded-md">
                                        <option value="" readonly>Select Department</option> <!-- Default empty option -->
                                        <?php
                                        // Query to get distinct dept_building values
                                        $query = "SELECT * FROM dept_tbl";
                                        $result = $conn->query($query);
                                        // Loop through the result and create an <option> element for each building
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo '<option value="' . $row['dept_id'] . '">' . $row['dept_name'] . '</option>';
                                            }
                                        } else {
                                            echo '<option value="">No department available</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="role" class="block text-sm font-medium text-gray-700">User Role</label>
                                    <select id="role" name="role" required class="shadow-sm p-1 focus:ring-blue-500 focus:border-blue-500 block w-full border border-gray-300 rounded-md">
                                        <option value="" readonly>Select Role</option> <!-- Default empty option -->
                                        <option value="Admin">Admin</option>
                                        <option value="Registrar">Registrar</option>
                                        <option value="Facility Head">Facility Head</option>
                                        <option value="Dept. Head">Dept. Head</option>
                                        <option value="Student Rep">Student Rep</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                    <div class="relative">
                                        <input id="password" name="password" type="password" required autocomplete="new-password" class="shadow-sm p-1 focus:ring-blue-500 focus:border-blue-500 block w-full border border-gray-300 rounded-md">
                                        <button type="button" onclick="togglePasswordVisibility('password', this)" class="absolute inset-y-0 right-0 flex items-center pr-2 text-gray-400">
                                            <i class="fas fa-eye" id="passwordIcon"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label for="confirmPassword" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                    <div class="relative">
                                        <input id="confirmPassword" name="confirmPassword" type="password" required autocomplete="new-password" class="shadow-sm p-1 focus:ring-blue-500 focus:border-blue-500 block w-full border border-gray-300 rounded-md">
                                        <button type="button" onclick="togglePasswordVisibility('confirmPassword', this)" class="absolute inset-y-0 right-0 flex items-center pr-2 text-gray-400">
                                            <i class="fas fa-eye" id="passwordIcon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <button onclick="closeForm()" class="col-span-1 px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">Cancel</button>
                                <button type="submit" class="col-span-1 px-4 py-2 bg-plv-blue text-white rounded-lg hover:bg-plv-highlight focus:outline-none focus:ring focus:ring-plv-highlight">Create Account</button>
                            </div>
                        </form>
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

    <!-- Edit User Modal -->
    <div id="editUserModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-md flex flex-col items-center">
            <h1 class="text-center text-slate-700 font-semibold text-xl">EDIT USER ACCOUNT</h1>
            <form id="editUserForm" class="space-y-6">
                <input type="hidden" id="editUserId" name="userId"> <!-- Hidden field for user ID -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="editFirstName" class="block text-sm font-medium text-gray-700">First Name</label>
                        <input id="editFirstName" name="firstName" type="text" required class="shadow-sm p-1 focus:ring-blue-500 focus:border-blue-500 block w-full border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label for="editLastName" class="block text-sm font-medium text-gray-700">Last Name</label>
                        <input id="editLastName" name="lastName" type="text" required class="shadow-sm p-1 focus:ring-blue-500 focus:border-blue-500 block w-full border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label for="editEmail" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="editEmail" name="email" type="email" required class="shadow-sm p-1 focus:ring-blue-500 focus:border-blue-500 block w-full border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label for="editIdNumber" class="block text-sm font-medium text-gray-700">ID Number</label>
                        <input id="editIdNumber" name="IdNumber" type="text" required class="shadow-sm p-1 focus:ring-blue-500 focus:border-blue-500 block w-full border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label for="editDepartment" class="block text-sm font-medium text-gray-700">Department</label>
                        <select id="editDepartment" name="department" required class="shadow-sm p-1 focus:ring-blue-500 focus:border-blue-500 block w-full border border-gray-300 rounded-md">
                            <option value="" readonly>Select Department</option> <!-- Default empty option -->
                                <?php
                                    // Query to get distinct dept_building values
                                    $query = "SELECT * FROM dept_tbl";
                                    $result = $conn->query($query);
                                    // Loop through the result and create an <option> element for each building
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<option value="' . $row['dept_id'] . '">' . $row['dept_name'] . '</option>';
                                        }
                                    } else {
                                        echo '<option value="">No department available</option>';
                                    }
                                ?>
                        </select>
                    </div>
                    <div>
                        <label for="editRole" class="block text-sm font-medium text-gray-700">User Role</label>
                        <select id="editRole" name="role" required class="shadow-sm p-1 focus:ring-blue-500 focus:border-blue-500 block w-full border border-gray-300 rounded-md">
                            <option value="Admin">Admin</option>
                            <option value="Registrar">Registrar</option>
                            <option value="Facility Head">Facility Head</option>
                            <option value="Dept. Head">Dept. Head</option>
                            <option value="Student Rep">Student Rep</option>
                        </select>
                    </div>
                    <div>
                        <label for="editPassword" class="block text-sm font-medium text-gray-700">New Password</label>
                        <div class="relative">
                            <input id="editPassword" name="password" type="password" class="shadow-sm p-1 focus:ring-blue-500 focus:border-blue-500 block w-full border border-gray-300 rounded-md">
                            <button type="button" onclick="togglePasswordVisibility('editPassword', this)" class="absolute inset-y-0 right-0 flex items-center mr-2 text-gray-400">
                                <i class="fas fa-eye" id="passwordIcon"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="editConfirmPassword" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <div class="relative">
                            <input id="editConfirmPassword" name="confirmPassword" type="password" class="shadow-sm p-1 focus:ring-blue-500 focus:border-blue-500 block w-full border border-gray-300 rounded-md">
                            <button type="button" onclick="togglePasswordVisibility('editConfirmPassword', this)" class="absolute inset-y-0 right-0 flex items-center mr-2 text-gray-400">
                                <i class="fas fa-eye" id="confirmPasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                </div>
                <div class="grid grid-cols-2 mt-2 gap-4">
                    <button type="button" onclick="closeModal()" class="col-span-1 px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="col-span-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600" >Update Account</button>
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
                <h2 class="text-lg font-semibold mb-4" id="successMessage">Account Created Successfully!</h2> <!-- Added id for dynamic message -->
                <!-- Close Button -->
                <button onclick="closeSuccessModal()" class="absolute top-0 right-0 mt-2 mr-2 focus:outline-none">
                    <svg class="w-6 h-6 text-gray-500 hover:text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmDeleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-md flex flex-col items-center">
            <h2 class="text-xl font-semibold mb-4">Confirm Deletion</h2>
            <img class="w-1/2 mb-4" src="img/undraw_throw_away_re_x60k.svg" alt="">
            <p class="text-lg text-slate-700 font-semibold mb-4">Are you sure you want to delete this user?</p>
            <div class="flex justify-center mt-5 space-x-4">
                <button id="cancelDelete" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400" onclick="closeDeleteModal()">Cancel</button>
                <button id="confirmDelete" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>
        
    <!-- confirm logout modal -->
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
    <script src="scripts/accMngmnt.js"></script>
    <script src="scripts/functions.js"></script>
    <script>
        document.getElementById("lastName").addEventListener("input", updatePassword);
document.getElementById("idNumber").addEventListener("input", updatePassword);

function updatePassword() {
    const lastName = document.getElementById("lastName").value;
    const idNumber = document.getElementById("idNumber").value;

    if (lastName && idNumber) {
        const generatedPassword = `${lastName}${idNumber}`;
        document.getElementById("password").value = generatedPassword;
        document.getElementById("confirmPassword").value = generatedPassword;
    } else {
        document.getElementById("password").value = "";
        document.getElementById("confirmPassword").value = "";
    }
}

    </script>
    <!-- JavaScript for edit and delete actions -->
    <script>
        let currentUserId;  // Declare a variable to store the current user ID

        // Prevent form redirection on submit
        document.getElementById("createUserForm").addEventListener("submit", function (event) {
            event.preventDefault(); // Prevent the default form submission (page reload/redirect)

            // Extract form data
            const formData = new FormData(this);

            // Use fetch API to send form data asynchronously
            fetch("handlers/create_user.php", {
            method: "POST",
            body: formData,
            })
            .then((response) => response.json()) // Assuming response is JSON
            .then((data) => {
                if (data.status === 'success') {
                // Show success message in modal
                showToast(data);
                setTimeout(() => {
                    location.reload(); // Reloads the current page
                }, 3000);
                closeModal();
                
                } else {
                // Show error message
                showToast(data);
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                // Show generic error message in modal
                showToast("There was an error processing the form.", false); // Passing `false` for error
            });
        });

        // Edit User
        function editUser(id) {
            // Store the user ID for future use when saving changes
            currentUserId = id;
            console.log("Current User ID set to:", currentUserId); 

            // Make an AJAX request to fetch the user details from the server using the user ID
            fetch(`handlers/fetch_user.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    // Populate the form fields with the fetched data
                    document.getElementById('editFirstName').value = data.first_name;
                    document.getElementById('editLastName').value = data.last_name;
                    document.getElementById('editEmail').value = data.email;
                    document.getElementById('editIdNumber').value = data.id_number;
                    document.getElementById('editDepartment').value = data.dept_id;
                    document.getElementById('editRole').value = data.userRole;
                    document.getElementById('editPassword').value = ''; // Clear password field
                    document.getElementById('editConfirmPassword').value = ''; // Clear confirm password field

                    // Show the form for editing
                    // Show the modal
                    document.getElementById('editUserModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error fetching user details:', error);
                    showError('Error fetching user details.');
                });
        }

        // Save Changes to the User Account
        document.getElementById('editUserForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the default form submission behavior

            // Get the values from the password and confirm password fields
            const password = document.getElementById('editPassword').value;
            const confirmPassword = document.getElementById('editConfirmPassword').value;

            // Check if the password and confirm password match
            if (password !== confirmPassword) {
                alert("Passwords do not match. Please try again.");
                return; // Exit the function if they don't match
            }

            // Create a FormData object to capture the form data
            const formData = new FormData(event.target); // Automatically capture form data

            // If the password is provided, append it to the FormData
            if (password) {
                formData.append('password', password);
            }

            // Append the current user ID to the form data (if needed)
            console.log('Appending User ID:', currentUserId);
            formData.append('id', currentUserId);


            // Log FormData to the console for debugging
            for (let [key, value] of formData.entries()) {
                console.log(key, value); // Logs each key-value pair in the form data
            }

            // Use Fetch API to submit the form data to the server
            fetch('handlers/update_user.php', {
                method: 'POST',
                body: formData // Send the FormData object
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Show success modal or message
                    showToast(data); // Display success message
                    closeModal(); // Close the modal
                    setTimeout(() => {
                        location.reload(); // Reload the current page
                    }, 3000); // Wait 3 seconds before reloading
                } else {
                    // Show error message
                    showToast(data);
                }
            })
            .catch(error => {
                console.error('Error updating user:', error);
                showToast('Error updating user.');
            });
        });


 
        function deleteUser(userId) {
            userIdToDelete = userId; // Store the user ID
            // Show the confirmation modal
            document.getElementById('confirmDeleteModal').classList.remove('hidden');
        }

        // Delete User Account
        function confirmDelete() {
            // Make an AJAX request to delete the user from the server
            fetch(`handlers/delete_user.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: userIdToDelete })
            })
            .then(response => response.json())
            .then(data => {
                closeDeleteModal(); // Close the modal after the response

                if (data.status === 'success') {
                    showToast(data);
                    setTimeout(() => {
                        location.reload(); // Reloads the current page
                    }, 3000); // 3 seconds
                } else {
                    showToast(data);
                }
            })
            .catch(error => {
                closeDeleteModal(); // Ensure the modal is closed
                console.error('Error deleting user:', error);
                showToast('Error deleting the user.');
            });
        }

        // Close modal
        function closeModal() {
            document.getElementById('editUserModal').classList.add('hidden');
            document.getElementById("createUserForm").reset();
        }

        function closeDeleteModal() {
            document.getElementById('confirmDeleteModal').classList.add('hidden');
        }

                // Function to show toast notifications
        function showToast(data) {
            const toastContainer = document.getElementById('toast-container');
            const toast = document.createElement('div');

            // Dynamically set Tailwind CSS classes
            toast.className = `p-4 rounded-md shadow-md mb-2 transition-opacity duration-300 ease-in-out fixed bottom-5 right-5 z-50 ${
                data.status === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;

            toast.textContent = data.message;

            // Create close button
            const closeButton = document.createElement('button');
            closeButton.innerHTML = '&times;';
            closeButton.className = 'ml-4 text-lg font-semibold';
            closeButton.onclick = () => toast.remove(); // Remove toast on close button click

            toast.appendChild(closeButton);
            toastContainer.appendChild(toast);

            // Automatically remove the toast after 5 seconds
            setTimeout(() => {
                toast.remove();
            }, 5000); // Change this value for longer display time
        }
    </script>
</body>
</html>

