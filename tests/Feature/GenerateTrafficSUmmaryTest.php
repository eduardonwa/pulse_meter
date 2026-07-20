<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateTrafficSummaryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-07-20 12:00:00', 'UTC'));
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_generates_historical_sessions_when_active_log_is_empty_and_numeric_rotation_has_traffic(): void
    {
        $directory = $this->makeLogDirectory();
        $activeLog = $directory . '/access.log';

        file_put_contents($activeLog, '');
        file_put_contents($activeLog . '.1', $this->logLine('/yesterday'));

        $this->artisan('traffic:generate-summary', [
            '--log' => $activeLog,
            '--days' => 7,
        ])->assertSuccessful();

        $summary = $this->summary();

        $this->assertSame(1, $summary['total_sessions']);
        $this->assertSame('/yesterday', $summary['sessions'][0]['paths'][0]);
        $this->assertContains($activeLog, $summary['log_files']);
        $this->assertContains($activeLog . '.1', $summary['log_files']);
    }

    public function test_reads_compressed_numeric_rotated_log_when_active_log_is_empty(): void
    {
        $directory = $this->makeLogDirectory();
        $activeLog = $directory . '/access.log';

        file_put_contents($activeLog, '');
        file_put_contents('compress.zlib://' . $activeLog . '.2.gz', $this->logLine('/compressed'));

        $this->artisan('traffic:generate-summary', [
            '--log' => $activeLog,
            '--days' => 7,
        ])->assertSuccessful();

        $summary = $this->summary();

        $this->assertSame(1, $summary['total_sessions']);
        $this->assertSame('/compressed', $summary['sessions'][0]['paths'][0]);
        $this->assertContains($activeLog . '.2.gz', $summary['log_files']);
    }

    public function test_reads_logrotate_dateext_rotated_log_when_active_log_is_empty(): void
    {
        $directory = $this->makeLogDirectory();
        $activeLog = $directory . '/access.log';

        file_put_contents($activeLog, '');
        file_put_contents($activeLog . '-20260719.gz', gzencode($this->logLine('/dateext')));

        $this->artisan('traffic:generate-summary', [
            '--log' => $activeLog,
            '--days' => 7,
        ])->assertSuccessful();

        $summary = $this->summary();

        $this->assertSame(1, $summary['total_sessions']);
        $this->assertSame('/dateext', $summary['sessions'][0]['paths'][0]);
        $this->assertContains($activeLog . '-20260719.gz', $summary['log_files']);
    }

    public function test_ignores_files_that_only_share_the_access_log_prefix(): void
    {
        $directory = $this->makeLogDirectory();
        $activeLog = $directory . '/access.log';

        file_put_contents($activeLog, '');
        file_put_contents($activeLog . '.1', $this->logLine('/accepted'));
        file_put_contents($activeLog . '.bak', $this->logLine('/rejected'));
        mkdir($activeLog . '.3');

        $this->artisan('traffic:generate-summary', [
            '--log' => $activeLog,
            '--days' => 7,
        ])->assertSuccessful();

        $summary = $this->summary();

        $this->assertSame(1, $summary['total_sessions']);
        $this->assertSame('/accepted', $summary['sessions'][0]['paths'][0]);
        $this->assertNotContains($activeLog . '.bak', $summary['log_files']);
        $this->assertNotContains($activeLog . '.3', $summary['log_files']);
    }

    private function makeLogDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/pulse-meter-traffic-' . bin2hex(random_bytes(8));

        mkdir($directory, 0777, true);

        return $directory;
    }

    private function logLine(string $path): string
    {
        return sprintf(
            '203.0.113.10 - - [19/Jul/2026:12:00:00 +0000] "GET %s HTTP/1.1" 200 123 "-" "Mozilla/5.0"' . PHP_EOL,
            $path
        );
    }

    private function summary(): array
    {
        $this->assertTrue(Storage::disk('local')->exists('traffic/traffic-summary.json'));

        return json_decode(Storage::disk('local')->get('traffic/traffic-summary.json'), true);
    }
}
