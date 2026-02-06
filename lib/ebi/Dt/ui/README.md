# DevTools UI

Developer Tools の React UI コンポーネント。

## 必要環境

- Node.js 18+
- npm

## ビルド方法

### Makefile を使う場合

```bash
cd lib/ebi/Dt/ui

# ビルド（npm install + npm run build）
make build

# または単に
make
```

### npm を直接使う場合

```bash
cd lib/ebi/Dt/ui

# 依存関係のインストール
npm install

# 本番ビルド
npm run build
```

ビルドが完了すると `../assets/app.js` が生成されます。

## 開発モード

```bash
# 開発サーバー起動（ホットリロード対応）
make dev

# または
npm run dev
```

開発サーバーは http://localhost:5173 で起動します。

## クリーンアップ

```bash
make clean
```

`node_modules/`、`dist/`、`../assets/` を削除します。

## ファイル構成

```
ui/
├── src/
│   └── main.jsx    # React アプリケーション
├── index.html      # 開発用 HTML
├── package.json    # npm 設定
├── vite.config.js  # Vite 設定
├── Makefile        # ビルドスクリプト
└── README.md       # このファイル
```

## 出力

ビルド後、`lib/ebi/Dt/assets/app.js` が生成され、`Dt.php` から読み込まれます。
