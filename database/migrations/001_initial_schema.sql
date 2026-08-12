CREATE TABLE admins (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_username (username)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE brands (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    logo_path VARCHAR(255) NULL,
    seo_title VARCHAR(255) NULL,
    seo_description VARCHAR(320) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_brands_name (name),
    UNIQUE KEY uq_brands_slug (slug)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    description TEXT NULL,
    seo_title VARCHAR(255) NULL,
    seo_description VARCHAR(320) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE products (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    brand_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    code VARCHAR(150) NULL,
    price DECIMAL(12, 2) NULL,
    short_description TEXT NULL,
    description LONGTEXT NULL,
    seo_title VARCHAR(255) NULL,
    seo_description VARCHAR(320) NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_slug (slug),
    KEY idx_products_code (code),
    KEY idx_products_brand_id (brand_id),
    KEY idx_products_category_id (category_id),
    KEY idx_products_is_active (is_active),
    CONSTRAINT fk_products_brand FOREIGN KEY (brand_id) REFERENCES brands (id)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE product_images (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NULL,
    is_main TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    width INT UNSIGNED NULL,
    height INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_product_images_product_id (product_id),
    CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE product_specifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    value VARCHAR(500) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_product_specifications_product_id (product_id),
    CONSTRAINT fk_product_specifications_product FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE product_views (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    viewed_at DATETIME NOT NULL,
    visitor_token_hash CHAR(64) NULL,
    PRIMARY KEY (id),
    KEY idx_product_views_product_viewed_at (product_id, viewed_at),
    KEY idx_product_views_viewed_at (viewed_at),
    CONSTRAINT fk_product_views_product FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE RESTRICT ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
