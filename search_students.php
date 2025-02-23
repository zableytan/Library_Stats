<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'user_activity');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle search query
$search_query = "";
if (isset($_GET['search'])) {
    $search_query = htmlspecialchars($_GET['search']);
}

// Fetch students based on search query
$stmt = $conn->prepare("SELECT * FROM user_logs WHERE name LIKE CONCAT('%', ?, '%') OR course LIKE CONCAT('%', ?, '%')");
$stmt->bind_param("ss", $search_query, $search_query);
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Students</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
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
    <div class="container mt-5">
        <h2 class="text-center">Search Students</h2>
        <div class="text-center mb-4">
            <a href="admin.php" class="btn btn-secondary">Back to Admin Page</a>
        </div>
        <form method="GET" action="search_students.php" class="mb-4">
            <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Search by name or course" value="<?php echo htmlspecialchars($search_query); ?>">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </div>
        </form>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Students List</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Course</th>
                            <th>Login Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo htmlspecialchars($student['course']); ?></td>
                                <td><?php echo htmlspecialchars($student['login_time']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
