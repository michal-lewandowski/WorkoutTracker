<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

\passthru(\sprintf('php "%s/../bin/console" doctrine:database:drop --if-exists --env=test --force --no-interaction --quiet', __DIR__));
\passthru(\sprintf('php "%s/../bin/console" doctrine:database:create --env=test --no-interaction --quiet', __DIR__));
\passthru(\sprintf('php "%s/../bin/console" doctrine:migrations:migrate --env=test --no-interaction --quiet', __DIR__));

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
