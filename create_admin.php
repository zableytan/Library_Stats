<?php
session_start();

// Ensure only logged-in admins can access this page
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

$error_message = "";
$success_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['email']) || empty($_POST['confirm_password'])) {
        $error_message = "All fields are required.";
    } elseif ($_POST['password'] !== $_POST['confirm_password']) {
        $error_message = "Passwords do not match.";
    } else {
        // Database connection
        $host = 'localhost';
        $dbname = 'user_activity';
        $username = 'root';
        $password = '';

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Get form data
            $admin_username = htmlspecialchars($_POST['username']);
            $admin_password = password_hash(htmlspecialchars($_POST['password']), PASSWORD_DEFAULT); // Encrypt password
            $admin_email = htmlspecialchars($_POST['email']);

            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username OR email = :email");
            $stmt->execute([
                ':username' => $admin_username,
                ':email' => $admin_email
            ]);
            $existing_admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_admin) {
                $error_message = "Username or email already exists.";
            } else {
                // Insert admin account
                $stmt = $pdo->prepare("INSERT INTO admins (username, password, email) VALUES (:username, :password, :email)");
                $stmt->execute([
                    ':username' => $admin_username,
                    ':password' => $admin_password,
                    ':email' => $admin_email
                ]);

                $success_message = "Admin account created successfully! You can now login.";
            }
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
            margin: 0;
        }

        .header {
            background-color: rgba(25, 97, 153, 0.8); /* Updated header color with opacity */
            height: 150px;
            width: 100%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 50px;
        }

        .header h1 {
            color: white;
            font-size: 3rem;
            position: relative;
            z-index: 1;
        }

        .header img.logo {
            position: absolute;
            top: 25px;
            left: 20px;
            height: 100px;
            width: 100px;
            z-index: 1;
        }

        .create-admin-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 400px;
        }

        .create-admin-container h1 {
            margin-bottom: 20px;
            font-size: 1.8rem;
            text-align: center;
        }

        .error-message {
            color: red;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .success-message {
            color: green;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .footer {
            background-color: #196199; /* Updated footer color */
            color: white;
            text-align: center;
            padding: 10px 0;
            margin-top: auto; /* Pushes footer to the bottom */
            width: 100%;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <img src="dmsflogo.png" alt="Logo" class="logo"> <!-- Placeholder for logo -->
        <h1>Library</h1>
    </div>

    <div class="create-admin-container">
        <h1>Create Admin Account</h1>
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <form action="create_admin.php" method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>
        <div class="text-center mt-3">
            <p>Already have an account? <a href="index.php">Login here</a>.</p>
        </div>
    </div>
</body>
</html>
