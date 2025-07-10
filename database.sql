-- ServiGo Database Creation Script
-- Smart Local Service Web Application for Cameroon

-- Create database
CREATE DATABASE IF NOT EXISTS servigo_db;
USE servigo_db;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    region VARCHAR(50),
    profile_image VARCHAR(255),
    user_type ENUM('customer', 'provider', 'admin') DEFAULT 'customer',
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Service categories table
CREATE TABLE service_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Service providers table
CREATE TABLE service_providers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    business_name VARCHAR(100),
    business_description TEXT,
    experience_years INT,
    hourly_rate DECIMAL(10,2),
    daily_rate DECIMAL(10,2),
    is_available BOOLEAN DEFAULT TRUE,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Provider services table (many-to-many relationship)
CREATE TABLE provider_services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    provider_id INT NOT NULL,
    category_id INT NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES service_providers(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES service_categories(id) ON DELETE CASCADE
);

-- Service requests table
CREATE TABLE service_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    provider_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255),
    budget DECIMAL(10,2),
    urgency ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'accepted', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    scheduled_date DATE,
    scheduled_time TIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES service_providers(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES service_categories(id) ON DELETE CASCADE
);

-- Reviews table
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    provider_id INT NOT NULL,
    request_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES service_providers(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE
);

-- Messages table
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    request_id INT,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('request', 'message', 'review', 'system') DEFAULT 'system',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'mobile_money', 'bank_transfer', 'card') DEFAULT 'cash',
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE
);

-- Provider documents table
CREATE TABLE provider_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    provider_id INT NOT NULL,
    document_type ENUM('id_card', 'certificate', 'license', 'insurance', 'other') NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES service_providers(id) ON DELETE CASCADE
);

-- Provider availability table
CREATE TABLE provider_availability (
    id INT PRIMARY KEY AUTO_INCREMENT,
    provider_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    start_time TIME,
    end_time TIME,
    is_available BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (provider_id) REFERENCES service_providers(id) ON DELETE CASCADE
);

-- Audit logs table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default service categories
INSERT INTO service_categories (name, description, icon) VALUES
('Electrician', 'Professional electrical services for homes and businesses', 'fas fa-bolt'),
('Plumbing', 'Expert plumbing and water system services', 'fas fa-wrench'),
('Tutoring', 'Qualified tutors for all subjects and levels', 'fas fa-graduation-cap'),
('Mechanic', 'Professional auto repair and maintenance', 'fas fa-car'),
('Tailoring', 'Custom clothing and alterations', 'fas fa-cut'),
('Cleaning', 'Professional cleaning and maintenance services', 'fas fa-broom'),
('Delivery', 'Fast and reliable delivery services', 'fas fa-truck'),
('Painting', 'Professional painting and decoration', 'fas fa-paint-brush'),
('Carpentry', 'Custom woodwork and furniture making', 'fas fa-hammer'),
('Gardening', 'Landscaping and garden maintenance', 'fas fa-seedling'),
('Photography', 'Professional photography services', 'fas fa-camera'),
('Catering', 'Event catering and food services', 'fas fa-utensils'),
('Hair Styling', 'Professional hair styling and beauty services', 'fas fa-cut'),
('IT Support', 'Computer and technology support', 'fas fa-laptop'),
('Security', 'Security and surveillance services', 'fas fa-shield-alt'),
('Transport', 'Transportation and moving services', 'fas fa-bus'),
('Pet Care', 'Pet grooming and care services', 'fas fa-paw'),
('Fitness', 'Personal training and fitness services', 'fas fa-dumbbell'),
('Legal', 'Legal consultation and services', 'fas fa-balance-scale'),
('Accounting', 'Financial and accounting services', 'fas fa-calculator');

-- Insert default admin user (password: password)
INSERT INTO users (username, email, password, first_name, last_name, user_type, is_verified, is_active) VALUES
('admin', 'admin@servigo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', TRUE, TRUE);

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_service_requests_status ON service_requests(status);
CREATE INDEX idx_service_requests_customer ON service_requests(customer_id);
CREATE INDEX idx_service_requests_provider ON service_requests(provider_id);
CREATE INDEX idx_reviews_provider ON reviews(provider_id);
CREATE INDEX idx_messages_sender ON messages(sender_id);
CREATE INDEX idx_messages_receiver ON messages(receiver_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_provider_services_provider ON provider_services(provider_id);
CREATE INDEX idx_provider_availability_provider ON provider_availability(provider_id);
