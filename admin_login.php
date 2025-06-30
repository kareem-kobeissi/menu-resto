<?php
session_start();
include 'db_connection.php'; // Include the database connection

// Initialize variables for errors or success messages
$error_message = '';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form inputs
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Secure input
    $username = mysqli_real_escape_string($conn, $username);
    $password = mysqli_real_escape_string($conn, $password);

    // Query to fetch the admin's data from the database
    $sql = "SELECT * FROM admins WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the username exists
    if ($result->num_rows === 1) {
        $row = mysqli_fetch_assoc($result);
        
        // Check if the password matches (assuming plain text for demo)
        if ($password == $row['password']) {
            // Start the session and store the username in session
            $_SESSION['admin'] = $username;
            header("Location: admin_dashboard.php"); // Change this to your desired page after successful login
            exit();
        } else {
            // Password incorrect
            $error_message = 'Invalid password!';
        }
    } else {
        // Username not found
        $error_message = 'Admin not found!';
    }

    // Close connections
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
   <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f5f5f5;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        text-align: center;
    }

    .container {
        background-color: #ffffff;
        padding: 30px;
        border: 1px solid #ccc;
        border-radius: 8px;
        width: 90%;
        max-width: 400px;
    }

    h1 {
        font-size: 1.8rem;
        color: #333;
        margin-bottom: 10px;
        font-weight: 600;
    }

    p {
        color: #666;
        margin-bottom: 20px;
        font-size: 0.95rem;
    }

    .form-group {
        margin-bottom: 20px;
        position: relative;
    }

    .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 1rem;
        outline: none;
        background-color: #fff;
        color: #333;
    }

    .form-group input::placeholder {
        color: #999;
    }

    .form-group input:focus {
        border-color: #666;
    }

    .toggle-visibility {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #777;
        font-size: 0.9rem;
        user-select: none;
    }

    .toggle-visibility:hover {
        color: #333;
    }

    button {
        padding: 10px;
        font-size: 1rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        width: 100%;
        color: white;
        background-color: #333;
    }

    button:hover {
        background-color: #555;
    }

    .back-link {
        margin-top: 15px;
        display: block;
        color: #333;
        text-decoration: none;
        font-size: 0.85rem;
    }

    .back-link:hover {
        text-decoration: underline;
    }

    .error-message {
        color: red;
        font-weight: bold;
        margin-top: 15px;
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .container {
            padding: 25px;
        }
    }
</style>

</head>
<body>
    <div class="container">
        <h1>Admin Login</h1>
        <p>Enter your credentials to access the admin panel</p>
        
        <?php if ($error_message): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="" method="post">
            <div class="form-group">
                <input type="text" name="username" placeholder="Username or Email" autocomplete="off" required>
            </div>
            <div class="form-group" style="position: relative;">
                <input type="password" id="password" name="password" placeholder="Password" autocomplete="new-password" required>
                <span class="toggle-visibility" onclick="toggleVisibility('password', this)">Show</span>
            </div>
            <button type="submit">Login</button>
        </form>
        <a href="index.php" class="back-link">← Back to Home</a>
        <a href="forgot_password.php" class="back-link">Forgot Password?</a>
    </div>

    <script>
        // Function to toggle password visibility
        function toggleVisibility(inputId, toggleElement) {
            var inputField = document.getElementById(inputId);
            if (inputField.type === "password") {
                inputField.type = "text";
                toggleElement.innerText = "Hide";
            } else {
                inputField.type = "password";
                toggleElement.innerText = "Show";
            }
        }
    </script>
</body>
</html>