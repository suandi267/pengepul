<?php
session_start();
require_once 'config/database.php';

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_message = '';
$success_message = '';

// Redirect to login if not a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit;
}

// Initialize variables to hold form data
$customer_name = $_SESSION['name'] ?? ''; // Pre-fill from session
$customer_phone = '';
$customer_address = '';
$waste_description = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = "Invalid CSRF token.";
    } else {
        $customer_id = $_SESSION['user_id']; // Get customer ID from session
        $customer_name = trim($_POST['customer_name']);
        $customer_phone = trim($_POST['customer_phone']);
        $customer_address = trim($_POST['customer_address']);
        $waste_description = trim($_POST['waste_description']);

        if (empty($customer_name) || empty($customer_phone) || empty($customer_address)) {
            $error_message = "Nama, nomor HP, dan alamat harus diisi.";
        } else {
            try {
                $photo_url = null;
                // Handle photo upload if provided
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/images/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $file_info = new finfo(FILEINFO_MIME_TYPE);
                    $mime_type = $file_info->file($_FILES['photo']['tmp_name']);
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

                    if (in_array($mime_type, $allowed_types)) {
                        if ($_FILES['photo']['size'] < 2000000) { // 2MB limit
                            $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                            $file_name = bin2hex(random_bytes(16)) . '.' . $file_extension;
                            $target_file = $upload_dir . $file_name;

                            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                                $photo_url = $file_name; // Save only the filename
                            } else {
                                throw new Exception("Gagal memindahkan file yang diunggah.");
                            }
                        } else {
                            throw new Exception("Ukuran file tidak boleh lebih dari 2MB.");
                        }
                    } else {
                        throw new Exception("Format file tidak valid. Hanya JPG, PNG, dan GIF yang diizinkan.");
                    }
                }

                $transaction_started = false;
                $connection->begin_transaction();
                $transaction_started = true;

                $sql = "INSERT INTO pickup_requests (customer_id, customer_name, customer_phone, customer_address, waste_description, photo_url) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $connection->prepare($sql);
                $stmt->bind_param('isssss', $customer_id, $customer_name, $customer_phone, $customer_address, $waste_description, $photo_url);
                $stmt->execute();
                $pickup_id = $connection->insert_id;

                // Notify all admins about new pickup request
                $admin_sql = "SELECT id FROM users WHERE role = 'admin'";
                $admin_result = $connection->query($admin_sql);
                $admins = $admin_result->fetch_all(MYSQLI_ASSOC);

                $message = "Permintaan jemput barang baru #{$pickup_id} dari {$customer_name}.";
                $link = "admin/notifikasi.php?pickup_id={$pickup_id}";
                $notif_sql = "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)";
                $notif_stmt = $connection->prepare($notif_sql);

                foreach ($admins as $admin) {
                    $notif_stmt->bind_param('iss', $admin['id'], $message, $link);
                    $notif_stmt->execute();
                }

                $connection->commit();
                $success_message = "Permintaan jemput barang berhasil dikirim. Agen akan menghubungi Anda segera.";
                // Clear form data on success
                $customer_name = $customer_phone = $customer_address = $waste_description = '';

            } catch (Exception $e) {
                if($transaction_started) {
                    $connection->rollback();
                }
                $error_message = "Gagal mengirim permintaan: " . $e->getMessage();
                // Log the error: error_log($e->getMessage());
            }
        }
    }
}

$page_title = 'Request Pickup';
include 'templates/header.php';
?>

<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center">Request Jemput Barang</h3>
                </div>
                <div class="card-body">
                    <?php if($success_message) echo '<div class="alert alert-success">'.htmlspecialchars($success_message).'</div>'; ?>
                    <?php if($error_message) echo '<div class="alert alert-danger">'.htmlspecialchars($error_message).'</div>'; ?>

                    <form method="POST" action="request_pickup.php" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required value="<?php echo htmlspecialchars($customer_name); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="customer_phone" class="form-label">Nomor HP</label>
                            <input type="tel" class="form-control" id="customer_phone" name="customer_phone" required value="<?php echo htmlspecialchars($customer_phone); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="customer_address" class="form-label">Alamat Lengkap</label>
                            <textarea class="form-control" id="customer_address" name="customer_address" rows="3" required><?php echo htmlspecialchars($customer_address); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="waste_description" class="form-label">Deskripsi Barang/Sampah</label>
                            <textarea class="form-control" id="waste_description" name="waste_description" rows="3"><?php echo htmlspecialchars($waste_description); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="photo" class="form-label">Foto Barang (Opsional, maks 2MB)</label>
                            <input type="file" class="form-control" id="photo" name="photo" accept="image/jpeg,image/png,image/gif">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Kirim Permintaan</button>
                        </div>
                    </form>
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