<?php
/**
 * iPHP - i PHP Framework
 * Copyright (c) 2012 iiiphp.com. All rights reserved.
 *
 * @author coolmoo <iiiphp@qq.com>
 * @site http://www.iiiphp.com
 * @licence http://www.iiiphp.com/license
 * @version 1.0.1
 * @package iCache
 * @$Id: iCache.class.php 2408 2014-04-30 18:58:23Z coolmoo $
 */
//array(
//	'enable'	=> true,falsh,
//	'engine'	=> memcached,redis,file,
//	'host'		=> 127.0.0.1,/tmp/redis.sock,
//	'port'		=> 11211,
//	'db'		=> 1,
//	'compress'	=> 1-9,
//	'time'		=> 0,
//)
class iCache {
	public static $link = null;
	protected static $config = null;

	public static function init($config) {
		self::$config = $config;
		if (!self::$config['enable']) {
			return;
		}
		if (isset($GLOBALS['iCache']['link'])) {
			self::$link = $GLOBALS['iCache']['link'];
			return self::$link;
		}
		self::$config['engine'] OR self::$config['engine'] = 'file';
		self::$config['reset'] && self::$link = null;
		self::connect();
		return self::$link;
	}
	public static function connect() {
		if (self::$link === null) {
			switch (self::$config['engine']) {
			case 'memcached':
				require_once iPHP_CORE . '/memcached.class.php';
				$_servers = explode("\n", str_replace(array("\r", " "), "", self::$config['host']));
				self::$link = new memcached_client(array(
					'servers' => $_servers,
					'compress_threshold' => 10240,
					'persistant' => false,
					'debug' => false,
					'compress' => self::$config['compress'],
				));
				unset($_servers);
				break;
			case 'redis':
				require_once iPHP_CORE . '/redis.class.php';
				list($hosts, $db) = explode('@', trim(self::$config['host']));
				list($host, $port) = explode(':', $hosts);
				if (strstr($hosts, 'unix:')) {
					$host = $hosts;
					$port = 0;
				}
				$db = (int) str_replace('db:', '', $db);
				$db == '' && $db = 1;
				self::$link = new Redis_client(array(
					'host' => $host,
					'port' => $port,
					'db' => $db,
					'compress' => self::$config['compress'],
				));
				break;
			case 'file':
				require_once iPHP_CORE . '/iFileCache.class.php';
				list($dirs, $level) = explode(':', self::$config['host']);
				$level OR $level = 0;
				self::$link = new iFC(array(
					'dirs' => $dirs,
					'level' => $level,
					'compress' => self::$config['compress'],
				));
				break;
			}
			$GLOBALS['iCache']['link'] = self::$link;
		}
	}
	public static function prefix($keys, $prefix = NULL) {
		if ($prefix) {
			if (is_array($keys)) {
				foreach ($keys AS $k) {
					$_keys[] = $prefix . '/' . $k;
				}
				$keys = $_keys;
			} else {
				$keys = $prefix . '/' . $keys;
			}
		}
		return $keys;
	}
	public static function get($keys, $ckey = NULL, $unserialize = true) {
		$keys = self::prefix($keys, self::$config['prefix']);
		$_keys = implode('', (array) $keys);
		if (!self::$config['enable']) {
			if (strpos($keys, iPHP_APP) === false) {
				return NULL;
			} else {
				return self::sysCache();
			}
		} else {
			self::connect();
		}
		if (!isset($GLOBALS['iCache'][$_keys])) {
			$GLOBALS['iCache'][$_keys] = is_array($keys) ?
			self::$link->get_multi($keys, $unserialize) :
			self::$link->get($keys, $unserialize);
		}
		return $ckey === NULL ? $GLOBALS['iCache'][$_keys] : $GLOBALS['iCache'][$_keys][$ckey];
	}
	public static function set($keys, $res, $cachetime = "-1") {
		$keys = self::prefix($keys, self::$config['prefix']);
		if (!self::$config['enable']) {
			if (strpos($keys, iPHP_APP) === false) {
				return NULL;
			} else {
				return self::sysCache();
			}
		} else {
			self::connect();
		}
		if (self::$config['engine'] == 'memcached') {
			self::$link->delete($keys);
		}
		self::$link->add($keys, $res, ($cachetime != "-1" ? $cachetime : self::$config['time']));
		return $this;
	}
	public static function delete($key = '', $time = 0) {
		$key = self::prefix($key, self::$config['prefix']);
		self::$link->delete($key, $time);
	}
	public static function getsys($keys, $ckey = NULL) {
		$keys = self::prefix($keys, iPHP_APP);
		$keys = self::prefix($keys, self::$config['prefix']);
		return self::get($keys, $ckey);
	}
	public static function sysCache() {
		iPHP::core('FileCache', 'FC');
		return new iFC(array(
			'dirs' => '',
			'level' => 0,
			'compress' => 1,
		));
	}
	public static function redis($host = '127.0.0.1:6379@db:1', $time = '86400') {
		if (self::$config['engine'] != 'redis') {
			iCache::init(array(
				'enable' => true,
				'reset' => true,
				'engine' => 'redis',
				'host' => $host,
				'time' => $time,
			));
		}
	}
	public static function destroy() {
		self::$link = null;
	}
}
