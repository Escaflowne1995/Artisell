<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php"); // Redirect to login page if not logged in or not an admin
    exit;
}

// Include database connection
require_once "../db_connection.php";

// Delete product if requested
if(isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $delete_sql = "DELETE FROM products WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Redirect to avoid resubmission on refresh
    header("Location: products.php");
    exit;
}

// Get all products with vendor info
$sql = "SELECT p.*, u.username as vendor_name 
      FROM products p 
      LEFT JOIN users u ON p.vendor_id = u.id 
      ORDER BY p.id DESC";
$result = mysqli_query($conn, $sql);
$products = [];
while($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Products - Artisell Admin</title>
  <link rel="stylesheet" href="../css/admin.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
  <style>
    .product-actions {
      display: flex;
      gap: 10px;
    }
    .btn-sm {
      padding: 5px 10px;
      border-radius: 4px;
      font-size: 0.8rem;
      text-decoration: none;
    }
    .btn-primary {
      background-color: #4361ee;
      color: white;
    }
    .btn-danger {
      background-color: #ef476f;
      color: white;
    }
    .product-image {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 6px;
    }
    .add-product-btn {
      background-color: #4CAF50;
      color: white;
      padding: 10px 15px;
      border-radius: 4px;
      text-decoration: none;
      display: inline-block;
      margin-bottom: 20px;
    }
    .add-product-btn:hover {
      background-color: #45a049;
    }
    .product-table {
      width: 100%;
      border-collapse: collapse;
    }
    .product-table th, .product-table td {
      padding: 12px 15px;
      border-bottom: 1px solid #e0e0e0;
    }
    .product-table th {
      background-color: #f8f9fa;
      text-align: left;
      font-weight: 600;
    }
    .search-form {
      margin-bottom: 20px;
      display: flex;
      gap: 10px;
    }
    .search-form input {
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      flex-grow: 1;
    }
    .search-form button {
      padding: 8px 15px;
      background-color: #4361ee;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    .deleted-message {
      padding: 10px;
      background-color: #d4edda;
      color: #155724;
      border-radius: 4px;
      margin-bottom: 20px;
    }
    .truncate {
      max-width: 150px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  </style>
</head>
<body>

  <div class="dashboard">
    <aside class="sidebar">
      <div class="sidebar-header">
        <h1 class="logo">ARTISELL</h1>
        <div class="menu-icon">
          <svg width="28" height="24" viewBox="0 0 28 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M2.61 0H25.041C25.7332 0 26.3971 0.274981 26.8865 0.764451C27.376 1.25392 27.651 1.91778 27.651 2.61C27.651 3.30221 27.376 3.96608 26.8865 4.45555C26.3971 4.94502 25.7332 5.22 25.041 5.22H2.61C1.91778 5.22 1.25392 4.94502 0.764451 4.45555C0.274981 3.96608 7.77841e-08 3.30221 7.77841e-08 2.61C7.77841e-08 1.91778 0.274981 1.25392 0.764451 0.764451C1.25392 0.274981 1.91778 0 2.61 0ZM2.61 9.39H25.041C25.3838 9.39 25.7231 9.45751 26.0398 9.58867C26.3565 9.71984 26.6442 9.91209 26.8865 10.1545C27.1289 10.3968 27.3212 10.6845 27.4523 11.0012C27.5835 11.3179 27.651 11.6572 27.651 12C27.651 12.3428 27.5835 12.6821 27.4523 12.9988C27.3212 13.3155 27.1289 13.6032 26.8865 13.8455C26.6442 14.0879 26.3565 14.2802 26.0398 14.4113C25.7231 14.5425 25.3838 14.61 25.041 14.61H2.61C2.26725 14.61 1.92786 14.5425 1.6112 14.4113C1.29454 14.2802 1.00681 14.0879 0.764451 13.8455C0.52209 13.6032 0.329839 13.3155 0.198674 12.9988C0.0675097 12.6821 2.27824e-08 12.3428 2.27824e-08 12C2.27824e-08 11.6572 0.0675097 11.3179 0.198674 11.0012C0.329839 10.6845 0.52209 10.3968 0.764451 10.1545C1.00681 9.91209 1.29454 9.71984 1.6112 9.58867C1.92786 9.45751 2.26725 9.39 2.61 9.39ZM2.61 18.781H25.041C25.3838 18.781 25.7231 18.8485 26.0398 18.9797C26.3565 19.1108 26.6442 19.3031 26.8865 19.5455C27.1289 19.7878 27.3212 20.0755 27.4523 20.3922C27.5835 20.7089 27.651 21.0483 27.651 21.391C27.651 21.7338 27.5835 22.0731 27.4523 22.3898C27.3212 22.7065 27.1289 22.9942 26.8865 23.2365C26.6442 23.4789 26.3565 23.6712 26.0398 23.8023C25.7231 23.9335 25.3838 24.001 25.041 24.001H2.61C2.26725 24.001 1.92786 23.9335 1.6112 23.8023C1.29454 23.6712 1.00681 23.4789 0.764451 23.2365C0.52209 22.9942 0.329839 22.7065 0.198674 22.3898C0.0675096 22.0731 0 21.7338 0 21.391C0 21.0483 0.0675096 20.7089 0.198674 20.3922C0.329839 20.0755 0.52209 19.7878 0.764451 19.5455C1.00681 19.3031 1.29454 19.1108 1.6112 18.9797C1.92786 18.8485 2.26725 18.781 2.61 18.781Z" fill="currentColor"/>
          </svg>
        </div>
      </div>

      <div class="profile">
        <div class="profile-image"></div>
        <div class="profile-role">Admin Profile</div>
        <div class="profile-name"><?php echo htmlspecialchars($_SESSION["username"]); ?></div>
      </div>

      <nav class="navigation">
        <a href="index.php" class="nav-link">
          <div class="nav-icon">
            <svg width="26" height="30" viewBox="0 0 26 30" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M0 28.05V11.1011C0 10.6019 0.106476 10.1293 0.319428 9.68346C0.532381 9.23757 0.825809 8.87033 1.19971 8.58174L11.2004 0.629835C11.7241 0.209946 12.3221 0 12.9944 0C13.6667 0 14.2684 0.209946 14.7996 0.629835L24.8003 8.57979C25.1754 8.86838 25.4689 9.23627 25.6806 9.68346C25.8935 10.1293 26 10.6019 26 11.1011V28.05C26 28.5726 25.8149 29.0283 25.4447 29.417C25.0745 29.8057 24.6406 30 24.1429 30H17.8583C17.4324 30 17.0758 29.8492 16.7886 29.5476C16.5013 29.2447 16.3577 28.8703 16.3577 28.4244V19.1251C16.3577 18.6792 16.2141 18.3055 15.9269 18.0039C15.6384 17.701 15.2818 17.5496 14.8571 17.5496H11.1429C10.7182 17.5496 10.3622 17.701 10.075 18.0039C9.78652 18.3055 9.64229 18.6792 9.64229 19.1251V28.4264C9.64229 28.8723 9.49867 29.246 9.21143 29.5476C8.92419 29.8492 8.56824 30 8.14357 30H1.85714C1.35943 30 0.925476 29.8057 0.555285 29.417C0.185095 29.0283 0 28.5726 0 28.05Z" fill="currentColor"/>
            </svg>
          </div>
          <span class="nav-text">Home</span>
        </a>
        <a href="user_new.php" class="nav-link">
          <div class="nav-icon">
            <svg width="46" height="46" viewBox="0 0 46 46" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M22.9998 7.66666C25.0332 7.66666 26.9832 8.47439 28.421 9.91217C29.8588 11.3499 30.6665 13.3 30.6665 15.3333C30.6665 17.3666 29.8588 19.3167 28.421 20.7545C26.9832 22.1923 25.0332 23 22.9998 23C20.9665 23 19.0165 22.1923 17.5787 20.7545C16.1409 19.3167 15.3332 17.3666 15.3332 15.3333C15.3332 13.3 16.1409 11.3499 17.5787 9.91217C19.0165 8.47439 20.9665 7.66666 22.9998 7.66666ZM22.9998 26.8333C31.4715 26.8333 38.3332 30.2642 38.3332 34.5V38.3333H7.6665V34.5C7.6665 30.2642 14.5282 26.8333 22.9998 26.8333Z" fill="currentColor"/>
            </svg>
          </div>
          <span class="nav-text">Users</span>
        </a>
        <a href="order_new.php" class="nav-link">
          <div class="nav-icon">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M6.6665 33.3333V11.8333L3.4165 4.75001L6.4165 3.33334L10.3332 11.75H29.6665L33.5832 3.33334L36.5832 4.75001L33.3332 11.8333V33.3333H6.6665ZM16.6665 21.6667H23.3332C23.8054 21.6667 24.2015 21.5067 24.5215 21.1867C24.8415 20.8667 25.0009 20.4711 24.9998 20C24.9987 19.5289 24.8387 19.1333 24.5198 18.8133C24.2009 18.4933 23.8054 18.3333 23.3332 18.3333H16.6665C16.1943 18.3333 15.7987 18.4933 15.4798 18.8133C15.1609 19.1333 15.0009 19.5289 14.9998 20C14.9987 20.4711 15.1587 20.8672 15.4798 21.1883C15.8009 21.5095 16.1965 21.6689 16.6665 21.6667Z" fill="currentColor"/>
            </svg>
          </div>
          <span class="nav-text">Orders</span>
        </a>
        <a href="products.php" class="nav-link active">
          <div class="nav-icon">
            <svg width="37" height="37" viewBox="0 0 37 37" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M33.9168 10.7916L18.5002 3.08331L3.0835 10.7916V26.2083L18.5002 33.9166L33.9168 26.2083V10.7916Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
              <path d="M3.0835 10.7916L18.5002 18.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M18.5 33.9167V18.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M33.9167 10.7916L18.5 18.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M26.2087 6.9375L10.792 14.6458" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <span class="nav-text">Products</span>
        </a>
        <a href="settings.php" class="nav-link">
          <div class="nav-icon">
            <svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M15 6C13.4087 6 11.8826 6.63214 10.7574 7.75736C9.63214 8.88258 9 10.4087 9 12C9 13.5913 9.63214 15.1174 10.7574 16.2426C11.8826 17.3679 13.4087 18 15 18C16.5913 18 18.1174 17.3679 19.2426 16.2426C20.3679 15.1174 21 13.5913 21 12C21 10.4087 20.3679 8.88258 19.2426 7.75736C18.1174 6.63214 16.5913 6 15 6ZM15 9C15.7956 9 16.5587 9.31607 17.1213 9.87868C17.6839 10.4413 18 11.2044 18 12C18 12.7956 17.6839 13.5587 17.1213 14.1213C16.5587 14.6839 15.7956 15 15 15C14.2044 15 13.4413 14.6839 12.8787 14.1213C12.3161 13.5587 12 12.7956 12 12C12 11.2044 12.3161 10.4413 12.8787 9.87868C13.4413 9.31607 14.2044 9 15 9ZM25.5 18C25.26 18 25.14 18.12 25.14 18.36L24.75 20.25C24.42 20.52 23.94 20.76 23.58 21L21.66 20.25C21.54 20.25 21.3 20.25 21.18 20.4L19.74 23.04C19.62 23.16 19.62 23.4 19.86 23.52L21.42 24.75V26.25L19.86 27.48C19.74 27.6 19.62 27.84 19.74 27.96L21.18 30.6C21.3 30.75 21.54 30.75 21.66 30.75L23.58 30C23.94 30.24 24.42 30.48 24.75 30.75L25.14 32.64C25.14 32.88 25.26 33 25.5 33H28.5C28.62 33 28.86 32.88 28.86 32.64L29.1 30.75C29.58 30.48 30.06 30.24 30.42 30L32.25 30.75C32.46 30.75 32.7 30.75 32.7 30.6L34.26 27.96C34.38 27.84 34.26 27.6 34.14 27.48L32.58 26.25V24.75L34.14 23.52C34.26 23.4 34.38 23.16 34.26 23.04L32.7 20.4C32.7 20.25 32.46 20.25 32.25 20.25L30.42 21C30.06 20.76 29.58 20.52 29.1 20.25L28.86 18.36C28.86 18.12 28.62 18 28.5 18H25.5ZM15 19.5C10.995 19.5 3 21.495 3 25.5V30H17.505C17.085 29.115 16.785 28.155 16.635 27.15H5.85V25.5C5.85 24.54 10.545 22.35 15 22.35C15.645 22.35 16.305 22.41 16.95 22.5C17.25 21.54 17.655 20.64 18.18 19.815C17.01 19.62 15.9 19.5 15 19.5ZM27.06 23.25C28.26 23.25 29.25 24.24 29.25 25.56C29.25 26.76 28.26 27.75 27.06 27.75C25.74 27.75 24.75 26.76 24.75 25.56C24.75 24.24 25.74 23.25 27.06 23.25Z" fill="currentColor"/>
            </svg>
          </div>
          <span class="nav-text">Settings</span>
        </a>
        <a href="logout.php" class="nav-link">
          <div class="nav-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M5 5H11C11.55 5 12 4.55 12 4C12 3.45 11.55 3 11 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H11C11.55 21 12 20.55 12 20C12 19.45 11.55 19 11 19H5V5Z" fill="currentColor"/>
              <path d="M20.65 11.65L17.86 8.86C17.54 8.54 17 8.76 17 9.21V11H10C9.45 11 9 11.45 9 12C9 12.55 9.45 13 10 13H17V14.79C17 15.24 17.54 15.46 17.85 15.14L20.64 12.35C20.84 12.16 20.84 11.84 20.65 11.65Z" fill="currentColor"/>
            </svg>
          </div>
          <span class="nav-text">Logout</span>
        </a>
      </nav>
    </aside>

    <main class="main-content">
      <div class="products-page">
        <header>
          <h1>Manage Products</h1>
          <p>View, edit and delete products from your catalog</p>
        </header>
        
        <?php if(isset($_GET['deleted'])): ?>
        <div class="deleted-message">
          Product has been deleted successfully.
        </div>
        <?php endif; ?>
        
        <div class="products-controls">
          <form class="search-form" method="GET">
            <input type="text" name="search" placeholder="Search products..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit">Search</button>
          </form>
          
          <a href="add_product_admin.php" class="add-product-btn">
            Add New Product
          </a>
        </div>
        
        <table class="product-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Image</th>
              <th>Name</th>
              <th>Price</th>
              <th>Category</th>
              <th>City</th>
              <th>Stock</th>
              <th>Vendor</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if(empty($products)): ?>
              <tr>
                <td colspan="9" style="text-align: center;">No products found</td>
              </tr>
            <?php else: ?>
              <?php foreach($products as $product): ?>
                <tr>
                  <td><?php echo $product['id']; ?></td>
                  <td>
                    <img src="<?php echo '../' . $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                  </td>
                  <td class="truncate"><?php echo htmlspecialchars($product['name']); ?></td>
                  <td>$<?php echo number_format($product['price'], 2); ?></td>
                  <td><?php echo htmlspecialchars($product['category']); ?></td>
                  <td><?php echo htmlspecialchars($product['city']); ?></td>
                  <td><?php echo $product['stock']; ?></td>
                  <td><?php echo htmlspecialchars($product['vendor_name']); ?></td>
                  <td class="product-actions">
                    <a href="edit_product_admin.php?id=<?php echo $product['id']; ?>" class="btn-sm btn-primary">Edit</a>
                    <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $product['id']; ?>)" class="btn-sm btn-danger">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <script>
    // Confirm delete
    function confirmDelete(id) {
      if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        window.location.href = 'products.php?delete=' + id;
      }
    }
    
    // Toggle sidebar on mobile
    const menuIcon = document.querySelector('.menu-icon');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuIcon && sidebar) {
      menuIcon.addEventListener('click', () => {
        sidebar.classList.toggle('active');
      });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
      if (sidebar && 
          sidebar.classList.contains('active') && 
          !sidebar.contains(e.target) && 
          e.target !== menuIcon) {
        sidebar.classList.remove('active');
      }
    });
  </script>
</body>
</html> 