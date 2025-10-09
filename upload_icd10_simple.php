<?php
$upload_dir = '/var/www/html/openemr/contrib/icd10/';
$message = '';

if (isset($_POST['upload']) && $_POST['upload']) {
    if (isset($_FILES['icd10_file']) && $_FILES['icd10_file']['error'] == 0) {
        $filename = $_FILES['icd10_file']['name'];
        $temp_file = $_FILES['icd10_file']['tmp_name'];
        $target_file = $upload_dir . $filename;
        
        if (move_uploaded_file($temp_file, $target_file)) {
            chmod($target_file, 0644);
            
            // Check if it's a ZIP file and extract it
            $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if ($file_extension === 'zip') {
                $zip = new ZipArchive();
                if ($zip->open($target_file) === TRUE) {
                    $zip->extractTo($upload_dir);
                    $zip->close();
                    $extracted_count = count(glob($upload_dir . '*'));
                    $message = "ZIP file uploaded and extracted successfully: " . $filename . " (extracted " . $extracted_count . " files)";
                } else {
                    $message = "ZIP file uploaded but could not be extracted: " . $filename;
                }
            } else {
                $message = "File uploaded successfully: " . $filename;
            }
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
        body { font-family: Arial, sans-serif; margin: 40px; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .upload-form { border: 2px solid #007bff; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .file-list { border: 2px solid #28a745; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .message { padding: 15px; margin: 15px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        input[type="file"] { margin: 15px 0; padding: 10px; border: 1px solid #ddd; border-radius: 4px; width: 100%; }
        input[type="submit"] { background: #007bff; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        input[type="submit"]:hover { background: #0056b3; }
        .btn { display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
        .btn:hover { background: #218838; }
        h1 { color: #007bff; text-align: center; }
        h2 { color: #495057; }
        ul { list-style-type: none; padding: 0; }
        li { padding: 8px; margin: 5px 0; background: #f8f9fa; border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏥 Upload ICD-10 Files for POLAR Healthcare EMR</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="upload-form">
            <h2>📁 Upload New ICD-10 File</h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="upload" value="1">
                <label for="icd10_file"><strong>Select ICD-10 File:</strong></label><br>
                <input type="file" name="icd10_file" id="icd10_file" required><br>
                <input type="submit" value="📤 Upload File">
            </form>
            <p><em>Upload files like: icd10cm_tabular_2026.txt, icd10cm_index_2026.txt, or ZIP files containing multiple ICD-10 files.</em></p>
        </div>
        
        <div class="file-list">
            <h2>📋 Existing Files in ICD-10 Directory</h2>
            <?php if (empty($existing_files)): ?>
                <p>No files found in the directory.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($existing_files as $file): ?>
                        <li><?php echo htmlspecialchars($file); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="../interface/code_systems/dataloads_ajax.php" class="btn">🔧 Go to ICD-10 Installation Page</a>
            <a href="../interface/main/tabs/main.php" class="btn">🏠 Back to EMR Dashboard</a>
        </div>
    </div>
</body>
</html>
