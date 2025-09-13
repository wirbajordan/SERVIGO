<?php
// Prevent direct access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'servigo_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to create a new user and service provider
function createServiceProvider($pdo, $data) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert user
        $stmt = $pdo->prepare("INSERT INTO users 
            (username, email, password, first_name, last_name, phone, address, city, region, profile_image, user_type, is_verified, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'provider', TRUE, TRUE)");
        
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        $stmt->execute([
            $data['username'],
            $data['email'],
            $hashedPassword,
            $data['first_name'],
            $data['last_name'],
            $data['phone'],
            $data['address'],
            $data['city'],
            $data['region'],
            $data['profile_image']
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Insert service provider
        $stmt = $pdo->prepare("INSERT INTO service_providers 
            (user_id, business_name, business_description, experience_years, hourly_rate, daily_rate, is_available, rating)
            VALUES (?, ?, ?, ?, ?, ?, TRUE, ?)");
            
        $stmt->execute([
            $userId,
            $data['business_name'],
            $data['business_description'],
            $data['experience_years'],
            $data['hourly_rate'],
            $data['daily_rate'],
            $data['rating']
        ]);
        
        $providerId = $pdo->lastInsertId();
        
        // Add provider services (categories)
        foreach ($data['categories'] as $categoryId => $serviceData) {
            $stmt = $pdo->prepare("INSERT INTO provider_services 
                (provider_id, category_id, description, price, is_active)
                VALUES (?, ?, ?, ?, TRUE)");
                
            $stmt->execute([
                $providerId,
                $categoryId,
                $serviceData['description'],
                $serviceData['price']
            ]);
        }
        
        // Add availability (available weekdays 9-5)
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $stmt = $pdo->prepare("INSERT INTO provider_availability 
            (provider_id, day_of_week, start_time, end_time, is_available)
            VALUES (?, ?, '09:00:00', '17:00:00', TRUE)");
            
        foreach ($days as $day) {
            $stmt->execute([$providerId, $day]);
        }
        
        $pdo->commit();
        return $providerId;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Sample service providers data - Expanded list with more providers across different cities and categories
$providers = [
    // Yaoundé Providers
    [
        'username' => 'yao_electro',
        'email' => 'yao_electro@example.com',
        'first_name' => 'Jean',
        'last_name' => 'Mbarga',
        'phone' => '677112233',
        'address' => 'Rue Nlongkak',
        'city' => 'Yaoundé',
        'region' => 'Centre',
        'profile_image' => 'electrician2.jpeg',
        'business_name' => 'Nlongkak Electric',
        'business_description' => 'Certified electrician serving Nlongkak and surrounding areas. Specializing in home and office electrical installations.',
        'experience_years' => 7,
        'hourly_rate' => 5500,
        'daily_rate' => 38000,
        'rating' => 4.6,
        'categories' => [1 => ['description' => 'Residential and commercial electrical work', 'price' => 5500]]
    ],
    [
        'username' => 'bastos_plumb',
        'email' => 'bastos_plumb@example.com',
        'first_name' => 'François',
        'last_name' => 'Ndong',
        'phone' => '677223344',
        'address' => 'Bastos Roundabout',
        'city' => 'Yaoundé',
        'region' => 'Centre',
        'profile_image' => 'plumbering.jpeg',
        'business_name' => 'Bastos Plumbing Experts',
        'business_description' => '24/7 emergency plumbing services in Bastos and nearby neighborhoods. Fast response time.',
        'experience_years' => 9,
        'hourly_rate' => 6000,
        'daily_rate' => 40000,
        'rating' => 4.8,
        'categories' => [2 => ['description' => 'Emergency plumbing and installations', 'price' => 6000]]
    ],
    [
        'username' => 'mendong_tutor',
        'email' => 'mendong_tutor@example.com',
        'first_name' => 'Amina',
        'last_name' => 'Mohammed',
        'phone' => '677334455',
        'address' => 'Mendong',
        'city' => 'Yaoundé',
        'region' => 'Centre',
        'profile_image' => 'tutoring2.jpeg',
        'business_name' => 'Mendong Tutors',
        'business_description' => 'Group and individual tutoring for primary and secondary students in Mendong area.',
        'experience_years' => 5,
        'hourly_rate' => 3500,
        'daily_rate' => 22000,
        'rating' => 4.7,
        'categories' => [3 => ['description' => 'All subjects for primary and secondary', 'price' => 3500]]
    ],

    // Douala Providers
    [
        'username' => 'bonanjo_mechanic',
        'email' => 'bonanjo_mech@example.com',
        'first_name' => 'Andre',
        'last_name' => 'Mvondo',
        'phone' => '677445566',
        'address' => 'Bonanjo',
        'city' => 'Douala',
        'region' => 'Littoral',
        'profile_image' => 'mechanic2.jpeg',
        'business_name' => 'Bonanjo Auto Care',
        'business_description' => 'Professional auto repair shop in the heart of Bonanjo. Specializing in European and Asian car models.',
        'experience_years' => 12,
        'hourly_rate' => 7000,
        'daily_rate' => 45000,
        'rating' => 4.9,
        'categories' => [4 => ['description' => 'Complete auto repair services', 'price' => 7000]]
    ],
    [
        'username' => 'akwa_tailor',
        'email' => 'akwa_tailor@example.com',
        'first_name' => 'Sophie',
        'last_name' => 'Ngando',
        'phone' => '677556677',
        'address' => 'Rue Joss',
        'city' => 'Douala',
        'region' => 'Littoral',
        'profile_image' => 'tailoring1.jpeg',
        'business_name' => 'Akwa Fashion House',
        'business_description' => 'Boutique tailoring services in Akwa. Specializing in African prints and formal wear.',
        'experience_years' => 8,
        'hourly_rate' => 4500,
        'daily_rate' => 28000,
        'rating' => 4.8,
        'categories' => [5 => ['description' => 'Custom African attire and alterations', 'price' => 4500]]
    ],

    // Bafoussam Providers
    [
        'username' => 'baf_elec',
        'email' => 'baf_elec@example.com',
        'first_name' => 'Pierre',
        'last_name' => 'Tchoupa',
        'phone' => '677667788',
        'address' => 'Quartier Djeleng',
        'city' => 'Bafoussam',
        'region' => 'West',
        'profile_image' => 'electrician.jpeg',
        'business_name' => 'Djeleng Electric',
        'business_description' => 'Residential and commercial electrical services in Bafoussam. Fast and reliable service.',
        'experience_years' => 6,
        'hourly_rate' => 4800,
        'daily_rate' => 32000,
        'rating' => 4.5,
        'categories' => [1 => ['description' => 'Electrical installations and repairs', 'price' => 4800]]
    ],

    // Buea Providers
    [
        'username' => 'buea_plumber',
        'email' => 'buea_plumber@example.com',
        'first_name' => 'Eric',
        'last_name' => 'Mbome',
        'phone' => '677778899',
        'address' => 'Molyko',
        'city' => 'Buea',
        'region' => 'Southwest',
        'profile_image' => 'plumbering1.jpeg',
        'business_name' => 'Molyko Plumbing',
        'business_description' => 'Complete plumbing solutions in Molyko and surrounding areas. Emergency services available.',
        'experience_years' => 7,
        'hourly_rate' => 5000,
        'daily_rate' => 35000,
        'rating' => 4.7,
        'categories' => [2 => ['description' => 'Residential plumbing services', 'price' => 5000]]
    ],

    // Bamenda Providers
    [
        'username' => 'bda_tutor',
        'email' => 'bda_tutor@example.com',
        'first_name' => 'Grace',
        'last_name' => 'Fon',
        'phone' => '677889900',
        'address' => 'Bambili',
        'city' => 'Bamenda',
        'region' => 'Northwest',
        'profile_image' => 'tutoring.png',
        'business_name' => 'Bambili Tutors',
        'business_description' => 'University and high school tutoring in Bambili. Specializing in science subjects.',
        'experience_years' => 4,
        'hourly_rate' => 3000,
        'daily_rate' => 20000,
        'rating' => 4.6,
        'categories' => [3 => ['description' => 'Science and mathematics tutoring', 'price' => 3000]]
    ],

    // Garoua Providers
    [
        'username' => 'garoua_mechanic',
        'email' => 'garoua_mech@example.com',
        'first_name' => 'Ibrahim',
        'last_name' => 'Ousmanou',
        'phone' => '677990011',
        'address' => 'Quartier Pitoare',
        'city' => 'Garoua',
        'region' => 'North',
        'profile_image' => 'mechanic.jpeg',
        'business_name' => 'Pitoare Auto',
        'business_description' => 'Auto repair and maintenance in Garoua. Specializing in 4x4 and truck repairs.',
        'experience_years' => 10,
        'hourly_rate' => 5500,
        'daily_rate' => 38000,
        'rating' => 4.7,
        'categories' => [4 => ['description' => '4x4 and truck repair services', 'price' => 5500]]
    ],

    // Maroua Providers
    [
        'username' => 'maroua_tailor',
        'email' => 'maroua_tailor@example.com',
        'first_name' => 'Aissatou',
        'last_name' => 'Mohamadou',
        'phone' => '677001122',
        'address' => 'Quartier Palaise',
        'city' => 'Maroua',
        'region' => 'Far North',
        'profile_image' => 'tailoring.jpeg',
        'business_name' => 'Palaise Couture',
        'business_description' => 'Traditional and modern tailoring services in Maroua. Specializing in African and Arab styles.',
        'experience_years' => 9,
        'hourly_rate' => 4000,
        'daily_rate' => 25000,
        'rating' => 4.8,
        'categories' => [5 => ['description' => 'Traditional and modern tailoring', 'price' => 4000]]
    ],

    // Edea Providers
    [
        'username' => 'edea_electric',
        'email' => 'edea_electric@example.com',
        'first_name' => 'Marcel',
        'last_name' => 'Ewane',
        'phone' => '677112233',
        'address' => 'Quartier Petit Paris',
        'city' => 'Edea',
        'region' => 'Littoral',
        'profile_image' => 'electrician1.jpeg',
        'business_name' => 'Edea Electric',
        'business_description' => 'Professional electrical services in Edea and surrounding areas. Industrial and residential expertise.',
        'experience_years' => 8,
        'hourly_rate' => 5200,
        'daily_rate' => 35000,
        'rating' => 4.7,
        'categories' => [1 => ['description' => 'Industrial and residential electrical work', 'price' => 5200]]
    ],

    // Kribi Providers
    [
        'username' => 'kribi_plumber',
        'email' => 'kribi_plumber@example.com',
        'first_name' => 'Jacques',
        'last_name' => 'Mvondo',
        'phone' => '677223344',
        'address' => 'Quartier Mboamanga',
        'city' => 'Kribi',
        'region' => 'South',
        'profile_image' => 'plumbering1.jpeg',
        'business_name' => 'Kribi Plumbing Solutions',
        'business_description' => 'Complete plumbing services in Kribi. Specializing in beachfront properties and hotels.',
        'experience_years' => 7,
        'hourly_rate' => 5800,
        'daily_rate' => 40000,
        'rating' => 4.9,
        'categories' => [2 => ['description' => 'Resort and residential plumbing', 'price' => 5800]]
    ],

    // Limbe Providers
    [
        'username' => 'limbe_tutor',
        'email' => 'limbe_tutor@example.com',
        'first_name' => 'Blessing',
        'last_name' => 'Ndifor',
        'phone' => '677334455',
        'address' => 'Down Beach',
        'city' => 'Limbe',
        'region' => 'Southwest',
        'profile_image' => 'tutoring1.jpeg',
        'business_name' => 'Limbe Learning Center',
        'business_description' => 'After-school tutoring and exam preparation in Limbe. Specializing in GCE and BACC preparation.',
        'experience_years' => 6,
        'hourly_rate' => 3500,
        'daily_rate' => 22000,
        'rating' => 4.8,
        'categories' => [3 => ['description' => 'GCE and BACC preparation', 'price' => 3500]]
    ],

    // Ngaoundéré Providers
    [
        'username' => 'ngaoundere_mechanic',
        'email' => 'ngaoundere_mech@example.com',
        'first_name' => 'Ousmanou',
        'last_name' => 'Bouba',
        'phone' => '677445566',
        'address' => 'Quartier Dang',
        'city' => 'Ngaoundéré',
        'region' => 'Adamawa',
        'profile_image' => 'mechanic1.jpeg',
        'business_name' => 'Dang Auto Service',
        'business_description' => 'Professional auto repair in Ngaoundéré. Specializing in truck and bus maintenance.',
        'experience_years' => 11,
        'hourly_rate' => 6000,
        'daily_rate' => 40000,
        'rating' => 4.7,
        'categories' => [4 => ['description' => 'Truck and bus repair', 'price' => 6000]]
    ],

    // Ebolowa Providers
    [
        'username' => 'ebolowa_tailor',
        'email' => 'ebolowa_tailor@example.com',
        'first_name' => 'Marthe',
        'last_name' => 'Mballa',
        'phone' => '677556677',
        'address' => 'Quartier Mvog-Mbi',
        'city' => 'Ebolowa',
        'region' => 'South',
        'profile_image' => 'tailoring2.jpeg',
        'business_name' => 'Mvog-Mbi Fashion',
        'business_description' => 'Traditional and modern tailoring in Ebolowa. Specializing in traditional southern styles.',
        'experience_years' => 7,
        'hourly_rate' => 3800,
        'daily_rate' => 25000,
        'rating' => 4.6,
        'categories' => [5 => ['description' => 'Traditional southern attire', 'price' => 3800]]
    ],

    // Additional Service Providers in Various Categories
    
    // Yaoundé - Cleaning Service
    [
        'username' => 'yao_clean',
        'email' => 'yao_clean@example.com',
        'first_name' => 'Marie',
        'last_name' => 'Ngo',
        'phone' => '677112244',
        'address' => 'Quartier Fouda',
        'city' => 'Yaoundé',
        'region' => 'Centre',
        'profile_image' => 'cleaning services1.jpeg',
        'business_name' => 'Fouda Clean Team',
        'business_description' => 'Professional cleaning services for homes and offices in Yaoundé. We provide deep cleaning, post-construction cleaning, and regular maintenance.',
        'experience_years' => 5,
        'hourly_rate' => 4000,
        'daily_rate' => 28000,
        'rating' => 4.7,
        'categories' => [6 => ['description' => 'Residential and office cleaning', 'price' => 4000]]
    ],
    
    // Douala - Delivery Service
    [
        'username' => 'dla_delivery',
        'email' => 'dla_delivery@example.com',
        'first_name' => 'Frank',
        'last_name' => 'Tchoupo',
        'phone' => '677223355',
        'address' => 'Bonapriso',
        'city' => 'Douala',
        'region' => 'Littoral',
        'profile_image' => 'delivery services.jpeg',
        'business_name' => 'Bonapriso Express',
        'business_description' => 'Reliable and fast delivery services in Douala. We handle documents, packages, and food delivery across the city.',
        'experience_years' => 3,
        'hourly_rate' => 3500,
        'daily_rate' => 25000,
        'rating' => 4.8,
        'categories' => [7 => ['description' => 'City-wide delivery services', 'price' => 3500]]
    ],
    
    // Bafoussam - Painting Service
    [
        'username' => 'baf_painter',
        'email' => 'baf_painter@example.com',
        'first_name' => 'Jean',
        'last_name' => 'Nkoulou',
        'phone' => '677334466',
        'address' => 'Quartier Djeleng',
        'city' => 'Bafoussam',
        'region' => 'West',
        'profile_image' => 'painting1.jpeg',
        'business_name' => 'Djeleng Paint Masters',
        'business_description' => 'Professional painting services for homes and businesses in Bafoussam. We offer interior and exterior painting with high-quality materials.',
        'experience_years' => 8,
        'hourly_rate' => 4500,
        'daily_rate' => 32000,
        'rating' => 4.9,
        'categories' => [8 => ['description' => 'Interior and exterior painting', 'price' => 4500]]
    ],
    
    // Buea - IT Support
    [
        'username' => 'buea_itsupport',
        'email' => 'buea_itsupport@example.com',
        'first_name' => 'Kevin',
        'last_name' => 'Tabi',
        'phone' => '677445577',
        'address' => 'Molyko',
        'city' => 'Buea',
        'region' => 'Southwest',
        'profile_image' => 'IT support.jpeg',
        'business_name' => 'Molyko Tech Solutions',
        'business_description' => 'Professional IT support services in Buea. We offer computer repair, network setup, software installation, and tech support for homes and businesses.',
        'experience_years' => 6,
        'hourly_rate' => 5000,
        'daily_rate' => 35000,
        'rating' => 4.8,
        'categories' => [14 => ['description' => 'Computer and tech support', 'price' => 5000]]
    ],
    
    // Yaoundé - Catering Service
    [
        'username' => 'yao_catering',
        'email' => 'yao_catering@example.com',
        'first_name' => 'Esther',
        'last_name' => 'Mefire',
        'phone' => '677556688',
        'address' => 'Bastos',
        'city' => 'Yaoundé',
        'region' => 'Centre',
        'profile_image' => 'catering1.jpeg',
        'business_name' => 'Bastos Catering Delight',
        'business_description' => 'Premium catering services for all occasions in Yaoundé. We specialize in corporate events, weddings, and private parties with customizable menus.',
        'experience_years' => 7,
        'hourly_rate' => 6000,
        'daily_rate' => 40000,
        'rating' => 4.9,
        'categories' => [12 => ['description' => 'Event catering services', 'price' => 6000]]
    ],
    
    // Douala - Hair Styling
    [
        'username' => 'dla_hairstyle',
        'email' => 'dla_hairstyle@example.com',
        'first_name' => 'Sophia',
        'last_name' => 'Ngono',
        'phone' => '677667799',
        'address' => 'Bonanjo',
        'city' => 'Douala',
        'region' => 'Littoral',
        'profile_image' => 'hair-styling.jpeg',
        'business_name' => 'Bonanjo Beauty Lounge',
        'business_description' => 'Luxury hair salon in Bonanjo offering professional hair styling, braiding, treatments, and beauty services in a relaxing environment.',
        'experience_years' => 9,
        'hourly_rate' => 5000,
        'daily_rate' => 35000,
        'rating' => 4.8,
        'categories' => [13 => ['description' => 'Hair styling and treatments', 'price' => 5000]]
    ],
    
    // Bafoussam - Security Services
    [
        'username' => 'baf_security',
        'email' => 'baf_security@example.com',
        'first_name' => 'Richard',
        'last_name' => 'Kamga',
        'phone' => '677778800',
        'address' => 'Quartier Djeleng',
        'city' => 'Bafoussam',
        'region' => 'West',
        'profile_image' => 'security1.jpeg',
        'business_name' => 'Djeleng Security Solutions',
        'business_description' => 'Professional security services in Bafoussam. We provide trained security personnel, surveillance systems, and security consultations for homes and businesses.',
        'experience_years' => 10,
        'hourly_rate' => 4500,
        'daily_rate' => 30000,
        'rating' => 4.7,
        'categories' => [15 => ['description' => 'Security personnel and systems', 'price' => 4500]]
    ],
    
    // Yaoundé - Fitness Training
    [
        'username' => 'yao_fitness',
        'email' => 'yao_fitness@example.com',
        'first_name' => 'David',
        'last_name' => 'Moukouri',
        'phone' => '677889911',
        'address' => 'Quartier Fouda',
        'city' => 'Yaoundé',
        'region' => 'Centre',
        'profile_image' => 'fitness1.jpeg',
        'business_name' => 'Fouda Fitness Center',
        'business_description' => 'Professional fitness training and personal coaching in Yaoundé. We offer personalized workout plans, group classes, and nutritional guidance.',
        'experience_years' => 8,
        'hourly_rate' => 4000,
        'daily_rate' => 28000,
        'rating' => 4.8,
        'categories' => [17 => ['description' => 'Personal training and fitness', 'price' => 4000]]
    ],
    
    // Douala - Legal Services
    [
        'username' => 'dla_legal',
        'email' => 'dla_legal@example.com',
        'first_name' => 'Emmanuel',
        'last_name' => 'Ngu',
        'phone' => '677990022',
        'address' => 'Bonapriso',
        'city' => 'Douala',
        'region' => 'Littoral',
        'profile_image' => 'legal services1.jpeg',
        'business_name' => 'Bonapriso Legal Associates',
        'business_description' => 'Professional legal services in Douala. We specialize in business law, real estate, immigration, and family law with experienced attorneys.',
        'experience_years' => 12,
        'hourly_rate' => 10000,
        'daily_rate' => 60000,
        'rating' => 4.9,
        'categories' => [19 => ['description' => 'Legal consultation and services', 'price' => 10000]]
    ],
    
    // Yaoundé - Accounting Services
    [
        'username' => 'yao_accounting',
        'email' => 'yao_accounting@example.com',
        'first_name' => 'Grace',
        'last_name' => 'Tchouapi',
        'phone' => '677001133',
        'address' => 'Bastos',
        'city' => 'Yaoundé',
        'region' => 'Centre',
        'profile_image' => 'Accounting1.jpeg',
        'business_name' => 'Bastos Accounting Pro',
        'business_description' => 'Professional accounting and financial services in Yaoundé. We offer bookkeeping, tax preparation, and financial consulting for businesses and individuals.',
        'experience_years' => 10,
        'hourly_rate' => 8000,
        'daily_rate' => 50000,
        'rating' => 4.9,
        'categories' => [20 => ['description' => 'Accounting and financial services', 'price' => 8000]]
    ]
];

try {
    // Clear existing providers to avoid duplicates
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE provider_availability;");
    $pdo->exec("TRUNCATE TABLE provider_services;");
    $pdo->exec("DELETE FROM service_providers;");
    $pdo->exec("DELETE FROM users WHERE user_type = 'provider';");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    
    echo "Cleared existing providers.\n";
    
    // Create sample providers
    $created = 0;
    foreach ($providers as $provider) {
        try {
            $providerId = createServiceProvider($pdo, $provider);
            echo "Created provider: {$provider['business_name']} (ID: $providerId)\n";
            $created++;
        } catch (Exception $e) {
            echo "Error creating provider {$provider['business_name']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nSuccessfully created $created service providers.\n";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>
