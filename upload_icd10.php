<?php
// Simple file upload interface for ICD-10 files
session_start();

// Check if user is logged in
if (!isset($_SESSION['authUser']) || empty($_SESSION['authUser'])) {
    die("Please login to OpenEMR first: <a href='../interface/login/login.php'>Login</a>");
}

$upload_dir = '/var/www/html/openemr/contrib/icd10/';
$message = '';

if ($_POST['upload']) {
    if (isset($_FILES['icd10_file']) && $_FILES['icd10_file']['error'] == 0) {
        $filename = $_FILES['icd10_file']['name'];
        $temp_file = $_FILES['icd10_file']['tmp_name'];
        $target_file = $upload_dir . $filename;
        
        if (move_uploaded_file($temp_file, $target_file)) {
            chmod($target_file, 0644);
            $message = "File uploaded successfully: " . $filename;
        } else {
            $message = "Error uploading file.";
        }
    } else {
        $message = "No file selected or upload error.";
    }
}

// List existing files
$existing_files = [];
if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && !is_dir($upload_dir . $file)) {
            $existing_files[] = $file;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload ICD-10 Files</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 800px; margin: 0 auto; }
        .upload-form { border: 1px solid #ccc; padding: 20px; margin: 20px 0; }
        .file-list { border: 1px solid #ccc; padding: 20px; margin: 20px 0; }
        .message { padding: 10px; margin: 10px 0; background: #d4edda; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; }
        input[type="file"] { margin: 10px 0; }
        input[type="submit"] { background: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        input[type="submit"]:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Upload ICD-10 Files</h1>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="upload-form">
            <h2>Upload New File</h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="upload" value="1">
                <label for="icd10_file">Select ICD-10 File:</label><br>
                <input type="file" name="icd10_file" id="icd10_file" required><br>
                <input type="submit" value="Upload File">
            </form>
        </div>
        
        <div class="file-list">
            <h2>Existing Files in /var/www/html/openemr/contrib/icd10/</h2>
            <?php if (empty($existing_files)): ?>
                <p>No files found.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($existing_files as $file): ?>
                        <li><?php echo htmlspecialchars($file); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <p><a href="../interface/code_systems/dataloads_ajax.php">Go to ICD-10 Installation Page</a></p>
    </div>
</body>
</html>
