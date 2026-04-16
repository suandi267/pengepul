<?php
// Simple router
if (isset($_GET['page'])) {
    $page = $_GET['page'];
    // Whitelist of allowed pages
    $allowed_pages = ['login', 'register', 'request_pickup'];

    if (in_array($page, $allowed_pages)) {
        $file = $page . '.php';
        if (file_exists($file)) {
            require_once $file;
            exit();
        }
    }
}

// Set the page title
$page_title = 'Selamat Datang di Kepul Point';

// Include necessary files
require_once 'config/database.php';
require_once 'templates/header.php';

// Enable mysqli error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$result = null; // Initialize result

?>

<!-- Hero Section -->
<div class="hero-section text-center text-white" style="background: linear-gradient(45deg, #3498db, #2c3e50);">
    <div class="container">
        <h1 class="display-3 fw-bold">Hkbp Distrix Merek</h1>
        <p class="lead my-4">Kepul Point membantu Anda mengelola sampah dengan lebih baik dan mendapatkan penghasilan tambahan. <br>Mudah, cepat, dan menguntungkan.</p>
        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
            <a href="register.php" class="btn btn-primary btn-lg px-4 gap-3">Daftar Sekarang</a>
            <a href="#how-it-works" class="btn btn-outline-light btn-lg px-4">Lihat Cara Kerja</a>
        </div>
    </div>
</div>

<div class="container my-5">
    <!-- How It Works Section -->
    <section id="how-it-works" class="py-5">
        <div class="row text-center mb-5">
            <div class="col-12">
                <h2 class="fw-bold">Bagaimana Caranya?</h2>
                <p class="text-muted">Hanya dengan 3 langkah mudah untuk mulai menghasilkan.</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="step-card text-center p-4">
                    <div class="step-icon mb-3"><i class="fas fa-user-plus"></i></div>
                    <h5 class="fw-bold">1. Daftar Akun</h5>
                    <p>Buat akun sebagai customer untuk memulai.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card text-center p-4">
                    <div class="step-icon mb-3"><i class="fas fa-paper-plane"></i></div>
                    <h5 class="fw-bold">2. Ajukan Penjemputan</h5>
                    <p>Minta agen kami untuk menjemput sampah Anda.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card text-center p-4">
                    <div class="step-icon mb-3"><i class="fas fa-hand-holding-usd"></i></div>
                    <h5 class="fw-bold">3. Dapatkan Bayaran</h5>
                    <p>Agen akan menimbang dan membayar sampah Anda.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Waste Prices Section -->
    <section id="waste-prices" class="py-5 bg-light rounded-3">
        <div class="row text-center mb-5">
            <div class="col-12">
                <h2 class="fw-bold">Harga Sampah Terbaru</h2>
                <p class="text-muted">Berikut adalah harga jual sampah terbaru yang kami terima. Tukarkan sampahmu sekarang!</p>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php
            try {
                $sql = "SELECT id, name, price_per_kg, points_per_kg, image FROM waste_types ORDER BY name ASC";
                $stmt = $connection->prepare($sql);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $image_path = 'uploads/images/' . (!empty($row['image']) ? htmlspecialchars($row['image']) : 'placeholder.jpg');
                ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm price-card">
                            <div class="image-container">
                                <img src="<?php echo $image_path; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['name']); ?>">
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title fw-bold text-primary"><?php echo htmlspecialchars($row['name']); ?></h5>
                                <p class="card-text mb-2">
                                    <span class="price">Rp <?php echo number_format($row['price_per_kg'], 0, ',', '.'); ?></span> / kg
                                </p>
                                <div class="mt-auto">
                                    <div class="input-group mb-2">
                                        <input type="number" class="form-control form-control-sm" placeholder="Kg" min="0" step="0.1" id="weight-<?php echo $row['id']; ?>" data-price="<?php echo $row['price_per_kg']; ?>" oninput="calculatePrice(<?php echo $row['id']; ?>)">
                                        <span class="input-group-text">Kg</span>
                                    </div>
                                    <p class="text-end mb-0 total-price">
                                        <strong>Total:</strong> <span id="total-price-<?php echo $row['id']; ?>">Rp 0</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                    }
                } else {
                    echo "<div class='col-12'><div class='alert alert-info'>Saat ini belum ada data harga yang tersedia.</div></div>";
                }
            } catch (mysqli_sql_exception $e) {
                echo "<div class='col-12'><div class='alert alert-danger'>Gagal memuat data harga. Silakan coba lagi nanti.</div></div>";
            }
            ?>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section id="why-choose-us" class="py-5">
        <div class="row text-center mb-5">
            <div class="col-12">
                <h2 class="fw-bold">Kenapa Memilih Kepul Point?</h2>
            </div>
        </div>
        <div class="row text-center g-4">
            <div class="col-md-4">
                <div class="feature-card p-4">
                    <div class="feature-icon mb-3"><i class="fas fa-recycle"></i></div>
                    <h5 class="fw-bold">Lingkungan Bersih</h5>
                    <p>Bantu mengurangi sampah dan jaga kebersihan lingkungan sekitar Anda.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card p-4">
                    <div class="feature-icon mb-3"><i class="fas fa-wallet"></i></div>
                    <h5 class="fw-bold">Penghasilan Tambahan</h5>
                    <p>Dapatkan uang tunai dari sampah yang selama ini tidak terpakai.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card p-4">
                    <div class="feature-icon mb-3"><i class="fas fa-shipping-fast"></i></div>
                    <h5 class="fw-bold">Layanan Cepat</h5>
                    <p>Agen kami siap menjemput sampah di lokasi Anda dengan cepat.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="cta" class="py-5 text-center text-white bg-primary rounded-3">
        <div class="container">
            <h2 class="display-5 fw-bold">Siap Bergabung?</h2>
            <p class="lead my-4">Jadilah bagian dari solusi untuk lingkungan yang lebih baik dan dapatkan keuntungannya. <br>Daftar sekarang juga!</p>
            <a href="register.php" class="btn btn-light btn-lg px-4">Buat Akun Gratis</a>
        </div>
    </section>
</div>

<script>
function calculatePrice(id) {
    const weightInput = document.getElementById('weight-' + id);
    const totalPriceSpan = document.getElementById('total-price-' + id);
    
    const weight = parseFloat(weightInput.value) || 0;
    const pricePerKg = parseFloat(weightInput.dataset.price) || 0;

    const totalPrice = weight * pricePerKg;
    
    totalPriceSpan.textContent = 'Rp ' + totalPrice.toLocaleString('id-ID');
}
</script>

<?php
require_once 'templates/footer.php';
if (isset($connection)) {
    $connection->close();
}
?>