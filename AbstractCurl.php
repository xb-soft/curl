<?php
/**
 * cURL抽象类
 *
 */
namespace xb\curl;

use xb\rpclog\RpcLog;
use xb\rpclog\Config as RpcLogEnvConfig;
/**
 * cURL抽象类
 *
 */
abstract class AbstractCurl {
	
	/**
	 * cURL资源
	 *
	 * @var resource
	 */
	protected $_ch = null;
	
	/**
	 * URL地址
	 *
	 * @var string
	 */
	protected $_url = '';
	
	/**
	 * 是否启用SSL模式
	 *
	 * @var boolean
	 */
	protected $_ssl = false;
	
	/**
	 * 初始化cURL资源
	 *
	 */
	protected function __construct() {
		if (false === function_exists('curl_init')) {
			$startTime = RpcLog::getMicroTime();
			$endTime = RpcLog::getMicroTime();
			RpcLog::log("[\033[31;6mERROR\033[0m] please install package of lib curl", $startTime, $endTime, RpcLogEnvConfig::RPC_LOG_TYPE_CURL);
			throw new \Exception('please install package of lib curl');
		}
		$this->_ch = curl_init();
	}
	
	/**
	 * cURL抽象方法，处理POST、GET、PUT(暂不提供)
	 *
	 * @param array $para
	 */
	abstract protected function _cUrl($para = array());
	
	/**
	 * 发送socket连接
	 *
	 * @param string $url
	 * @param array $para
	 * @param boolean $return
	 * 
	 * @return mix [void|string]
	 */
	private function _socket($url, $para, $return, $decode = true) {		
		$this->_setUrl($url);

		/*
		 * 强制转换为boolean类型，这里不使用(boolean)与settype
		 */
		if (false === isset($para['header'])) {
			$para['header'] = false;
		} else {
			$para['header'] = true;
		}
		curl_setopt($this->_ch, CURLOPT_HEADER, $para['header']);
		unset($para['header']);

		/*
		 * 处理302
		 */
		if (false === isset($para['location'])) {
			$para['location'] = false;
		} else {
			$para['location'] = true;
		}
		curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, $para['location']);
		if (true === $para['location']) {
			curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, 'GET');
		}
		unset($para['location']);
		
		if (false === isset($para['cookieFile'])) {
			$para['cookieFile'][0] = null;
			curl_setopt($this->_ch, CURLOPT_COOKIEFILE, $para['cookieFile'][0]);
			curl_setopt($this->_ch, CURLOPT_COOKIEJAR, $para['cookieFile'][0]);
			unset($para['cookieFile']);
		}

		/*
		 * exec执行结果是否保存到变量中
		 */
		if (true === $return) {
			curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
		}

		/*
		 * 是否启用SSL验证
		 */
		if (true === $this->_ssl) {
			//curl_setopt($this->_ch, CURLOPT_SSLVERSION, 3);
			curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, 2);
		}

		/*
		 * 调用子类处理方法
		 */
		$this->_cUrl($para);
		$startTime = RpcLog::getMicroTime();

		$result = curl_exec($this->_ch);

		$endTime = RpcLog::getMicroTime();

		if (curl_errno($this->_ch)) {
			RpcLog::log("url:{$url} response:(" . curl_errno($this->_ch) . ")" . curl_error($this->_ch), $startTime, $endTime, RpcLogEnvConfig::RPC_LOG_TYPE_CURL);
		} else {
			RpcLog::log("url:{$url} response:" . (true === $decode ? $result : json_encode($result)), $startTime, $endTime, RpcLogEnvConfig::RPC_LOG_TYPE_CURL);
		}
		curl_close($this->_ch);

		if (true === $return) {
			return (true === $decode ? json_decode($result, true) : $result);
		}
	}

	/**
	 * 初始化URL
	 *
	 * @param string $url
	 * 
	 * @return boolean [true成功 | false失败]
	 */
	private function _setUrl($url) {
		$this->_url = $url;
		/*
		 * 以下代码在PHP > 5.3有效
		 */
		if (false !== strstr($this->_url, 'https://', true)) {
			$this->_ssl = true;
		}
		return curl_setopt($this->_ch, CURLOPT_URL, $this->_url);
	}
	
	/**************************公共接口***********************/

	/**
	 * 发起通信请求接口
	 *
	 * @param string $url
	 * @param array $para
	 * @param boolean $return
	 * @param boolean $decode 是否json_decode后返回 ，只有return为true才有效
	 * 
	 * @return string
	 */
	final public function socket($url, $para = array(), $return = true, $decode = true) {
		return $this->_socket($url, $para, $return, $decode);
	}
}