<?php
/**
 * Get方式 cURL
 *
 */
namespace xb\curl;

class GetCurl extends AbstractCurl {
	
	/**
	 * 调用父构造方法
	 *
	 */
	public function __construct() {
		parent::__construct();
	}
	
	/**
	 * 实现cURL主体的抽象方法
	 *
	 * @param array $para
	 * 
	 * @return void
	 */
	protected function _cUrl($para = array()) {
		foreach ($para as $key => $value) {
			curl_setopt($this->_ch, $key, $value);
		}
	}
}