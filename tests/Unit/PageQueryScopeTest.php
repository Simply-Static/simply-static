<?php

declare(strict_types=1);

namespace Simply_Static\Tests\Unit;

use Simply_Static\Options;
use Simply_Static\Task;
use Simply_Static\Tests\Support\UnitTestCase;

require_once dirname(__DIR__, 2) . '/src/class-ss-plugin.php';
require_once dirname(__DIR__, 2) . '/src/class-ss-options.php';
require_once dirname(__DIR__, 2) . '/src/class-ss-util.php';
require_once dirname(__DIR__, 2) . '/src/class-ss-query.php';
require_once dirname(__DIR__, 2) . '/src/models/class-ss-model.php';
require_once dirname(__DIR__, 2) . '/src/models/class-ss-page.php';
require_once dirname(__DIR__, 2) . '/src/tasks/class-ss-task.php';
require_once dirname(__DIR__, 2) . '/src/tasks/traits/trait-ss-can-process-pages.php';

final class PageQueryScopeTask extends Task {
    use \Simply_Static\canProcessPages;
    public function perform() { return true; }
}

final class PageQueryScopeTest extends UnitTestCase {
    public function testUnfilteredBaseQueryRetainsExistingFilePredicates(): void {
        $query = (new PageQueryScopeTask())->get_main_query();
        $property = new \ReflectionProperty($query, 'where');
        $property->setAccessible(true);
        self::assertSame(array('file_path IS NOT NULL', "file_path != ''"), $property->getValue($query));
    }

    public function testSharedQueryHookReceivesTaskAndProcessingColumnForEveryQuery(): void {
        Options::instance()->set('archive_start_time', '2026-11-16 10:00:00')->save();
        $task = new PageQueryScopeTask();
        $seen = array();
        add_filter('simply_static_task_pages_query', static function ($query, $current, $column) use (&$seen) {
            $seen[] = array($current, $column);
            return $query->where('language_scope = 1');
        }, 10, 3);
        foreach (array($task->get_main_query(), $task->get_pages_to_process_sql(), $task->get_processed_pages_sql()) as $query) {
            $property = new \ReflectionProperty($query, 'where');
            $property->setAccessible(true);
            self::assertContains('language_scope = 1', $property->getValue($query));
        }
        self::assertSame(array_fill(0, 3, array($task, 'last_transferred_at')), $seen);
    }
}
