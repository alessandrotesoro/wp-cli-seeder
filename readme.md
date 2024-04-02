<!-- ABOUT THE PROJECT -->
## ℹ️ WP CLI Seeder
A WP CLI command to seed your WordPress database with dummy data.

<!-- GETTING STARTED -->
## ✅ Requirements

1. PHP 8.0 or higher.
2. Composer

<!-- GETTING STARTED -->
## 📖 Usage

### Installation

Clone the repository under the `wp-content/plugins` directory of your WordPress installation.

```
git clone git@github.com:alessandrotesoro/wp-cli-seeder.git
```

Install the dependencies.

```
composer install
```

Activate the plugin.

```
wp plugin activate wp-cli-seeder
```

Run the seed command.

```
wp seed
```
---

### Available commands

#### `wp seed products generate`

Seed the database with dummy products for WooCommerce.

The command will automatically download images for the products from Algolia's CDN. If you want to skip this step, you can use the `--skip-images` option. Additionally, the command will generate categories and 2 custom product attributes.

Note that the command will always wipe out the existing products, categories, and attributes before seeding the database.

The maximum number of products that can be seeded is 10000.

##### Options

- `--items=<number>`: The number of products to seed. Default is 100.
- `--skip-images`: Skip downloading images for the products.

#### `wp seed products delete`

Delete all products from the database.

#### `wp seed products sales`

Seed the database with dummy discounted sale prices for products. Note that this command will randomly select products to apply the sale price to.

##### Options

- `--items=<number>`: The number of products to apply the sale price to. Default is 10.

#### `wp seed users generate`

Seed the database with dummy users.

##### Options

- `--number=<number>`: The number of users to seed. Default is 10.

#### `wp seed users delete`

Delete all users from the database.

<!-- CONTRIBUTING -->
## 🤝 Contributing

Contributions are welcome from everyone. For major changes, please open an issue first to discuss what you would like to change.

## 🚨 Security Issues
If you discover a security vulnerability, please email [alessandro.tesoro@icloud.com](mailto:alessandro.tesoro@icloud.com). All security vulnerabilities will be promptly addressed.

<!-- LICENSE -->
## 🔖 License

Distributed under the MIT License. See `LICENSE` for more information.
