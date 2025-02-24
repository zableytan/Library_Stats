<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'user_activity');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the logged-in user's name
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name FROM user_logs WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($logged_in_user);
$stmt->fetch();
$stmt->close();

// Fetch all links from the database
$result = $conn->query("SELECT * FROM links");
$links = $result->fetch_all(MYSQLI_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['logout'])) {
        // Logout the user
        session_destroy();
        header("Location: index.php");
        exit();
    }

    $link_number = $_POST['link_number'];

    // Insert link click record
    $stmt = $conn->prepare("INSERT INTO link_clicks (user_id, link_number) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $link_number);
    $stmt->execute();
    $stmt->close();

    // Respond with the link URL
    $link = $links[$link_number - 1];
    echo json_encode(['url' => $link['url']]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Links Page</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Body styling for footer fix */
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding-bottom: 60px;
            position: relative;
        }

        /* Header with background image and opacity */
        .header {
            background-color: #196199; /* Updated header color */
            background-image: url('dmsf.png');
            background-size: cover;
            background-position: center;
            height: 150px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 40px;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
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

        /* Footer styling */
        .footer {
            background-color: #196199; /* Updated footer color */
            color: white;
            text-align: center;
            padding: 10px 0;
            height: 60px;
            position: absolute;
            bottom: 0;
            width: 100%;
        }

        /* Container styling */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            flex: 1;
        }

        /* Welcome message styling */
        .welcome-message {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 20px;
        }

        /* Image button styling */
        .image-button {
            display: block;
            width: 100%;
            max-width: 280px; /* Set max-width to 250px */
            height: 280px;
            border: solid;
            border-width: 3px;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background-color: white;
            object-fit: contain;
            margin: 0 auto; /* Center the button */
        }

        .image-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        /* Title below image */
        .image-title {
            font-size: 1.1rem;
            color: #333;
            margin-top: 10px;
            text-align: center;
        }

         /* Exit button styling */
         .exit-button {
            display: block;
            width: 100%;
            max-width: 200px;
            margin: 20px auto;
            padding: 10px 20px;
            font-size: 1rem;
            color: white;
            background-color: #dc3545;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .exit-button:hover {
            background-color: #c82333;
        }

        /* Media queries for responsiveness */
        @media (min-width: 992px) {
            .col-lg-4 {
                width: 33.333333%; /* Three columns on large screens */
            }
        }

        @media (max-width: 991px) and (min-width: 768px) {
            .col-md-6 {
                width: 50%; /* Two columns on medium screens */
            }
        }

        @media (max-width: 767px) {
            .col-sm-12 {
                width: 100%; /* One column on small screens */
            }
        }

        @media (max-width: 576px) {
            .header {
                height: 100px;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .welcome-message {
                font-size: 1rem;
            }

            .image-title {
                font-size: 0.9rem;
            }

            .container {
                padding: 10px;
            }

            .row {
                margin: 0 -5px;
            }

            .col-md-4, .col-sm-6 {
                padding: 5px;
            }

            .exit-button {
                max-width: 150px;
                font-size: 0.9rem;
            }
        }

        /* Adjust spacing between columns */
        .row {
            margin-left: -15px;
            margin-right: -15px;
        }

        .col-lg-4, .col-md-6, .col-sm-12 {
            padding-left: 15px;
            padding-right: 15px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <img src="dmsflogo.png" alt="Logo" class="logo">
        <h1>Library</h1>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Display logged-in user's name -->
        <div class="welcome-message text-center">
            Welcome, <strong><?php echo htmlspecialchars($logged_in_user); ?></strong>!
        </div>

        <h2 class="text-center">Choose a Link</h2>
        <p class="text-center">Select one of the links below to proceed.</p>

        <!-- Image Buttons in a Grid -->
        <div class="row">
            <?php foreach ($links as $index => $link): ?>
                <!-- Column size adjusted for different screen sizes -->
                <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                    <input type="image" src="<?php echo htmlspecialchars($link['photo']); ?>" alt="Link <?php echo $index + 1; ?>" class="image-button" onclick="handleClick(event, <?php echo $index + 1; ?>)">
                    <div class="image-title"><?php echo htmlspecialchars($link['title']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Exit Button -->
    <form method="POST" action="links.php">
        <button type="submit" name="logout" class="exit-button">Exit</button>
    </form>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2025 Davao Medical School Foundation, Inc. All rights reserved.</p>
    </div>

    <script>
        function handleClick(event, linkNumber) {
            event.preventDefault();

            fetch('links.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `link_number=${linkNumber}`,
            })
            .then(response => response.json())
            .then(data => {
                window.open(data.url, '_blank');
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
