# HanbellShop — task helper
#
# WHY THIS FILE EXISTS
#
# This machine injects a complete foreign application's configuration into the
# process environment — APP_NAME=Templatr, APP_URL=localhost:8000, DB_CONNECTION
# = mysql / DB_DATABASE=templatr, CACHE_STORE=file, SESSION_DRIVER=file,
# MAIL_MAILER=smtp and more.
#
# Laravel's Dotenv deliberately never overrides a variable that is already
# present in the environment, and phpunit.xml's <env> entries are subject to the
# same rule. So those injected values win over both this project's .env AND its
# test configuration. The symptoms are confusing and unrelated to each other:
#
#   * `php artisan migrate` tries to reach a MySQL database called "templatr"
#   * `php artisan about` reports the application name as "Templatr"
#   * tests run against the FILE cache, not the array cache phpunit.xml asks for,
#     which makes them write to storage/framework/cache on every run — slower,
#     and a recurring source of "Failed to open stream: Permission denied"
#
# Clearing them here means .env and phpunit.xml are authoritative, every time.
#
# Usage:  . .\art.ps1     then   art migrate,  art serve,  tst,  npmrun build

function Clear-HanbellForeignEnv {
    $vars = @(
        # Database
        'DB_CONNECTION', 'DB_DATABASE', 'DB_HOST', 'DB_PORT',
        'DB_USERNAME', 'DB_PASSWORD', 'DATABASE_URL', 'DB_SOCKET',
        # Application identity
        'APP_NAME', 'APP_URL', 'APP_ENV', 'APP_KEY', 'APP_DEBUG',
        'APP_LOCALE', 'APP_FALLBACK_LOCALE', 'APP_FAKER_LOCALE',
        'APP_MAINTENANCE_DRIVER',
        # Drivers that change behaviour between environments
        'CACHE_STORE', 'CACHE_PREFIX', 'SESSION_DRIVER',
        'QUEUE_CONNECTION', 'BROADCAST_CONNECTION', 'FILESYSTEM_DISK',
        # Mail — the injected MAIL_* points at a foreign SMTP host
        'MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME',
        'MAIL_PASSWORD', 'MAIL_SCHEME', 'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME',
        # Logging
        'LOG_CHANNEL', 'LOG_STACK', 'LOG_LEVEL', 'LOG_DEPRECATIONS_CHANNEL'
    )

    foreach ($v in $vars) {
        Remove-Item "env:$v" -ErrorAction SilentlyContinue
    }
}

function art {
    Clear-HanbellForeignEnv
    & php artisan @args
}

function tst {
    Clear-HanbellForeignEnv
    & php artisan test @args
}

function npmrun {
    Clear-HanbellForeignEnv
    & npm run @args
}

Clear-HanbellForeignEnv
