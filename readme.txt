=== QF-GetThumb ===
Contributors: Q.F.
Donate link: http://la-passeggiata.com/?lang=en
Tags: automatic, code, content, excerpt, files, Formatting, gallery, html, image, images, list, media, mobile, performance, photo, photos, picture, pictures, plugin, plugins, Post, posts, preview, thumbnail, url, wordpress, xhtml, RSS, ATOM, FEED
Requires at least: 2.6
Tested up to: 2.7
Stable tag: 1.1.3

QF-GetThumb is a plug-in that extracts the image data from the content and the argument, and makes the thumbnail.
Outside RSS can be read depending on the application.
"The image of the article published in other blogs is read to my blog" Applied technique can be done. 

Demonstration site is here!!
http://la-passeggiata.com/sample/sp01/?lang=en

== Description ==

QF-GetThumb is plugin to make the thumbnail and the cache of first image data in the content and the argument source.This plugin can make JPEG, GIF, a format of PNG, And makes the image of not only a local server but also the outside link a thumbnail and can become cache.
Outside RSS can be read depending on the application.
"The image of the article published in other blogs is read to my blog" Applied technique can be done. 

Demonstration site is here!!
http://la-passeggiata.com/sample/sp01/?lang=en


== Installation ==

1. Upload the entire QF-GetThumb folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. The setting is automatic, and it is performed.

The control panel of QF-GetThumb is in 'Settings > QF-GetThumb'.


== Frequently Asked Questions ==

It is very easy to use QF-GetThumb.
You write in "<?php echo the_qf_get_thumb_one(); ?>" the source of theme.
Include various settings in a function, if necessary.

Example => <?php echo the_qf_get_thumb_one('num=0&width=160&tag=0&global=1&crop_w=160&crop_h=120&find=logo', './images/defaultimg.png'); ?>

[first-options]
num	 	: Index of take out the image in the source of content.
		  (type: integer , defualt : 0)
width, height	: Image-width and image-height.
		  If set width or height only one, The image size fits numerical value.
		  When You set both value, The image size is within both value.
		  (type: integer , default : 0, 
		  It means automatic detection of size of the original image.)
tag		: That you output the HTML tag of the image is the setting that you do not do.
		  When You set 1, the source of tag is output, and the URL of the image is output
		  when You set 0.
		  (type: boolean 0 or 1 , defualt / 1)
global		: It is setting whether or not it limits an object of the extraction to data
		  in the local server.
		  (type: boolean 0 or 1 , defualt / 0 local server only)
crop_w		: It is width when it does a crop of an image.
		  (defualt / 0, It means don't crop.)
crop_h		: It is height when it does a crop of an image.
		  (defualt / 0, It means don't crop.)
find		: Only an image according for the character string
		  that You appointed does this setting in an output object.
		  (defualt / Null)

[second-option]
default_image	: The path of the image,
		  that is used when an image is not included in the source of content.
		  (default / The optional setting is used.)

[third-option]
source		: HTML code of target,
		  The object of the image extraction is set. 
		  the_excerpt and custom_field can be specified, and other codes specify it.
		  (default / the_content() is used.)


== Screenshots ==

1. screenshot-1.png
2. screenshot-2.png
