USE ordify;
CREATE TABLE restaurants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  owner_id INT NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  description TEXT,
  address VARCHAR(255) NOT NULL,
  phone VARCHAR(30),
  image VARCHAR(60),
  is_open TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE menu_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  restaurant_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(255),
  category VARCHAR(50) NOT NULL DEFAULT 'Mains',
  price DECIMAL(10,2) NOT NULL,
  image VARCHAR(60),
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);
