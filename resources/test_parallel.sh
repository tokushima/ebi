#!/bin/sh
#
# 並列機能テストランナー（ebi + testman）
# --------------------------------------------------------------------------
# worker 毎に独立したサーバ(HTTP) + SQLite DB + ストレージで testman -p を実行して
# 大幅短縮する。
#
# このスクリプトの責務は「worker 毎の HTTP サーバを起動/停止する」ことだけ。
# それ以外は testman / ebi が担う:
#   - worker 環境の seed        : testman --seed-workers（各 worker DB を fixture で用意）
#   - base 環境の seed          : testman 起動時の fixture 実行（--retry の再実行先）
#   - 失敗テストの救済           : testman --retry N（直列で再実行）
#   - worker 毎の DB/URL 分離     : ebi が TESTMAN_WORKER_ID から自動で行う
#
# 前提: PATH 上に testman(--retry/--seed-workers 対応版)、PHP built-in server。
#
# 使い方:
#   sh test_parallel.sh [N] [testdir]
#     N       並列 worker 数        （既定: CPU コア数）
#     testdir 対象ディレクトリ/ファイル（既定: test）
#   環境変数:
#     TESTMAN_BASE_PORT  ベースポート（既定 8888。worker は +1..+N を使う）
#     TESTMAN_RETRY      リトライ回数（既定 1）
#     SRV_WORKERS        各サーバの PHP worker 数（既定 2。自己ネスト HTTP のデッドロック回避）
#     TESTMAN            testman バイナリ（既定 testman。開発中の phar を差す用）
#
# 終了コード: testman の結果（0=全緑 / 1=リトライ後も失敗あり）
# --------------------------------------------------------------------------
set -eu

TESTS_DIR=$(cd "$(dirname "$0")" && pwd)
cd "$TESTS_DIR"

N="${1:-$(sysctl -n hw.ncpu 2>/dev/null || getconf _NPROCESSORS_ONLN 2>/dev/null || echo 4)}"
TESTTARGET="${2:-test}"
BASE_PORT="${TESTMAN_BASE_PORT:-8888}"
RETRY="${TESTMAN_RETRY:-1}"
SRV_WORKERS="${SRV_WORKERS:-2}"
TESTMAN="${TESTMAN:-testman}"

started_pids=""
started_ports=""
cleanup() {
	# pid と port の両方で確実に停止する（php -S の worker 子は pid kill だけだと残ることがある）
	for p in $started_pids; do kill "$p" 2>/dev/null || true; done
	for pt in $started_ports; do lsof -ti "tcp:$pt" 2>/dev/null | xargs -r kill 2>/dev/null || true; done
}
trap cleanup EXIT INT TERM

# $1 = worker slot 番号（空=base）, $2 = port。ebi が TESTMAN_WORKER_ID から DB/URL を分離する。
start_srv() {
	if [ -n "$1" ]; then
		TESTMAN_WORKER_ID="$1" PHP_CLI_SERVER_WORKERS="$SRV_WORKERS" php -S "127.0.0.1:$2" "$TESTS_DIR/test_router.php" >/dev/null 2>&1 &
	else
		PHP_CLI_SERVER_WORKERS="$SRV_WORKERS" php -S "127.0.0.1:$2" "$TESTS_DIR/test_router.php" >/dev/null 2>&1 &
	fi
	started_pids="$started_pids $!"
	started_ports="$started_ports $2"
}
is_up() { curl -s -o /dev/null --max-time 3 "http://127.0.0.1:$1/" 2>/dev/null; }
# 指定ポートに残っている「このテストの test_router.php サーバ」だけを停止する。
# 中断した前回 run の孤児がポートを占有すると新 run が bind できず壊れるため、worker
# ポートは起動前に掃除する。test_router.php 以外(他のサービス等)は絶対に触らない。
free_test_port() { # $1=port
	for _p in $(lsof -ti "tcp:$1" 2>/dev/null); do
		case "$(ps -o command= -p "$_p" 2>/dev/null)" in
			*"$TESTS_DIR/test_router.php"*) kill "$_p" 2>/dev/null || true ;;
		esac
	done
}

# --- サーバ起動: base(port BASE_PORT) + worker 1..N(port BASE_PORT+slot) ------
# DB の中身は testman が用意する（base=起動時 fixture / worker=--seed-workers）ので、
# ここでは空 DB のままサーバを立てるだけでよい。
if is_up "$BASE_PORT"; then
	echo "base server :$BASE_PORT … 既存を使用"
else
	start_srv "" "$BASE_PORT"
fi
# worker ポート(BASE_PORT+1..+N)は test 専用。孤児 test_router.php を掃除してから起動する。
i=1; while [ "$i" -le "$N" ]; do free_test_port "$((BASE_PORT+i))"; i=$((i+1)); done
i=1; while [ "$i" -le "$N" ]; do start_srv "$i" "$((BASE_PORT+i))"; i=$((i+1)); done
echo "started $N worker servers (:$((BASE_PORT+1))..:$((BASE_PORT+N)))"
sleep 2

# --- 実行（seed も retry も testman が担う）-----------------------------------
set +e
TESTMAN_BASE_PORT="$BASE_PORT" "$TESTMAN" -p "$N" --seed-workers --retry "$RETRY" "$TESTTARGET"
rc=$?
set -e
exit "$rc"
