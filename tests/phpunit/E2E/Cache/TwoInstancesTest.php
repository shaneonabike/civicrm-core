<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * If you make two instances of a cache driver, do they coexist as you would expect?
 *
 * @group e2e
 */
class E2E_Cache_TwoInstancesTest extends CiviEndToEndTestCase {

  /**
   * @var Psr\SimpleCache\CacheInterface
   */
  protected $a;

  /**
   * @var Psr\SimpleCache\CacheInterface
   */
  protected $b;

  protected function setUp(): void {
    parent::setUp();
    $this->a = $this->b = NULL;
  }

  protected function tearDown(): void {
    parent::tearDown();
    if ($this->a) {
      $this->a->clear();
    }
    if ($this->b) {
      $this->b->clear();
    }
  }

  /**
   * Get a list of cache-creation specs.
   */
  public static function getSingleGenerators() {
    $exs = [];
    $exs[] = [
      ['type' => ['SqlGroup'], 'name' => 'TwoInstancesTest_SameSQL'],
    ];
    $exs[] = [
      ['type' => ['*memory*'], 'name' => 'TwoInstancesTest_SameMem'],
    ];
    return $exs;
  }

  /**
   * Add item to one cache instance then read with another.
   *
   * @param array $cacheDef
   *   Cache definition. See CRM_Utils_Cache::create().
   * @dataProvider getSingleGenerators
   */
  public function testSingle_reload($cacheDef) {
    if (!E2E_Cache_ConfiguredMemoryTest::isMemorySupported() && $cacheDef['type'] === ['*memory*']) {
      $this->markTestSkipped('This environment is not configured to use a memory-backed cache service.');
    }

    $a = $this->a = CRM_Utils_Cache::create($cacheDef);
    $a->set('foo', 1234);
    $this->assertEquals(1234, $a->get('foo'));

    $b = $this->b = CRM_Utils_Cache::create($cacheDef + ['prefetch' => TRUE]);
    $this->assertEquals(1234, $b->get('foo'));

    $b = $this->b = CRM_Utils_Cache::create($cacheDef + ['prefetch' => FALSE]);
    $this->assertEquals(1234, $b->get('foo'));
  }

  /**
   * Get a list of distinct cache-creation specs.
   */
  public static function getTwoGenerators() {
    $exs = [];
    $exs[] = [
      ['type' => ['SqlGroup'], 'name' => 'testTwo_a'],
      ['type' => ['SqlGroup'], 'name' => 'testTwo_b'],
    ];
    $exs[] = [
      ['type' => ['*memory*'], 'name' => 'testTwo_a'],
      ['type' => ['*memory*'], 'name' => 'testTwo_b'],
    ];
    $exs[] = [
      ['type' => ['*memory*'], 'name' => 'testTwo_drv'],
      ['type' => ['SqlGroup'], 'name' => 'testTwo_drv'],
    ];
    return $exs;
  }

  /**
   * Add items to the two caches. Then clear the first.
   *
   * @param array $cacheA
   *   Cache definition. See CRM_Utils_Cache::create().
   * @param array $cacheB
   *   Cache definition. See CRM_Utils_Cache::create().
   * @dataProvider getTwoGenerators
   */
  public function testDiff_clearA($cacheA, $cacheB) {
    [$a, $b] = $this->createTwoCaches($cacheA, $cacheB);
    $a->set('foo', 1234);
    $b->set('foo', 5678);
    $this->assertEquals(1234, $a->get('foo'), 'Check value A after initial setup');
    $this->assertEquals(5678, $b->get('foo'), 'Check value B after initial setup');

    $a->clear();
    $this->assertEquals(NULL, $a->get('foo'), 'Check value A after clearing A');
    $this->assertEquals(5678, $b->get('foo'), 'Check value B after clearing A');
  }

  /**
   * Add items to the two caches. Then clear the second.
   *
   * @param array $cacheA
   *   Cache definition. See CRM_Utils_Cache::create().
   * @param array $cacheB
   *   Cache definition. See CRM_Utils_Cache::create().
   * @dataProvider getTwoGenerators
   */
  public function testDiff_clearB($cacheA, $cacheB) {
    [$a, $b] = $this->createTwoCaches($cacheA, $cacheB);
    $a->set('foo', 1234);
    $b->set('foo', 5678);
    $this->assertEquals(1234, $a->get('foo'), 'Check value A after initial setup');
    $this->assertEquals(5678, $b->get('foo'), 'Check value B after initial setup');

    $b->clear();
    $this->assertEquals(1234, $a->get('foo'), 'Check value A after clearing B');
    $this->assertEquals(NULL, $b->get('foo'), 'Check value B after clearing B');
  }

  /**
   * Add items to the two caches. Then reload both caches and read from each.
   *
   * @param array $cacheA
   *   Cache definition. See CRM_Utils_Cache::create().
   * @param array $cacheB
   *   Cache definition. See CRM_Utils_Cache::create().
   * @dataProvider getTwoGenerators
   */
  public function testDiff_reload($cacheA, $cacheB) {
    [$a, $b] = $this->createTwoCaches($cacheA, $cacheB);
    $a->set('foo', 1234);
    $b->set('foo', 5678);
    $this->assertEquals(1234, $a->get('foo'), 'Check value A after initial setup');
    $this->assertEquals(5678, $b->get('foo'), 'Check value B after initial setup');

    [$a, $b] = $this->createTwoCaches($cacheA, $cacheB);
    $this->assertEquals(1234, $a->get('foo'), 'Check value A after initial setup');
    $this->assertEquals(5678, $b->get('foo'), 'Check value B after initial setup');
  }

  /**
   * @param $cacheA
   * @param $cacheB
   * @return array
   */
  protected function createTwoCaches($cacheA, $cacheB) {
    if (!E2E_Cache_ConfiguredMemoryTest::isMemorySupported() && $cacheA['type'] === ['*memory*']) {
      $this->markTestSkipped('This environment is not configured to use a memory-backed cache service.');
    }
    if (!E2E_Cache_ConfiguredMemoryTest::isMemorySupported() && $cacheB['type'] === ['*memory*']) {
      $this->markTestSkipped('This environment is not configured to use a memory-backed cache service.');
    }

    $a = $this->a = CRM_Utils_Cache::create($cacheA);
    $b = $this->b = CRM_Utils_Cache::create($cacheB);
    return [$a, $b];
  }

}
