<?php
session_start();
require_once __DIR__ . '/database/auth_helpers.php';
require_once __DIR__ . '/includes/navigation.php';

if (is_authenticated()) {
    $user_type = get_current_user_type();
    switch ($user_type) {
        case 'student':
            header('Location: Dashboards/student_dashboard.php');
            exit;
        case 'employer':
            header('Location: Dashboards/employer_dashboard.php');
            exit;
        case 'admin':
            header('Location: Dashboards/admin_dashboard.php');
            exit;
    }
}

$contact_message = '';
$contact_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($message)) {
        $contact_error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contact_error = 'Please enter a valid email address.';
    } else {
        $contact_message = "Thank you, $name! Your message has been received. We'll get back to you at $email soon.";
        $name = $email = $message = '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Intern Connect | Connecting Talent & Opportunity</title>
  <link rel="icon" type="image/x-icon" href="static/images/title.png">
  <link rel="stylesheet" href="static/css/index.css">
  <?php echo get_navigation_styles(); ?>
  <style>
    .bg-animation {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100vh;
      z-index: -1;
      opacity: 0.3;
      pointer-events: none;
    }
    
    body {
      position: relative;
      z-index: 1;
    }
    
    .hero-section {
      max-width: 1200px;
      margin: 0 auto;
      text-align: center;
      padding: 60px 20px;
    }
    
    .hero-title {
      font-size: 3.5rem;
      font-weight: 300;
      margin-bottom: 30px;
      color: white;
      background: linear-gradient(45deg, #ffffff, #00ff88, #007bff);
      background-clip: text;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      animation: titleGlow 3s ease-in-out infinite alternate;
    }
    
    @keyframes titleGlow {
      from { opacity: 0.8; transform: translateY(0); }
      to { opacity: 1; transform: translateY(-5px); }
    }
    
    .hero-subtitle {
      font-size: 1.3rem;
      opacity: 0.9;
      margin-bottom: 40px;
      max-width: 800px;
      margin-left: auto;
      margin-right: auto;
      line-height: 1.6;
    }
    
    .hero-image {
      width: 100%;
      max-width: 800px;
      height: 300px;
      object-fit: cover;
      border-radius: 20px;
      margin: 40px auto;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      transition: transform 0.3s ease;
    }
    
    .hero-image:hover {
      transform: scale(1.02);
    }
    
    .cta-buttons {
      display: flex;
      gap: 20px;
      justify-content: center;
      flex-wrap: wrap;
      margin-top: 40px;
    }
    
    .cta-btn {
      padding: 15px 30px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      border: 2px solid transparent;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      min-width: 180px;
      justify-content: center;
    }
    
    .cta-primary {
      background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
      color: white;
    }
    
    .cta-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
    }
    
    .cta-secondary {
      background: rgba(255, 255, 255, 0.1);
      color: white;
      border-color: rgba(255, 255, 255, 0.3);
      backdrop-filter: blur(10px);
    }
    
    .cta-secondary:hover {
      background: rgba(255, 255, 255, 0.2);
      transform: translateY(-3px);
    }
    
    .features-section {
      max-width: 1200px;
      margin: 80px auto;
      padding: 0 20px;
    }
    
    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 30px;
      margin-top: 50px;
    }
    
    .feature-card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 40px 30px;
      text-align: center;
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: all 0.3s ease;
    }
    
    .feature-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 40px rgba(0,0,0,0.2);
      border-color: rgba(255, 255, 255, 0.4);
    }
    
    .feature-icon {
      font-size: 4rem;
      margin-bottom: 20px;
      display: block;
    }
    
    .feature-title {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 15px;
      color: white;
    }
    
    .feature-description {
      opacity: 0.9;
      line-height: 1.6;
    }
    
    .testimonials-section {
      max-width: 1200px;
      margin: 80px auto;
      padding: 0 20px;
    }
    
    .testimonials-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
      gap: 30px;
      margin-top: 50px;
    }
    
    .testimonial-card {
      background: rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 30px;
      border: 1px solid rgba(255, 255, 255, 0.1);
      transition: all 0.3s ease;
    }
    
    .testimonial-card:hover {
      transform: translateY(-5px);
      border-color: rgba(255, 255, 255, 0.3);
    }
    
    .testimonial-header {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 20px;
    }
    
    .testimonial-avatar {
      width: 50px;
      height: 50px;
      background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      color: white;
      font-weight: bold;
    }
    
    .testimonial-info h4 {
      margin: 0;
      font-weight: 600;
      color: white;
    }
    
    .testimonial-info p {
      margin: 5px 0 0 0;
      opacity: 0.8;
      font-size: 0.9rem;
    }
    
    .testimonial-quote {
      font-style: italic;
      line-height: 1.6;
      opacity: 0.95;
    }
    
    .contact-section {
      max-width: 600px;
      margin: 80px auto;
      padding: 0 20px;
    }
    
    .section-title {
      font-size: 2.5rem;
      font-weight: 300;
      text-align: center;
      margin-bottom: 20px;
      color: white;
    }
    
    .section-subtitle {
      text-align: center;
      opacity: 0.9;
      margin-bottom: 40px;
      font-size: 1.1rem;
    }
    
    .contact-info {
      display: flex;
      justify-content: center;
      gap: 30px;
      flex-wrap: wrap;
      margin-top: 30px;
      padding: 20px;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 15px;
    }
    
    .contact-info span {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 1rem;
      opacity: 0.9;
    }
    
    @media (max-width: 768px) {
      .hero-title {
        font-size: 2.5rem;
      }
      
      .hero-subtitle {
        font-size: 1.1rem;
      }
      
      .cta-buttons {
        flex-direction: column;
        align-items: center;
      }
      
      .features-grid,
      .testimonials-grid {
        grid-template-columns: 1fr;
      }
      
      .contact-info {
        flex-direction: column;
        text-align: center;
      }
      
      .section-title {
        font-size: 2rem;
      }
    }
  </style>
</head>
<body>
  <!-- Aurora Background Animation -->
  <svg class="bg-animation" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
    <path fill="url(#aurora-gradient)" d="M0,128L60,138.7C120,149,240,171,360,154.7C480,139,600,85,720,96C840,107,960,181,1080,197.3C1200,213,1320,171,1380,149.3L1440,128L1440,0L1380,0C1320,0,1200,0,1080,0C960,0,840,0,720,0C600,0,480,0,360,0C240,0,120,0,60,0L0,0Z"></path>
    <defs>
      <linearGradient id="aurora-gradient" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0%" stop-color="#4f46e5"/>
        <stop offset="50%" stop-color="#06b6d4"/>
        <stop offset="100%" stop-color="#8b5cf6"/>
      </linearGradient>
    </defs>
  </svg>

  <?php echo render_page_header('INTERN CONNECT', 'Connecting Talent & Opportunity'); ?>
  
  <?php echo render_navigation('home'); ?>

  <div class="content">
    <!-- Hero Section -->
    <section class="hero-section">
      <h1 class="hero-title">Connecting Talent & Opportunity</h1>
      <p class="hero-subtitle">
        Empowering students, employers, and administrators to discover, post, and manage internships with ease. 
        Your future career starts here with meaningful connections and valuable experiences.
      </p>
      
      <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=80" 
           alt="Students collaborating and learning" 
           class="hero-image"
           loading="lazy">
      
      <div class="cta-buttons">
        <a href="Authentication/login.php" class="cta-btn cta-primary">
          🚀 Get Started
        </a>
        <a href="#features" class="cta-btn cta-secondary">
          📖 Learn More
        </a>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
      <h2 class="section-title">Why Choose Intern Connect?</h2>
      <p class="section-subtitle">
        Discover the features that make finding and managing internships effortless
      </p>
      
      <div class="features-grid">
        <div class="feature-card">
          <span class="feature-icon">🎓</span>
          <h3 class="feature-title">For Students</h3>
          <p class="feature-description">
            Browse exciting internship opportunities, apply with ease, and track your applications. 
            Build your professional network and gain valuable work experience.
          </p>
        </div>
        
        <div class="feature-card">
          <span class="feature-icon">🏢</span>
          <h3 class="feature-title">For Employers</h3>
          <p class="feature-description">
            Post internship positions, manage applications efficiently, and discover talented students. 
            Streamline your recruitment process with our intuitive platform.
          </p>
        </div>
        
        <div class="feature-card">
          <span class="feature-icon">⚙️</span>
          <h3 class="feature-title">For Administrators</h3>
          <p class="feature-description">
            Oversee the entire internship ecosystem, manage users, monitor activities, and ensure 
            quality connections between students and employers.
          </p>
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section">
      <h2 class="section-title">What People Are Saying</h2>
      <p class="section-subtitle">
        Real experiences from our community members
      </p>
      
      <div class="testimonials-grid">
        <div class="testimonial-card">
          <div class="testimonial-header">
            <div class="testimonial-avatar">AM</div>
            <div class="testimonial-info">
              <h4>Aisha Mwangi</h4>
              <p>Computer Science Student, Strathmore University</p>
            </div>
          </div>
          <div class="testimonial-quote">
            "Intern Connect made it so easy to find and apply for internships. The platform is intuitive, 
            and I landed my dream software development role in just two weeks!"
          </div>
        </div>
        
        <div class="testimonial-card">
          <div class="testimonial-header">
            <div class="testimonial-avatar">DK</div>
            <div class="testimonial-info">
              <h4>David Kimani</h4>
              <p>HR Manager, TechNova Solutions</p>
            </div>
          </div>
          <div class="testimonial-quote">
            "We received high-quality applications from motivated students. The platform streamlined 
            our hiring process and helped us find exceptional talent faster than ever."
          </div>
        </div>
        
        <div class="testimonial-card">
          <div class="testimonial-header">
            <div class="testimonial-avatar">GO</div>
            <div class="testimonial-info">
              <h4>Grace Otieno</h4>
              <p>System Administrator</p>
            </div>
          </div>
          <div class="testimonial-quote">
            "Managing users and internship postings is incredibly efficient. The admin dashboard 
            provides excellent oversight and has streamlined our entire internship program."
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section">
      <div class="content-card">
        <h2 class="section-title">Get In Touch</h2>
        <p class="section-subtitle">
          Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.
        </p>
        
        <?php if ($contact_message): ?>
          <?php echo render_message($contact_message, 'success'); ?>
        <?php endif; ?>
        
        <?php if ($contact_error): ?>
          <?php echo render_message($contact_error, 'error'); ?>
        <?php endif; ?>
        
        <form method="POST" class="register-form">
          <input type="hidden" name="contact_submit" value="1">
          
          <div class="input-group">
            <div class="input-container">
              <span class="input-icon">👤</span>
              <input type="text" name="name" class="form-input" 
                     placeholder="Your Full Name" 
                     value="<?php echo htmlspecialchars($name ?? ''); ?>" 
                     required>
            </div>
          </div>
          
          <div class="input-group">
            <div class="input-container">
              <span class="input-icon">📧</span>
              <input type="email" name="email" class="form-input" 
                     placeholder="Your Email Address" 
                     value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                     required>
            </div>
          </div>
          
          <div class="input-group">
            <div class="input-container">
              <span class="input-icon">💬</span>
              <textarea name="message" class="form-textarea" 
                        placeholder="Your Message..." 
                        rows="5" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
            </div>
          </div>
          
          <div class="button-container">
            <button type="submit" class="submit-btn">
              📤 Send Message
            </button>
          </div>
        </form>
        
        <div class="contact-info">
          <span>📧 info@internconnect.co.ke</span>
          <span>📞 +254 700 123 456</span>
          <span>🏢 Nairobi, Kenya</span>
        </div>
      </div>
    </section>
  </div>

  <footer class="footer">
    Intern Connect &copy; <?php echo date('Y'); ?> | All Rights Reserved | 
    <a href="Authentication/login.php" style="color: rgba(255,255,255,0.8); text-decoration: none;">
      Sign In
    </a>
  </footer>

  <script>
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });

    document.addEventListener('DOMContentLoaded', function() {
      const form = document.querySelector('form[method="POST"]');
      const submitBtn = form?.querySelector('.submit-btn');
      
      if (form && submitBtn) {
        form.addEventListener('submit', function() {
          submitBtn.innerHTML = '⏳ Sending...';
          submitBtn.disabled = true;
        });
      }

      document.querySelectorAll('.cta-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
          if (this.href.includes('login.php')) {
            this.innerHTML = '⏳ Loading...';
          }
        });
      });
    });
  </script>
</body>
</html>
