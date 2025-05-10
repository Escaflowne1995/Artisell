<?php
// Ensure this file is included in an admin context
if(!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in and is an admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    header("location: ../login.php");
    exit;
}
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <h2>Artisell Admin</h2>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="icon">📊</span>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="order_new.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'order_new.php' ? 'active' : ''; ?>">
                    <span class="icon">📦</span>
                    Orders
                </a>
            </li>
            <li>
                <a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                    <span class="icon">🛍️</span>
                    Products
                </a>
            </li>
            <li>
                <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <span class="icon">👥</span>
                    Users
                </a>
            </li>
            <li>
                <a href="categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                    <span class="icon">📑</span>
                    Categories
                </a>
            </li>
            <li>
                <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <span class="icon">⚙️</span>
                    Settings
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="../logout.php" class="logout-btn">
            <span class="icon">🚪</span>
            Logout
        </a>
    </div>
</aside>

<style>
.sidebar {
    width: 250px;
    background: #2c3e50;
    color: #ecf0f1;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    padding: 20px 0;
    display: flex;
    flex-direction: column;
}

.sidebar-header {
    padding: 0 20px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-header h2 {
    margin: 0;
    font-size: 1.5rem;
    color: #ecf0f1;
}

.sidebar-nav {
    flex: 1;
    padding: 20px 0;
}

.sidebar-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-nav li {
    margin: 5px 0;
}

.sidebar-nav a {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #ecf0f1;
    text-decoration: none;
    transition: background-color 0.3s;
}

.sidebar-nav a:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.sidebar-nav a.active {
    background-color: rgba(255, 255, 255, 0.2);
    border-left: 4px solid #3498db;
}

.icon {
    margin-right: 10px;
    font-size: 1.2em;
}

.sidebar-footer {
    padding: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.logout-btn {
    display: flex;
    align-items: center;
    padding: 10px;
    color: #ecf0f1;
    text-decoration: none;
    border-radius: 5px;
    transition: background-color 0.3s;
}

.logout-btn:hover {
    background-color: rgba(255, 255, 255, 0.1);
}
</style> 