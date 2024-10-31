<?php   
/*  
Plugin Name: QF-GetThumb
Plugin URI: http://la-passeggiata.com/?p=290&lang=en
Description: QF-GetThumb is a plug-in that extracts the image data from the content and the argument, and makes the thumbnail.
Version: 1.1.3
Author: Q.F.
Author URI: http://la-passeggiata.com/?lang=en


Copyright 2009 Q.F. (email : info@la-passeggiata.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/


// 多言語ファイル読込
if (version_compare($wp_version, '2.6', '<')) {
	load_plugin_textdomain('wpqfgt', 'wp-content/plugins/qf-getthumb/languages');
}else{
	load_plugin_textdomain('wpqfgt', 'wp-content/plugins/qf-getthumb/languages', 'qf-getthumb/languages');
}

// イメージ編集用ライブラリ
require_once('fnc_image.php');

// オプション設定
$data = qf_load_default();

// 初期値をデータベースへ登録
add_option('qf_get_thumb_settings',$data,'qf_get_thumb Options');

// 管理者メニューに設定画面を呼び出し
add_action('admin_menu', 'qf_get_thumb_options');

// 記事ソース内の指定された画像を1つ抽出する関数
//
// num=0	  : 何番目の画像を取り出すかの指定
// width=0	  : 画像の幅指定
// height=0	  : 画像の高さ指定
// tag=1	  : イメージタグを返すか / 返さないか(返さない場合、画像のURLを返す)
// global=0	  : 同一サーバ内のデータに限定するかどうか
// crop_w=0	  : クロップ時の横幅
// crop_h=0	  : クロップ時の縦幅
// find=string	  : 検索文字列(全文検索一致データの画像指定)
// $default_image : 画像が無い場合は、ここに指定された値を返す(無ければfalse を返す)
// $source	  : ソース取得先の指定(無ければ the_content を参照する)
//
function the_qf_get_thumb_one($gt_settings = "", $default_image = "", $source = Null) {
	// 記事内容をグローバル変数で定義
	global $post;

	// 参照先指定が無ければ、content をベースにする
	if (is_null($source)) { $source = $post->post_content; }

	// フォーマット変数初期化
	$format = NULL;

	// WP設定データを取得
	$settings = get_option('qf_get_thumb_settings');
	if ($default_image == "") { $default_image = $settings['default_image']; }

	// base_path の Windows <=> Linux パス互換対策及びパス整形
	$settings['base_path'] = str_replace("\\", "/", $settings['base_path']);
	$settings['base_path'] = str_replace("//", "/", $settings['base_path']."/");

	// スラッシュの重複消去
	$settings['full_domain_name'] = $settings['full_domain_name']."/";
	while(strstr($settings['full_domain_name'], "//")) {
		$settings['full_domain_name'] = str_replace("//", "/", $settings['full_domain_name']);
	}
	$settings['full_domain_name'] = str_replace(":/", "://", $settings['full_domain_name']);

	// 設定取得
	$gt_settings = qf_get_parameter($gt_settings, $default_image);

	// 指定箇所のイメージタグを取得
	$imgtag = qf_get_imagetag($source, $gt_settings['num'], $gt_settings['find'], $settings['full_domain_name'], $gt_settings['global'], $gt_settings['default_image']);

	// イメージタグからイメージパスを取得
	$url = qf_get_imagepath($imgtag);

	// 引数に問題が無いかチェックの上、修正を実行
	if (!$gt_settings['width'] && !$gt_settings['height']) {
		list($gt_settings['width'], $gt_settings['height'], $format) = @getimagesize($url);
	}

	// デフォルトイメージ出力の場合、ここで処理を終える
	if ($url == $gt_settings['default_image'] && $gt_settings['tag'] == 1) {
		return $imgtag;
	}elseif($url == $gt_settings['default_image'] && $gt_settings['tag'] == 0) {
		return $gt_settings['default_image'];
	}

	// 保存先を設定
	$save = qf_get_savepath($url, $settings['base_path'], $settings['full_domain_name'], $settings['append_text'], $settings['folder_name'], $gt_settings['width'], $gt_settings['height'], $gt_settings['crop_w'], $gt_settings['crop_h']);

	// キャッシュファイルが存在しなければ、サムネイル生成・保存
	if (!file_exists($save)) {
		// サムネイルデータ生成
		$image = qf_make_thumbnail($url, $gt_settings['width'], $gt_settings['height']);

		// イメージデータクロップ実行
		$image = qf_make_cropimage($image, $gt_settings['crop_w'], $gt_settings['crop_h']);

		// 保存先ディレクトリ確認・生成
		if (!qf_check_savedir($save)) {
			return false;
		}

		// 対象ファイルのフォーマット取得
		if (is_null($format)) {
			$format = getformat_image($url);
		}

		// サムネイル保存
		qf_save_thumbnail($save, $image, $format);
	}

	// サムネイルファイルのフルパスを返す
	if ($gt_settings['tag'] == 1) {
		// イメージタグを整形して出力
		return qf_get_new_imagetag($imgtag, $url, $save, $settings['base_path'], $settings['full_domain_name']);
	}else{
		return qf_get_thumburl($save, $settings['base_path'], $settings['full_domain_name']);
	}
}


// イメージタグを整形
function qf_get_new_imagetag($imgtag, $url, $save, $base_path, $full_domain_name) {
	// まずはURLを変換
	$imgtag = str_replace($url, str_replace($base_path, $full_domain_name, $save), $imgtag);

	// イメージタグ内から width、height 要素を削除
	$tmp_imgtag = split(' ', $imgtag);
	foreach ($tmp_imgtag as $tmp_imgurl) {
		if(preg_match("/([Ww][Ii][Dd][Tt][Hh])([\s\t\n\r]*)=([\s\t\n\r]*)/" , $tmp_imgurl)) {
			$imgtag = str_replace($tmp_imgurl, "", $imgtag);
		}elseif(preg_match("/([Hh][Ee][Ii][Gg][Hh][Tt])([\s\t\n\r]*)=([\s\t\n\r]*)/" , $tmp_imgurl)){
			$imgtag = str_replace($tmp_imgurl, "", $imgtag);
		}
	}

	return $imgtag;
}


// 設定取得
function qf_get_parameter($gt_settings, $default_image) {
	// WP設定データを取得
	// 設定文字列のデコード処理
	$pairs = split("&", $gt_settings);
	$gt_settings = "";

	// 受け取った引数「$gt_settings」をデコード
	foreach ($pairs as $data) {
		$data = split("=", $data);
		$gt_settings[$data[0]] = $data[1];
	}

	// 設定値型変換・初期設定
	if (is_null($gt_settings['tag'])) { $gt_settings['tag'] = 1;}
	$gt_settings['tag'] = (int)$gt_settings['tag'];
	$gt_settings['global'] = (int)$gt_settings['global'];
	$gt_settings['num'] = (int)$gt_settings['num'];
	$gt_settings['width'] = (int)$gt_settings['width'];
	$gt_settings['height'] = (int)$gt_settings['height'];
	$gt_settings['crop_w'] = (int)$gt_settings['crop_w'];
	$gt_settings['crop_h'] = (int)$gt_settings['crop_h'];
	$gt_settings['find'] = (string)$gt_settings['find'];
	$gt_settings['default_image'] = (string)$default_image;
	if ($gt_settings['tag'] != 0 && gettype($gt_settings['tag']) != integer) { $gt_settings['tag'] = 1; }
	if (gettype($gt_settings['num']) != integer) { $gt_settings['num'] = 0; }
	if (gettype($gt_settings['global']) != integer) { $gt_settings['global'] = 0; }
	if (gettype($gt_settings['width']) != integer) { $gt_settings['width'] = 0; }
	if (gettype($gt_settings['height']) != integer) { $gt_settings['height'] = 0; }
	if (gettype($gt_settings['crop_w']) != integer) { $gt_settings['crop_w'] = 0; }
	if (gettype($gt_settings['crop_h']) != integer) { $gt_settings['crop_h'] = 0; }

	return $gt_settings;
}


// 指定箇所のイメージタグを取得
function qf_get_imagetag($content, $num, $find, $full_domain_name, $global, $default_image) {
	// 記事ソースからイメージタグを抽出
	if (!preg_match_all("/<([Ii][Mm][Gg])[\s\t][\"']*([^>]*)*>/" , $content, $imgList)) {
		// イメージ要素が無い場合、デフォルト画像を出力
		return "<img src=\"".$default_image."\" />";
	}

	// 配列整形
	$new_imgList = array();
	foreach ($imgList[0] as $value) {
		// 検索文字列指定がある場合、検索実行及び判定
		if (!$find == "" && !strstr($value, $find)) {
			next;
		}elseif (!$global && !strstr($value, $full_domain_name)) {
		// 外部リンク不許可の場合、スキップする
			next;
		}else{
			array_push($new_imgList, $value);
		}
	}

	// イメージタグ配列要素数を確認
	$count = count($new_imgList) - 1;

	if ($count < 0) {
		// イメージ要素が無い場合、デフォルト画像を出力
		return "<img src=\"".$default_image."\" />";
	}


	// 配列要素の範囲外の値は調整する
	if ($count < $num || $num < 0) {
		$num = $count;
	}

	// 指定箇所のIMGタグを取り出す
	$imgtag = $new_imgList[$num];

	return $imgtag;
}


// 指定箇所のイメージパスを取得
function qf_get_imagepath($imgtag) {
	// イメージタグ内からパスのみを取り出す
	$tmp_imgtag = split(' ', $imgtag);
	foreach ($tmp_imgtag as $tmp_imgurl) {
		if(preg_match("/([Ss][Rr][Cc])([\s\t\n\r]*)=([\s\t\n\r]*)/" , $tmp_imgurl)) {
			$tmp_imgurl = preg_replace("/[\s\t\n\r\'\"]/", "", $tmp_imgurl);
			$imgurl = preg_replace("/([Ss][Rr][Cc])=/", "", $tmp_imgurl);
		}
	}

	return $imgurl;
}


// サムネイルデータ生成
function qf_make_thumbnail($url, $width, $height) {
	// イメージリソース取得
	$image = makerc_image($url);

	// イメージのリサイズ処理
	$image = imageresize($image, $width, $height, true);

	return $image;
}


// 保存先を設定
function qf_get_savepath($url, $base_path, $full_domain_name, $append_text, $folder_name, $width, $height, $crop_w, $crop_h) {

	// uploads ディレクトリ取得前処理
	$uploads = wp_upload_dir();
	$uploads['basedir'] = str_replace("\\", "/", $uploads['basedir']);

	// URLを保存先パスに変換
	$file = $uploads['basedir']."/".$folder_name."/".preg_replace("/https?:\/\//", "", $url);

	// リモートファイルのサイズ取得
	$size = qf_get_remotefilesize($url);

	// 保存ファイル名定義
	$file = dirname($file)."/".$folder_name."/".$width."-".$height."x".$crop_w."-".$crop_h."/".$append_text."_".$size."_".basename($file);

	return $file;
}


// イメージデータクロップ実行
function qf_make_cropimage($image, $crop_w, $crop_h) {
	// クロップ指定が無ければ処理を終える
	if ($crop_w == 0 && $crop_h == 0) {
		return $image;
	}

	// イメージの縦幅・横幅取得
	$width = @imagesx($image);
	$height = @imagesy($image);

	// クロップサイズ計算
	$left = ($width - $crop_w) / 2;
	$right = $left;
	$top = ($height - $crop_h) / 2;
	$bottom = $top;

	if ($crop_w == 0) {
		$left = 0;
		$right = 0;
	}

	if ($crop_h == 0) {
		$top = 0;
		$bottom = 0;
	}

	// イメージのクロップ処理
	$image = imagecrop($image, $left, $top, $right, $bottom);

	return $image;
}


// 保存先ディレクトリ確認・生成
function qf_check_savedir($save) {
	$save_dir = dirname($save);

	if(is_dir($save_dir)){
		return true;
	}else{
		if (!@mkdir($save_dir,02775)) {
			qf_check_savedir($save_dir);
			@mkdir($save_dir,02775);
		}
	}

	return true;
}


// 対象ファイルとキャッシュを比較
function qf_check_samefile($save, $url, $append_text) {
	//キャッシュファイルサイズ取得
	$c_size = str_replace($append_text, "", str_replace("_".basename($url), "", basename($save)));

	// リモートファイルのサイズ取得
	$r_size = qf_get_remotefilesize($url);

	// ファイルが同一でなければ偽を返す
	if (!$c_size == $r_size) {
		return false;
	}else{
		return true;
	}
}


// サムネイル保存
function qf_save_thumbnail($save, $image, $format) {
	// イメージ保存
	switch ($format) {
		case 1:
			// GIFを出力
			imagegif($image, $save);
			break;
		case 2:
			// JPEGを出力
			imagejpeg($image, $save);
			break;
		case 3:
			// PNGを出力
			imagepng($image, $save);
			break;
		default:
			// 念の為...
			return false;
	}
	return true;
}


// サムネイルファイルのフルパスを返す
function qf_get_thumburl($save, $base_path, $full_domain_name) {
	$save = str_replace($base_path, $full_domain_name, $save);
	return $save;
}


// リモートファイルのサイズ取得
function qf_get_remotefilesize($url) {
	$sch = parse_url($url, PHP_URL_SCHEME);

	$headers = get_headers($url, 1);

	if ((!array_key_exists("Content-Length", $headers)))
		return false;

	return $headers["Content-Length"];
}


function qf_get_thumb_options() {
	if (function_exists('add_options_page')) {
		add_options_page('qf_get_thumb', 'QF-GetThumb', 8, basename(__FILE__), 'qf_get_thumb_options_subpanel');
	}
}


// 初期設定ロード
function qf_load_default() {
	$settings['domain_name'] = $_SERVER{'SERVER_NAME'};
	$settings['full_domain_name'] = 'http://'.$_SERVER{'SERVER_NAME'}.'/';
	$settings['base_path'] = $_SERVER{'DOCUMENT_ROOT'}.'/';
	$settings['default_image'] = str_replace($_SERVER{'DOCUMENT_ROOT'}, 'http://'.$_SERVER{'SERVER_NAME'}, str_replace("\\", "/", dirname(__FILE__).'/default_image.png'));
	$settings['folder_name'] = 'qfgt';
	$settings['append_text'] = 'qfgt';

	// base_path 整形
	$settings['base_path'] = str_replace("//", "/", str_replace("\\", "/", $settings['base_path']));

	return $settings;
}


// 設定画面
function qf_get_thumb_options_subpanel() {
	if (isset($_POST['info_update'])) {
		// エスケープシーケンス対策
		if (get_magic_quotes_gpc()) {
			$_POST['base_path'] = stripslashes($_POST['base_path']);
		}

		$new_options = array(
			'domain_name' => $_POST['domain_name'],
			'full_domain_name' => $_POST['full_domain_name'],
			'base_path' => $_POST['base_path'],
			'default_image' => $_POST['default_image'],
			'folder_name' => $_POST['folder_name'],
			'append_text' => $_POST['append_text']
		);

		// 設定を更新
		update_option('qf_get_thumb_settings', $new_options);

		// 設定保存メッセージ出力
		echo "<div class=\"updated\">\n";
		if (!empty($update_error)) {
			echo "<strong>Update error:</strong>".$update_error;
		}else{
			echo "<strong>設定が保存されました。</strong>\n";
		}
		echo "</div>\n";

	}elseif(isset($_POST['load_default'])){
		// 設定初期化
		$new_options = qf_load_default();
		update_option('qf_get_thumb_settings',$new_options);

		// 初期化完了メッセージ出力
		echo "<div class=\"updated\">\n";
		echo "<strong>設定を初期化しました。</strong>\n";
		echo "</div>\n";
	}

	$qf_get_thumb_settings = get_option('qf_get_thumb_settings');

?>
<div class=wrap>
<form method="post">
<h2>
<? _e('QF-GetThumb Options', 'wpqfgt'); ?>
</h2>
<fieldset name="options">
<table cellpadding="2" cellspacing="0" width="100%">
<tr>
<td><strong>
<? _e('Domain name', 'wpqfgt'); ?>
</strong></td>
<td><input type="text" name="domain_name" value="<?php echo $qf_get_thumb_settings['domain_name']; ?>"  size="30%" /></td>
</tr>
<tr>
<td><strong>
<? _e('Full domain name', 'wpqfgt'); ?>
</strong></td>
<td><input type="text" name="full_domain_name" value="<?php echo $qf_get_thumb_settings['full_domain_name']; ?>" size="30%" /></td>
</tr>
<tr>
<td><strong>
<? _e('Document root', 'wpqfgt'); ?>
</strong></td>
<td><input type="text" name="base_path" value="<?php echo $qf_get_thumb_settings['base_path']; ?>" size="100%" /></td>
</tr>
<tr>
<tr>
<td><strong>
<? _e('Default image', 'wpqfgt'); ?>
</strong></td>
<td><input type="text" name="default_image" value="<?php echo $qf_get_thumb_settings['default_image']; ?>" size="100%" /></td>
</tr>
<tr>
<tr>
<td><strong>
<? _e('Save folder', 'wpqfgt'); ?>
</strong></td>
<td><input type="text" name="folder_name" value="<?php echo $qf_get_thumb_settings['folder_name']; ?>"  size="10" /></td>
</tr>
<tr>
<td><strong>
<? _e('Apend text', 'wpqfgt'); ?>
</strong></td>
<td><input type="text" name="append_text" value="<?php echo $qf_get_thumb_settings['append_text']; ?>"  size="10" /></td>
</tr>
</table>
</fieldset>
<div class="submit">
<input type="submit" name="info_update" value="<? _e('Save settings', 'wpqfgt'); ?>" />
<input type="submit" name="load_default" value="<? _e('Load default settings', 'wpqfgt'); ?>" />
</div>
</form>
</div>
<?php

}

?>