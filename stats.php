<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'user_activity');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to count total people who clicked Link 1
$query_link1_count = "SELECT COUNT(DISTINCT user_id) AS total_people_clicked_link1 FROM link_clicks WHERE link_number = 1";
$result_link1_count = $conn->query($query_link1_count);
$link1_count = $result_link1_count->fetch_assoc();

// Query to find which course clicked Link 1 the most
$query_course_clicks = "
    SELECT 
        UPPER(TRIM(u.course)) AS course, 
        COUNT(DISTINCT u.id) AS total_clicks
    FROM 
        user_logs u
    JOIN 
        link_clicks l ON u.id = l.user_id
    WHERE 
        l.link_number = 1
    GROUP BY 
        UPPER(TRIM(u.course))
    ORDER BY 
        total_clicks DESC";
$result_course_clicks = $conn->query($query_course_clicks);

// Debugging output to check the data being processed
echo '<pre>';
var_dump($link1_count);
while ($row = $result_course_clicks->fetch_assoc()) {
    var_dump($row);
}
echo '</pre>';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics</title>
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
            background-image: url('dmsf.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        /* Header with background color and opacity */
        .header {
            background-color: rgba(25, 97, 153, 0.8); /* Updated header color with opacity */
            height: 150px;
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
            top: 10px;
            left: 10px;
            height: 50px;
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

        /* Card styling */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        /* Table styling */
        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }

        .table th {
            background-color: #343a40;
            color: white;
        }

        @media (max-width: 576px) {
            .header {
                height: 100px; /* Smaller header height for mobile */
            }

            .header h1 {
                font-size: 1.5rem; /* Smaller font size for mobile */
            }

            .container {
                padding: 10px; /* Reduced padding for mobile */
            }

            .card-title {
                font-size: 1.2rem; /* Smaller font size for mobile */
            }

            .table th, .table td {
                font-size: 0.9rem; /* Smaller font size for mobile */
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <img src="logo.png" alt="Logo" class="logo"> <!-- Placeholder for logo -->
        <h1>Statistics</h1>
    </div>

    <!-- Main Content -->
    <div class="container mt-5">
        <h2 class="text-center">Statistics</h2>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Total People Who Clicked Link 1</h5>
                <p class="card-text"><?php echo $link1_count['total_people_clicked_link1']; ?></p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Course with Most Clicks on Link 1</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Total Clicks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Reset the result pointer and fetch the data again for display
                        $result_course_clicks->data_seek(0);
                        while ($row = $result_course_clicks->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['course']); ?></td>
                                <td><?php echo $row['total_clicks']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2025 Davao Medical School Foundation, Inc. All rights reserved.</p>
    </div>
</body>
</html>