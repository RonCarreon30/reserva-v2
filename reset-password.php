<?php
require 'database/config.php';

// Check if token exists in URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if the token is valid and not expired
    $query = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
    $query->bind_param("s", $token);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows === 0) {
        echo '<script>
                window.onload = function() {
                    openModal("error-modal", "Invalid or expired token.");
                };
              </script>';
        die();
    }

    $reset = $result->fetch_assoc();
    $user_id = $reset['user_id'];
    $expires_at = $reset['expires_at'];

    if (strtotime($expires_at) < time()) {
        echo '<script>
                window.onload = function() {
                    openModal("error-modal", "Token has expired.");
                };
              </script>';
        die();
    }

    // Handle form submission (reset password)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Check if passwords match
        if ($password !== $confirm_password) {
            echo '<script>
                    window.onload = function() {
                        openModal("error-modal", "Passwords do not match.");
                    };
                  </script>';
            die();
        }

        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Update the user's password in the database
        $update_query = $conn->prepare("UPDATE users SET userPassword = ? WHERE id = ?");
        $update_query->bind_param("si", $hashed_password, $user_id);

        if ($update_query->execute()) {
            // Delete the reset token after successful reset
            $delete_token_query = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $delete_token_query->bind_param("s", $token);
            $delete_token_query->execute();

            // Redirect to the desired page after successful reset
            echo '<script>
                    window.onload = function() {
                        openModal("success-modal", "Password successfully reset.");
                        setTimeout(function() {
                            window.location.href = "http://localhost/reserva-v2/index";
                        }, 3000);
                    };
                  </script>';
            exit;
        } else {
            echo '<script>
                    window.onload = function() {
                        openModal("error-modal", "Failed to reset password.");
                    };
                  </script>';
        }
    }

} else {
    echo '<script>
            window.onload = function() {
                openModal("error-modal", "No token provided.");
            };
          </script>';
    die();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">

    <!-- Header -->
    <header class="bg-plv-blue text-white py-2 m">
        <a href="/index" class="m-16 hover:underline">Home</a>
    </header>

    <!-- Main Content -->
    <main class="flex-grow">
        <div class="max-w-lg w-full mx-auto mt-12">
            <h2 class="text-center text-4xl lg:text-6xl font-extrabold text-gray-800 leading-tight">RESERVA</h2>
            <div class="rounded shadow-lg bg-white p-6 mt-12">
                <h1 class="text-xl font-bold mb-4 text-center">Reset Your Password</h1>
                <form action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST" class="space-y-4">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">

                    <div class="relative">
                        <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                        <input type="password" name="password" id="password" required
                            class="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded shadow-sm focus:outline-none focus:ring focus:ring-blue-500">
                        <button type="button" id="toggle-password" class="absolute top-2/3 right-3 transform -translate-y-1/2 text-gray-500">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>

                    <div class="relative">
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" required
                            class="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded shadow-sm focus:outline-none focus:ring focus:ring-blue-500">
                        <button type="button" id="toggle-confirm-password" class="absolute top-2/3 right-3 transform -translate-y-1/2 text-gray-500">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded shadow hover:bg-blue-700">
                        Reset Password
                    </button>
                </form>
            </div>
        </div>
        <div class="mt-4">
            <?php include 'faqBtn.php'; ?>
        </div>
    </main>

    <!-- Footer -->
    <div id="footer-container">
        <?php include 'footer.php'; ?>
    </div>

    <!-- Success Modal -->
<div id="success-modal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded-lg w-1/3 text-center">
        <h3 class="text-2xl font-semibold text-green-600" id="success-message"></h3>
        <button onclick="closeModal('success-modal')" class="mt-4 bg-green-600 text-white px-4 py-2 rounded">Close</button>
    </div>
</div>

<!-- Error Modal -->
<div id="error-modal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center hidden">
    <div class="bg-white p-6 rounded-lg w-1/3 text-center">
        <h3 class="text-2xl font-semibold text-red-600" id="error-message"></h3>
        <button onclick="closeModal('error-modal')" class="mt-4 bg-red-600 text-white px-4 py-2 rounded">Close</button>
    </div>
</div>

<script>
    // Function to open the modal with the message
    function openModal(modalId, message) {
        const modal = document.getElementById(modalId);
        const messageElement = document.getElementById(modalId === 'success-modal' ? 'success-message' : 'error-message');
        messageElement.textContent = message;
        modal.classList.remove('hidden');
    }

    // Function to close the modal
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.add('hidden');
    }

    // Toggle password visibility
    const togglePassword = document.getElementById('toggle-password');
    const passwordField = document.getElementById('password');
    togglePassword.addEventListener('click', function () {
        const type = passwordField.type === 'password' ? 'text' : 'password';
        passwordField.type = type;
        this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });

    // Toggle confirm password visibility
    const toggleConfirmPassword = document.getElementById('toggle-confirm-password');
    const confirmPasswordField = document.getElementById('confirm_password');
    toggleConfirmPassword.addEventListener('click', function () {
        const type = confirmPasswordField.type === 'password' ? 'text' : 'password';
        confirmPasswordField.type = type;
        this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });

    // Password validation regex
    const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_\-])[A-Za-z\d@$!%*?&_\-]{8,}$/;

console.log(passwordPattern.test('F@rt0123'));
    // Handle form submission and show success/error modals
    const form = document.querySelector('form');
    form.addEventListener('submit', async (e) => {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        // Check if passwords match
        if (password !== confirmPassword) {
            e.preventDefault();
            openModal('error-modal', 'Passwords do not match!');
            return;
        }

        // Check if the password meets the required strength
        if (!passwordPattern.test(password)) {
            e.preventDefault();
            openModal('error-modal', 'Password must be at least 8 characters long, with at least one uppercase letter, one lowercase letter, one number, and one special character.');
            return;
        }

        // If the form passes validation, send it to the server (example using fetch)
        const response = await fetch('reset-password.php?token=' + encodeURIComponent("<?php echo htmlspecialchars($token); ?>"), {
            method: 'POST',
            body: new FormData(form)
        });

        const data = await response.json();
        if (data.success) {
            openModal('success-modal', data.message);
        } else {
            openModal('error-modal', data.message);
        }
    });
</script>



</body>
</html>
