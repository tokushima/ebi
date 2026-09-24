#!/usr/bin/env bash
# ebi 配信 SPA 共通デプロイスクリプト:
#   SPA プロジェクトをビルドし、dist/ で配布先 (ebi server repo の各アプリの resources/spa) をミラー置換する。
#
# ── 原本と配布 ─────────────────────────────────────────────────────────────
#   このファイルの原本は ebi の resources/ に置いてあり、各 SPA プロジェクトへはコピーして使う。
#   - コピー先のファイルは直接いじらない。改修は原本に入れてから各プロジェクトへコピーし直す。
#   - プロジェクト固有の値 (配布先パス) はスクリプトに書かず、各プロジェクトの .env.local に置く。
#
# ── 新しい SPA プロジェクトへの導入手順 ─────────────────────────────────────
#   1. このファイルを SPA プロジェクトのルート (package.json と同じ階層) にコピーし、chmod +x する。
#   2. .gitignore に `*.local` (または `.env.local`) を追加する。配布先は個人 PC 固有なのでコミットしない。
#   3. .env.local に配布先を書く (初回実行時に対話入力して保存することもできる):
#        SPA_DEPLOY_DEST="/path/to/ebi/.../<App>/resources/spa"
#   4. 前提: `npm run build` で dist/ に出力されること。vite.config.ts の base は './' にする (下記【重要】)。
#
# ── 使い方 ─────────────────────────────────────────────────────────────────
#   ./deploy-spa.sh                         # .env.local の SPA_DEPLOY_DEST へ (無ければ対話入力)
#   ./deploy-spa.sh /path/to/spa            # 配布先を第1引数で指定
#   SPA_DEPLOY_DEST=/path ./deploy-spa.sh   # env で指定
#   優先順位: 第1引数 > env SPA_DEPLOY_DEST > .env.local > 対話入力
#
#   ※ 配布先は rsync --delete で丸ごと置き換わる。パスを間違えると別ディレクトリの中身が消えるので注意。
#
# 【重要 / ebi SPA 共通の前提】base は必ず相対 = vite.config.ts の base:'./' のままにする。
#   ebi (App::serve_spa) は index.html の src/href しか media パスへ書き換えず、JS/CSS 内の
#   アセット参照は書き換えない。base:'/' だと /assets/*・/fonts/* が 404 になる。
#   理由の詳細 → vite.config.ts の base コメント / ebi の App::serve_spa docblock。
set -euo pipefail
cd "$(dirname "$0")"

# 配布先: 第1引数 → env SPA_DEPLOY_DEST → .env.local の SPA_DEPLOY_DEST → 対話入力 の順で決める。
# .env.local は Vite 標準のローカル専用 env (要 gitignore)。VITE_ 接頭辞なしなのでバンドルには入らない。
ENV_LOCAL=".env.local"
read_env_local() {
  [ -f "$ENV_LOCAL" ] || return 0
  # source せず該当行だけ読む (他の変数でシェル環境を汚さない)。前後のクォートは外す。
  sed -n 's/^[[:space:]]*SPA_DEPLOY_DEST[[:space:]]*=[[:space:]]*//p' "$ENV_LOCAL" | tail -n 1 \
    | sed -e 's/^["'\'']//' -e 's/["'\'']$//'
}
DEST="${1:-${SPA_DEPLOY_DEST:-$(read_env_local)}}"
if [ -z "$DEST" ]; then
  if [ ! -t 0 ]; then
    echo "✗ 配布先が未指定です (非対話実行)。第1引数 / SPA_DEPLOY_DEST / $ENV_LOCAL のいずれかで指定してください。" >&2
    exit 1
  fi
  read -r -e -p "配布先 (resources/spa) のパス: " DEST
  DEST="${DEST/#\~/$HOME}"
  if [ -z "$DEST" ]; then
    echo "✗ 配布先が入力されませんでした。" >&2
    exit 1
  fi
  read -r -p "このパスを $ENV_LOCAL に保存しますか? [y/N] " save
  if [[ "$save" =~ ^[Yy]$ ]]; then
    printf 'SPA_DEPLOY_DEST="%s"\n' "${DEST%/}" >> "$ENV_LOCAL"
    echo "  → $ENV_LOCAL に保存しました"
  fi
fi
DEST="${DEST%/}"

# 置換は破壊的なので、配布先の親が無ければパス誤りとみなして中断する。
parent="$(dirname "$DEST")"
if [ ! -d "$parent" ]; then
  echo "✗ 配布先の親ディレクトリが見つかりません: $parent" >&2
  echo "  第1引数 / SPA_DEPLOY_DEST / $ENV_LOCAL のいずれかで正しいパスを指定してください。" >&2
  exit 1
fi

echo "▶ build  (npm run build)"
npm run build

echo "▶ deploy → $DEST"
mkdir -p "$DEST"
# dist/ の中身で配布先を完全置換 (--delete で配布先に残る古いファイルも消す = ミラー)。
rsync -a --delete dist/ "$DEST/"

echo "✓ done: $DEST"
