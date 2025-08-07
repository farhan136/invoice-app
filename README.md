# Invoice Management API - Recruitment Test

This is a RESTful API for managing customers and invoices, built with the Laravel framework. It features user authentication, comprehensive API documentation via Swagger, and includes a suite of feature and performance tests.

## ✨ Features

  * **Authentication**: Secure user login and logout using Laravel Sanctum API tokens.
  * **Customer Management**: Full CRUD (Create, Read, Update, Delete) functionality for customers.
  * **Invoice Management**: Full CRUD functionality for invoices, with line items.
  * **Authorization**: Users can only view and manage their own invoices.
  * **API Documentation**: Interactive API documentation powered by Swagger (OpenAPI).
  * **Performance Testing**: Sequential load tests to benchmark API response times.

## 🛠️ Tech Stack

  * **Backend**: Laravel
  * **Database**: SQLite
  * **Authentication**: Laravel Sanctum
  * **API Documentation**: Swagger
  * **Testing**: PestPHP

-----

## 🚀 Getting Started

Follow these instructions to get the project up and running on your local machine for development and testing purposes.

### Prerequisites

Make sure you have the following software installed:

  * PHP (\>= 8.2)
  * Composer

### Installation

1.  **Clone the repository**

    ```bash
    git clone https://github.com/your-username/your-repository-name.git
    cd your-repository-name
    ```

2.  **Install PHP dependencies**

    ```bash
    composer install
    ```

3.  **Create your environment file**
    Copy the example environment file.

    ```bash
    cp .env.example .env
    ```

4.  **Generate an application key**

    ```bash
    php artisan key:generate
    ```

5.  **Set up the SQLite Database**
    First, create the database file.

    ```bash
    touch database/database.sqlite
    ```

    Next, open the `.env` file and ensure the database connection is set to `sqlite`. The `DB_DATABASE` key will be ignored, but it's good practice to leave `DB_CONNECTION` correctly set.

    ```dotenv
    DB_CONNECTION=sqlite
    # The following variables will be ignored by the sqlite driver
    # DB_HOST=127.0.0.1
    # DB_PORT=3306
    # DB_DATABASE=laravel
    # DB_USERNAME=root
    # DB_PASSWORD=
    ```

6.  **Run database migrations and seeders**
    This will create all the necessary tables in your `database.sqlite` file and seed it with a default user.

    ```bash
    php artisan migrate --seed
    ```

7.  **Serve the application**
    You can now start the local development server.

    ```bash
    php artisan serve
    ```

    The API will be available at `http://127.0.0.1:8000`.

-----

## 📖 API Documentation (Swagger)

This project uses Swagger for interactive API documentation. Once the application is running, you can access the documentation in your browser.

  * **Swagger UI**: [http://127.0.0.1:8000/api/documentation](http://127.0.0.1:8000/api/documentation)

The raw OpenAPI specification file is also available in the project at `storage/api-docs/api-docs.json`.

-----

## 💻 API Usage

### Authentication

To use the protected endpoints, you first need to log in to get an API token. The database seeder creates a default user with the following credentials:

  * **Email**: `user@example.com`
  * **Password**: `password`

Send a `POST` request to `/api/login` or use the Swagger UI to test the endpoint. The response will contain an `access_token`. Include this token in the `Authorization` header for all subsequent requests (`Authorization: Bearer <YOUR_ACCESS_TOKEN>`).

### Endpoints

For a complete list of endpoints and to try them live, please visit the **Swagger Documentation**.

-----

## 🧪 Running Tests

This project includes a comprehensive test suite. Instead of using dedicated tools like JMeter, this project includes a suite of **sequential performance tests written with PestPHP** to benchmark API response times under repeated sequential load.

1.  **Run all tests**

    ```bash
    php artisan test
    ```

2.  **Run only Feature tests**

    ```bash
    php artisan test --testsuite=Feature
    ```

3.  **Run only Performance tests**

    ```bash
    php artisan test tests/Feature/Performance/
    ```