<?php
/* JPEG、GIF、PNGのフォーマットを判定して返す関数 */
function getformat_image($file = "") {
	// ファイルパスが存在しなければ、偽を返す
	if (!$file) {
		return false;
	}

	// フォーマットの取得
	list($tmp_w, $tmp_h, $format) = @getimagesize($file);

	// フォーマットが取得できなければ、false を返す
	if ($format < 1 || $format > 3) {
		return false;
	}

	return $format;
}


/* JPEG、GIF、PNGのイメージリソースを返す関数 */
function makerc_image($file = "", $format = 0) {
	// パスから元画像のフォーマットを取得
	list($width, $height, $format) = @getimagesize($file);

	// フォーマットが既定外であれば、false を返す
	if ($format < 1 || $format > 3) {
		return false;
	}

	// フォーマット別に、イメージリソースを生成して返す
	switch ($format) {
		case 1:
			// ファイルパスからGIFを取得
			return imagecreatefromgif($file);
			break;
		case 2:
			// ファイルパスからJPEGを取得
			return imagecreatefromjpeg($file);
			break;
		case 3:
			// ファイルパスからPNGを取得
			return imagecreatefrompng($file);
			break;
		default:
			// 念の為...
			return false;
	}
}



/* JPEG、GIF、PNGのイメージデータをリサイズして返す関数 */
function imageresize($image = "", $width = 0, $height = 0, $aspect = true) {
	// 幅と高さを取得
	$o_width = @imagesx($image);
	$o_height = @imagesy($image);

	// イメージデータが無い、又はフォーマット不明な場合、偽を返す
	if (!$image || !$o_width || !$o_height) {
		return false;
	}

	// 縦横ともにサイズ指定が無ければ、偽を返す
	if ($width == 0 && $height == 0) {
		return false;
	}

	// 縦横どちらかが 0 の場合、アスペクト比保持を有効化
	if ($width == 0 || $height == 0) {
		$aspect = true;
	}

	// アスペクト比保持の場合、リサイズ後の画像サイズを計算
	if ($aspect) {
		if ($width == 0) {
			// 縦幅に合わせて計算
			$ratio = $height / $o_height;
			$width = $o_width * $ratio;
		}elseif($height == 0){
			// 横幅に合わせて計算
			$ratio = $width / $o_width;
			$height = $o_height * $ratio;
		}else{
			// 縦横からはみ出ないように計算
			$w_ratio = $width / $o_width;
			$h_ratio = $height / $o_height;

			// 小さい方の値を基準に縦横幅を再計算
			if ($w_ratio < $h_ratio) {
				$height = $o_height * $w_ratio;
			}else{
				$width = $o_width * $h_ratio;
			}
		}
	}


	// リサイズ用の画像を作成
	$new_image = imagecreatetruecolor($width, $height);

	// PNG形式の場合、オプションを設定する
	imagealphablending($new_image, false);
	/* 透過色設定 */ 
	$color = imagecolorallocatealpha($new_image, 0, 0, 0, 127);
	imagefill($new_image, 0, 0, $color);
	imagesavealpha($new_image, true);

	// 元画像からリサイズしてコピー
	imagecopyresampled($new_image,$image, 0, 0, 0, 0, $width, $height, $o_width, $o_height);

	// イメージデータを返す
	return $new_image;

	// イメージデータ初期化
	imagedestroy($image);
	imagedestroy($new_image);
}


/* JPEG、GIF、PNGのイメージリソースをクロップして返す関数 */
function imagecrop($image = "", $left = 0, $top = 0, $right = 0, $bottom = 0) {
	// 幅と高さを取得
	$width = @imagesx($image);
	$height = @imagesy($image);

	// イメージデータが無い、又はフォーマット不明な場合、偽を返す
	if (!$image || !$width || !$height) {
		return false;
	}


	// 切取指定が無ければ、元画像を返す
	if ($top == 0 && $bottom == 0 && $left == 0 && $right == 0) {
		return $image;
	}

	// リサイズ用の画像を作成
	$n_width = $width + (-1 * $right) + (-1 * $left);
	$n_height = $height + (-1 * $bottom) + (-1 * $top);

	// 幅又は高さが 0 以下になった場合、偽を返す
	if ($n_width <= 0 || $n_height <= 0) {
		return false;
	}

	$new_image = imagecreatetruecolor($n_width, $n_height);

	// PNG形式の場合、オプションを設定する
	if ($format == 3) {
		imagealphablending($new_image, false);
		/* 透過色設定 */ 
		$color = imagecolorallocatealpha($new_image, 0, 0, 0, 127);
		imagefill($new_image, 0, 0, $color);
		imagesavealpha($new_image, true);
	}


	// 元画像からリサイズしてコピー
	imagecopyresampled($new_image,$image, 0, 0, $left, $top, $width, $height, $width, $height);

	// イメージデータを返す
	return $new_image;

	// イメージデータ初期化
	imagedestroy($image);
	imagedestroy($new_image);
}
?>