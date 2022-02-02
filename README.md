# PukiWiki用プラグイン<br>PWA対応 pwa.inc.php

サイトをPWA（プログレッシブ・ウェブ・アプリ）に対応させる[PukiWiki](https://pukiwiki.osdn.jp/)用プラグイン。

ウェブアプリマニフェストを提供し、サイトをアプリとしてスマートフォン等のホーム画面にインストールできるようにします。  
また、サービスワーカーによりリソースをキャッシュし、一度表示したページをオフラインでも閲覧できるようにします。  
HTTPS接続時のみ有効です。

|対象PukiWikiバージョン|対象PHPバージョン|
|:---:|:---:|
|PukiWiki 1.5.3 ~ 1.5.4RC (UTF-8)|PHP 7.4 ~ 8.1|

## インストール

下記GitHubページからダウンロードした pwa.inc.php を PukiWiki の plugin ディレクトリに配置してください。

[https://github.com/ikamonster/pukiwiki-pwa](https://github.com/ikamonster/pukiwiki-pwa)

## 使い方

1. pwa.inc.phpをPukiWikiのpluginディレクトリに配置する。
2. スキンファイルHTML内<head>と</head>の間の適当な箇所に次のコードを挿入する。
```
<?php if (exist_plugin_convert('pwa')) echo do_plugin_convert('pwa'); // PWA plugin ?>
```

## 設定

ソース内の下記の定数で動作を制御することができます。

|定数名|値|既定値|意味|
|:---|:---:|:---:|:---|
|PLUGIN_PWA_MANIFEST|文字列||ウェブアプリマニフェストファイルのURL（例：'manifest.webmanifest'）。空なら内蔵マニフェストを使用|
|PLUGIN_PWA_SERVICEWORKER|文字列||サービスワーカーファイルのURL（例：'sw.js'）。空なら内蔵サービスワーカーを使用|
|PLUGIN_PWA_APPLETOUCHICON|文字列||180×180px``アプリアイコンPNG画像のURL（例：'apple-touch-icon.png'）。空なら内蔵画像を使用|
|PLUGIN_PWA_ICON144|文字列||144×144px``アプリアイコンPNG画像のURL（例：'image/icon144.png'）。空なら内蔵画像を使用|
|PLUGIN_PWA_ICON192|文字列||192×192px``アプリアイコンPNG画像のURL（例：'image/icon192.png'）。空なら内蔵画像を使用|
|PLUGIN_PWA_ICON512|文字列||512×512px``アプリアイコンPNG画像のURL（例：'image/icon512.png'）。空なら内蔵画像を使用|
|PLUGIN_PWA_DISABLED|0 or 1|0|1なら本プラグインを無効化|

## 詳細

キャッシュを司るサービスワーカーの挙動は用途によりさまざまですが、本プラグインに内蔵のものは次のように動作します。

- リソースをサーバーに要求する
  - 正常に取得できたら、そのリソースをキャッシュに加える
  - オフライン等の理由により取得できなければ、キャッシュ内のリソースを用いる  
ただし、キャッシュ内に該当リソースがなければエラーを表示する

ウィキサイトの性質上、常にサーバーから最新のリソースを取得する必要があるため、キャッシュは消極的にしか使われません。  
したがって、ロードの効率化・高速化には寄与しません。  
なお、ここでいうキャッシュとはサービスワーカー専用のそれであり、ブラウザーのキャッシュとは異なることにご注意ください。
