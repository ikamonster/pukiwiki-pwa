<?php
/**
PukiWiki - Yet another WikiWikiWeb clone.
pwa.inc.php, v1.1.3 2020 M.Taniguchi
License: GPL v3 or (at your option) any later version

サイトをPWA（プログレッシブウェブアプリ）に対応させるPukiWiki用プラグイン。

ウェブアプリマニフェストを提供し、サイトをアプリとしてスマートフォン等のホーム画面にインストールできるようにします。
また、サービスワーカーによりリソースをキャッシュし、一度表示したページをオフラインでも閲覧できるようにします。
HTTPS接続時のみ有効です。

【導入手順】
スキンファイルHTML内<head>と</head>の間の適当な箇所に次のコードを挿入する。
<?php if (exist_plugin_convert('pwa')) echo do_plugin_convert('pwa'); // PWA plugin ?>
*/

/////////////////////////////////////////////////
// PWA対応プラグイン（pwa.inc.php）
if (!defined('PLUGIN_PWA_MANIFEST'))       define('PLUGIN_PWA_MANIFEST',       ''); // ウェブアプリマニフェストファイルのURL。空なら内蔵マニフェストを使用
if (!defined('PLUGIN_PWA_SERVICEWORKER'))  define('PLUGIN_PWA_SERVICEWORKER',  ''); // サービスワーカーファイルのURL。空なら内蔵サービスワーカーを使用
if (!defined('PLUGIN_PWA_APPLETOUCHICON')) define('PLUGIN_PWA_APPLETOUCHICON', ''); // 180×180pxアイコンPNG画像のURL。空なら内蔵画像を使用
if (!defined('PLUGIN_PWA_ICON144'))        define('PLUGIN_PWA_ICON144',        ''); // 144×144pxアイコンPNG画像のURL。空なら内蔵画像を使用
if (!defined('PLUGIN_PWA_ICON192'))        define('PLUGIN_PWA_ICON192',        ''); // 192×192pxアイコンPNG画像のURL。空なら内蔵画像を使用
if (!defined('PLUGIN_PWA_ICON512'))        define('PLUGIN_PWA_ICON512',        ''); // 512×512pxアイコンPNG画像のURL。空なら内蔵画像を使用
if (!defined('PLUGIN_PWA_DISABLED'))       define('PLUGIN_PWA_DISABLED',        0); // 1なら本プラグインを無効化


// ブロック呼び出し：必要タグを出力
function plugin_pwa_convert() {
	// HTTPSでなければ何もしない
	if (PLUGIN_PWA_DISABLED || !$_SERVER['HTTPS']) return '';

	static	$included = 0;
	$body = '';

	// apple-touch-iconリンク
	if (!defined('IKASKIN_APPLETOUCHICON') || !IKASKIN_APPLETOUCHICON) {
		$body .= '<link rel="apple-touch-icon" href="' . (PLUGIN_PWA_APPLETOUCHICON ? PLUGIN_PWA_APPLETOUCHICON : './?plugin=pwa&type=icon180') . '" />';
	}

	// ウェブアプリマニフェスト
	if (!($included & (1 << 0))) {
		$included |= (1 << 0);
		$body .= '<link rel="manifest" href="' . (PLUGIN_PWA_MANIFEST ? PLUGIN_PWA_MANIFEST : './?plugin=pwa&type=manifest') . '" crossorigin="use-credentials" />';
	}

	// サービスワーカーJavaScript
	if (!($included & (1 << 1))) {
		$included |= (1 << 1);
 		$body .= '<script>';
		$body .= "'use strict'; if ('serviceWorker' in navigator) { window.addEventListener('load', function(){ navigator.serviceWorker.register('" . (PLUGIN_PWA_SERVICEWORKER ? PLUGIN_PWA_SERVICEWORKER : './?plugin=pwa&type=sw') . "'); }); }";
		$body .= "window.addEventListener('beforeinstallprompt', function(e) { e.preventDefault(); return false; });"; // インストールを促すメッセージを抑える（積極的にインストールさせたければこの行をコメントアウト）
		$body .= '</script>';
	}

	return $body;
}


// コマンド呼び出し：typeに応じてデータを出力
function plugin_pwa_action() {
	global	$vars, $page_title;
	$type = (isset($vars['type']))? $vars['type'] : null;
	$url = get_script_uri();

	switch ($type) {
	case 'manifest':	// ウェブアプリマニフェストを出力
		$title = htmlsc($page_title);
		$icon144 = $url . (PLUGIN_PWA_ICON144 ? PLUGIN_PWA_ICON144 : '?plugin=pwa&type=icon144');
		$icon192 = $url . (PLUGIN_PWA_ICON192 ? PLUGIN_PWA_ICON192 : '?plugin=pwa&type=icon192');
		$icon512 = $url . (PLUGIN_PWA_ICON512 ? PLUGIN_PWA_ICON512 : '?plugin=pwa&type=icon512');

		header('Content-Type: application/manifest+json; charset=utf-8');
		header('Cache-Control: max-age=604800');
		echo '{"name":"' . $page_title . '","short_name":"' . $page_title . '","start_url":"' . $url . '","display":"minimal-ui","background_color":"#ffffff","theme_color":"#ffffff","icons":[{"src":"' . $icon144 . '","type":"image/png","sizes":"144x144","purpose":"any maskable"},{"src":"' . $icon192 . '","type":"image/png","sizes":"192x192","purpose":"maskable"},{"src":"' . $icon512 . '","type":"image/png","sizes":"512x512","purpose":"maskable"}]}';
		exit;

	case 'sw':	// サービスワーカーを出力
		$hostName = (isset($_SERVER['HTTP_HOST']))? $_SERVER['HTTP_HOST'] : gethostname();
		$swCacheName = $hostName . '-pwa.inc.php-sw-1.11';
		header('Content-Type: application/javascript; charset=utf-8');
		header('Cache-Control: max-age=86400');
		echo <<< EOT
"use strict";const CACHE_NAME="{$swCacheName}";var errorPage='<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml"><head><meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1,viewport-fit=cover"/><meta name="format-detection" content="telephone=no"/><meta name="robots" content="noindex,nofollow,noarchive,nosnippet,noodp"/><title>ERR Internet Disconnected</title><style>html{font-family: "Trebuchet MS", Trebuchet, Verdana, Geneva, Arial, sans-serif;margin: 0;padding: 0;border: 0 none;width: 100%;height: 100%;font-size: 20px;font-feature-settings: "palt";user-select: none;-moz-user-select: none;-webkit-user-select: none;-ms-user-select: none;-webkit-font-smoothing: subpixel-antialiased;font-smoothing: subpixel-antialiased;-moz-osx-font-smoothing: unset;text-rendering: optimizeLegibility;-webkit-tap-highlight-color: rgba(0,0,0,0);-webkit-touch-callout: none;-webkit-text-size-adjust: 100%;}@media only screen and (-webkit-min-device-pixel-ratio: 2),(min-resolution: 2dppx){html{-webkit-font-smoothing: antialiased;font-smoothing: antialiased;-moz-osx-font-smoothing: grayscale;}}body{width: 100%;height: 100%;min-height: 100%;position: relative;margin: auto;padding: 0;overflow: visible;z-index: 1;display: flex;align-items: center;justify-content: center;}main{position: relative;margin: auto;width: 100%;height: 100%;max-width: 512px;max-height: 512px;text-align: center;}span{display: block;font-size: 3.2em;font-weight: bold;text-align: center;line-height: 1em;margin: auto;}div{position: absolute;width: 100%;left: 0;top: calc(50% - 3.65em);line-height: 2em;font-weight: bold;}a{display: none;outline: 0 none;}@media screen{:root{--bg-color: #fff;--fg-color: #202020;}html{color: var(--fg-color);background-color: var(--bg-color);}a{display: block;position: relative;font-size: 14px;text-decoration:none;vertical-align: middle;box-sizing: border-box;border-radius: 1em;border: 1px solid var(--fg-color);margin: 0.8em auto 0;line-height: 1.4em;width: 4.25em;color: var(--fg-color);}a:hover, a:focus{text-decoration: underline;}}@media screen and (prefers-color-scheme: dark), screen and (light-level: dim), screen and (environment-blending: additive){:root{--bg-color: #000;--fg-color: #ccccc0;}}}</style></head><body><main><div><span>ERR</span>Internet Disconnected<a href="/">HOME</a></div></main></body></html>';self.addEventListener("install",function(e){e.waitUntil(caches.open(CACHE_NAME).then(function(e){return e.addAll([new Request("{$url}",{cache:"no-cache"})])}))}),self.addEventListener("activate",function(e){var t=[CACHE_NAME];e.waitUntil(caches.keys().then(function(e){return Promise.all(e.map(function(e){if(-1===t.indexOf(e))return caches.delete(e)}))}))}),self.addEventListener("fetch",function(e){const t=e.request.url,n=new URL(t);"{$hostName}"===n.hostname&&"GET"===e.request.method&&/^https?:$/i.test(n.protocol)&&(navigator.onLine?e.respondWith(fetch(e.request).then(function(e){if(e.ok){let n=e.clone();caches.open(CACHE_NAME).then(function(e){e.put(t,n)})}return e}).catch(function(){return caches.match(t,{ignoreSearch:!0})})):e.respondWith(caches.match(t,{ignoreSearch:!0}).then(function(e){return e||new Response(errorPage,{status:200,headers:{"Content-Type":"text/html;charset=UTF-8","Cache-Control":"no-store,no-cache,must-revalidate"}})}).catch(function(){return new Response(errorPage,{status:200,headers:{"Content-Type":"text/html;charset=UTF-8","Cache-Control":"no-store,no-cache,must-revalidate"}})})))});
EOT;
		exit;

	case 'icon180':	// アイコン画像（180px）を出力
		header('Content-Type: image/png');
		header('Cache-Control: max-age=31536000,immutable,public');
		echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAALQAAAC0AQMAAAAHA5RxAAAABlBMVEX///8AAABVwtN+AAAA80lEQVRYw+3PsWrDMBCAYZmDeDv1AcrpNTqo6mtlyOByg7foDfoehQ4qHjrmDYohe+LRQzA5kUAk79nuRyD4lrszmqZpmqY9P7x9TVfYx2gMGfSb5sgdpIdfslvfNsNQ+XJ3GLh0H7JvXi38QldM8CQuz3IC0Ydbj+QC2T4yH1LtFNCJ97FwFPfBuj/LsXQS36J1B+BY7SM+Yfsu/lXd5dGN2H6fgX9Kn+9+Ap5LH8UnhHkBvhTuzc0n8WXltBXfAf+vfYdN9lg7kcdmFO+59BCIxN/W7hZyaLLzsHKLJmU/lt7uqd2b9AL8Ka5pmqZp2lO7ArCBZLUI1JYrAAAAAElFTkSuQmCC');
		exit;

	case 'icon144':	// アイコン画像（144px）を出力
		header('Content-Type: image/png');
		header('Cache-Control: max-age=31536000,immutable,public');
		echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAJAAAACQAQMAAADdiHD7AAAABlBMVEX///8AAABVwtN+AAAB7UlEQVRIx+3Vwa0cIQwGYCMOHEkHtJEbpcGrJKU8pBSQFiiBIweEg2d3PAZHTyngIe1K8+3i+Y01Gvhe/7s8lpMStkMsYj8oIA61D+cuBtfayWkKRHkvpcjgQb7Zi4pM+YeJS//G7iVRFcxGkKVEP+mmTJ66+wEQH4p0BoNKFlGqWiTKImXx65I+T3WqI8ghzuuUHRHf0CB2QWFdusWLRIbqEaeghFgWI5HoMF00RSxAciaLOC1RkTQcUXXjSdr9Tn5dRKLmBZVE1H1/wmdUZC4aoXE/0yp6ZcAZKrfYg6IWXxQLd13TRSiovARTZvp4kzgue5DB6d4ymYZX1MObxhd0J30eIctJsTEVVMRJKxMnLTdR0h42Gouq24lGZDeiTdls1KKiughAkilJEyr6YMo3GUVgNTlFluiVi//kNQWkHiX5X0gnISl80mwijZEJ78UUmQbTp6LEO/tNqGn6c7Jmui+o8sz0sIc5yXWmzAQ38UF0SEeLvkE8+gkNAodn8ntSiA0cJ2UyMhbR+nHPAKku5gxMgagzUVXH1ZkMUZbE7xtJTgSlR+/6RjjIsjD5uRHliV0Rto0GlaqSKJDHLIliJ4SdqsOxE62uqWrKiiYoGhsBUdNUdkpyXPwkDE31IK/2geVuRH34Xv9cfwFIOzdvAu5yCQAAAABJRU5ErkJggg==');
		exit;

	case 'icon192':	// アイコン画像（192px）を出力
		header('Content-Type: image/png');
		header('Cache-Control: max-age=31536000,immutable,public');
		echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAMAAAADAAQMAAABoEv5EAAAABlBMVEX///8AAABVwtN+AAABBklEQVRYw+3PMWrDMBSAYZk3eCnVBYrpETx2CORKhS4aCnmgoaNv0LPIaPDYG7TPeMgYhywuBDvPdiCSMmV//6BBH3qSlCRJkiRJ0kO9qjUX7OmBF1Jv/RN01im6wbSAYfDeqT4AXOH5DtwyyhTg41H5AsoUOUJ0OdAK2xSyPRL9D5ttXtm66xM4MmhtvU+gbYfNrtK2sQFAg9Q587KrwP5gAJphj6b4+Ab7F/3jC+mgGH7BjhHkSBPDieEcwjgDGn08JyfUFVqG6Q7cCocUTs7kbpifm0BLKzQuBKip7Q3M4GOYiBjQMFAKhuEdbB1BNhJ9MpRgXc9wq1RchqWSJEmSJEl6pAsxP6imR/keIAAAAABJRU5ErkJggg==');
		exit;

	case 'icon512':	// アイコン画像（512px）を出力
		header('Content-Type: image/png');
		header('Cache-Control: max-age=31536000,immutable,public');
		echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAgAAAAIAAQMAAADOtka5AAAABlBMVEX///8AAABVwtN+AAAC90lEQVR42u3XMW7bMBTGcQoctJVH0E3KixWhgg4Zc4QeJdo69goKeoCw6FACJfj6RFKiYsci1XYKvv8gEDD40zNoC7ZACCGEEEIIIYQQQgghhBBCCCGEEEIIIYQQQgghhBBCCCGEEHo39aO47sMJwLwFBHFcR1NZRuBOpNK6j0Dnbt+VaC5AvEaTd6ggaExA748A9wqQNC6kZ8B3GWDrCKAbwMDA1ASM10AXAdkIzK+ByTAQGHAFoEPAXkxgbAS0O3gLNUDSGcAVIF51Bmy3HeMBYDX5AnBBMNDT8pKgvFW628DcXQCfeHYGxgX4mIHOHgDC7IDYsAFl+CNAh2tA8ZrmRmC4AjwDUzugLgH1f4COpmZgO/WOZkMjiwPNDOj4OejpnsIR0BdA0nQNKOqaAT49BvoISBoTMIR2QJFYABKabAF8BVAFGMIbgHbtgPYLICPATALMGcAlYDQ7gGw7YOwGuBXgo60AQwEoAov04vqQAElTBdB+Be5pFnIBJvrmVRDdAixfTHUImA34TNM1oEgcA5LcCjzQuACCgUevfAZC7ZlYgEcSCXimh7ACg68CdgPCDhgyoF078MUnwDwT39klwNSBeQWeXAa+k6QVIFsFphUgm4GfDOgEsF8FxAbkCfSv0JG2CahPEArgd8BTM2ALEHYArYCrAdMVMPAoBfDHwIsVBaA9MLcBs6gB4QwwRkCRE+ZvgN807YApAS/UDvygOQO2AF9pbAaed4DOwPKQaAYmshHoIzAmQJ4ARnMB5IdqAzBmwGVgFsMKCLLtgPZxLSMgzgFmAYa3AOMqQPrTQBEIGZiE2gDdAswyAooi0C1A2ADfAFh1GxhCFRDE5R8YCeCT2AB1ApA0LYBYAL8B9I8Aj1UFTAY6mhMghDwF6AwIshEwDLgV4LGqwED0dBPgsaqAIq8jYFwE7njzBrBaBSTZBGgfgY+82TYAJS32ZQAhhBBCCCGEEEIIIYQQQgghhBBCCCGEEEIIIYQQQgghhBBC6N30B2P7+C3Re66mAAAAAElFTkSuQmCC');
		exit;
	}

	return array('msg' => 'PWA plugin', 'body' => 'Bad request');
}
