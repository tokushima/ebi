#!/bin/sh

PORT=8000
WORKERS=10
PHP_CLI_SERVER_WORKERS=${WORKERS} php -S 0.0.0.0:${PORT} test_router.php
