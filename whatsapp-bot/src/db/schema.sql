CREATE TABLE IF NOT EXISTS vendors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  phone_number VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(255),
  email VARCHAR(255),
  password_hash VARCHAR(255),
  pays VARCHAR(100),
  shop_name VARCHAR(255),
  shop_type ENUM('physique','digital','les_deux','restaurant'),
  site_externe VARCHAR(500),
  mantota_vendor_id INT NULL,
  bot_status ENUM('inactive','active','suspended') DEFAULT 'inactive',
  whatsapp_number VARCHAR(30),
  setup_status ENUM('not_required','pending_number','pending_code','active') DEFAULT 'not_required' COMMENT 'Statut configuration numéro dédié plan Pro',
  meta_verification_code VARCHAR(10) NULL COMMENT 'Code SMS Meta envoyé par le client pour valider son numéro',
  short_code VARCHAR(10) NULL UNIQUE COMMENT 'Code court unique de la boutique ex: AM001',
  localisation_lat DECIMAL(10,7) NULL,
  localisation_lng DECIMAL(10,7) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendor_id INT NOT NULL,
  plan ENUM('gratuit','starter','standard','pro') NOT NULL,
  conversations_included INT NOT NULL,
  conversations_count INT DEFAULT 0,
  surplus_count INT DEFAULT 0,
  surplus_amount DECIMAL(10,2) DEFAULT 0.00,
  date_debut DATE NOT NULL,
  date_fin DATE NOT NULL,
  status ENUM('active','suspended','expired') DEFAULT 'active',
  alert_80_sent TINYINT DEFAULT 0,
  alert_100_sent TINYINT DEFAULT 0,
  alert_150_sent TINYINT DEFAULT 0,
  surplus_link_sent TINYINT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vendor_id INT NOT NULL,
  mantota_product_id INT NULL,
  nom VARCHAR(255) NOT NULL,
  prix DECIMAL(10,2) NOT NULL,
  description TEXT,
  image_url VARCHAR(500),
  categorie VARCHAR(100) NULL,
  statut ENUM('actif','inactif') DEFAULT 'actif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS conversations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  phone_number VARCHAR(30) NOT NULL UNIQUE,
  vendor_id INT NULL,
  current_step VARCHAR(50) DEFAULT 'welcome',
  client_type ENUM('onboarding','vendor_management','end_customer') DEFAULT 'onboarding',
  collected_data JSON,
  conversation_count INT DEFAULT 0,
  last_message_at TIMESTAMP NULL,
  current_vendor_id INT NULL COMMENT 'ID de la boutique active pour ce client',
  vendor_history JSON NULL COMMENT 'Historique des boutiques consultées : [{vendor_id, shop_name, short_code, visited_at}]',
  pending_first_message TEXT NULL COMMENT 'Premier message du client à transmettre à la boutique après identification du code. NE JAMAIS EFFACER AVANT TRANSMISSION.',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS vendors_pending (
  id INT AUTO_INCREMENT PRIMARY KEY,
  phone_number VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(255),
  shop_type VARCHAR(50),
  products_json JSON,
  site_externe VARCHAR(500),
  scanned_products_json JSON NULL,
  status ENUM('collecting','scanned','ready','submitted') DEFAULT 'collecting',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS prospects_relance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  phone_number VARCHAR(30) NOT NULL,
  short_code VARCHAR(10) NULL COMMENT 'Code boutique via lequel le prospect est arrivé',
  vendor_id INT NOT NULL,
  last_interaction_at TIMESTAMP NOT NULL,
  relance_sent TINYINT DEFAULT 0,
  relance_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
);

-- Index pour optimiser les requêtes
CREATE INDEX IF NOT EXISTS idx_vendors_phone ON vendors(phone_number);
CREATE INDEX IF NOT EXISTS idx_subscriptions_vendor ON subscriptions(vendor_id);
CREATE INDEX IF NOT EXISTS idx_subscriptions_date_fin ON subscriptions(date_fin);
CREATE INDEX IF NOT EXISTS idx_products_vendor ON products(vendor_id);
CREATE INDEX IF NOT EXISTS idx_conversations_phone ON conversations(phone_number);
CREATE INDEX IF NOT EXISTS idx_prospects_relance_vendor ON prospects_relance(vendor_id);
CREATE INDEX IF NOT EXISTS idx_prospects_relance_sent ON prospects_relance(relance_sent);

CREATE TABLE IF NOT EXISTS logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  level ENUM('info','warning','error') NOT NULL,
  service VARCHAR(50) NOT NULL,
  -- webhook / billing / scanner / mantota / cron / gpt
  message TEXT NOT NULL,
  details JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin_sessions (
  id VARCHAR(128) PRIMARY KEY,
  data TEXT NOT NULL,
  expires DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS plan_config (
  plan ENUM('gratuit','starter','standard','pro')
    PRIMARY KEY,
  prix INT NOT NULL,
  conversations INT NOT NULL,
  surplus_per_100 INT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP
);

-- Valeurs initiales des plans
INSERT IGNORE INTO plan_config VALUES
  ('gratuit', 0, 50, 0, NOW()),
  ('starter', 2500, 500, 800, NOW()),
  ('standard', 6000, 2000, 600, NOW()),
  ('pro', 12000, -1, 0, NOW());

CREATE INDEX IF NOT EXISTS idx_logs_level
  ON logs(level);
CREATE INDEX IF NOT EXISTS idx_logs_service
  ON logs(service);
CREATE INDEX IF NOT EXISTS idx_logs_created
  ON logs(created_at);

CREATE INDEX IF NOT EXISTS idx_vendors_setup_status
  ON vendors(setup_status);

CREATE INDEX IF NOT EXISTS idx_vendors_short_code
  ON vendors(short_code);

CREATE INDEX IF NOT EXISTS idx_conversations_vendor
  ON conversations(current_vendor_id);
