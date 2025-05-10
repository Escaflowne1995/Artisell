<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<header class="header">
    <div class="container header-inner">
        <a href="index.php" class="logo">Arti<span>Sell</span></a>
        <div class="header-right">
            <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                <div class="profile-dropdown">
                    <a href="profile.php" class="nav-link profile-link">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                        <?php if (!empty($_SESSION['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" alt="Profile" class="profile-pic">
                        <?php else: ?>
                            <img src="images/default-profile.jpg" alt="Profile" class="profile-pic">
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-content">
                        <a href="settings.php" class="dropdown-item">Settings</a>
                        <a href="logout.php" class="dropdown-item">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'login.php' ? 'active' : ''; ?>">Login</a>
                <a href="signup.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'signup.php' ? 'active' : ''; ?>">Register</a>
            <?php endif; ?>
        </div>
    </div>
</header> 