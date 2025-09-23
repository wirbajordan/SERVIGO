<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
$db = getDB();
// Fetch latest service categories
$latest_categories = [];
$stmt = $db->prepare("SELECT * FROM service_categories WHERE is_active = 1 ORDER BY created_at DESC LIMIT 4");
$stmt->execute();
$latest_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Fetch latest providers
$latest_providers = $db->query("SELECT u.first_name, u.last_name, u.city, u.region, u.profile_image, sp.business_name FROM service_providers sp JOIN users u ON sp.user_id = u.id ORDER BY sp.created_at DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ServiGo - Smart Local Services in Cameroon</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .carousel-caption {
            background: linear-gradient(90deg, rgba(0,0,0,0.7) 60%, rgba(0,0,0,0.2) 100%);
            border-radius: 10px;
            padding: 1.5rem 2rem;
            left: 5%;
            right: 5%;
            bottom: 2rem;
            text-align: left;
            animation: fadeInUp 1s;
        }
        .carousel-item img {
            object-fit: cover;
            height: 280px;
            width: 100%;
        }
        .carousel-service-icon {
            font-size: 2.2rem;
            margin-right: 0.7rem;
            vertical-align: middle;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 768px) {
            .carousel-caption { padding: 1rem; font-size: 0.95rem; }
            .carousel-item img { height: 160px; }
        }
        .hero { overflow: hidden; }
        .hero .content-col { position: relative; z-index: 2; }
        .hero-images { display: flex; gap: 12px; justify-content: flex-end; align-items: center; flex-wrap: wrap; }
        .hero-images img { border-radius: 8px; box-shadow: 0 6px 18px rgba(0,0,0,0.15); height: 200px; width: auto; max-width: 48%; }
        @media (max-width: 992px) { .hero-images img { height: 180px; max-width: 48%; } }
        @media (max-width: 768px) { .hero-images { justify-content: center; } .hero-images img { height: 120px; max-width: 48%; } }
    </style>
</head>
<body class="bg-light">
    <!-- Header -->
    <?php include 'includes/site_header.php'; ?>
    <!-- Hero Section -->
    <section class="hero py-5 bg-primary-servigo text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 content-col">
                    <h1 class="display-4 fw-bold mb-3">Find Local Services in Cameroon</h1>
                    <p class="lead mb-4">Connect with trusted professionals for all your service needs - from electricians to tutors, mechanics to cleaners.</p>
                    <a href="services.php" class="btn btn-servigo btn-lg me-2 mb-2" style="font-size:1.25rem; padding:0.75em 2em;">Find Services</a>
                    <a href="register.php" class="btn btn-success btn-lg mb-2" style="font-size:1.25rem; padding:0.75em 2em;">Become a Provider</a>
                </div>
                <div class="col-md-6 text-center">
                    <div class="hero-images">
                        <img src="assets/images/electrician.jpeg" alt="Electrician at work - Local Services" class="img-fluid">
                        <img src="assets/images/cleaning services.jpeg" alt="Cleaning service - Home and office cleaning" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Carousel Section -->
<div id="featureCarousel" class="carousel slide mb-5" data-bs-ride="carousel" data-bs-interval="3500" data-bs-pause="hover">
  <div class="carousel-indicators">
    <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
    <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
    <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
    <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="3" aria-label="Slide 4"></button>
    <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="4" aria-label="Slide 5"></button>
    <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="5" aria-label="Slide 6"></button>
    <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="6" aria-label="Slide 7"></button>
    <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="7" aria-label="Slide 8"></button>
    <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="8" aria-label="Slide 9"></button>
    <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="9" aria-label="Slide 10"></button>
    <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="10" aria-label="Slide 11"></button>
    <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="11" aria-label="Slide 12"></button>
    <button type="button" data-bs-target="#featureCarousel" data-bs-slide-to="12" aria-label="Slide 13"></button>
  </div>
  <div class="carousel-inner">
    <div class="carousel-item active">
      <div class="row justify-content-center align-items-end">
        <div class="col-md-6 text-center">
          <img src="assets/images/tailoring1.jpeg" alt="Tailoring service - Custom clothes" class="carousel-img" />
          <div class="carousel-desc mt-3">
            <span class="carousel-service-icon"><i class="fas fa-cut"></i></span>
            <h5 class="d-inline">Tailoring</h5>
            <p>Get custom clothes made or repaired by local tailoring experts.</p>
            <a href="services.php?category=Tailoring" class="btn btn-light btn-sm mt-2">Learn More</a>
          </div>
        </div>
        <div class="col-md-6 text-center">
          <img src="assets/images/plumbering1.jpeg" alt="Plumbering service - Fixing pipes" class="carousel-img" />
          <div class="carousel-desc mt-3">
            <span class="carousel-service-icon"><i class="fas fa-wrench"></i></span>
            <h5 class="d-inline">Plumbering</h5>
            <p>Fix leaks, install pipes, and solve all your plumbing issues quickly.</p>
            <a href="services.php?category=Plumbering" class="btn btn-light btn-sm mt-2">Learn More</a>
          </div>
        </div>
      </div>
    </div>
    <div class="carousel-item">
      <div class="row justify-content-center align-items-end">
        <div class="col-md-6 text-center">
          <img src="assets/images/mechanic.jpeg" alt="Mechanic service - Car repair" class="carousel-img" />
          <div class="carousel-desc mt-3">
            <span class="carousel-service-icon"><i class="fas fa-car"></i></span>
            <h5 class="d-inline">Mechanic</h5>
            <p>Professional mechanics for car repairs and maintenance at your convenience.</p>
            <a href="services.php?category=Mechanic" class="btn btn-light btn-sm mt-2">Learn More</a>
          </div>
        </div>
        <div class="col-md-6 text-center">
          <img src="assets/images/capenters.jpeg" alt="Carpentry service - Woodwork" class="carousel-img" />
          <div class="carousel-desc mt-3">
            <span class="carousel-service-icon"><i class="fas fa-hammer"></i></span>
            <h5 class="d-inline">Carpentry</h5>
            <p>Custom furniture, repairs, and woodwork by skilled carpenters.</p>
            <a href="services.php?category=Carpentry" class="btn btn-light btn-sm mt-2">Learn More</a>
          </div>
        </div>
      </div>
    </div>
    <div class="carousel-item">
      <div class="row justify-content-center align-items-end">
        <div class="col-md-6 text-center">
          <img src="assets/images/delivery services.jpeg" alt="Delivery service - Package delivery" class="carousel-img" />
          <div class="carousel-desc mt-3">
            <span class="carousel-service-icon"><i class="fas fa-truck"></i></span>
            <h5 class="d-inline">Delivery</h5>
            <p>Fast and reliable delivery services for your packages and goods.</p>
            <a href="services.php?category=Delivery" class="btn btn-light btn-sm mt-2">Learn More</a>
          </div>
        </div>
        <div class="col-md-6 text-center">
          <img src="assets/images/catering1.jpeg" alt="Catering service - Event food" class="carousel-img" />
          <div class="carousel-desc mt-3">
            <span class="carousel-service-icon"><i class="fas fa-utensils"></i></span>
            <h5 class="d-inline">Catering</h5>
            <p>Delicious catering for your events, parties, and special occasions.</p>
            <a href="services.php?category=Catering" class="btn btn-light btn-sm mt-2">Learn More</a>
          </div>
        </div>
      </div>
    </div>
    <div class="carousel-item">
      <div class="row justify-content-center align-items-end">
        <div class="col-md-6 text-center">
          <img src="assets/images/electrician1.jpeg" alt="Electrician service - Electrical work" class="carousel-img" />
          <div class="carousel-desc mt-3">
            <span class="carousel-service-icon"><i class="fas fa-bolt"></i></span>
            <h5 class="d-inline">Electrician</h5>
            <p>Expert electrical installations, repairs, and troubleshooting for your home or business.</p>
            <a href="services.php?category=Electrician" class="btn btn-light btn-sm mt-2">Learn More</a>
          </div>
        </div>
        <div class="col-md-6 text-center">
          <img src="assets/images/hair-styling.jpeg" alt="Hair Styling service - Salon" class="carousel-img" />
          <div class="carousel-desc mt-3">
            <span class="carousel-service-icon"><i class="fas fa-scissors"></i></span>
            <h5 class="d-inline">Hair Styling</h5>
            <p>Professional haircuts, styling, and grooming for all ages and occasions.</p>
            <a href="services.php?category=Hair Styling" class="btn btn-light btn-sm mt-2">Learn More</a>
          </div>
        </div>
      </div>
    </div>
    <div class="carousel-item">
      <div class="row justify-content-center align-items-end">
        <div class="col-md-6 text-center">
          <img src="assets/images/painting.jpeg" alt="Painting service - House painting" class="carousel-img" />
          <div class="carousel-desc mt-3">
            <span class="carousel-service-icon"><i class="fas fa-paint-roller"></i></span>
            <h5 class="d-inline">Painting</h5>
            <p>Quality painting services for homes, offices, and commercial spaces.</p>
            <a href="services.php?category=Painting" class="btn btn-light btn-sm mt-2">Learn More</a>
          </div>
        </div>
        <div class="col-md-6 text-center">
          <img src="assets/images/photographer.jpeg" alt="Photographer service - Event photography" class="carousel-img" />
          <div class="carousel-desc mt-3">
            <span class="carousel-service-icon"><i class="fas fa-camera"></i></span>
            <h5 class="d-inline">Photographer</h5>
            <p>Capture your special moments with professional photography services.</p>
            <a href="services.php?category=Photographer" class="btn btn-light btn-sm mt-2">Learn More</a>
          </div>
        </div>
      </div>
    </div>
    <div class="carousel-item">
      <div class="row justify-content-center align-items-end">
        <div class="col-md-6 text-center mx-auto">
          <img src="assets/images/Pet care.jpeg" alt="Pet Care service - Animal care" class="carousel-img" />
          <div class="carousel-desc mt-3">
            <span class="carousel-service-icon"><i class="fas fa-paw"></i></span>
            <h5 class="d-inline">Pet Care</h5>
            <p>Quality care and grooming for your beloved pets.</p>
            <a href="services.php?category=Pet Care" class="btn btn-light btn-sm mt-2">Learn More</a>
          </div>
        </div>
        <div class="col-md-6 text-center">
          <img src="assets/images/tutoring1.jpeg" alt="Tutoring service - Education" class="carousel-img" />
          <div class="carousel-desc mt-3">
            <span class="carousel-service-icon"><i class="fas fa-graduation-cap"></i></span>
            <h5 class="d-inline">Tutoring</h5>
            <p>Personalized tutoring for all subjects and levels to help you succeed.</p>
            <a href="services.php?category=Tutoring" class="btn btn-light btn-sm mt-2">Learn More</a>
          </div>
        </div>
      </div>
    </div>
    <div class="carousel-item">
      <div class="row justify-content-center align-items-end">
        <div class="col-md-6 text-center">
          <img src="assets/images/cleaning services.jpeg" alt="Cleaning service - Home and office cleaning" class="carousel-img" />
          <div class="carousel-desc mt-3">
            <span class="carousel-service-icon"><i class="fas fa-broom"></i></span>
            <h5 class="d-inline">Cleaning</h5>
            <p>Professional cleaning for homes, offices, and commercial spaces.</p>
            <a href="services.php?category=Cleaning" class="btn btn-light btn-sm mt-2">Learn More</a>
          </div>
        </div>
        <div class="col-md-6 text-center">
          <img src="assets/images/security.jpeg" alt="Security service - Guard and surveillance" class="carousel-img" />
          <div class="carousel-desc mt-3">
            <span class="carousel-service-icon"><i class="fas fa-shield-alt"></i></span>
            <h5 class="d-inline">Security</h5>
            <p>Reliable security services for your property and events.</p>
            <a href="services.php?category=Security" class="btn btn-light btn-sm mt-2">Learn More</a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#featureCarousel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Previous</span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#featureCarousel" data-bs-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Next</span>
  </button>
</div>
    <!-- Services Section -->
    <section class="services-section py-5 bg-light">
        <div class="container">
            <h2 class="section-title text-center mb-5">Popular Services</h2>
            <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                    <div class="card card-servigo text-center p-4 h-100">
                        <i class="fas fa-bolt fa-2x text-primary-servigo mb-3"></i>
                        <h3 class="h5">Electricians</h3>
                        <p>Professional electrical services for your home and business</p>
                        <a href="services.php?category=electrician" class="service-link">Find Electricians</a>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card card-servigo text-center p-4 h-100">
                        <i class="fas fa-wrench fa-2x text-primary-servigo mb-3"></i>
                        <h3 class="h5">Plumbing</h3>
                        <p>Expert plumbing and water system services</p>
                        <a href="services.php?category=plumbering" class="service-link">Find Plumbers</a>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card card-servigo text-center p-4 h-100">
                        <i class="fas fa-graduation-cap fa-2x text-primary-servigo mb-3"></i>
                        <h3 class="h5">Tutors</h3>
                        <p>Qualified tutors for all subjects and levels</p>
                        <a href="services.php?category=tutoring" class="service-link">Find Tutors</a>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card card-servigo text-center p-4 h-100">
                        <i class="fas fa-car fa-2x text-primary-servigo mb-3"></i>
                        <h3 class="h5">Mechanics</h3>
                        <p>Professional auto repair and maintenance</p>
                        <a href="services.php?category=mechanic" class="service-link">Find Mechanics</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Features Section -->
    <section class="features-section py-5 bg-light">
        <div class="container">
            <h2 class="section-title text-center mb-5">Why Choose ServiGo?</h2>
            <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                    <div class="card card-servigo text-center p-4 h-100">
                        <i class="fas fa-shield-alt fa-2x text-primary-servigo mb-3"></i>
                        <h3 class="h5">Verified Providers</h3>
                        <p>All service providers are verified and background-checked for your safety</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card card-servigo text-center p-4 h-100">
                        <i class="fas fa-clock fa-2x text-primary-servigo mb-3"></i>
                        <h3 class="h5">Quick Response</h3>
                        <p>Get responses from providers within minutes of your request</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card card-servigo text-center p-4 h-100">
                        <i class="fas fa-star fa-2x text-primary-servigo mb-3"></i>
                        <h3 class="h5">Quality Guaranteed</h3>
                        <p>Rate and review providers to ensure quality service</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card card-servigo text-center p-4 h-100">
                        <i class="fas fa-map-marker-alt fa-2x text-primary-servigo mb-3"></i>
                        <h3 class="h5">Local Services</h3>
                        <p>Find services in your neighborhood across Cameroon</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Latest Services Section -->
    <section class="latest-services-section py-5 bg-white">
        <div class="container">
            <h2 class="section-title text-center mb-5">Latest Services</h2>
            <div class="row g-4">
                <?php foreach ($latest_categories as $cat): ?>
                <div class="col-md-3 col-sm-6">
                    <div class="card card-servigo text-center p-4 h-100">
                        <i class="<?php echo htmlspecialchars($cat['icon']); ?> fa-2x text-primary-servigo mb-3"></i>
                        <h3 class="h5"><?php echo htmlspecialchars($cat['name']); ?></h3>
                        <p><?php echo htmlspecialchars($cat['description']); ?></p>
                        <a href="services.php?category=<?php echo $cat['id']; ?>" class="service-link">Explore</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <!-- Latest Providers Section -->
    <section class="latest-providers-section py-5 bg-light">
        <div class="container">
            <h2 class="section-title text-center mb-5">Newest Providers</h2>
            <div class="row g-4">
                <?php foreach ($latest_providers as $prov): ?>
                <div class="col-md-3 col-sm-6">
                    <div class="card card-servigo text-center p-4 h-100">
                        <img src="<?php echo $prov['profile_image'] ? htmlspecialchars($prov['profile_image']) : 'assets/images/default-profile.png'; ?>" class="rounded-circle mb-3" style="width:60px;height:60px;object-fit:cover;" alt="Provider profile image">
                        <h3 class="h6 mb-1">
                            <?php echo htmlspecialchars($prov['first_name'] . ' ' . $prov['last_name']); ?>
                            <span class="verified-badge" title="Verified"><i class="fas fa-check"></i></span>
                        </h3>
                        <div class="text-muted mb-1"><?php echo htmlspecialchars($prov['business_name']); ?></div>
                        <div class="small text-muted"><?php echo htmlspecialchars($prov['city']); ?>, <?php echo htmlspecialchars($prov['region']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <!-- Blog/News Highlights Section -->
    <section class="blog-section py-5 bg-white">
        <div class="container">
            <h2 class="section-title text-center mb-5">Latest News & Tips</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 p-3">
                        <h5>How to Choose the Right Service Provider</h5>
                        <p class="text-muted">Tips for finding the best fit for your needs and ensuring quality service every time.</p>
                        <a href="#" class="btn btn-link">Read More</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 p-3">
                        <h5>Why Reviews Matter</h5>
                        <p class="text-muted">Learn how customer feedback helps keep our platform safe and reliable for everyone.</p>
                        <a href="#" class="btn btn-link">Read More</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 p-3">
                        <h5>ServiGo Mobile App Coming Soon!</h5>
                        <p class="text-muted">Stay tuned for our app launch and enjoy even more convenience on the go.</p>
                        <a href="#" class="btn btn-link">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- App Download/Coming Soon Section -->
    <section class="app-section py-5 bg-primary-servigo text-white text-center">
        <div class="container">
            <h2 class="mb-4">Get the ServiGo App</h2>
            <p class="mb-4">Coming soon to Android and iOS for even easier access to trusted local services.</p>
            <a href="#" class="btn btn-light btn-lg disabled me-2 mb-2"><i class="fab fa-android me-2"></i>Google Play</a>
            <a href="#" class="btn btn-light btn-lg disabled mb-2"><i class="fab fa-apple me-2"></i>App Store</a>
        </div>
    </section>
    <!-- How It Works Section -->
    <section class="how-it-works-section py-5 bg-white">
        <div class="container">
            <h2 class="section-title text-center mb-5">How It Works</h2>
            <div class="row g-4 text-center">
                <div class="col-md-3">
                    <div class="p-4">
                        <i class="fas fa-search fa-2x text-primary-servigo mb-3"></i>
                        <h5>1. Browse Services</h5>
                        <p>Find the service you need from our wide range of categories.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-4">
                        <i class="fas fa-user-check fa-2x text-primary-servigo mb-3"></i>
                        <h5>2. Choose a Provider</h5>
                        <p>Compare verified providers, read reviews, and select the best fit.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-4">
                        <i class="fas fa-calendar-check fa-2x text-primary-servigo mb-3"></i>
                        <h5>3. Book & Schedule</h5>
                        <p>Request the service and schedule at your convenience.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-4">
                        <i class="fas fa-smile-beam fa-2x text-primary-servigo mb-3"></i>
                        <h5>4. Enjoy & Rate</h5>
                        <p>Enjoy quality service and leave a review for your provider.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Testimonials Section -->
    <section class="testimonials-section py-5 bg-light">
        <div class="container">
            <h2 class="section-title text-center mb-5">What Our Users Say</h2>
            <div id="testimonialCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <div class="row justify-content-center">
                            <div class="col-md-8 text-center">
                                <blockquote class="blockquote">
                                    <p class="mb-4">“ServiGo made it so easy to find a reliable electrician. The process was smooth and the provider was professional!”</p>
                                    <footer class="blockquote-footer">Marie, Yaoundé</footer>
                                </blockquote>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="row justify-content-center">
                            <div class="col-md-8 text-center">
                                <blockquote class="blockquote">
                                    <p class="mb-4">“I love how quickly I got responses from providers. Highly recommend ServiGo to anyone!”</p>
                                    <footer class="blockquote-footer">Jean, Douala</footer>
                                </blockquote>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="row justify-content-center">
                            <div class="col-md-8 text-center">
                                <blockquote class="blockquote">
                                    <p class="mb-4">“As a provider, ServiGo helped me grow my business and connect with more clients.”</p>
                                    <footer class="blockquote-footer">Amin, Bafoussam</footer>
                                </blockquote>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
    </section>
    <!-- Call to Action Section -->
    <section class="cta-section py-5 bg-primary-servigo text-white text-center">
        <div class="container">
            <h2 class="mb-4">Ready to get started?</h2>
            <a href="register.php" class="btn btn-success btn-lg me-3 mb-2" style="font-size:1.25rem; padding:0.75em 2em;">Become a Provider</a>
            <a href="services.php" class="btn btn-outline-light btn-lg mb-2" style="font-size:1.25rem; padding:0.75em 2em;">Find a Service</a>
        </div>
    </section>
    <!-- FAQ Section -->
    <section class="faq-section py-5 bg-white">
        <div class="container">
            <h2 class="section-title text-center mb-5">Frequently Asked Questions</h2>
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq1">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="true" aria-controls="collapse1">
                            How do I book a service?
                        </button>
                    </h2>
                    <div id="collapse1" class="accordion-collapse collapse show" aria-labelledby="faq1" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Simply browse our services, choose a provider, and click “Book Service” to schedule.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq2">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                            How are providers verified?
                        </button>
                    </h2>
                    <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="faq2" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            All providers go through a verification process including ID and background checks.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq3">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                            Is ServiGo free to use?
                        </button>
                    </h2>
                    <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="faq3" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes! Browsing and requesting services is free for customers. Providers may pay a small fee to join.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq4">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
                            How do I contact support?
                        </button>
                    </h2>
                    <div id="collapse4" class="accordion-collapse collapse" aria-labelledby="faq4" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            You can reach us via the <a href="contact.php">contact form</a> or email info@servigo.com.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Footer -->
    <footer class="footer bg-neutral-dark text-white mt-5">
        <div class="container py-4">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="fw-bold"><i class="fas fa-tools me-2"></i>ServiGo</h3>
                    <p>Connecting Cameroon with trusted local service providers.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; 2024 ServiGo. All rights reserved. | Made for Cameroon</p>
                </div>
            </div>
        </div>
    </footer>
    <script src="assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
// Ensure carousel autoplay and pause on hover
$('#featureCarousel').carousel({
  interval: 3500,
  pause: 'hover'
});
</script>
</body>
</html> 