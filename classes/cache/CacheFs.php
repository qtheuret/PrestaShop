<?php
/*
* 2007-2011 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2011 PrestaShop SA
*  @version  Release: $Revision: 6844 $
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class CacheFsCore extends Cache
{
	/**
	 * @var int Number of subfolders to dispatch cached filenames
	 */
	protected $depth;

	/**
	 * @var string Cache directory path
	 */
	protected $path;

	protected function __construct()
	{
		$this->depth = Db::getInstance()->getValue('SELECT value FROM '._DB_PREFIX_.'configuration WHERE name= \'PS_CACHEFS_DIRECTORY_DEPTH\'', false);
		$this->path = _PS_CACHEFS_DIRECTORY_;

		$keys_filename = $this->getFilename(self::KEYS_NAME);
		if (file_exists($keys_filename))
			$this->keys = unserialize(file_get_contents($keys_filename));
	}

	/**
	 * @see Cache::_set()
	 */
	protected function _set($key, $value, $ttl = 0)
	{
		return (@file_put_contents($this->getFilename($key), serialize($value)));
	}

	/**
	 * @see Cache::_get()
	 */
	protected function _get($key)
	{
		$filename = $this->getFilename($key);
		if (!file_exists($filename))
		{
			unset($this->keys[$key]);
			$this->_writeKeys();
			return false;
		}
		$file = file_get_contents($filename);
		return unserialize($file);
	}

	/**
	 * @see Cache::_exists()
	 */
	protected function _exists($key)
	{
		return file_exists($this->getFilename($key));
	}

	/**
	 * @see Cache::_delete()
	 */
	protected function _delete($key)
	{
		$filename = $this->getFilename($key);
		if (!file_exists($filename))
			return true;
		return unlink($filename);
	}

	/**
	 * @see Cache::_writeKeys()
	 */
	protected function _writeKeys()
	{
		@file_put_contents($this->getFilename(self::KEYS_NAME), serialize($this->keys));
	}

	/**
	 * @see Cache::flush()
	 */
	public function flush()
	{
		$this->delete('*');
	}

	/**
	 * Delete cache directory
	 */
	public static function deleteCacheDirectory()
	{
		Tools::deleteDirectory($this->path, false);
	}

	/**
	 * Create cache directory
	 *
	 * @param int $level_depth
	 * @param string $directory
	 */
	public static function createCacheDirectories($level_depth, $directory = false)
	{
		if (!$directory)
			$directory = $this->path;
		$chars = '0123456789abcdefghijklmnopqrstuvwxyz';
		for ($i = 0; $i < strlen($chars); $i++)
		{
			$new_dir = $directory.$chars[$i].'/';
			if (mkdir($new_dir))
				if (chmod($new_dir, 0777))
					if ($level_depth - 1 > 0)
						self::createCacheDirectories($level_depth - 1, $new_dir);
		}
	}

	/**
	 * Transform a key into its absolute path
	 *
	 * @param string $key
	 * @return string
	 */
	protected function getFilename($key)
	{
		$path = $this->path;
		for ($i = 0; $i < $this->depth; $i++)
			$path .= $key[$i].'/';

		return $path.$key;
	}
}
