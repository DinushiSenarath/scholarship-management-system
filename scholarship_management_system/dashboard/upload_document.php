<?php
session_start();
include('../includes/db.php');
if ($_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$sid = $conn->query("SELECT student_id FROM STUDENT WHERE user_id = '$user_id'")->fetch_assoc()['student_id'];
$apps = $conn->query("SELECT application_id FROM STUDENT_APPLICATION WHERE student_id = '$sid'");

$upload_success = false;
$upload_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aid = $_POST['application_id'];
    $type = $_POST['type'];
    $file = $_FILES['file'];
    
    // Basic validation
    if (empty($aid) || empty($type) || !isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $upload_error = 'Please fill all fields and select a valid file.';
    } else {
        $filename = "../uploads/" . basename($file['name']);
        if (move_uploaded_file($file['tmp_name'], $filename)) {
            $did = uniqid('D');
            $result = $conn->query("INSERT INTO DOCUMENT (application_id, document_id, type, file_path) 
                          VALUES ('$aid', '$did', '$type', '$filename')");
            if ($result) {
                $upload_success = true;
            } else {
                $upload_error = 'Database error occurred. Please try again.';
            }
        } else {
            $upload_error = 'File upload failed. Please try again.';
        }
    }
}

// Reset the result pointer for the form
$apps = $conn->query("SELECT application_id FROM STUDENT_APPLICATION WHERE student_id = '$sid'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Document - Scholarship Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .upload-container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .upload-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            padding: 30px;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .upload-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(180deg); }
        }

        .upload-header h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .upload-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .breadcrumb {
            padding: 20px 30px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .breadcrumb a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .breadcrumb a:hover {
            color: #7c3aed;
        }

        .breadcrumb span {
            color: #6b7280;
            margin: 0 10px;
        }

        .upload-content {
            padding: 40px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border: 1px solid #10b981;
        }

        .alert-error {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .form-container {
            background: white;
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .form-label i {
            margin-right: 8px;
            color: #4f46e5;
        }

        .form-select,
        .form-input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        .form-select:focus,
        .form-input:focus {
            outline: none;
            border-color: #4f46e5;
            background: white;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .file-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            background: #f9fafb;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .file-upload-area:hover {
            border-color: #4f46e5;
            background: #f0f7ff;
        }

        .file-upload-area.dragover {
            border-color: #4f46e5;
            background: #e0f2fe;
            transform: scale(1.02);
        }

        .file-upload-icon {
            font-size: 3rem;
            color: #9ca3af;
            margin-bottom: 15px;
        }

        .file-upload-text {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }

        .file-upload-hint {
            color: #9ca3af;
            font-size: 0.9rem;
        }

        #file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-selected {
            display: none;
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            border: 2px solid #3b82f6;
            color: #1e40af;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }

        .file-selected i {
            margin-right: 10px;
        }

        .submit-button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
        }

        .submit-button:active {
            transform: translateY(0);
        }

        .info-section {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border: 1px solid #0ea5e9;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .info-title {
            font-weight: 600;
            color: #0c4a6e;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .info-list {
            color: #0c4a6e;
            margin-left: 20px;
        }

        .info-list li {
            margin-bottom: 5px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #f3f4f6;
            color: #374151;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 25px;
        }

        .back-button:hover {
            background: #e5e7eb;
            transform: translateX(-2px);
        }

        @media (max-width: 768px) {
            .upload-container {
                margin: 10px;
            }
            
            .upload-content {
                padding: 20px;
            }
            
            .form-container {
                padding: 25px;
            }
            
            .upload-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <!-- Header Section -->
        <div class="upload-header">
            <h1><i class="fas fa-cloud-upload-alt"></i> Upload Document</h1>
            <p>Submit your required documents for scholarship applications</p>
        </div>

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="student.php"><i class="fas fa-home"></i> Dashboard</a>
            <span>/</span>
            <span>Upload Document</span>
        </div>

        <!-- Content Section -->
        <div class="upload-content">
            <!-- Back Button -->
            <a href="student.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>

            <!-- Success/Error Messages -->
            <?php if ($upload_success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Document uploaded successfully! Your file has been saved and linked to your application.
                </div>
            <?php endif; ?>

            <?php if (!empty($upload_error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($upload_error) ?>
                </div>
            <?php endif; ?>

            <!-- Information Section -->
            <div class="info-section">
                <div class="info-title">
                    <i class="fas fa-info-circle"></i> Upload Guidelines
                </div>
                <ul class="info-list">
                    <li>Supported formats: PDF, DOC, DOCX, JPG, PNG (Max 10MB)</li>
                    <li>Ensure documents are clear and readable</li>
                    <li>Upload documents only for your submitted applications</li>
                    <li>Document type should clearly describe the content (e.g., "Transcript", "ID Copy")</li>
                </ul>
            </div>

            <!-- Upload Form -->
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data" id="upload-form">
                    <!-- Application Selection -->
                    <div class="form-group">
                        <label class="form-label" for="application_id">
                            <i class="fas fa-file-alt"></i>
                            Select Application
                        </label>
                        <select name="application_id" id="application_id" class="form-select" required>
                            <option value="">Choose an application...</option>
                            <?php while ($a = $apps->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($a['application_id']) ?>">
                                    Application ID: <?= htmlspecialchars($a['application_id']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Document Type -->
                    <div class="form-group">
                        <label class="form-label" for="document_type">
                            <i class="fas fa-tags"></i>
                            Document Type
                        </label>
                        <input type="text" 
                               name="type" 
                               id="document_type" 
                               class="form-input" 
                               placeholder="e.g., Academic Transcript, ID Copy, Income Certificate" 
                               required>
                    </div>

                    <!-- File Upload -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-paperclip"></i>
                            Select Document
                        </label>
                        <div class="file-upload-area" id="file-upload-area">
                            <div class="file-upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <div class="file-upload-text">
                                Click to select file or drag and drop
                            </div>
                            <div class="file-upload-hint">
                                PDF, DOC, DOCX, JPG, PNG up to 10MB
                            </div>
                            <input type="file" 
                                   name="file" 
                                   id="file-input" 
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" 
                                   required>
                        </div>
                        <div class="file-selected" id="file-selected">
                            <i class="fas fa-file"></i>
                            <span id="file-name"></span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="submit-button">
                        <i class="fas fa-upload"></i>
                        Upload Document
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // File upload handling
        const fileInput = document.getElementById('file-input');
        const fileUploadArea = document.getElementById('file-upload-area');
        const fileSelected = document.getElementById('file-selected');
        const fileName = document.getElementById('file-name');

        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                fileName.textContent = file.name;
                fileSelected.style.display = 'block';
                fileUploadArea.style.border = '2px solid #10b981';
                fileUploadArea.style.background = '#f0fdf4';
            }
        });

        // Drag and drop functionality
        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                const file = files[0];
                fileName.textContent = file.name;
                fileSelected.style.display = 'block';
                fileUploadArea.style.border = '2px solid #10b981';
                fileUploadArea.style.background = '#f0fdf4';
            }
        });

        // Form validation
        document.getElementById('upload-form').addEventListener('submit', function(e) {
            const applicationId = document.getElementById('application_id').value;
            const documentType = document.getElementById('document_type').value;
            const fileInput = document.getElementById('file-input');

            if (!applicationId || !documentType || !fileInput.files.length) {
                e.preventDefault();
                alert('Please fill all required fields and select a file.');
                return false;
            }

            // File size validation (10MB = 10 * 1024 * 1024 bytes)
            const maxSize = 10 * 1024 * 1024;
            if (fileInput.files[0].size > maxSize) {
                e.preventDefault();
                alert('File size must be less than 10MB.');
                return false;
            }
        });
    </script>
</body>
</html>