<?php
require 'database/config.php';

$showSuccessMessage = false; // Flag for displaying success message

function displayError($message) {
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error</title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    </head>
    <body class="bg-gray-100 flex items-center justify-center min-h-screen">
        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4 max-w-md w-full text-center">
            <h2 class="text-2xl font-bold text-red-600 mb-4">Error</h2>
            <p class="text-gray-700 mb-6">' . htmlspecialchars($message) . '</p>
            <a href="../reserva-v2/index" class="text-blue-600 hover:underline">Back to Login</a>
        </div>
    </body>
    </html>';
    exit;
}

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $query = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
    $query->bind_param("s", $token);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows === 0) {
        displayError('Invalid or expired token.');
    }

    $reset = $result->fetch_assoc();
    $user_id = $reset['user_id'];
    $expires_at = $reset['expires_at'];

    if (strtotime($expires_at) < time()) {
        displayError('Token has expired.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            displayError('Passwords do not match.');
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $update_query = $conn->prepare("UPDATE users SET userPassword = ? WHERE id = ?");
        $update_query->bind_param("si", $hashed_password, $user_id);

        if ($update_query->execute()) {
            $delete_token_query = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $delete_token_query->bind_param("s", $token);
            $delete_token_query->execute();

            $showSuccessMessage = true;
        } else {
            displayError('Failed to reset password.');
        }
    }
} else {
    displayError('No token provided.');
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
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">

<?php if ($showSuccessMessage): ?>
    <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4 max-w-md w-full text-center mx-auto mt-12">
        <h2 class="text-2xl font-bold text-green-600 mb-4">Success</h2>
        <p class="text-gray-700 mb-6">Password successfully reset.</p>
        <a href="../reserva-v2/" class="text-blue-600 hover:underline">Back to Login</a>
    </div>
<?php else: ?>
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
    <?php endif; ?>

<script>
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
    const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

    // Optional: Add client-side validation for confirming passwords
    const form = document.querySelector('form');
    form.addEventListener('submit', (e) => {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        // Check if passwords match
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
            return;
        }

        /*/ Check if the password meets the required strength
        if (!passwordPattern.test(password)) {
            e.preventDefault();
            alert('Password too short or lacks complexity!');
            return;
        }*/
    });
</script>


</body>
</html>