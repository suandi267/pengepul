<?php
// Start the session to manage user login state
session_start();

// Include necessary files
require_once 'config/database.php';

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// If user is already logged in, redirect to their dashboard
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    if ($role === 'admin') {
        header("Location: admin/index.php");
    } elseif ($role === 'agent') {
        header("Location: agent/index.php");
    } elseif ($role === 'customer') {
        header("Location: customer/index.php");
    }
    exit;
}

$error_message = '';

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = "Invalid CSRF token.";
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error_message = "Username dan password tidak boleh kosong.";
        } else {
            try {
                // Prepare a statement to prevent SQL injection
                $sql = "SELECT id, username, password, role, name FROM users WHERE username = ? LIMIT 1";
                $stmt = $connection->prepare($sql);
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 1) {
                    $user = $result->fetch_assoc();

                    // Verify the password
                    if (password_verify($password, $user['password'])) {
                        // Password is correct, start a new session
                        session_regenerate_id(true); // Prevent session fixation
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['role'] = $user['role'];

                        // Redirect based on role
                        $role = $user['role'];
                        if ($role === 'admin') {
                            header("Location: admin/index.php");
                        } elseif ($role === 'agent') {
                            header("Location: agent/index.php");
                        } elseif ($role === 'customer') {
                            header("Location: customer/index.php");
                        }
                        exit;
                    } else {
                        // Password is not valid
                        $error_message = "Username atau password salah.";
                    }
                } else {
                    // Username does not exist
                    $error_message = "Username atau password salah.";
                }
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                $error_message = "Terjadi kesalahan pada database. Silakan coba lagi.";
                // Log the error for debugging: error_log($e->getMessage());
            }
        }
    }
}

// Set the page title
$page_title = 'Login Pengguna';
require_once 'templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header text-center">
                <h3><?php echo htmlspecialchars($page_title); ?></h3>
            </div>
            <div class="card-body">
                <?php 
                if (!empty($error_message)) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
                }
                ?>
                <form action="login.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <a href="register.php">Belum punya akun? Daftar</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'templates/footer.php';

// Close the database connection
if (isset($connection)) {
    $connection->close();
}
?>