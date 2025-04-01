CREATE TABLE IF NOT EXISTS pos_user (
                        user_id INT AUTO_INCREMENT PRIMARY KEY,
                        user_name VARCHAR(255) NOT NULL,
                        user_email VARCHAR(255) NOT NULL UNIQUE,
                        user_password VARCHAR(255) NOT NULL,
                        user_type ENUM('Admin', 'User') NOT NULL,
                        user_status ENUM('Active', 'Inactive') NOT NULL
                    );
CREATE TABLE IF NOT EXISTS pos_category (
                        category_id INT AUTO_INCREMENT PRIMARY KEY,
                        category_name VARCHAR(255) NOT NULL,
                        category_status ENUM('Active', 'Inactive') NOT NULL
                    );
CREATE TABLE IF NOT EXISTS pos_product (
                        product_id INT AUTO_INCREMENT PRIMARY KEY,
                        category_id INT,
                        product_name VARCHAR(255) NOT NULL,
                        product_image VARCHAR(100) NOT NULL,
                        product_price DECIMAL(10, 2) NOT NULL,
                        product_status ENUM('Available', 'Out of Stock') NOT NULL,
                        FOREIGN KEY (category_id) REFERENCES pos_category(category_id)
                    );
CREATE TABLE IF NOT EXISTS pos_order (
                        order_id INT AUTO_INCREMENT PRIMARY KEY,
                        order_number VARCHAR(255) UNIQUE NOT NULL,
                        order_total DECIMAL(10, 2) NOT NULL,
                        order_money_receive DECIMAL(10, 2) NOT NULL,
                        order_money_return DECIMAL(10, 2) NOT NULL,
                        order_datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        order_created_by INT,
                        FOREIGN KEY (order_created_by) REFERENCES pos_user(user_id)
                    );
CREATE TABLE IF NOT EXISTS pos_order_item (
                        order_item_id INT AUTO_INCREMENT PRIMARY KEY,
                        order_id INT,
                        product_id INT,
                        product_qty INT NOT NULL,
                        product_price DECIMAL(10, 2) NOT NULL,
                        FOREIGN KEY (order_id) REFERENCES pos_order(order_id),
                        FOREIGN KEY (product_id) REFERENCES pos_product(product_id)
                    );
CREATE TABLE IF NOT EXISTS pos_configuration (
                        config_id INT AUTO_INCREMENT PRIMARY KEY,
                        restaurant_name VARCHAR(255) NOT NULL,
                        restaurant_address VARCHAR(255) NOT NULL,
                        restaurant_phone VARCHAR(20) NOT NULL,
                        restaurant_email VARCHAR(255),
                        opening_hours VARCHAR(255),
                        closing_hours VARCHAR(255),
                        tax_rate DECIMAL(5, 2),
                        currency VARCHAR(10),
                        logo VARCHAR(100)
                    );