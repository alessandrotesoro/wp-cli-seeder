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

The seeding process makes use of a dataset of 10k products. The command will ask you a few questions to determine how many products you want to seed and if you want to seed products with images.

More datasets may be added in the future.

#### `wp seed products delete`

Delete all products from the database.

#### `wp seed products sales`

Seed the database with dummy discounted sale prices for products. Note that this command will randomly select products to apply the sale price to. You will be asked to specify how many products you want to apply the sale price to.

#### `wp seed products featured`

This command will randomly select products to mark as featured. You will be asked to specify how many products you want to mark as featured.

#### `wp seed products stock_status`

Apply a stock status to random products. You will be asked to specify how many products you want to apply the stock status to and which status you want to apply.

#### `wp seed products stock_quantity`

Apply a stock quantity to random products. You will be asked to specify how many products you want to apply the stock quantity to. The quantity will be randomly generated between 1 and 100.

#### `wp seed products categories`

Seed the database with dummy product categories. You will be asked to specify how many categories you want to seed and if you want to then assign the categories to products.

#### `wp seed products tags`

Seed the database with dummy product tags. You will be asked to specify how many tags you want to seed and if you want to then assign the tags to products.

#### `wp seed products attributes`

The command will help you generate random terms for product attributes that already exist in the database. You will be asked to specify how many terms you want to generate and which attribute you want to generate the terms for.

At the end of the process, you will be asked if you want to assign the generated terms to products.

#### `wp seed products reviews`

Seed the database with dummy product reviews. You will be asked to specify for how many products you want to seed reviews. The command will create one review for each product.

#### `wp seed products custom_fields`

The command will help you generate meta values for products based on custom fields that created via the Advanced Custom Fields plugin. You will be asked to specify how many products you want to generate meta values for and which custom field you want to generate the values for.

Currently supported field types are:

- Text
- Textarea
- Number
- Radio
- Select
- Checkbox

#### `wp seed posts generate`

Seed the database with dummy posts. You will be asked to specify how many posts you want to seed and for which post type. Only public post types are supported.

#### `wp seed posts delete`

Delete all posts from the database.

#### `wp seed posts custom_fields`

The command will help you generate meta values for posts based on custom fields that created via the Advanced Custom Fields plugin. You will be asked to specify how many posts you want to generate meta values for and which custom field you want to generate the values for.

Currently supported field types are the same as for products.

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
