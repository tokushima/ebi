#!/bin/sh
# ebi アプリを PHP built-in server で起動する（開発 / 手動テスト用）。
#   sh test_server.sh [port]
PORT="${1:-8888}"
DIR=$(cd "$(dirname "$0")" && pwd)
PHP_CLI_SERVER_WORKERS="${SRV_WORKERS:-2}" php -S "0.0.0.0:${PORT}" "$DIR/test_router.php"
