<?php
/**
 * CacheGarbageCollector
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://krsteski.de/new-bsd-license/
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to gjero@krsteski.de so we can send you a copy immediately.
 *
 * @category  CacheDba
 * @copyright Copyright (c) 2010-2011 Gjero Krsteski (http://krsteski.de)
 * @license   http://krsteski.de/new-bsd-license New BSD License
 */

/**
 * CacheGarbageCollector
 *
 * @category  CacheDba
 * @copyright Copyright (c) 2010-2011 Gjero Krsteski (http://krsteski.de)
 * @license   http://krsteski.de/new-bsd-license New BSD License
 */
class Sweep
{
  /**
   * @var Cache
   */
  protected $_cache;

  /**
   * @param Cache $cache
   */
  public function __construct(Cache $cache)
  {
    $this->_cache = $cache;
  }

  /**
   * Remove all cache entries.
   * @return void
   */
  public function all()
  {
    $this->_process();
  }

  /**
   * Remove too old cache entries.
   * @return void
   */
  public function old()
  {
    $this->_process(false);
  }

  /**
   * Internal cleaning process.
   * @param boolean $cleanAll
   * @return void
   */
  protected function _process($cleanAll = true)
  {
    $key = dba_firstkey($this->_cache->getDba());

    while ($key !== false && $key !== null) {
      if (true === $cleanAll) {
        $this->_cache->delete($key);
      } else {
        $this->_cache->get($key);
      }

      $key = dba_nextkey($this->_cache->getDba());
    }

    dba_optimize($this->_cache->getDba());
  }

  /**
   * Flush the whole storage.
   * @throws RuntimeException If can not flush file.
   * @return bool
   */
  public function flush()
  {
    $file = $this->_cache->getStorage();

    if (file_exists($file)) {

      // close the dba file before delete
      // and reopen on next use
      $this->_cache->closeDba();

      if (!@unlink($file)) {
        throw new RuntimeException("can not flush file '{$file}'");
      }
    }

    return true;
  }
}
