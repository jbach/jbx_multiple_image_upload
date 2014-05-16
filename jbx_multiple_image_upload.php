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
		'fileslimit' => array('label'=>'Max. Files Limit', 'descr'=>'The number of files you can add to the batch.', 'type'=>0, 'default'=>20),
		'thumb' =>array('label'=>'Create thumbnail', 'descr'=>'If a file thumb-imagename.ext is found the thumbnail will still be imported.', 'type'=>1, 'default'=>'1'),
		'thumbcrop' => array('label'=>'Crop thumbnail', 'descr'=>'The thumbnail shall be cropped.', 'type'=>1, 'default'=>'0'),
		'thumbx' => array('label'=>'Thumbnail width', 'descr'=>'May be 0 if thumbnail height is >0 and crop disabled.', 'type'=>0, 'default'=>150),
		'thumby'=> array('label'=>'Thumbnail height', 'descr'=>'May be 0 if thumbnail width is >0 and crop disabled.', 'type'=>0, 'default'=>0),
		'thumbhint'=> array('label'=>'Thumbnail icon', 'descr'=>'Add d small looking glass icon to thumbnail.', 'type'=>1, 'default'=>'0'),
		'thumbgreyhint'=> array('label'=>'Grey bar at bottom of thumb', 'descr'=>'Grey bar at bottom of thumbnail, use it with hint.', 'type'=>1, 'default'=>'0'),
		'resize'=> array('label'=>'Resize image', 'descr'=>'Resize the image (what a surprise).', 'type'=>1, 'default'=>'0'),
		'sharpen'=> array('label'=>'Sharpen image', 'descr'=>'Claims to result in better quality resize.', 'type'=>1, 'default'=>'0'),
		'imgx'=> array('label'=>'Resize to width', 'descr'=>'Width to resize image to (may be 0 if height >0).', 'type'=>0, 'default'=>640),
		'imgy'=> array('label'=>'Resize to height', 'descr'=>'Height to resize image to (may be 0 if width >0).', 'type'=>0, 'default'=>480),
		'importinfo'=> array('label'=>'Import additional info', 'descr'=>'Import meta info into caption.', 'type'=>2, 'default'=>'none'),
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
		if($step === 'update'){
			$this->update_prefs();
		}

		pageTop(gTxt('Multiple Image Upload'));
		
		// Generate Preferences Table
		$out = hed(gTxt('Multiple Image Upload - Preferences'), 1);
		$out .= startTable($this->prefix('preferences'), 'center', 5);
		foreach (self::$preferences as $key => $pref) {
			$out .= $this->render_pref($key, $pref);
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
	private function render_pref($id, $pref){
		$value = $this->get_pref($id);
		$id = $this->prefix($id);

		// render label
		$out = fLabelCell(gTxt($pref['label']), '', $id);

		// render field
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
	 * Update preferences from submitted form
	 */
	private function update_prefs(){
		foreach (self::$preferences as $key => $pref) {
			$this->set_pref($key, gps($this->prefix($key)));
		}
		txp_die('', '302', '?event=plugin_prefs.'.self::$slug);
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
	 * Get preference from DB
	 * @param  string $id field to get
	 * @return mixed      stored preference
	 */
	private function get_pref($id){
		return get_pref($this->prefix($id), self::$preferences[$id]['default']);
	}

	/**
	 * Set preference (update/insert)
	 * @param string $id  unprefixed key of preference
	 * @param string $val value to save
	 */
	private function set_pref($id, $val = '', $default = ''){
		$default = ($default === '')? self::$preferences[$id]['default'] : $default;
		$val = trim($val);
		$val = ($val === '') ? $default : $val;
		return set_pref($this->prefix($id), $val, self::$slug, 2);
	}

	/**
	 * Recursive directory delete
	 * @param  string $dirname path to directory
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