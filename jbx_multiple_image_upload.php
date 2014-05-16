<?php
/*DEBUG*/ global $debug;
/*DEBUG*/ $debug = true;
/*DEBUG*/ if ($debug){ ini_set('display_errors', 1); error_reporting(E_ALL); }
/**
 * Multiple Image Upload Plugin
 * @author Jonas Bach <hallo@jonasbach.de>
 */
class jbx_MIU{
	/**
	 * Stores an instance
	 * @var jbx_MIU
	 */
	protected static $instance;

	/**
	 * Slug of this plugin
	 * @var string
	 */
	protected static $slug = 'jbx_multiple_image_upload';

	/**
	 * Plugin prefix
	 * @var string
	 */
	protected static $prefix = 'jbx_';

	/**
	 * All available preferences for this plugin
	 * @var array
	 */
	protected static $preferences = array(
		array('id'=>'fileslimit', 'label'=>'Max. Files Limit', 'descr'=>'The number of files you can add to the batch.', 'type'=>0, 'standard'=>20),
		array('id'=>'thumb', 'label'=>'Create thumbnail', 'descr'=>'If a file thumb-imagename.ext is found the thumbnail will still be imported.', 'type'=>1, 'standard'=>'1'),
		array('id'=>'thumbcrop', 'label'=>'Crop thumbnail', 'descr'=>'The thumbnail shall be cropped.', 'type'=>1, 'standard'=>'0'),
		array('id'=>'thumbx', 'label'=>'Thumbnail width', 'descr'=>'May be 0 if thumbnail height is >0 and crop disabled.', 'type'=>0, 'standard'=>150),
		array('id'=>'thumby', 'label'=>'Thumbnail height', 'descr'=>'May be 0 if thumbnail width is >0 and crop disabled.', 'type'=>0, 'standard'=>0),
		array('id'=>'thumbhint', 'label'=>'Thumbnail icon', 'descr'=>'Add d small looking glass icon to thumbnail.', 'type'=>1, 'standard'=>'0'),
		array('id'=>'thumbgreyhint', 'label'=>'Grey bar at bottom of thumb', 'descr'=>'Grey bar at bottom of thumbnail, use it with hint.', 'type'=>1, 'standard'=>'0'),
		array('id'=>'resize', 'label'=>'Resize image', 'descr'=>'Resize the image (what a surprise).', 'type'=>1, 'standard'=>'0'),
		array('id'=>'sharpen', 'label'=>'Sharpen image', 'descr'=>'Claims to result in better quality resize.', 'type'=>1, 'standard'=>'0'),
		array('id'=>'imgx', 'label'=>'Resize to width', 'descr'=>'Width to resize image to (may be 0 if height >0).', 'type'=>0, 'standard'=>640),
		array('id'=>'imgy', 'label'=>'Resize to height', 'descr'=>'Height to resize image to (may be 0 if width >0).', 'type'=>0, 'standard'=>480),
		array('id'=>'importinfo', 'label'=>'Import additional info', 'descr'=>'Import meta info into caption.', 'type'=>2, 'standard'=>'none'),
	);

	/**
	 * Returns an instance
	 * @return jbx_MIU instance
	 */
	static function get_instance(){
		if ( null == self::$instance ) {
			self::$instance = new jbx_MIU();
		}

		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct(){
		add_privs('plugin_prefs.'.self::$slug, '1');
		register_callback(array($this, 'render_prefs'), 'plugin_prefs.'.self::$slug);
	}

	/**
	 * Render options page
	 * @param  string $event the event being called
	 * @param  string $step  the step being called
	 */
	public function render_prefs($event, $step){
		$msg = '';

		pageTop(gTxt('Multiple Image Upload'), $msg);
		
		// Generate Preferences Table
		$out = hed(gTxt('Multiple Image Upload - Preferences'), 1);
		$out .= startTable($this->prefix_id('preferences'), 'center', 5);
		foreach (self::$preferences as $pref) {
			$out .= $this->render_pref($pref);
		}
		
		// render save button
		$out .= tr(tdcs(eInput('plugin_prefs.'.self::$slug).sInput('update').fInput('submit', 'save', gTxt('save_button'), 'publish'), 3, '', 'nolin'));
		
		$out .= endtable();

		echo form($out);
	}

	/**
	 * Render single preference row
	 * @param  array $pref preference array
	 * @return string      <tr> containing the preference
	 */
	private function render_pref($pref){
		// render label
		$out = fLabelCell(gTxt($pref['label']), '', $this->prefix_id($pref['id']));

		// render field
		$id = $this->prefix_id($pref['id']);
		$value = $this->get_pref($pref['id']);

		switch($pref['type']){
			case 2:
				$out .= td(selectInput($id, array(gTxt('None') => '', 'EXIF'=>'exif', 'IPTC'=>'iptc'), $value));
			break;

			case 1:
				$out .= td(yesnoRadio($id, $value));
			break;

			default:
				$out .= fInputCell($id, $value, '', 20, '', $id);
			break;
		}

		// render help
		$out .= td(gTxt($pref['descr']));

		// render save
		
		return tr($out);
	}

	/**
	 * Get prefixed string
	 * @param  string $value unprefixed string
	 * @return string        prefixed string
	 */
	private function prefix($value){
		return self::$prefix.$value;
	}

	/**
	 * Get prefixed HTML id attribute
	 * @param  string $value unprefixed id
	 * @return string        prefixed id
	 */
	private function prefix_id($value){
		return self::$slug . '_' . $value;
	}

	/**
	 * Get preference from DB
	 * @param  string $id field to get
	 * @return mixed     stored preference
	 */
	private function get_pref($id){
		return get_pref($this->prefix($id));
	}

	/**
	 * Recursive directory delete
	 * @param  string $dirname path to directory
	 * @return [type]          [description]
	 */
	private function rmdirr($dirname){
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

// Initialize plugin
if(@txpinterface === 'admin'){
	jbx_MIU::get_instance();
}
?>