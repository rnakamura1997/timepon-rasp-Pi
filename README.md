# カンファレンスタイマー「TIME-PON」

## 概要
「TIME-PON」はカンファレンスやプレゼンテーションなどの場で、遠隔操作で講演者に残り時間を表示したり、カンペを出すための Web アプリケーションです。  

- タイマーを演台において、制御側はオペレータが操作するという、ビューとコントロールが完全に分離されたタイマーシステムです。
- スピーカービューはカウントダウンタイマーと、オペレーターからの「カンペ」を見ることができます。
- オペレータは、全体の時間や警告時間などを設定し、適時スピーカーに「カンペ」を送ることができます。

- PHP による単一ファイル・軽量構成 (`index.php` など) で動作。
- 複数ルームを同時に作成・管理が可能。各ルームごとに “演台”（発表用画面）と “オペレータ”（管理用画面）を持つ。
- 持ち時間・警告タイミング・色の遷移などをカスタマイズ可能。
- 発表中の“カンペ”（補助メモ）表示／非表示、時間の一時停止・リセット等、操作性を考慮したショートカット付き。
- JSON データ形式で各ルームの状態を `data/` ディレクトリに保存。ルーム削除の自動処理あり。
- 単に１つのルームだけでなく **複数のルームを同時に管理**するダッシュボードもあります。

このリポジトリには Web サーバーに配置する PHP ファイル（`index.php` 等）その他、設定やデータ保存用のディレクトリ構造、ショートカットキーなどのクライアント／サーバー間の挙動制御コードが含まれています。

---

## 設置方法
1. 本ファイルを Web サーバーの公開ディレクトリに配置してください（例: `/public_html/timepon.php` または `index.php` 等）。  
2. 同階層に `data/` が無い場合は自動作成されます。手動作成時は **0775 以上の書込権限** を付与してください。  
   - 直接アクセス対策として `data/` を直リンク不可にすることを推奨（例: `.htaccess` で `Deny from all`）。  
   - もしくは公開領域外に移し、`id_to_file()` のパスを変更してください。  
3. PHP 8 以上推奨。排他制御／ロック（flock）が使用できる環境を推奨。

---

## 使い方

- ルートURLにアクセスし、「オペレータ」または「演台」を選択。  
- 新規ルーム作成で6桁IDが発行されます。  
  - 管理URL: `?op=1&id=XXXXXX`  
  - 演台URL: `?id=XXXXXX` を発表者などに配布してください。  
- ダッシュボード画面（オペレータ画面）では、複数ルームの状態一覧が見られます。各ルームに対して、持ち時間・第1・第2警告の設定変更、タイマーのスタート／一時停止／リセットなどが可能です。  
- 時計設定（持ち時間・第1/第2警告）はルームIDと独立に保存・更新されます（同じIDのまま）。  
- 警告色の遷移：グレー → 黄色（第1警告） → 朱色（第2警告） → 赤（0秒以降はやわらか点滅）。

---

## ショートカットキー

- **SHIFT+SPACE** : スタート / 一時停止のトグル  
- **SHIFT+R**     : リセット  
- **SHIFT+K**     : カンペ送信  
- **SHIFT+C**     : カンペ消去  

---

## 注意事項

- **ルームの保存期間**: 最後の更新から 7 日で自動削除されます。  
- **管理キー**: 作成時に生成され、管理URL の `#k=` フラグメントに含まれます。管理URLは共有しないでください。  
  - 演台URL（`?id=XXXXXX`）のみ登壇者へ配布してください。  
  - adminKey は推測困難ですが再発行はできません。  
- **6桁 ID**: ID 自体は秘匿情報ではありません（推測可能）。「管理URL」を漏らさない運用が前提です。  
- **保存データ**:  
  - 持ち時間・警告設定・カンペ・ステージ状態・adminKey が `data/` 配下の JSON に保存されます。  
  - 個人情報や機微情報はカンペに入力しないでください。  
  - パーミッションの目安: `dir=0775`, `file=0664`（umask 007）。  
- **レート制限**: 作成 10 件／分、HB 300 件／分、書き込み 120 件／分（IP 単位）。  
  - `429`／エラー相当の応答が出たら、間隔を空けて再試行してください。  
- **免責**: MIT ライセンス・無保証です。本番利用前に各自の環境・要件に合わせて十分なテストを行ってください。  

---

## ライセンス

MIT License

Copyright (c) 2025 Tetsu Suzuki

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.


---

## Phase 3: 実機運用メモ（Raspberry Pi 3B 想定）

### ローカルAP経由の基本運用
1. Raspberry Pi を会場内ローカルAP（オフライン可）として起動。
2. オペレータ端末と演台端末を同一APに接続。
3. まずオペレータ画面を開き、次に演台画面を開く。
4. 演台側で一度「チャイム有効化」を押して、ブラウザの音声再生許可を得る。

### URLの考え方（固定ルーム）
- 管理画面: `/admin.php?room={roomId}`
- 演台画面: `/stage.php?room={roomId}`
- 代表的な固定ルームURL（互換）: `/admin/main`, `/stage/main`, `/admin/sub`, `/stage/sub`
- ルームID一覧やURL素材は `api.php?act=room_meta&room={roomId}` と `api.php?act=cli_snapshot` で取得可能。

### チャイム有効化ボタン
- ブラウザの自動再生制限回避のため、演台端末のユーザー操作で有効化が必要。
- 有効化前はチャイムが鳴らない場合がある（仕様）。

### 会場Wi-Fiは補助経路
- 本番はローカルAPを主経路とし、会場Wi-Fiは補助（障害時切替）を推奨。
- 通信断があっても状態はサーバ側に保存されるため、再接続後に復帰可能。

### 既知の制約
- ブラウザの自動再生制限により、無操作状態の初回チャイム再生がブロックされることがある。
- 端末スリープ／省電力時は heartbeat が遅延し、監視画面で一時的に offline 判定になることがある。
- 音源が存在しない設定は安全側（該当チャイム無効化＋既定候補へフォールバック）に丸める。

---

## Phase 4: Raspberry Pi ローカル運用仕上げ

### 追加したCLI/TUI（LCD向け）
- `scripts/timepon_cli_tui.py` は `api.php?act=cli_snapshot` を定期取得して、Pi の小型LCD向けに以下を表示します。  
  - AP接続情報（`--base-url` とAPI先）
  - 管理画面URL
  - 演台画面URL
  - 固定ルーム一覧
  - 現在状態（idle/running/paused）
  - 残り時間（`HH:MM:SS`）
  - stage 接続状態（online/stale/offline）
- GUIやChromiumを前提にせず、SSHコンソールでも同じ情報が確認できます。

実行例:
```bash
python3 scripts/timepon_cli_tui.py --base-url http://127.0.0.1
python3 scripts/timepon_cli_tui.py --base-url http://192.168.50.1 --interval 2 --offline-sec 10
```

### Raspberry Pi セットアップスクリプト
- `scripts/setup_pi.sh` を追加。以下を一括実施します。
  1. `php`, `lighttpd` など最低限パッケージ導入
  2. 配置先 (`/opt/timepon`) と公開パス (`/var/www/timepon`) 準備
  3. リポジトリ内容を配置先へ同期
  4. `var/data`, `var/chimes`, `var/log` を作成し権限設定
  5. `app/config/config.local.php` の初期配置
- `config.local.php` がある場合は、`storage_dir` や `chime_dir` を環境別に上書きできます。

実行例:
```bash
chmod +x scripts/setup_pi.sh
./scripts/setup_pi.sh
```

### 実機運用向けの調整方針
- Web UI は既存導線を維持し、運用監視は CLI/TUI に分離。
- API は `cli_snapshot` だけを定期参照し、画面側の責務と重複しないように整理。
- ポーリング間隔は既定2秒（必要に応じて調整）で、Pi 3B への負荷を抑制。
- stage監視は `stageLastSeen` の差分判定のみとし、重い追加処理を導入しない。

### AP経由の運用手順（再掲+補強）
1. Pi をローカルAPとして起動し、オペレータ端末・演台端末を同一APへ接続。
2. CLI/TUI で `admin.php?room=...` / `stage.php?room=...` のURLを確認。
3. オペレータは管理画面、演台は演台画面を開く。
4. 演台端末で「チャイム有効化」を押して音声再生を許可。
5. 会場Wi-Fiは補助経路（バックアップ）として扱い、主経路はローカルAPを維持。

### トラブル時の確認
- CLI/TUIで `stage=offline/stale` の場合:
  - 演台端末の画面スリープ解除
  - AP再接続状態を確認
  - `api.php?act=cli_snapshot` が取得可能か確認
- チャイムが鳴らない場合:
  - 演台端末で「チャイム有効化」を再実施
  - 音量・ブラウザ自動再生制限・音源ファイル存在を確認
