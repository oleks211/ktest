install project

git clone git@github.com:oleks211/ktest.git
cd ktest
docker-compose build
docker-compose up -d
docker-compose exec php-fpm php composer.phar install
docker-compose exec php-fpm php bin/console doctrine:migrations:migrate

http://localhost:8080
