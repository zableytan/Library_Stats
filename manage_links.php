<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');

// Create uploads directory if it doesn't exist
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
    // Grant Users full control over the uploads directory
    shell_exec('icacls "C:\xampp\htdocs\library\uploads" /grant Users:F');
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
    $photo = !empty($_POST['photo']) ? htmlspecialchars($_POST['photo']) : null;
    $link_id = isset($_POST['link_id']) ? (int)$_POST['link_id'] : null;

    // Handle cropped image if present
    if (!empty($_POST['cropped_data'])) {
        $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $_POST['cropped_data']));
        $image_name = 'link_image_' . time() . '.png';
        
        // Create uploads directory if it doesn't exist
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        file_put_contents('uploads/' . $image_name, $image_data);
        $photo = 'uploads/' . $image_name;
    }

 // Handle file upload if present
    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/';
        $fileExtension = strtolower(pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedTypes)) {
            $fileName = uniqid() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $uploadPath)) {
                $photo = 'uploads/' . $fileName;
            } else {
                $upload_error = "Failed to move uploaded file.";
            }
        } else {
            $upload_error = "Invalid file type. Allowed types: " . implode(', ', $allowedTypes);
        }
    }

    if (!empty($_POST['cropped_image'])) {
        $cropped_image = $_POST['cropped_image'];
        $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $cropped_image));
        $image_name = 'link_image_' . time() . '.png';
        
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        file_put_contents('uploads/' . $image_name, $image_data);
        $photo = 'uploads/' . $image_name;
    }

    // Update database
    if ($link_id) {
        $stmt = $conn->prepare("UPDATE links SET title = ?, url = ?, photo = ? WHERE id = ?");
        $stmt->bind_param("sssi", $title, $url, $photo, $link_id);
    } else {
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <style>
        .footer {
            background-color: #196199; /* Updated footer color */
            color: white;
            text-align: center;
            padding: 10px 0;
            margin-top: auto; /* Pushes footer to the bottom */
            width: 100%;
        }
        .img-container {
            max-width: 100%;
            max-height: 400px;
        }
        #image-to-crop {
            max-width: 100%;
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
                <form method="POST" action="manage_links.php" enctype="multipart/form-data">
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
                        <input type="text" class="form-control" id="photo" name="photo">
                    </div>
                    <div class="form-group">
                        <label for="imageInput">Upload Image</label>
                        <input type="file" class="form-control-file" id="imageInput" name="image" accept="image/*">
                        <input type="hidden" name="cropped_data" id="croppedData">
                        <div id="previewContainer" class="mt-2"></div>
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
                            <th>Photo URL</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($links as $link): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($link['title']); ?></td>
                                <td><?php echo htmlspecialchars($link['url']); ?></td>
                                <td>
                                    <?php if (!empty($link['photo'])): ?>
                                        <img src="<?php echo htmlspecialchars($link['photo']); ?>" alt="Link photo" style="max-width: 100px;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick="editLink(<?php echo $link['id']; ?>, '<?php echo addslashes($link['title']); ?>', '<?php echo addslashes($link['url']); ?>', '<?php echo addslashes($link['photo'] ?? ''); ?>')" class="btn btn-sm btn-primary">Edit</button>
                                    <a href="?delete=<?php echo $link['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="modal fade" id="cropModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crop Image</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="img-container">
                        <img id="image-to-crop" src="">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="crop">Crop & Save</button>
                </div>
            </div>
        </div>
    </div>

 <!-- Image  -->
    <div class="modal fade" id="cropperModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crop Image</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="img-container">
                        <img id="imageToEdit" src="" style="max-width: 100%;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="cropButton">Crop & Save</button>
                </div>
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
    <script>
        let cropper = null;

        document.getElementById('imageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#imageToEdit').attr('src', e.target.result);
                    $('#cropperModal').modal('show');
                    
                    if (cropper) {
                        cropper.destroy();
                    }
                    
                    cropper = new Cropper(document.getElementById('imageToEdit'), {
                        aspectRatio: 1, // Enforce a square aspect ratio
                        viewMode: 1,
                        dragMode: 'move',
                        responsive: true,
                        guides: true,
                        center: true,
                        highlight: true,
                        background: false,
                        autoCropArea: 1,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                        minContainerWidth: 200,
                        minContainerHeight: 200,
                        minCropBoxWidth: 100,
                        minCropBoxHeight: 100
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('cropButton').addEventListener('click', function() {
    if (cropper) {
        const canvas = cropper.getCroppedCanvas({
            width: 280,    // Fixed width
            height: 280,   // Fixed height
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });
        
        const croppedImage = canvas.toDataURL('image/png', 1.0); // Maximum quality
        document.getElementById('croppedData').value = croppedImage;
        
        // Update preview
        const previewContainer = document.getElementById('previewContainer');
        previewContainer.innerHTML = `
            <div class="mt-2">
                <img src="${croppedImage}" class="preview-image" 
                     style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;">
            </div>`;
        
        $('#cropperModal').modal('hide');
    }
});
    </script>
</body>
</html>
