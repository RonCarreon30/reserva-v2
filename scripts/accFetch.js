    // Function to fetch users and populate the user list table
    function fetchUsers() {
        // Fetch data using AJAX
        fetch('fetch_users.php')
            .then(response => response.json())
            .then(data => {
                // Get the userList tbody element
                const userList = document.getElementById('userList');

                // Clear existing table rows
                userList.innerHTML = '';

                // Loop through the user data and create table rows
                data.forEach(user => {
                    // Create a new table row
                    const row = document.createElement('tr');

                    // Populate the table row with user data
                    row.innerHTML = `
                        <td class="px-3 py-3">${user.first_name} ${user.last_name}</td>
                        <td class="px-6 py-3">${user.email}</td>
                        <td class="px-6 py-3">${user.role}</td>
                        <td class="px-6 py-3">
                            <button class="text-indigo-600 hover:text-indigo-900">Edit</button>
                            <button class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    `;

                    // Append the row to the userList tbody
                    userList.appendChild(row);
                });
            })
            .catch(error => console.error('Error fetching users:', error));
    }

    // Call the fetchUsers function when the page loads
    document.addEventListener('DOMContentLoaded', fetchUsers);