<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['admin_login'])) {
        // Admin login logic
        $username = htmlspecialchars($_POST['username']);
        $password = htmlspecialchars($_POST['password']);

        // Database connection
        $host = 'localhost';
        $dbname = 'user_activity'; // Replace with your database name
        $username_db = 'root'; // Replace with your database username
        $password_db = ''; // Replace with your database password

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username_db, $password_db);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Fetch admin from database
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                header("Location: admin.php");
                exit();
            } else {
                $admin_error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $admin_error = "Error: " . $e->getMessage();
        }
    } else {
        // Regular user login logic
        $name = htmlspecialchars($_POST['name']);
        $course = htmlspecialchars($_POST['course']);

        // Database connection
        $host = 'localhost';
        $dbname = 'user_activity'; // Replace with your database name
        $username_db = 'root'; // Replace with your database username
        $password_db = ''; // Replace with your database password

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username_db, $password_db);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Insert user login record
            $stmt = $pdo->prepare("INSERT INTO user_logs (name, course) VALUES (:name, :course)");
            $stmt->execute([
                ':name' => $name,
                ':course' => $course
            ]);

            // Insert login count record
            $stmt = $pdo->prepare("INSERT INTO login_counts (course) VALUES (:course)");
            $stmt->execute([
                ':course' => $course
            ]);

            // Redirect to links page after login
            $_SESSION['user_id'] = $pdo->lastInsertId(); // Store the user ID in the session
            header("Location: links.php");
            exit();
        } catch (PDOException $e) {
            $user_error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Body styling for footer fix */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            background-image: url('library.png'); /* Replace with your photo */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6); /* Adjust opacity here */
            z-index: -1;
        }

        /* Header with background color and opacity */
        .header {
            background-color: rgba(25, 97, 153, 0.8); /* Updated header color with opacity */
            height: 200px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 40px;
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
            height: 150px;
            width: 150px;
            z-index: 1;
        }

        /* Footer styling */
        .footer {
            background-color: #196199; /* Updated footer color */
            color: white;
            text-align: center;
            padding: 10px 0;
            margin-top: auto; /* Pushes footer to the bottom */
            width: 100%;
        }

        /* Container styling */
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            flex: 1; /* Allows the container to grow and take up available space */
        }

        /* Login container styling */
        .login-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 400px;
            margin: 0 auto;
        }

        .login-container h2 {
            margin-bottom: 20px;
            font-size: 1.8rem;
            text-align: center;
        }

        .admin-login {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            display: none; /* Initially hidden */
        }

        .admin-login h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .error-message {
            color: red;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .create-admin-link {
            margin-top: 10px;
            text-align: center;
        }

        @media (max-width: 576px) {
            .header h1 {
                font-size: 2rem;
            }

            .login-container h2 {
                font-size: 1.5rem;
            }
        }
    </style>
    <script>
        function toggleAdminLogin() {
            var userLogin = document.querySelector('.user-login');
            var adminLogin = document.querySelector('.admin-login');
            if (adminLogin.style.display === 'none') {
                userLogin.style.display = 'none';
                adminLogin.style.display = 'block';
            } else {
                userLogin.style.display = 'block';
                adminLogin.style.display = 'none';
            }
        }

        function capitalizeFirstLetter(input) {
            input.value = input.value.charAt(0).toUpperCase() + input.value.slice(1);
        }
    </script>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <img src="dmsflogo.png" alt="Logo" class="logo"> <!-- Placeholder for logo -->
        <h1>Library</h1>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="login-container">
            <!-- User Login Section -->
            <div class="user-login">
                <h2>Login</h2>
                <form method="POST" action="index.php">
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" class="form-control" id="name" name="name" required oninput="capitalizeFirstLetter(this)">
                    </div>
                    <div class="form-group">
                        <label for="course">Course:</label>
                        <select class="form-control" id="course" name="course" required>
                            <option value="" disabled selected>--SELECT--</option>
                            <option value="NMD">NMD</option>
                            <option value="IMD">IMD</option>
                            <option value="DENTISTRY">DENTISTRY</option>
                            <option value="NURSING">NURSING</option>
                            <option value="MIDWIFERY">MIDWIFERY</option>
                            <option value="BIOLOGY">BIOLOGY</option>
                            <option value="" disabled>--POST-GRADUATE STUDIES--</option>
                            <option value="MCH">MCH</option>
                            <option value="MPD">MPD</option>
                            <option value="MHPED">MHPED</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Login</button>
                </form>

                <!-- Admin Login Link -->
                <div class="text-center mt-3">
                    <a href="#" onclick="toggleAdminLogin()">Admin Login</a>
                </div>
            </div>

            <!-- Admin Login Section -->
            <div class="admin-login">
                <h3>Admin Login</h3>
                <form method="POST" action="index.php">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" name="admin_login" class="btn btn-danger btn-block">Admin Login</button>
                </form>
                <?php if (isset($admin_error)): ?>
                    <div class="error-message"><?php echo $admin_error; ?></div>
                <?php endif; ?>

                <!-- Back to User Login Link -->
                <div class="text-center mt-3">
                    <a href="#" onclick="toggleAdminLogin()">Back to User Login</a>
                </div>
            </div>

        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2025 Davao Medical School Foundation, Inc. All rights reserved.</p>
    </div>
</body>
</html>