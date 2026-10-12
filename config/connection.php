<?php
    // Setup PDO Connection
    try {
        $dsn = 'mysql:host=localhost;dbname=lavorhub_restaurant_db';
        $username = 'root';
        $password = '';
    
        // Create a new PDO instance
        $pdo = new PDO($dsn, $username, $password);
    
        // Set the PDO error mode to exception
        $pdo -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connection failed: " . $e -> getMessage());
    }

    // menu_items
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `menu_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `description` TEXT,
            `type` ENUM('food', 'beverage') DEFAULT 'food',
            `title` VARCHAR(50),
            `category` VARCHAR(50),
            `price` DECIMAL(8, 2) NOT NULL,
            `image` VARCHAR(255),
            `menu_section` ENUM('kitchen', 'bar', 'both') DEFAULT 'kitchen',
            `is_available` BOOLEAN DEFAULT TRUE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($sql);
    } catch (PDOException $e) {
        die("Error: " . $e -> getMessage());
    }

    // restaurant_tables
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `restaurant_tables` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `table_number` VARCHAR(40) NOT NULL UNIQUE,
            `location` VARCHAR(100),
            `table_section` ENUM('kitchen', 'bar') DEFAULT 'kitchen',
            `is_active` BOOLEAN DEFAULT TRUE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($sql);
    } catch (PDOException $e) {
        die("Error: " . $e -> getMessage());
    }

    // users
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `remote_address` VARCHAR(45) NOT NULL,
            `user_agent` TEXT NOT NULL,
            `temporary_name` VARCHAR(100),
            `is_active` BOOLEAN DEFAULT TRUE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($sql);
    } catch (PDOException $e) {
        die("Error: " . $e -> getMessage());
    }

    // table_users
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `table_users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT,
            `table_id` INT,
            `is_active` BOOLEAN DEFAULT TRUE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT `table_user` FOREIGN KEY(`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
            CONSTRAINT `table_table` FOREIGN KEY(`table_id`) REFERENCES `restaurant_tables`(`id`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($sql);
    } catch (PDOException $e) {
        die("Error: " . $e -> getMessage());
    }

    // item_orders
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `item_orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `table_id` INT,
            `item_id` INT,
            `user_id` INT,
            `quantity` INT NOT NULL,
            `delivered` BOOLEAN DEFAULT FALSE,
            `is_active` BOOLEAN DEFAULT TRUE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT `item_table` FOREIGN KEY(`table_id`) REFERENCES `restaurant_tables`(`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
            CONSTRAINT `item_item` FOREIGN KEY(`item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
            CONSTRAINT `item_user` FOREIGN KEY(`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($sql);
    } catch (PDOException $e) {
        die("Error: " . $e -> getMessage());
    }

?>
