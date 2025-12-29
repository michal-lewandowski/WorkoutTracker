
docker compose exec -T php php bin/console doctrine:database:drop --if-exists --force --env=dev
docker compose exec -T php php bin/console doctrine:database:create --if-not-exists --env=dev
docker compose exec -T php php bin/console doctrine:migrations:migrate --no-interaction --env=dev
docker compose exec -T php php bin/console doctrine:fixtures:load --no-interaction --env=dev
docker compose exec -T php php bin/console app:seed:exercises
