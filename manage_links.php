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

// Handle form submission for adding/editing links
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars($_POST['title']);
    $url = htmlspecialchars($_POST['url']);
    $photo = htmlspecialchars($_POST['photo']);
    $link_id = isset($_POST['link_id']) ? (int)$_POST['link_id'] : null;

    if ($link_id) {
        // Update existing link
        $stmt = $conn->prepare("UPDATE links SET title = ?, url = ?, photo = ? WHERE id = ?");
        $stmt->bind_param("sssi", $title, $url, $photo, $link_id);
    } else {
        // Add new link
        $stmt = $conn->prepare("INSERT INTO links (title, url, photo) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $url, $photo);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: manage_links.php");
    exit();
}

// Handle link deletion
if (isset($_GET['delete'])) {
    $link_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM links WHERE id = ?");
    $stmt->bind_param("i", $link_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_links.php");
    exit();
}

// Fetch all links
$result = $conn->query("SELECT * FROM links");
$links = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Links</title>
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
        <h2 class="text-center">Manage Links</h2>
        <div class="text-center mb-4">
            <a href="admin.php" class="btn btn-secondary">Back to Admin Page</a>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Add/Edit Link</h5>
                <form method="POST" action="manage_links.php">
                    <input type="hidden" name="link_id" id="link_id">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="url">Link URL</label>
                        <input type="url" class="form-control" id="url" name="url" required>
                    </div>
                    <div class="form-group">
                        <label for="photo">Photo URL</label>
                        <input type="text" class="form-control" id="photo" name="photo" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Existing Links</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>URL</th>
                            <th>Photo</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($links as $link): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($link['title']); ?></td>
                                <td><?php echo htmlspecialchars($link['url']); ?></td>
                                <td><img src="<?php echo htmlspecialchars($link['photo']); ?>" alt="Photo" width="50"></td>
                                <td>
                                    <a href="#" class="btn btn-warning btn-sm" onclick="editLink(<?php echo $link['id']; ?>, '<?php echo htmlspecialchars($link['title']); ?>', '<?php echo htmlspecialchars($link['url']); ?>', '<?php echo htmlspecialchars($link['photo']); ?>')">Edit</a>
                                    <a href="manage_links.php?delete=<?php echo $link['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this link?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        function editLink(id, title, url, photo) {
            document.getElementById('link_id').value = id;
            document.getElementById('title').value = title;
            document.getElementById('url').value = url;
            document.getElementById('photo').value = photo;
        }
    </script>
</body>
</html>
