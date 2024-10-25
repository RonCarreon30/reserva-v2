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
if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Registrar') {
    // Redirect to a page indicating unauthorized access
    header("Location: index.html");
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
                    <div id="error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Error!</strong>
                        <span class="block sm:inline">Something went wrong.</span>
                    </div>
                    <table id="usersTable" class="min-w-full bg-white rounded-md shadow-md border border-gray-200">
                        <thead>
                            <tr class="bg-gray-200 border-b">
                                <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(0)">
                                    <span class="flex items-center">First Name
                                        <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                        </svg>
                                    </span>
                                    
                                </th>
                                <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(1)">
                                    <span class="flex items-center">Last Name
                                        <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                        </svg>
                                    </span>
                                </th>
                                <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(2)">
                                    <span class="flex items-center">Email
                                        <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                        </svg>
                                    </span>
                                </th>
                                <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(3)">
                                    <span class="flex items-center">Department
                                        <svg class="w-4 h-4 ml-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
                                        </svg>
                                    </span>
                                </th>
                                <th class="py-3 px-4 text-left cursor-pointer hover:bg-gray-100" onclick="sortTable(4)">
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
                                        <td class="py-3 px-4"><?php echo $row['first_name']; ?></td>
                                        <td class="py-3 px-4"><?php echo $row['last_name']; ?></td>
                                        <td class="py-3 px-4"><?php echo $row['email']; ?></td>
                                        <td class="py-3 px-4"><?php echo $row['dept_name']; ?></td>
                                        <td class="py-3 px-4"><?php echo $row['userRole']; ?></td>
                                        <td class="py-3 px-4">
                                            <button onclick="editUser(<?php echo $row['id']; ?>)" class="bg-blue-500 text-white rounded-md px-4 py-2 hover:bg-blue-600">Edit</button>
                                            <button onclick="deleteUser(<?php echo $row['id']; ?>)" class="bg-red-500 text-white rounded-md px-4 py-2 hover:bg-red-600">Delete</button>
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
                                    <input id="firstName" name="firstName" type="text" required class="shadow-sm p-1 focus:ring-blue-500 focus:border-blue-500 block w-full border border-gray-300 rounded-md">
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
                    <button type="button" onclick="saveUserChanges()" class="col-span-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600" >Update Account</button>
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
    <!-- JavaScript for edit and delete actions -->
    <script>
        let currentUserId;  // Declare a variable to store the current user ID

        // Edit User
        function editUser(id) {
            // Store the user ID for future use when saving changes
            currentUserId = id;

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
        function saveUserChanges() {
            // Get the values from the password and confirm password fields
            const password = document.getElementById('editPassword').value;
            const confirmPassword = document.getElementById('editConfirmPassword').value;

            // Check if the password and confirm password match
            if (password !== confirmPassword) {
                alert("Passwords do not match. Please try again."); // Show an error message
                return; // Exit the function if they don't match
            }

            // Get the updated data from the form fields
            const updatedUserData = {
                id: currentUserId, // Make sure currentUserId is defined
                first_name: document.getElementById('editFirstName').value,
                last_name: document.getElementById('editLastName').value,
                email: document.getElementById('editEmail').value,
                idNumber: document.getElementById('editIdNumber').value,
                department: document.getElementById('editDepartment').value,
                role: document.getElementById('editRole').value,
                password: document.getElementById('editPassword').value // Include password if needed
            };

            // Make an AJAX request to save the updated user data to the server
            fetch('handlers/update_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(updatedUserData),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success modal or message
                        showSuccessModal('User updated successfully!');
                        setTimeout(() => {
                            closeSuccessModal();
                            location.reload(); // Reloads the current page
                        }, 3000); // 3000 milliseconds = 3 seconds
                // Reload the user list or update the table row
                        closeModal();
                    } else {
                        // Show error message
                        showError(data.error || 'Failed to update user.');
                    }
                })
                .catch(error => {
                    console.error('Error updating user:', error);
                    showError('Error updating user.');
                });
        }
 
        function deleteUser(userId) {
            userIdToDelete = userId; // Store the user ID
            // Show the confirmation modal
            document.getElementById('confirmDeleteModal').classList.remove('hidden');
        }

        // Delete User Account
        function confirmDelete() {
            // Make an AJAX request to delete the user from the server
            fetch(`handlers/delete_user.php?id=${userIdToDelete}`, {
                method: 'DELETE',
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showSuccessModal('User deleted successfully!');
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
                console.error('Error deleting user:', error);
                showError('Error deleting user.');
            });

            // Close the confirmation modal after confirming
            closeDeleteModal();
        }

        function closeDeleteModal() {
            document.getElementById('confirmDeleteModal').classList.add('hidden');
}


        // Close modal
        function closeModal() {
            document.getElementById('editUserModal').classList.add('hidden');
            document.getElementById("createUserForm").reset();
        }

        function showSuccessModal(message, isSuccess = true) {
            const successMessageElement = document.getElementById('successMessage');
            successMessageElement.textContent = message; // Set the dynamic success or error message

            const successModal = document.getElementById('successModal');
            
            if (isSuccess) {
                // Apply success styling
                successModal.classList.remove('bg-red-500'); // Remove error styling if present
                successModal.classList.add('bg-white'); // Success styling
            } else {
                // Apply error styling
                successModal.classList.remove('bg-white'); // Remove success styling
                successModal.classList.add('bg-red-500'); // Error styling
            }

            successModal.classList.remove('hidden'); // Show the modal

            // Auto-close modal after 3 seconds for success, or let users manually close it for errors
            if (isSuccess) {
                setTimeout(() => {
                    closeSuccessModal();
                    location.reload(); // Reload the current page after success
                }, 3000); // 3000 milliseconds = 3 seconds
            }
        }

        function closeSuccessModal() {
            var successModal = document.getElementById('successModal');
            // Hide modal
            successModal.classList.add('hidden');
            document.getElementById("createUserForm").reset();
            location.reload(); // Reload the current page after success
        }
    </script>
</body>
</html>

