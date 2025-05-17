<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About ArtiSell - Cebu's Cultural Marketplace</title>
    <link rel="stylesheet" href="css/modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* About page specific styles */
        body { 
            background-color: #f9f9f9; 
            color: #333333; 
            font-family: Arial, sans-serif; 
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        
        /* Basic button styles */
        .btn {
            padding: 8px 20px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .btn-outline {
            border: 1px solid #2E8B57;
            color: #2E8B57;
            background: transparent;
        }
        
        .btn-primary {
            background-color: #2E8B57;
            color: white;
        }
        
        .btn-white {
            background-color: white;
            color: #2E8B57;
        }
        
        .btn-outline.btn-white {
            border: 1px solid white;
            color: white;
            background: transparent;
        }
        
        .text-green {
            color: #008a39;
        }
        
        .text-blue {
            color: #0066cc;
        }
        
        /* Hero Section */
        .about-hero {
            background: #6E6E6E;
            color: white;
            text-align: center;
            padding: 100px 0;
            margin-bottom: 50px;
            margin-top: 0; /* Remove the space at the top */
        }
        
        .about-hero h1 {
            font-size: 36px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .about-hero p {
            font-size: 18px;
            max-width: 700px;
            margin: 0 auto;
            opacity: 0.9;
        }
        
        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* About Sections */
        .about-section {
            margin-bottom: 60px;
        }
        
        .about-section h2 {
            font-size: 28px;
            margin-bottom: 20px;
            color: #2E8B57;
            position: relative;
            padding-bottom: 10px;
        }
        
        .about-section h2::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 80px;
            height: 3px;
            background: #2E8B57;
        }
        
        .about-section p {
            margin-bottom: 20px;
            color: #555;
        }
        
        /* Two Column Layout */
        .two-column {
            display: flex;
            align-items: center;
            gap: 40px;
            flex-wrap: wrap;
        }
        
        .column-text {
            flex: 1;
            min-width: 300px;
        }
        
        .column-image {
            flex: 1;
            min-width: 300px;
        }
        
        .column-image img {
            width: 100%;
            border-radius: 6px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        /* Mission & Values */
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .value-card {
            background: #fff;
            padding: 25px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .value-card:hover {
            transform: translateY(-5px);
        }
        
        .value-icon {
            font-size: 30px;
            color: #2E8B57;
            margin-bottom: 15px;
        }
        
        .value-card h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #333;
        }
        
        /* Team Section */
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .team-member {
            background: #fff;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .team-photo {
            height: 220px;
            overflow: hidden;
        }
        
        .team-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .team-info {
            padding: 20px;
        }
        
        .team-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .team-role {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .team-bio {
            font-size: 14px;
            color: #555;
        }
        
        /* Statistics */
        .stats-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            margin: 40px 0;
        }
        
        .stat-item {
            flex: 1;
            min-width: 220px;
            background: white;
            padding: 25px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #2E8B57;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 16px;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* CTA Section */
        .cta-section {
            background: #6E6E6E;
            color: white;
            padding: 50px 0;
            text-align: center;
            margin: 0;
        }
        
        .cta-content {
            max-width: 700px;
            margin: 0 auto;
        }
        
        .cta-content h2 {
            font-size: 28px;
            margin-bottom: 15px;
        }
        
        .cta-content p {
            margin-bottom: 25px;
            opacity: 0.9;
        }
        
        .cta-buttons {
            margin-top: 20px;
        }
        
        .btn-seller {
            background-color: #2E8B57;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            border: none;
        }
        
        .btn-seller:hover {
            background-color: #246e45;
            transform: translateY(-3px);
        }
        
        /* Profile dropdown */
        .profile-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .profile-link {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .profile-pic {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #fff;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-radius: 8px;
            z-index: 1;
        }
        
        .profile-dropdown:hover .dropdown-content {
            display: block;
        }
        
        .dropdown-item {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
        }
        
        .dropdown-item:hover {
            background-color: #e8f5e9;
            color: #2E8B57;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .two-column {
                flex-direction: column;
            }
            
            .about-hero h1 {
                font-size: 28px;
            }
            
            .about-hero p {
                font-size: 16px;
            }
            
            .stats-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include 'components/navbar.php'; ?>

        <main class="main-content">
            <!-- Hero Section -->
            <section class="about-hero">
                <div class="container">
                    <h1>About ArtiSell</h1>
                    <p>Connecting Artisans with Art Enthusiasts: Preserving Cebuano Culture Through Craftsmanship</p>
                </div>
            </section>

            <div class="container">
                <!-- Our Story -->
                <section class="about-section">
                    <div class="two-column">
                        <div class="column-text">
                            <h2>Our Story</h2>
                            <p>ArtiSell was born out of a deep appreciation for the rich cultural heritage of Cebu and a desire to support local artisans who keep traditional crafts alive. Founded in 2025, our platform serves as a bridge between skilled craftspeople and customers who value authentic, handcrafted products.</p>
                            <p>What started as a small initiative to showcase local crafts at community markets has evolved into a comprehensive online marketplace, connecting artisans from across Cebu with customers from all over the Philippines and beyond.</p>
                            <p>Today, ArtiSell is home to hundreds of vendors offering a diverse range of products - from traditional woven baskets and handcrafted jewelry to local delicacies that represent the unique flavors of Cebu.</p>
                        </div>
                        <div class="column-image">
                            <img src="image/about-story.jpg" alt="ArtiSell Founders with artisans">
                        </div>
                    </div>
                </section>

                <!-- Mission & Values -->
                <section class="about-section">
                    <h2>Our Mission & Values</h2>
                    <p>At ArtiSell, our mission is to preserve and promote Cebuano cultural heritage by providing artisans with a platform to showcase their skills and connect with customers who appreciate their craftsmanship.</p>
                    
                    <div class="values-grid">
                        <div class="value-card">
                            <div class="value-icon">🌱</div>
                            <h3>Authenticity</h3>
                            <p>We celebrate genuine craftsmanship and traditional techniques, ensuring that all products on our platform reflect the true cultural heritage of Cebu.</p>
                        </div>
                        <div class="value-card">
                            <div class="value-icon">🤝</div>
                            <h3>Community Support</h3>
                            <p>We are committed to empowering local artisans by providing them with fair opportunities to showcase and sell their work.</p>
                        </div>
                        <div class="value-card">
                            <div class="value-icon">♻️</div>
                            <h3>Sustainability</h3>
                            <p>We promote environmentally conscious practices, encouraging artisans to use sustainable materials and traditional techniques that have minimal impact on the environment.</p>
                        </div>
                        <div class="value-card">
                            <div class="value-icon">📚</div>
                            <h3>Cultural Preservation</h3>
                            <p>We are dedicated to preserving Cebuano culture by documenting and sharing the stories behind traditional crafts and cultural practices.</p>
                        </div>
                    </div>
                </section>

                <!-- Stats -->
                <div class="stats-container">
                    <div class="stat-item">
                        <div class="stat-number">300+</div>
                        <div class="stat-label">Artisan Partners</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">15+</div>
                        <div class="stat-label">Cebu Cities</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">5,000+</div>
                        <div class="stat-label">Products</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">10K+</div>
                        <div class="stat-label">Happy Customers</div>
                    </div>
                </div>

                <!-- How It Works -->
                <section class="about-section">
                    <div class="two-column">
                        <div class="column-image">
                            <img src="image/about-works.jpg" alt="Artisan creating crafts">
                        </div>
                        <div class="column-text">
                            <h2>How ArtiSell Works</h2>
                            <p>ArtiSell operates on a simple principle: connect artisans directly with customers to create a sustainable ecosystem that benefits everyone involved.</p>
                            <p><strong>For Artisans:</strong> We provide a user-friendly platform where artisans can create their own shops, showcase their products, and reach a wider audience. We handle the technical aspects of online selling, allowing artisans to focus on what they do best - creating beautiful crafts.</p>
                            <p><strong>For Customers:</strong> ArtiSell offers a curated marketplace where you can discover authentic Cebuano products, learn about their cultural significance, and support local artisans directly. Every purchase not only brings home a unique piece of Cebu's cultural heritage but also contributes to sustaining traditional crafts.</p>
                        </div>
                    </div>
                </section>

                <!-- Our Team -->
                <section class="about-section">
                    <h2>Meet Our Team</h2>
                    <p>ArtiSell is run by a passionate team of individuals who are committed to preserving Cebuano culture and supporting local communities.</p>
                    
                    <div class="team-grid">
                        <div class="team-member">
                            <div class="team-photo">
                                <img src="image/team-1.jpg" alt="Maria Santos">
                            </div>
                            <div class="team-info">
                                <h3 class="team-name">Mike Cortez</h3>
                                <div class="team-role">Founder & CEO</div>
                                <p class="team-bio">A Cebu native with a background in cultural anthropology, Maria founded ArtiSell to help preserve traditional crafts she grew up seeing.</p>
                            </div>
                        </div>
                        <div class="team-member">
                            <div class="team-photo">
                                <img src="image/team-2.jpg" alt="Juan Reyes">
                            </div>
                            <div class="team-info">
                                <h3 class="team-name">Daisy Gondao</h3>
                                <div class="team-role">Head of Artisan Relations</div>
                                <p class="team-bio">With deep roots in rural Cebu communities, Juan works directly with artisans to bring their products to the platform.</p>
                            </div>
                        </div>
                        <div class="team-member">
                            <div class="team-photo">
                                <img src="image/team-3.jpg" alt="Elena Cruz">
                            </div>
                            <div class="team-info">
                                <h3 class="team-name">Janneth Traya</h3>
                                <div class="team-role">Creative Director</div>
                                <p class="team-bio">A designer with expertise in traditional Filipino aesthetics, Elena ensures ArtiSell presents local crafts in their best light.</p>
                            </div>
                        </div>
                        <div class="team-member">
                            <div class="team-photo">
                                <img src="image/team-4.jpg" alt="Miguel Lim">
                            </div>
                            <div class="team-info">
                                <h3 class="team-name">San And Guko</h3>
                                <div class="team-role">Tech Lead</div>
                                <p class="team-bio">San And Guko combines his passion for technology with his love for Filipino culture to create a seamless platform experience.</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- CTA Section -->
            <section class="cta-section">
                <div class="container">
                    <div class="cta-content">
                        <h2>Join Our Artisan Community</h2>
                        <p>Are you a local artisan looking to reach more customers? Partner with ArtiSell and showcase your crafts to a wider audience.</p>
                        <div class="cta-buttons">
                            <a href="seller-signup.php" class="btn-seller">Become a Seller</a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <?php include 'components/footer.php'; ?>
        </main>
    </div>
</body>
</html> 