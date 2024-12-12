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

6. Run database migrations:

    ```bash
    docker-compose exec php8.3-fpm php bin/console doctrine:migrations:migrate
    ```

7. Access the application at [http://localhost:8080](http://localhost:8080)