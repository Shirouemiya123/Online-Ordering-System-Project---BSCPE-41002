USE ordify;

-- Cuisine tag for each restaurant (used by search filters)
ALTER TABLE restaurants ADD COLUMN cuisine VARCHAR(40) NOT NULL DEFAULT 'Other' AFTER description;

-- One review per delivered order: rates the restaurant and (optionally) the courier
CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL UNIQUE,
  customer_id INT NOT NULL,
  restaurant_id INT NOT NULL,
  courier_id INT NULL,
  restaurant_rating TINYINT NOT NULL,
  courier_rating TINYINT NULL,
  comment VARCHAR(500),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES users(id),
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
  FOREIGN KEY (courier_id) REFERENCES users(id)
);
