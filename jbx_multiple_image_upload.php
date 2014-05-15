<?php
/*DEBUG*/ global $debug;
/*DEBUG*/ $debug = '1';
/*DEBUG*/ if ($debug=='1'){ ini_set('display_errors', 1); error_reporting(E_ALL); }

if(txpinterface === 'admin')
{
	global $prefs, $jbxMIU;

	// Add new tab under "extensions" and assign function "jbx_multiple_imageupload"
	add_privs('jbx_image_multiple_upload', 1);
	register_tab("extensions", "jbx_image_multiple_upload", "Multiple Upload");
	register_callback("jbx_image_multiple_upload", "jbx_image_multiple_upload");

	// 'jbx_image_import_list' will be called to handle the 'image' event
	register_callback("jbx_upload", "image");
	register_callback("jbx_import", "image", "jbx_import");

	// Create Object
	$jbxMIU = new jbx_MultiImgUpload;
}

// Preferences Event
function jbx_image_multiple_upload($event, $step)
{
	global $jbxMIU, $prefs;

	if ($step === 'update')
	{
		foreach($jbxMIU->preferences_array as $pref)
		{
			$value = trim(gps($pref["name"]));
			$value = ($value === "")? $pref["standard"] : $value;
			$jbxMIU->upsertPref($pref["name"], doSlash($value));
		}
		$msg = "Preferences updated";
	}

	pageTop("Multiple Image Upload", (isset($msg) ? $msg : ''));

	// Generate Preferences Table
	$out = startTable("list").tr(tdcs(hed("Multiple Image Upload Preferences",1),3));
	foreach($jbxMIU->preferences_array as $pref)
	{
		$out .= $jbxMIU->prefInput($pref['name'], $pref['title'], $pref['descr'], $pref['type']);
	}
	$out .= tr(tda(fInput("submit","save",gTxt("save_button"),"publish").eInput("jbx_image_multiple_upload").sInput('update'), " colspan=\"3\" class=\"noline\"")).
	endtable();

	echo form($out);
}

function jbx_upload($event, $step)
{
	global $jbxMIU, $prefs;

	if($step !== "image_edit")
	{
	$absolute_upload_path = rtrim(rhu, DS).DS.$prefs['img_dir'].DS."upload".DS;

?>
<script type="text/javascript" src="<?php echo $absolute_upload_path;?>uploadify.js"></script>
<script type="text/javascript">
/* <![CDATA[ */

$(document).ready(function(){
	$('head').append('<link rel="stylesheet" href="<?php echo $absolute_upload_path;?>style.css" type="text/css" />');
	$('.upload-form').before($('#jbx_div'));
	if($('#warning')){
		$('#jbx_div').before($('#warning'));
	}

	$("#upload-browse").uploadify({
		'uploader'	: '<?php echo $absolute_upload_path;?>uploadify.swf',
		'script'	: '<?php echo rhu.DS.$prefs['img_dir'].DS."upload".DS."upscript_".$jbxMIU->version.".php";?>',
		'cancelImg'	: '<?php echo $absolute_upload_path;?>cancel.png',
		'buttonImg'	: '<?php echo $absolute_upload_path;?>browse.gif',
		'scriptData'	: {'path': '<?php echo $jbxMIU->path_to_tmp;?>'},
		'queueID'	: 'upload-list',
		'queueSizeLimit': <?php echo $prefs[$jbxMIU->prefix('fileslimit')];?>,
		'multi'		: true,
		'sizeLimit'	: 2 * 1024 * 1024,
		'fileDesc'	: 'Images',
		'fileExt'	: '*.jpg;*.jpeg;*.gif;*.png',
		'width'		: 70,
		'height'	: 10,
		'onComplete'	:function(event, queueID, fileObj, response, data){
			if(Number(response) !== 1)
			{
				$('#uploadify'+queueID+' .percentage').text(response);
				$('#uploadify'+queueID).addClass('uploadifyError');
				return false;
			}
		},
		'onAllComplete'	: function(){
			$('#upload-status p').html('<strong>Will Refresh in 3 Seconds...</strong>');
			setTimeout(function(){
				$('#jbx_cat').removeAttr('disabled');
				$('#upload-form').submit();
			}, 3000);
		}
	});
	
	$('#upload-clear').click(function(){
		$("#upload-browse").uploadifyClearQueue();
		$('#jbx_cat').removeAttr('disabled');
		return false;
	});
	
	$('#upload-submit').click(function(){
		if ($('#upload-list .uploadifyQueueItem').size() > 0) {
			$('#jbx_cat').attr('disabled', 'disabled');
		}
		$("#upload-browse").uploadifyUpload();
		return false;
	});
});


/* ]]> */
</script>
<div id="jbx_div" style="display:none">
	<h2>Upload Multiple Images</h2>
	<form action="index.php" method="post" id="upload-form" name="upload-form">
        <p>Images will be uploaded to this category. Leave blank if no category should be assigned.</p>
	<label for="jbx_cat">Category </label><?php echo fInput('text', 'jbx_cat', '', '', '', '', '20', '', 'jbx_cat');?>
	<div id="upload-status" class="hide">
		<p>
			<a href="#" id="upload-browse">Browse Files</a> |
			<a href="#" id="upload-clear">Clear List</a> |
			<a href="#" id="upload-submit">Upload</a>
		</p>
		<div id="upload-list"></div>
	</div>
	<input name="event" value="image" type="hidden" />
	<input name="step" value="jbx_import" type="hidden" />
        <input name="jbx" value="Upload" type="hidden" />
	</form>
<h2 style="padding-top:25px;">Upload Single Image</h2>
</div>
<?php
	}
}

function jbx_import()
{
	global $jbxMIU, $prefs;

	$success=true;

	// check sanity of preferences
	print "<p id=\"warning\">";
	$warnings = $jbxMIU->preflightChecks();

	$num = 0;

	if(ps('jbx_cat') === FALSE){
		$jbx_cat = "";
	}else{
		$jbx_cat = ps('jbx_cat');
	}

	if (@is_dir($jbxMIU->path_to_tmp))
	{
		$handlesub = @opendir($jbxMIU->path_to_tmp);
		// process files in dir
		while ($filesub = readdir ($handlesub))
		{
			// import images from folder to category
			if ($filesub != "." && $filesub != "..")
			{
				if (@is_file($jbxMIU->path_to_tmp.$filesub) && @is_readable($jbxMIU->path_to_tmp.$filesub))
				{
					$helpfileext = explode('.',$filesub);
					$fileextsub = strtolower($helpfileext[count($helpfileext)-1]);
					$prefixsub = substr($filesub, 0, 6);
					if (($fileextsub == "jpeg" || $fileextsub == "jpg" || $fileextsub == "png" || $fileextsub == "gif") && $prefixsub != "thumb-")
					{
						// import file in category $file
						if ($jbxMIU->import($filesub, $jbx_cat))
						{
							$num++;
						}
						else
						{
							$success = false;
						}
					} //if
				} //if
			} //if
		} //while
		@closedir($handlesub);
	}
	else
	{
		// import file $file
		if ($jbxMIU->import($file, $jbx_cat))
		{
			$num++;
		}
		else
		{
			$success = false;
		}
	}

	// now redirect to image without step so the list is refreshed.
	if ($success === true AND !$warnings) {
		header("Location: index.php?event=image");
	}
	else
	{
		print "There were errors importing some images. $num images were imported<br/><a href=\"index.php?event=image\"><strong>Refresh the imagelist now</strong></a></p>";
	}
}


class jbx_MultiImgUpload
{
	// All preferences
	var $preferences_array = array(
					array("name"=>"fileslimit", "title"=>"Max. Files Limit", "descr"=>"The number of files you can add to the batch.", "type"=>0, "standard"=>20),
					array("name"=>"thumb", "title"=>"Create thumbnail", "descr"=>"If a file thumb-imagename.ext is found the thumbnail will still be imported.", "type"=>1, "standard"=>'1'),
					array("name"=>"thumbcrop", "title"=>"Crop thumbnail", "descr"=>"The thumbnail shall be cropped.", "type"=>1, "standard"=>'0'),
					array("name"=>"thumbx", "title"=>"Thumbnail width", "descr"=>"May be 0 if thumbnail height is >0 and crop disabled.", "type"=>0, "standard"=>150),
					array("name"=>"thumby", "title"=>"Thumbnail height", "descr"=>"May be 0 if thumbnail width is >0 and crop disabled.", "type"=>0, "standard"=>0),
					array("name"=>"thumbhint", "title"=>"Thumbnail icon", "descr"=>"Add d small looking glass icon to thumbnail.", "type"=>1, "standard"=>'0'),
					array("name"=>"thumbgreyhint", "title"=>"Grey bar at bottom of thumb", "descr"=>"Grey bar at bottom of thumbnail, use it with hint.", "type"=>1, "standard"=>'0'),
					array("name"=>"resize", "title"=>"Resize image", "descr"=>"Resize the image (what a surprise).", "type"=>1, "standard"=>'0'),
					array("name"=>"sharpen", "title"=>"Sharpen image", "descr"=>"Claims to result in better quality resize.", "type"=>1, "standard"=>'0'),
					array("name"=>"imgx", "title"=>"Resize to width", "descr"=>"Width to resize image to (may be 0 if height >0).", "type"=>0, "standard"=>640),
					array("name"=>"imgy", "title"=>"Resize to height", "descr"=>"Height to resize image to (may be 0 if width >0).", "type"=>0, "standard"=>480),
					array("name"=>"importinfo", "title"=>"Import additional info", "descr"=>"Import meta info into caption.", "type"=>2, "standard"=>'none'),
					array("name"=>"filenameasalt", "title"=>"Filename as alt-text", "descr"=>"Use filename without extension as alt-text.", "type"=>1, "standard"=>'0')
					);
	var $path_to_images;
	var $path_to_upload;
	var $path_to_tmp;
	var $version = "0.4";


	function jbx_MultiImgUpload()
	{
		global $prefs;


		//Insert Preferences
		foreach($this->preferences_array as $pref)
		{
			if (!isset($prefs[$this->prefix($pref['name'])]) OR $prefs[$this->prefix($pref['name'])] == "")
			{
				$this->upsertPref($pref['name'], $pref['standard'], 1);
			}
		}

		// Set Path 'Constants'
		$this->path_to_images = $prefs['path_to_site'].DS.$prefs['img_dir'].DS;
		$this->path_to_upload = $this->path_to_images."upload".DS;
		$this->path_to_tmp = $this->path_to_upload."tmp".DS;

		// Create Assets
		if(!file_exists($this->path_to_upload."upscript_".$this->version.".php") AND is_writable($this->path_to_images))
		{
			$this->rmdirr($this->path_to_upload);
			if (mkdir($this->path_to_upload))
			{
				mkdir($this->path_to_tmp);
				$assets = array();
				
				//Swiff.Uploader.swf
				$assets[] = array('uploadify.swf','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');
				//upload.js
				$assets[] = array('uploadify.js','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');
				//upscript.php
				$assets[] = array('upscript_'.$this->version.'.php','eJyVlGFv2jAQhj/jX3FUqE6mrBts0qaytZvUUHUaKgK0LxGKDnIh1kIS2WYVa/nvs+NQqg6q7VN0yd29z/le59NllVWMiRQ8oRRprxMPbr6Hk4gPRE4JauQzH3x2z1qd1LyBz3AgI+J6VcUFrojP+iaTpCylSU0xV2RfKPGb9jGwlhVsCxWvq7zEhJLYNvdqCR8eHuAQR8RtGz6DC+jBK+i+7b1vHpawZRD3ynyUEyoC1x/KIt+Aba5ArTDPSYLOsIDecN7mBnDbEDX1p6fQ9nbQX5akxQqXZOMd4r8purIAihJKnRlRh4CSQK2rqpSakrMj+qKIUUrcOI6oNwvAxd0AegG8C+BDAB//jwTKFPSmIvg2Cq/fXN8MGnGzkA4miS02w2al0vONjc0WJuH4RziO+Dgc3k7D+OvV1dg44nGHTnRHIEmtcx1x9zSbMjAp2gVanf33uqj+7OrdCZAxh21kMzVKQxJXqLPacqPbyTTiNuSzs4PeaNzXaplykXqr8hc9d9fLxg3gqarvw9GR1HqxIKXqmbaW+mimG/TY7E/kzk7gNUyNRQju0DikALdNN4EolmAnMk/fGJdqHwVQuT1ruQFcoijaJzURcyvdsvpA7c19Ee0AGR+an4EVra+8wRCFJllg7qjsldkydSf0IvP+au7fs4XF2m3+vAld+3NGi6yE56J9NpeEP/tN7u6Em2ze5Y8JW3Z58QfKM3Lw');
				//style.css
				$assets[] = array('style.css','eJx9k91ygjAQha/lKdJhekkHtRnbcOcPM71rn6CTkICpgWXCUm2dvnuBaEXFkityDpvzbRb/Q+zepf7ce1stcc0eaVjuIg/VDgNudFawRBWobOTl3Ga6YONGJ7xGiDypq9LwLyYMJBtyp/MSLPICI+/H8w91yXqyP3waIJRscqt8CgUGW6WzNTIBRnZF6tIAl0GFHOuK7D3Bk01moS5kkIABy/z4OZ6tltExvSt/HbXkUuoic1tj2pqCHL4DAVYqG1gudV11aiNsldhoHNSG9no5a5Fr3J+xzMKwtXgPzqPTr7da1eoFVd4AjVxBNm5iVWC0JL6icpbwqJEuYYkfx/EinjTagZDQP8LREZG4VCPXEfIYutcBXHqbll7DUsd64lhZC7bHQCYniHi+mM8XZzMxyLNc0eXyYnSGOvWQ8CJRpj0tbURkxLbdPbe/Wsisqqou02Dzmic65u3GkfQa/xS266QbleK/hi7CmWNB23VyCECEfNjS+ykurmwchvfDZHNub8ClKhHTaa9GV3DtZpBMu7v7BbpmTWA=');
				//cancel.png
				$assets[] = array('cancel.png','eJwBWwKk/YlQTkcNChoKAAAADUlIRFIAAAAQAAAAEAgGAAAAH/P/YQAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAH9SURBVHjaZFM7SyRBEP66Z36AYGJ2K/hgQQ6FQxHk/AliYKKzRsvCIpj4QDG5EzE4/QtGPljMjEx8HFwipt7dXnqBcIkYGtxMtVU13UOv9vBRU9VdXz262hzVarOJtd+stZPGGAicfORAjlAUggI5I8g8z0V2WW+n7NxJkmSACWDZGQLHzsbBkPyL6pAwRFoi6FmiOhnTSVlRZybpITAMVkonIRQbO2uWslPKASs/1mNsZQULDw+Y2N72Nn+QMbO3h/bjI6Y2NsogUBYoAYxVpd5uq31oaQnjW1tqk/3p3V3UGw3dm1xfjzNAKilyxxgGfy8u8GFuTg8OZxmb2Z6mGFlcRFi/jo+lLVqaCwTka77f2VFjzZOMLC8jXr9PTnDDGUD7oyxIyTdGdZZ3XL9EHpyf73Hunp7i++ZmFbnKoJA0vYF8zf9fXvB2iU2CSbZBik8qwxGuKjQsrjmsj82mOt5whjJgkqXAxhM29eUrRvkG4rT/nJ9X+nirhc8cgKjsm8DG41lvZD0Nu+U7v1pdRbfTqeyfeFa0BF+GrWabcX94WF3V9dpamHlcstPPszPd+7G/D2Ib+RLMQV+fk1E2frJEOn9FIuNoKqXkSE85whM/jP5wleE1hkek3fYv8x0Z0ZP0IGOSfyHdspzqyWqDiyIvJUUkRM8cIHsVYAAt0ZRNIEb+UAAAAABJRU5ErkJggtz9C50=');
				//browse.gif
				$assets[] = array('browse.gif','eJwBTAGz/kdJRjg5YUYACgCzAAC5lnHm2si/oH3GqorZx6+zjWWmeUzMs5asg1ifcD/z7eHTvaPf0Lvs5NSZZjP59+0h+QQAAAAAACwAAAAARgAKAAAE+fDJSau9OFvmWHONRnFOCYhoKpGlM31hyklBp97YbMGqzhiKh2EBAB1KhoDCEXgACo+aYpEoLaKI0qCik8B4VEfiGnZcHywHIGQoNDmMx8DwQBwUVcUB8Rg/FgN4cQ0JcRNpB18gPyEECXhkW2guEgVQQxIHUA9LAQMABJYHAFsFCAJXaWYjlF4gMEctIKaoh60LdJgPmhKdAQkCB5qFEgsCCAAcQRddD4oNexbGyCuUCtS6cA8CdH0OCksJzghNe3gCnMmsFM8cVwEIDeS7fJMtBUG6u0hNTpsFJxQUcWAgDoMsDgrEqMbuFYhdVRwIEIjEEI6LGDFGAAA7PIx6Rg==');

				foreach($assets as $file)
				{
					$handle = fopen($this->path_to_upload.$file[0], 'w');
					fwrite($handle, gzuncompress(base64_decode($file[1])));
					chmod($this->path_to_upload.$file[0], 0644);
					fclose($handle);
				}

				chmod($this->path_to_upload, 0755);
			}
			else
			{
				echo "jbx_multiple_image_upload: Failed to create 'upload' folder within textpatterns /images/ directory.";
			}
		}

	}


	function preflightChecks()
	{
		global $prefs;

		$warnings = false;
		if (!function_exists('gd_info'))
		{
			print('GD is not present - image manipulation disabled<br />');
			$this->upsertPref('thumb', '0');
			$this->upsertPref('resize', '0');
			$warnings = true;
		}

		if (strtolower($prefs[$this->prefix('importinfo')]) == 'iptc' && !function_exists('iptcparse'))
		{
			print('IPTC is not supported by your PHP, IPTC parsing disabled<br />');
			$this->upsertPref('importinfo', 'none');
			$warnings = true;
		}

		if (strtolower($prefs[$this->prefix('importinfo')]) == 'exif' && !function_exists('exif_read_data'))
		{
			print('EXIF is not supported by your PHP, EXIF parsing disabled<br />');
			$this->upsertPref('importinfo', 'none');
			$warnings = true;
		}

		if ($prefs[$this->prefix('resize')] == '1' && $prefs[$this->prefix('imgx')] == '0' && $prefs[$this->prefix('imgy')] == '0')
		{
			print('You should make up your mind, resize to 0x0 is highly illogical. Resizing disabled<br />');
			$this->upsertPref('resize', '0');
			$warnings = true;
		}

		if ($prefs[$this->prefix('thumb')] == '1' && $prefs[$this->prefix('thumbx')] == '0' && $prefs[$this->prefix('thumby')] == '0')
		{
			print('You should make up your mind, thumbnailing to 0x0 is highly illogical. Thumbnailing disabled<br />');
			$this->upsertPref('thumb', '0');
			$warnings = true;
		}

		if(!ini_get('safe_mode'))
		{
			set_time_limit(0);
		}

		return $warnings;
	}


	function gd_works($ext)
	{
		switch($ext)
		{
			case '.jpg':
			{
				if (!function_exists('imagecreatefromjpeg'))
				{
					print 'your gd does not support JPEG<br />';
					return false;
				}
			}
			break;

			case '.png':
			{
				if (!function_exists('imagecreatefrompng'))
				{
					print 'your gd does not support PNG<br />';
					return false;
				}
			}
			break;

			case '.gif':
			{
				if (!function_exists('imagecreatefromgif'))
				{
					print 'your gd does not support GIF<br />';
					return false;
				}
			}
		}
		return true;
	}


	function import($filename, $category = "")
	{
		global $txp_user, $txpcfg, $prefs,$extensions;
		$hasError = false;
		$catname = "";
		if (!empty($category))
		{
			$cattitle = doSlash($category);

			//Prevent non url chars on category names
			include_once $txpcfg['txpath'].'/lib/classTextile.php';
			$textile = new Textile();
			$catname = utf8_encode($category);
			$catname = dumbDown($textile->TextileThis(trim(doSlash($catname)), 1));
			$catname = preg_replace("/[^[:alnum:]\-_]/", "", str_replace(" ", "-", $catname));

			if (!empty($catname))
			{
				$check = safe_field("name", "txp_category", "name='$catname' and type='image'");
				if (!$check)
				{
					$q = safe_insert("txp_category", "name='$catname', title='$cattitle', type='image', parent='root'");
					rebuild_tree('root', 1, 'image');
				}
			}
			$imgfilename = $this->path_to_tmp.$filename;
			$imgthumbfilename = $this->path_to_tmp."thumb-".$filename;
		}
		else
		{
			$imgfilename = $this->path_to_tmp.$filename;
			$imgthumbfilename = $this->path_to_tmp."thumb-".$filename;
		}

		if (list($x, $y, $extension) = @getimagesize($imgfilename))
		{
			$ext = strtolower($extensions[$extension]);
			$imagename = utf8_encode(substr($filename, 0, strrpos($filename, '.')));
			$alt = $imagename;
			$imagename .= $ext;
			$name2db = doSlash($imagename);
			$caption='';

			switch(strtolower($prefs[$this->prefix('importinfo')]))
			{
				case 'exif':
				{
					if ( $ext=='.jpg' )
					{
						$exif_info = exif_read_data($imgfilename, 0, TRUE);
						if ( @is_array($exif_info) && !empty($exif_info['COMPUTED']['UserComment']) )
						{
							$caption = $exif_info['COMPUTED']['UserComment'];
							$caption = utf8_encode($caption);
							$caption = doSlash($caption);
						}
					}
				}
				break;
				case 'iptc':
				{
					@getimagesize($imgfilename, $info);
					if ( !empty($info["APP13"]) )
					{
						$iptc_info = iptcparse($info["APP13"]);
						if ( @is_array($iptc_info) && !empty($iptc_info["2#120"][0]) )
						{
							$caption = $iptc_info["2#120"][0];
							$caption = utf8_encode($caption);
							$caption = doSlash($caption);
						}
					}
				}
				break;
			}

			if ( $prefs[$this->prefix('filenameasalt')] == '1' )
			{
				$alt = str_replace("_"," ",$alt);
				$alt = doSlash($alt);
			}
			else
			{
				$alt='';
			}


			$rs = safe_insert("txp_image",
			"w= '$x',
			 h= '$y',
			 category = '$catname',
			 ext  = '$ext',
			 `name`   = '$name2db',
			 `date`   = now(),
			 caption  = '$caption',
			 alt  = '$alt',
			 author   = '$txp_user'");
			 $id = mysql_insert_id();

			if (!$rs)
			{
				print('There was a problem saving image data to the database (Image: '.$imagename.')<br />');
				$hasError = true;
			}

			if ($prefs[$this->prefix('resize')] == '1' && $prefs[$this->prefix('imgx')]<$x && $prefs[$this->prefix('imgy')]<$y && $this->gd_works($ext))
			{
				$t = new wet_thumb();

				// we want the aspect ratio to be correct
				if ($prefs[$this->prefix('imgx')] =='0')
				{
					$newy = $prefs[$this->prefix('imgy')];
					$newx = floor ($prefs[$this->prefix('imgx')] * $x / $y);
				}
				if ($prefs[$this->prefix('imgy')] =='0')
				{
					$newx = $prefs[$this->prefix('imgx')];
					$newy = floor ($prefs[$this->prefix('imgx')] * $y / $x);
				}
				if ($prefs[$this->prefix('imgx')] != '0' && $prefs[$this->prefix('imgy')] != '0')
				{
					if ($x<$y)
					{
						$newx = floor($newy*$x/$y);
						$newy = $prefs[$this->prefix('imgy')];
					}
					else
					{
						$newx = $prefs[$this->prefix('imgx')];
						$newy = floor($newx*$y/$x);
					}
				}

				$t->width = $newx;
				$t->height = $newy;
				$t->sharpen = ($prefs[$this->prefix('sharpen')] == '1');
				$t->crop = false;
				$t->hint = false;
				$t->extrapolate = false;
				$t->addgreytohint = false;
				if (!$t->write($imgfilename, $this->path_to_images.$id.$ext))
				{
					print('resized image <b>'.$id.'</b> not saved! (Image: '.$imagename.')<br />');
					$hasError = true;
				}else{
					chmod($this->path_to_images.$id.$ext,0644);
					unlink($imgfilename);
				}

				if ( list($x, $y, $extension) = @getimagesize($this->path_to_images.$id.$ext) )
				{
					$rs = safe_update("txp_image",
					"w= '$x',
					 h= '$y',
					 `date`   = now(),
					 id   = '$id'",
					 "id = '$id'");
					if ( !$rs )
					{
						print('There was a problem saving image data to the database. (Image: '.$imagename.')<br />');
						$hasError = true;
					}
				}
				else
				{
					print("There was a problem reading the resized image (and I have no clue what). Check everything... (Image: '.$imagename.')<br />");
					$hasError = true;
				}
			}
			else
			{
				rename($imgfilename, $this->path_to_images.$id.$ext);
				chmod($this->path_to_images.$id.$ext, 0644);
			}

			if (file_exists($imgthumbfilename))
			{
				list(,,$extension) = getimagesize($imgthumbfilename);

				if ( $extensions[$extension] )
				{
					$extt = $extensions[$extension];
					if ( $extt!=$ext )
					{
						print ('textpattern currently only understands thumbnails that do have the same image format. Sorry, your thumbnail is not working. (Image: '.$imagename.')<br />');
						$hasError = true;
					}
					else
					{
						$newpath = $this->path_to_images.$id.'t'.$extt;
						safe_update("txp_image", "thumbnail = '1'", "id = '$id'");
						@copy($imgthumbfilename, $newpath);
						if (!@unlink($imgthumbfilename))
						{
							print("Could not delete <b>".$imgthumbfilename."</b> - please delete it yourself to prevent reimporting. (Image: '.$imagename.')<br />");
							$hasError = true;
						}
					}
				}
			}
			else
			{
				if ( $prefs[$this->prefix('thumb')] =='1' && $this->gd_works($ext) )
				{
					$t = new txp_thumb($id);
					if ($prefs[$this->prefix('thumbcrop')]=='0')
					{
						if ( $prefs[$this->prefix('thumbx')]=='0' )
						{
							$newx = floor ($prefs[$this->prefix('thumby')] * $x / $y);
							$newy = $prefs[$this->prefix('thumby')];
						}
						if ( $prefs[$this->prefix('thumby')]=='0' )
						{
							$newx = $prefs[$this->prefix('thumbx')];
							$newy = floor ($prefs[$this->prefix('thumbx')] * $y / $x);
						}
						if ( $prefs[$this->prefix('thumbx')]!='0' && $prefs[$this->prefix('thumby')]!='0' )
						{
								if ( $x<$y )
								{
									$newx = floor($newy*$x/$y);
									$newy = $prefs[$this->prefix('thumby')];
								}
								else
								{
									$newx = $prefs[$this->prefix('thumbx')];
									$newy = floor($newx*$y/$x);
								}
						}
						$t->width = $newx;
						$t->height = $newy;
					}
					else
					{
						$t->width = $prefs[$this->prefix('thumbx')];
						$t->height = $prefs[$this->prefix('thumby')];
					}
					$t->crop = ($prefs[$this->prefix('thumbcrop')]=='1');
					$t->hint = ($prefs[$this->prefix('thumbhint')]=='1');
					$t->addgreytohint = ($prefs[$this->prefix('thumbgreyhint')]=='1');
					$t->extrapolate = false;
					if (!$t->write())
					{
						print('Thumbnail <b>'.$id.'</b> not saved. (Image: '.$imagename.')<br />');
						$hasError = true;
					}
				}//if thumb is wanted
			}//if thumb does not exist
		}
		return !$hasError;
	} //function jpx_import

	//helpers
	function prefix($suffix = '')
	{
		$out = 'jbx_multiple_images';
		if ($suffix)
		{
			$out .= '_' . $suffix;
		}
		return $out;
	}

	function prefInput($name, $title, $descr, $type = 0)
	{
		global $prefs;

		$out = tda(($type === 0)?"<label for=\"".$name."\">".$title."</label>":$title);
		switch($type)
		{
			case 2:
			$out .= td(selectInput($name, array('None'=>gTxt(''), 'EXIF'=>'exif', 'IPTC'=>'iptc'), $prefs[$this->prefix($name)]));
			break;

			case 1:
			$out .= td(yesnoradio($name,$prefs[$this->prefix($name)]));
			break;

			case 0:
			//$out .= td(text_input($name,$prefs[$this->prefix($name)],'20'));
			$out .= td(fInput('text', $name, $prefs[$this->prefix($name)], '', '', '', '20', '', $name));
		}
		$out .= td($descr);
		return tr($out);
	}

	// Update or Insert Preferences
	function upsertPref($name, $value, $insert = 0)
	{
		global $prefs;
		$name = $this->prefix($name);
		$prefs[$name] = $value;
		if ($insert === 1)
		{
			safe_insert("txp_prefs", "prefs_id=1,
			    name='$name',
			    val='$value',
			    type=2,
			    event='admin',
			    html='text_input',
			    position=0
			");
		}
		else
		{
			safe_update("txp_prefs", "val='$value'", "name='$name'");
		}

	}

	// Remove directory with all its content
	function rmdirr($dirname)
	{
		// Sanity check
		if (!file_exists($dirname)) {
			return false;
		}

		// Simple delete for a file
		if (is_file($dirname) || is_link($dirname)) {
			return unlink($dirname);
		}

		// Loop through the folder
		$dir = dir($dirname);
		while (false !== $entry = $dir->read()) {
			// Skip pointers
			if ($entry == '.' || $entry == '..') {
				continue;
			}

			// Recurse
			$this->rmdirr($dirname . DIRECTORY_SEPARATOR . $entry);
		}

		// Clean up
		$dir->close();
		return rmdir($dirname);
	}
}
?>