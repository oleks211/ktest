# Project Installation

To install and run the project, follow these steps:

1. Clone the repository:

    ```bash
    git clone git@github.com:oleks211/ktest.git
    ```

2. Navigate to the project directory:

    ```bash
    cd ktest
    ```

3. Build the Docker containers:

    ```bash
    docker-compose build
    ```

4. Start the containers in the background:

    ```bash
    docker-compose up -d
    ```

5. Install PHP dependencies using Composer:

    ```bash
    docker-compose exec php8.3-fpm php composer.phar install
    ```

6. Install PHP dependencies using Composer:

    ```bash
    docker-compose exec php8.3-fpm chmod -R 777 /var/www/html/var
    ```

7. Run database migrations:

    ```bash
    docker-compose exec php8.3-fpm php bin/console doctrine:migrations:migrate
    ```

8. Access the application at [http://localhost:8080](http://localhost:8080)

## REQUESTS
#### Create User Limits
```bash
curl -X POST http://localhost:8080/ut-limits \
-H "Content-Type: application/json" \
-d '{
    "user_id": 1,
    "daily_limit": "5000.00",
    "monthly_limit": "150000.00",
    "created_at": "2024-12-10T00:00:00+00:00",
    "updated_at": "2024-12-10T00:00:00+00:00"
}'
```

#### View User Limits
```bash
curl -X GET http://localhost:8080/limits/1
```

#### Add Transaction
```bash
curl -X POST http://localhost:8080/transactions \
-H "Content-Type: application/json" \
-H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
-d '{
    "uuid": "3d54185f-907e-4285-a56d-cc06bc6b99e5",
    "user_id": 1,
    "amount": 500.00,
    "status": "success",
    "date": "2024-12-11T12:00:00Z"
}'
```