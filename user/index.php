<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already authenticated, redirect them straight to the dashboard
if (isset($_SESSION['user_token'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to CarbonWise - BatStateU Sustainability Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-body: #F3F4F6;
            --bg-card: #ffffff;
            --text-main: #1F2937;
            --text-muted: #6B7280;
            --border-color: #E5E7EB;
            --accent-green: #2D6A4F;
            --accent-green-hover: #22513B;
            --accent-light: #e2f0d9;
            --hero-gradient: linear-gradient(135deg, #1B4332 0%, #2D6A4F 100%);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden; }

        /* Navigation Bar */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 8%;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .brand-box { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .brand-logo-container { width: 40px; height: 40px; border-radius: 50%; background-color: var(--accent-light); display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid var(--accent-green); }
        .brand-logo-container img { width: 85%; height: 85%; object-fit: contain; }
        .logo-text { font-size: 1.25rem; font-weight: 800; letter-spacing: 0.5px; color: var(--text-main); }

        /* Hero Section */
        .hero-section {
            background: var(--hero-gradient);
            color: #ffffff;
            padding: 90px 8% 110px 8%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 50px;
            position: relative;
            clip-path: ellipse(150% 100% at 50% 0%);
        }

        .hero-content { flex: 1; max-width: 650px; }
        .hero-badge { display: inline-flex; align-items: center; gap: 8px; background: rgba(255, 255, 255, 0.15); padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; margin-bottom: 20px; backdrop-filter: blur(5px); }
        .hero-content h1 { font-size: 3rem; font-weight: 800; line-height: 1.2; margin-bottom: 20px; }
        .hero-content p { font-size: 1.05rem; line-height: 1.6; color: rgba(255, 255, 255, 0.85); margin-bottom: 35px; }

        .hero-buttons { display: flex; gap: 15px; }
        .btn-hero-primary { padding: 14px 28px; background: #ffffff; color: #1B4332; font-weight: 700; border-radius: 8px; text-decoration: none; font-size: 1rem; box-shadow: 0 4px 14px rgba(0,0,0,0.1); display: inline-flex; align-items: center; gap: 10px; }
        .btn-hero-primary:hover { background: #f0f0f0; }

        .btn-hero-outline { padding: 14px 28px; background: transparent; color: #ffffff; font-weight: 700; border: 2px solid rgba(255, 255, 255, 0.6); border-radius: 8px; text-decoration: none; font-size: 1rem; display: inline-flex; align-items: center; gap: 10px; }
        .btn-hero-outline:hover { border-color: #ffffff; background: rgba(255,255,255,0.05); }

        .hero-card-preview { flex: 1; display: flex; justify-content: center; }
        .preview-box { background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); padding: 30px; border-radius: 16px; backdrop-filter: blur(10px); width: 100%; max-width: 420px; display: flex; flex-direction: column; gap: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .preview-metric { display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.2); padding: 15px 20px; border-radius: 10px; }
        .preview-metric span:first-child { font-size: 0.9rem; font-weight: 500; color: rgba(255,255,255,0.8); }
        .preview-metric span:last-child { font-size: 1.2rem; font-weight: 700; color: #52B788; }

        /* Features Grid */
        .features-section { padding: 80px 8%; }
        .section-header { text-align: center; max-width: 600px; margin: 0 auto 50px auto; }
        .section-header h2 { font-size: 2.2rem; font-weight: 800; color: var(--text-main); margin-bottom: 15px; }
        .section-header p { font-size: 0.95rem; color: var(--text-muted); line-height: 1.5; }

        .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; }
        @media(max-width: 900px) { 
            .features-grid { grid-template-columns: 1fr; } 
            .hero-section { flex-direction: column; text-align: center; clip-path: none; } 
            .hero-buttons { justify-content: center; } 
        }

        .feature-card { background: var(--bg-card); border: 1px solid var(--border-color); padding: 35px 30px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .feature-icon { width: 50px; height: 50px; border-radius: 12px; background: var(--accent-light); color: var(--accent-green); display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 20px; }
        .feature-card h3 { font-size: 1.15rem; font-weight: 700; color: var(--text-main); margin-bottom: 10px; }
        .feature-card p { font-size: 0.9rem; color: var(--text-muted); line-height: 1.5; }

        /* Footer */
        footer { background: var(--bg-card); border-top: 1px solid var(--border-color); padding: 30px 8%; text-align: center; font-size: 0.85rem; color: var(--text-muted); margin-top: auto; }
    </style>
</head>
<body>

    <!-- Top Navigation -->
    <nav>
        <a href="index.php" class="brand-box">
            <div class="brand-logo-container">
                <img src="logo.png" alt="CarbonWise Logo">
            </div>
            <span class="logo-text">CARBONWISE</span>
        </a>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <div class="hero-badge"><i class="fa-solid fa-leaf"></i> Empowering Sustainable Campuses</div>
            <h1>Track, Analyze, & Reduce Your Carbon Footprint</h1>
            <p>Welcome to CarbonWise. Monitor campus transport routing, evaluate office appliance energy consumption, log meal emissions, and collaborate toward a greener academic ecosystem.</p>
            <div class="hero-buttons">
                <a href="login.php" class="btn-hero-primary"><i class="fa-solid fa-right-to-bracket"></i> Sign In to Account</a>
                <a href="register.php" class="btn-hero-outline"><i class="fa-solid fa-user-plus"></i> Register Now</a>
            </div>
        </div>
        <div class="hero-card-preview">
            <div class="preview-box">
                <h3 style="color: #ffffff; font-size: 1.1rem; border-bottom: 1px solid rgba(255,255,255,0.15); padding-bottom: 10px;">
                    <i class="fa-solid fa-chart-line" style="color: #52B788;"></i> Live Metrics Preview
                </h3>
                <div class="preview-metric">
                    <span>Green Points Scale</span>
                    <span>94 / 100</span>
                </div>
                <div class="preview-metric">
                    <span>Weekly Transport Impact</span>
                    <span>14.2 kg CO₂e</span>
                </div>
                <div class="preview-metric">
                    <span>Office Resource Tracking</span>
                    <span>Active</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="section-header">
            <h2>Built for Environmental Accountability</h2>
            <p>Explore features designed to make tracking emissions straightforward, fast, and precise across departments.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-map-location-dot"></i></div>
                <h3>Interactive Route Planner</h3>
                <p>Calculate precise road driving distances automatically between user points and university campuses using integrated mapping tools.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-bolt"></i></div>
                <h3>Resource Energy Logs</h3>
                <p>Estimate appliance and IT hardware energy consumption instantly based on active hourly duration parameters.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-lightbulb"></i></div>
                <h3>Mitigation & AI Recommendations</h3>
                <p>Receive intelligent, data-driven action plans, custom footprint reduction strategies, and automated tips to actively lower your emissions.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> CarbonWise Platform. All rights reserved.</p>
    </footer>

</body>
</html>