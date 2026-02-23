<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectBasedOnRole();
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = trim($_POST['role']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($role) || empty($username) || empty($password) || empty($confirm_password)) {
        $message = "All fields are required!";
        $message_type = "danger";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match!";
        $message_type = "danger";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long!";
        $message_type = "danger";
    } else {
        // Check if username already exists
        $check = $conn->prepare("SELECT id FROM admins WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $message = "Username already exists!";
            $message_type = "danger";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            if ($role === 'admin') {
                // Create admin account
                $stmt = $conn->prepare("INSERT INTO admins (username, password, role) VALUES (?, ?, 'admin')");
                $stmt->bind_param("ss", $username, $hashed_password);
                
                if ($stmt->execute()) {
                    $message = "Admin account created successfully! You can now login.";
                    $message_type = "success";
                } else {
                    $message = "Error creating admin account!";
                    $message_type = "danger";
                }
            } elseif ($role === 'teacher') {
                // Create teacher account
                $first_name = trim($_POST['teacher_first_name']);
                $last_name = trim($_POST['teacher_last_name']);
                $email = trim($_POST['teacher_email']);
                $phone = trim($_POST['teacher_phone']);
                $subject_id = !empty($_POST['subject_id']) ? $_POST['subject_id'] : null;
                $assigned_classes = trim($_POST['assigned_classes']);
                
                if (empty($first_name) || empty($last_name) || empty($email)) {
                    $message = "All teacher fields are required!";
                    $message_type = "danger";
                } else {
                    $stmt = $conn->prepare("INSERT INTO admins (username, password, role, subject_id, assigned_classes) VALUES (?, ?, 'teacher', ?, ?)");
                    $stmt->bind_param("ssis", $username, $hashed_password, $subject_id, $assigned_classes);
                    
                    if ($stmt->execute()) {
                        $message = "Teacher account created successfully! You can now login.";
                        $message_type = "success";
                    } else {
                        $message = "Error creating teacher account!";
                        $message_type = "danger";
                    }
                }
            } elseif ($role === 'parent') {
                // Create parent account
                $first_name = trim($_POST['parent_first_name']);
                $last_name = trim($_POST['parent_last_name']);
                $email = trim($_POST['parent_email']);
                $phone = trim($_POST['parent_phone']);
                $student_reference = trim($_POST['student_reference']);
                
                if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($student_reference)) {
                    $message = "All parent fields are required!";
                    $message_type = "danger";
                } else {
                    // Verify student exists
                    $check_student = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
                    $check_student->bind_param("s", $student_reference);
                    $check_student->execute();
                    $student_result = $check_student->get_result();
                    
                    if ($student_result->num_rows == 0) {
                        $message = "Student with ID '$student_reference' not found!";
                        $message_type = "danger";
                    } else {
                        $student_data = $student_result->fetch_assoc();
                        $student_db_id = $student_data['id'];
                        
                        $stmt = $conn->prepare("INSERT INTO admins (username, password, role, student_id) VALUES (?, ?, 'parent', ?)");
                        $stmt->bind_param("ssi", $username, $hashed_password, $student_db_id);
                        
                        if ($stmt->execute()) {
                            $message = "Parent account created successfully! You can now login.";
                            $message_type = "success";
                        } else {
                            $message = "Error creating parent account!";
                            $message_type = "danger";
                        }
                    }
                }
            } else {
                // For student role
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $student_id = trim($_POST['student_id']);
                
                if (empty($first_name) || empty($last_name) || empty($email) || empty($student_id)) {
                    $message = "All student fields are required!";
                    $message_type = "danger";
                } else {
                    // Check if student ID or email already exists
                    $check_student = $conn->prepare("SELECT id FROM students WHERE student_id = ? OR email = ?");
                    $check_student->bind_param("ss", $student_id, $email);
                    $check_student->execute();
                    
                    if ($check_student->get_result()->num_rows > 0) {
                        $message = "Student ID or Email already exists!";
                        $message_type = "danger";
                    } else {
                        // Start transaction
                        $conn->begin_transaction();
                        
                        try {
                            // Create student record
                            $stmt_student = $conn->prepare("INSERT INTO students (student_id, first_name, last_name, email, phone, status) VALUES (?, ?, ?, ?, ?, 'active')");
                            $stmt_student->bind_param("sssss", $student_id, $first_name, $last_name, $email, $phone);
                            $stmt_student->execute();
                            $student_db_id = $conn->insert_id;
                            
                            // Create student login account
                            $stmt_admin = $conn->prepare("INSERT INTO admins (username, password, role, student_id) VALUES (?, ?, 'student', ?)");
                            $stmt_admin->bind_param("ssi", $username, $hashed_password, $student_db_id);
                            $stmt_admin->execute();
                            
                            // Commit transaction
                            $conn->commit();
                            
                            $message = "Student account created successfully! You can now login.";
                            $message_type = "success";
                        } catch (Exception $e) {
                            // Rollback transaction
                            $conn->rollback();
                            $message = "Error creating student account!";
                            $message_type = "danger";
                        }
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Student Management System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .register-body {
            padding: 30px;
        }
        .role-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .role-option {
            flex: 1;
            min-width: 120px;
            text-align: center;
            padding: 15px;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .role-option:hover {
            border-color: #667eea;
            background-color: #f8f9ff;
        }
        .role-option.active {
            border-color: #667eea;
            background-color: #667eea;
            color: white;
        }
        .role-option i {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }
        .student-fields, .teacher-fields, .parent-fields {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="register-card">
                    <div class="register-header">
                        <i class="fas fa-user-plus fa-3x mb-3"></i>
                        <h2>Create Account</h2>
                        <p class="mb-0">Join our Student Management System</p>
                    </div>
                    
                    <div class="register-body">
                        <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="registerForm">
                            <!-- Role Selection -->
                            <div class="role-selector">
                                <div class="role-option active" onclick="selectRole('admin')">
                                    <i class="fas fa-user-shield"></i>
                                    <div>Admin</div>
                                </div>
                                <div class="role-option" onclick="selectRole('teacher')">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                    <div>Teacher</div>
                                </div>
                                <div class="role-option" onclick="selectRole('parent')">
                                    <i class="fas fa-user-friends"></i>
                                    <div>Parent</div>
                                </div>
                                <div class="role-option" onclick="selectRole('student')">
                                    <i class="fas fa-user-graduate"></i>
                                    <div>Student</div>
                                </div>
                            </div>
                            
                            <input type="hidden" name="role" id="selected_role" value="admin">
                            
                            <!-- Common Fields -->
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Username *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <input type="text" name="username" class="form-control" 
                                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                               required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" name="password" id="password" class="form-control" required minlength="6">
                                    </div>
                                    <small class="text-muted">At least 6 characters</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirm Password *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                    </div>
                                    <div id="password_match" class="form-text"></div>
                                </div>
                            </div>
                            
                            <!-- Student-Specific Fields -->
                            <div class="student-fields" id="studentFields">
                                <hr class="my-4">
                                <h5 class="mb-3"><i class="fas fa-graduation-cap me-2"></i>Student Information</h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">First Name *</label>
                                        <input type="text" name="first_name" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Last Name *</label>
                                        <input type="text" name="last_name" class="form-control">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" name="email" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Student ID *</label>
                                        <input type="text" name="student_id" class="form-control" placeholder="e.g., STU001">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Teacher-Specific Fields -->
                            <div class="teacher-fields" id="teacherFields">
                                <hr class="my-4">
                                <h5 class="mb-3"><i class="fas fa-chalkboard-teacher me-2"></i>Teacher Information</h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">First Name *</label>
                                        <input type="text" name="teacher_first_name" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Last Name *</label>
                                        <input type="text" name="teacher_last_name" class="form-control">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" name="teacher_email" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="teacher_phone" class="form-control">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Subject *</label>
                                        <select name="subject_id" class="form-control">
                                            <option value="">Select Subject</option>
                                            <?php 
                                            $subjects = $conn->query("SELECT id, course_name FROM courses ORDER BY course_name");
                                            while($subject = $subjects->fetch_assoc()): ?>
                                                <option value="<?php echo $subject['id']; ?>">
                                                    <?php echo htmlspecialchars($subject['course_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Assigned Classes</label>
                                        <input type="text" name="assigned_classes" class="form-control" placeholder="e.g., Class 9A, Class 10B">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Parent-Specific Fields -->
                            <div class="parent-fields" id="parentFields">
                                <hr class="my-4">
                                <h5 class="mb-3"><i class="fas fa-user-friends me-2"></i>Parent Information</h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">First Name *</label>
                                        <input type="text" name="parent_first_name" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Last Name *</label>
                                        <input type="text" name="parent_last_name" class="form-control">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" name="parent_email" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone *</label>
                                        <input type="text" name="parent_phone" class="form-control">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Student Reference (Student ID) *</label>
                                        <input type="text" name="student_reference" class="form-control" 
                                               placeholder="Enter student ID to link with child">
                                        <small class="text-muted">This will link you to your child's account</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function selectRole(role) {
            // Update hidden input
            document.getElementById('selected_role').value = role;
            
            // Update UI
            document.querySelectorAll('.role-option').forEach(option => {
                option.classList.remove('active');
            });
            
            event.currentTarget.classList.add('active');
            
            // Show/hide fields based on role
            document.getElementById('studentFields').style.display = 'none';
            document.getElementById('teacherFields').style.display = 'none';
            document.getElementById('parentFields').style.display = 'none';
            
            if (role === 'student') {
                document.getElementById('studentFields').style.display = 'block';
                // Make student fields required
                document.getElementById('studentFields').querySelectorAll('input').forEach(input => {
                    input.required = true;
                });
            } else if (role === 'teacher') {
                document.getElementById('teacherFields').style.display = 'block';
                // Make teacher fields required
                document.getElementById('teacherFields').querySelectorAll('input[required], select[required]').forEach(input => {
                    input.required = true;
                });
            } else if (role === 'parent') {
                document.getElementById('parentFields').style.display = 'block';
                // Make parent fields required
                document.getElementById('parentFields').querySelectorAll('input[required]').forEach(input => {
                    input.required = true;
                });
            }
        }
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const matchDiv = document.getElementById('password_match');
            
            if (confirmPassword === '') {
                matchDiv.textContent = '';
                matchDiv.className = 'form-text';
            } else if (password === confirmPassword) {
                matchDiv.textContent = 'Passwords match!';
                matchDiv.className = 'form-text text-success';
            } else {
                matchDiv.textContent = 'Passwords do not match!';
                matchDiv.className = 'form-text text-danger';
            }
        });
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });
    </script>
</body>
</html>