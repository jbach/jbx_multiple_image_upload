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
	 * external assets
	 * @var array
	 */
	protected static $assets = array(
		'jquery.uploadify.min.js' => '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'
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
		add_privs(self::$slug, '1');
		add_privs('plugin_prefs.'.self::$slug, '1');
		register_callback(array($this, 'handle_request'), self::$slug, '', 1);
		register_callback(array($this, 'inject_assets'), 'admin_side', 'head_end');
		register_callback(array($this, 'render_prefs'), 'plugin_prefs.'.self::$slug);
		register_callback(array($this, 'install'), 'plugin_lifecycle.'.self::$slug, 'installed');
		register_callback(array($this, 'uninstall'), 'plugin_lifecycle.'.self::$slug, 'deleted');
	}

	/**
	 * Called on plugin installation
	 */
	public function install(){

	}

	/**
	 * Called on plugin deletion
	 */
	public function uninstall(){

	}

	/**
	 * Inject scripts/styles in <head>
	 */
	public function inject_assets(){
		echo '<script type="text/javascript" src="?event='.self::$slug.'&step=js"></script>';
	}

	/**
	 * Entry point for requests
	 * @param  string $event the event being called
	 * @param  string $step  the step being called
	 */
	public function handle_request($event, $step){
		$steps = array(
			'js' => false
		);
		if (!$step || !bouncer($step, $steps)) {
			return;
		}

		$this->{'render_'.$step}();
	}

	/**
	 * Render all JS assets
	 */
	public function render_js(){
		$script = $this->get_asset('jquery.uploadify.min.js');
		send_script_response($script);
		die();
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

	private function get_asset($file){
		if(array_key_exists($file, self::$assets)){
			return gzuncompress(base64_decode(self::$assets[$file]));
		}
		return '';
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