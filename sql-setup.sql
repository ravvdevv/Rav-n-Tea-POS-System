

-- 1. USERS 
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- store hashed passwords
    role ENUM('cashier', 'barista', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- 2. PRODUCTS (menu items)
CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    cost DECIMAL(10,2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

-- 3. INVENTORY ITEMS (raw ingredients)
CREATE TABLE inventory_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    threshold DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. PRODUCT INGREDIENTS (link menu → inventory)
CREATE TABLE product_ingredients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    inventory_item_id INT UNSIGNED NOT NULL,
    quantity_needed DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    UNIQUE KEY(product_id, inventory_item_id) -- prevent duplicates
);

-- 5. ORDERS
CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cashier_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'in-progress', 'completed') DEFAULT 'pending',
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (cashier_id) REFERENCES users(id)
);

-- 6. ORDER ITEMS
CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- 7. SALES (for reporting)
CREATE TABLE sales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL UNIQUE,
    total DECIMAL(10,2) NOT NULL,
    profit DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);







-- 1. Add stock to products
ALTER TABLE products
ADD COLUMN stock INT UNSIGNED DEFAULT 0 AFTER cost;

-- 2. Update orders table
ALTER TABLE orders 
MODIFY COLUMN status ENUM('pending', 'in-progress', 'completed', 'cancelled') DEFAULT 'pending',
ADD COLUMN notes TEXT AFTER total,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 3. Add payments table
CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'debit_card', 'mobile_payment') NOT NULL,
    transaction_id VARCHAR(100),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- 4. Add indexes for performance
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created_at ON orders(created_at);
CREATE INDEX idx_order_items_order_id ON order_items(order_id);

-- Create default admin account
-- Default credentials: admin / password (change this in production!)
INSERT INTO users (username, password, role) VALUES (
    'admin', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    'admin'
) ON DUPLICATE KEY UPDATE 
    password = VALUES(password),
    role = VALUES(role);

INSERT INTO users (username, password, role) VALUES (
    'cashier', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    'cashier'
) ON DUPLICATE KEY UPDATE 
    password = VALUES(password),
    role = VALUES(role);

INSERT INTO users (username, password, role) VALUES (
    'barista', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    'barista'
) ON DUPLICATE KEY UPDATE 
    password = VALUES(password),
    role = VALUES(role);