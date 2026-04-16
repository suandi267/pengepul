<?php
session_start();
require_once 'config/database.php';

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = "Invalid CSRF token.";
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);

        if (empty($username) || empty($password) || empty($name)) {
            $error_message = "Username, password, dan nama harus diisi.";
        } elseif ($password !== $confirm_password) {
            $error_message = "Password dan konfirmasi password tidak cocok.";
        } elseif (strlen($password) < 6) {
            $error_message = "Password minimal 6 karakter.";
        } elseif (strlen($username) > 50) {
            $error_message = "Username tidak boleh lebih dari 50 karakter.";
        } elseif (strlen($name) > 100) {
            $error_message = "Nama tidak boleh lebih dari 100 karakter.";
        } elseif (!empty($phone) && !preg_match('/^[0-9]{10,15}$/', $phone)) {
            $error_message = "Format nomor telepon tidak valid.";
        } else {
            try {
                // Check if username exists
                $stmt = $connection->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->bind_param('s', $username);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $error_message = "Username sudah digunakan.";
                }
                $stmt->close();

                if (empty($error_message)) {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Insert new customer
                    $stmt = $connection->prepare("INSERT INTO users (username, password, name, role, phone, address) VALUES (?, ?, ?, 'customer', ?, ?)");
                    $stmt->bind_param('sssss', $username, $hashed_password, $name, $phone, $address);
                    if ($stmt->execute()) {
                        $success_message = "Registrasi berhasil. Silakan login.";
                    } else {
                        $error_message = "Gagal registrasi. Silakan coba lagi.";
                    }
                    $stmt->close();
                }
            } catch (mysqli_sql_exception $e) {
                $error_message = "Terjadi kesalahan pada database. Silakan coba lagi.";
                // Log the error for debugging: error_log($e->getMessage());
            }
        }
    }
}

$page_title = 'Registrasi';
include 'templates/header.php';
?>

<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center"><?php echo htmlspecialchars($page_title); ?></h3>
                </div>
                <div class="card-body">
                    <?php if($success_message) echo '<div class="alert alert-success">'.htmlspecialchars($success_message).'</div>'; ?>
                    <?php if($error_message) echo '<div class="alert alert-danger">'.htmlspecialchars($error_message).'</div>'; ?>

                    <form method="POST" action="register.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="name" name="name" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Nomor HP</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Alamat</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo isset($address) ? htmlspecialchars($address) : ''; ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Daftar</button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <a href="login.php">Sudah punya akun? Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php 
include 'templates/footer.php'; 

// Close the database connection
if (isset($connection)) {
    $connection->close();
}
?>