<?php

/**
 * WeEngine Document System
 *
 * (c) We7Team 2019 <https://www.w7.cc>
 *
 * This is not a free software
 * Using it under the license terms
 * visited https://www.w7.cc for more details
 */

namespace W7\App\Handler\Cache;

use W7\App\Model\Entity\Cache;
use W7\Core\Cache\Handler\HandlerAbstract;

class DbHandler extends HandlerAbstract
{
	private $getValue = null;
	private $time = 999999999;

	public static function getHandler($config): HandlerAbstract
	{
		return new static();
	}

	public function set($key, $value, $ttl = null)
	{
		if (!$key || !$value) {
			return false;
		}

		$cache = new Cache();

		if ($this->has($key)) {
			$result = $cache->update(['value'=>$this->getValue , 'expired_at' => $this->getTtl($ttl)]);
		} else {
			$cache->key = $key;
			$cache->value = $value;
			$cache->expired_at = $this->getTtl($ttl);
			$result = $cache->save();
		}
		if ($result) {
			return true;
		}
		return false;
	}

	public function get($key, $default = null)
	{
		if (!$key) {
			return false;
		}

		if ($this->has($key) === true) {
			if ($this->getValue === false || $this->getValue === null) {
				return $default;
			}
			return $this->getValue;
		}
		return $default;
	}

	public function has($key)
	{
		if (!$key) {
			return false;
		}

		$value = $this->getValue($key);
		if ($value) {
			if ($value['expired_at'] == $this->time || $value['expired_at'] >= time()) {
				$this->getValue = $value['value'];
				return true;
			} else {
				$this->delete($key);
			}
		}
		return false;
	}

	public function setMultiple($values, $ttl = null)
	{
		if ($values) {
			idb()->beginTransaction();
			foreach ($values as $key => $val) {
				$result = $this->set($key, $val, $ttl);
				if ($result === false) {
					idb()->rollBack();
					return false;
				}
			}
			idb()->commit();
			return true;
		}
		return false;
	}

	public function getMultiple($keys, $default = null)
	{
		$values = [];
		if ($keys) {
			foreach ($keys as $key => $val) {
				$values[] = $this->get($val);
			}
		}
		return $values;
	}

	public function delete($key)
	{
		return Cache::query()->where('key', $key)->delete();
	}

	public function deleteMultiple($keys)
	{
		if ($keys) {
			idb()->beginTransaction();
			foreach ($keys as $key => $val) {
				$result = $this->delete($val);
				if ($result === false) {
					idb()->rollBack();
					return false;
				}
			}
			idb()->commit();
			return true;
		}
		return false;
	}

	public function clear()
	{
		return Cache::query()->delete();
	}

	private function getValue($key) {
		return Cache::query()->where('key', $key)->first();
	}

	private function getTtl($ttl): int {
		return ($ttl === null) ? $this->time : (time() + (int)$ttl);
	}
}