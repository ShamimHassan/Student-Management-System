<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectBasedOnRole();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    if (empty($username) || empty($password) || empty($role)) {
        $error = "Please fill in all fields";
    } else {
        if (login($conn, $username, $password, $role)) {
            redirectBasedOnRole();
        } else {
            $error = "Invalid username, password, or role";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Management System</title>
    
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
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-body {
            padding: 30px;
        }
        .role-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .role-option {
            flex: 1;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-card">
                    <div class="login-header">
                        <i class="fas fa-graduation-cap fa-3x mb-3"></i>
                        <h2>Student Management System</h2>
                        <p class="mb-0" id="roleDescription">Login to your account</p>
                    </div>
                    
                    <div class="login-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST">
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
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                           required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p class="mb-0">Don't have an account? <a href="register.php">Register here</a></p>
                        </div>

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
            
            // Update role description
            const roleDescription = document.getElementById('roleDescription');
            const roleTexts = {
                'admin': 'Administrator Login',
                'teacher': 'Teacher Login',
                'parent': 'Parent Login',
                'student': 'Student Login'
            };
            roleDescription.textContent = roleTexts[role] || 'Login to your account';
            
            // Update form styling based on role
            const card = document.querySelector('.login-card');
            const header = document.querySelector('.login-header');
            
            switch(role) {
                case 'admin':
                    header.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                    break;
                case 'teacher':
                    header.style.background = 'linear-gradient(135deg, #4e73df 0%, #224abe 100%)';
                    break;
                case 'parent':
                    header.style.background = 'linear-gradient(135deg, #36b9cc 0%, #1a8caa 100%)';
                    break;
                case 'student':
                    header.style.background = 'linear-gradient(135deg, #1cc88a 0%, #13855c 100%)';
                    break;
            }
        }
    </script>
</body>
</html>