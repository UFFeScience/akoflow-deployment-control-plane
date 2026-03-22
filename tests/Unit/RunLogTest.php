<?php

namespace Tests\Unit;

use App\Models\RunLog;
use PHPUnit\Framework\TestCase;

class RunLogTest extends TestCase
{
    // ── inferLevel ────────────────────────────────────────────────────────────

    public function test_infer_level_returns_error_for_error_keyword(): void
    {
        $this->assertSame(RunLog::LEVEL_ERROR, RunLog::inferLevel('[error] something broke'));
        $this->assertSame(RunLog::LEVEL_ERROR, RunLog::inferLevel('error: cannot open file'));
        $this->assertSame(RunLog::LEVEL_ERROR, RunLog::inferLevel('Apply failed with exit code 1'));
    }

    public function test_infer_level_is_case_insensitive_for_error(): void
    {
        $this->assertSame(RunLog::LEVEL_ERROR, RunLog::inferLevel('[ERROR] Fatal'));
        $this->assertSame(RunLog::LEVEL_ERROR, RunLog::inferLevel('Error: something went wrong'));
        $this->assertSame(RunLog::LEVEL_ERROR, RunLog::inferLevel('FAILED to connect'));
    }

    public function test_infer_level_returns_warn_for_warning_keyword(): void
    {
        $this->assertSame(RunLog::LEVEL_WARN, RunLog::inferLevel('[warning] deprecated resource'));
        $this->assertSame(RunLog::LEVEL_WARN, RunLog::inferLevel('warn: provider version mismatch'));
        $this->assertSame(RunLog::LEVEL_WARN, RunLog::inferLevel('This is a warning message'));
    }

    public function test_infer_level_is_case_insensitive_for_warn(): void
    {
        $this->assertSame(RunLog::LEVEL_WARN, RunLog::inferLevel('[WARNING] resource deprecated'));
        $this->assertSame(RunLog::LEVEL_WARN, RunLog::inferLevel('Warn: something odd'));
        $this->assertSame(RunLog::LEVEL_WARN, RunLog::inferLevel('WARNING: check your config'));
    }

    public function test_infer_level_returns_debug_for_debug_keyword(): void
    {
        $this->assertSame(RunLog::LEVEL_DEBUG, RunLog::inferLevel('[debug] reading state file'));
        $this->assertSame(RunLog::LEVEL_DEBUG, RunLog::inferLevel('debug: backend initialized'));
    }

    public function test_infer_level_is_case_insensitive_for_debug(): void
    {
        $this->assertSame(RunLog::LEVEL_DEBUG, RunLog::inferLevel('[DEBUG] verbose output'));
        $this->assertSame(RunLog::LEVEL_DEBUG, RunLog::inferLevel('Debug: reading tfstate'));
    }

    public function test_infer_level_defaults_to_info_for_plain_lines(): void
    {
        $this->assertSame(RunLog::LEVEL_INFO, RunLog::inferLevel('Initializing provider plugins'));
        $this->assertSame(RunLog::LEVEL_INFO, RunLog::inferLevel('[akocloud] Running: terraform init'));
        $this->assertSame(RunLog::LEVEL_INFO, RunLog::inferLevel('Apply complete! Resources: 3 added.'));
        $this->assertSame(RunLog::LEVEL_INFO, RunLog::inferLevel(''));
    }

    public function test_infer_level_error_takes_priority_over_warn(): void
    {
        // A line that contains both "error" and "warning" keywords — error wins (checked first)
        $this->assertSame(RunLog::LEVEL_ERROR, RunLog::inferLevel('error: warning threshold exceeded'));
    }
}
