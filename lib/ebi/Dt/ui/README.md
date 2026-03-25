# DevTools UI

Developer Tools の React UI コンポーネント。

## 注意

- `lib/ebi/Dt/assets/app.js` はビルド成果物です。直接編集しないでください。
- 変更は `lib/ebi/Dt/ui/src/` 配下の TypeScript ソースを修正し、`npm run build` で反映します。

## 必要環境

- Node.js 18+
- npm

## ビルド方法

### npm を直接使う場合

```bash
cd lib/ebi/Dt/ui

# 依存関係のインストール
npm install

# 本番ビルド
npm run build
```

ビルドが完了すると `../assets/app.js` が生成されます。
