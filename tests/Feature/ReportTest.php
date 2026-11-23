<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTontines;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase, BuildsTontines;

    public function test_tour_and_round_reports_render(): void
    {
        $t = $this->complete($this->start($this->makeTontine()));
        $cycle = $t->roundCycles()->first();

        $this->actingAs($t->admin)->get(route('tontines.reports.index', $t))->assertOk();
        $this->actingAs($t->admin)->get(route('tontines.reports.tour', [$t, $cycle]))
            ->assertOk()->assertSee('Membre 1')->assertSee('Sòl Test');
        $this->actingAs($t->admin)->get(route('tontines.reports.round', [$t, 1]))
            ->assertOk()->assertSee('Membre 3');
    }

    public function test_round_report_totals_are_correct(): void
    {
        $t = $this->complete($this->start($this->makeTontine()));

        $res = $this->actingAs($t->admin)->get(route('tontines.reports.round', [$t, 1]))->assertOk();
        $totals = $res->viewData('totals');

        $this->assertEquals(4500, $totals['collected']);
        $this->assertEquals(4500, $totals['paid_out']);
        $this->assertEquals(0, $totals['owed']);
        $this->assertSame(3, $totals['closed']);
    }

    public function test_round_report_shows_what_is_owed(): void
    {
        $t = $this->start($this->makeTontine(null, 3));
        $cycle = $this->openCycle($t);
        $members = $t->members()->get();
        $this->pay($t, $cycle, $members[0]);
        $this->pay($t, $cycle, $members[1]);
        $this->payout($t, $cycle, true); // le membre 3 n'a pas payé le tour 1

        $res = $this->actingAs($t->admin)->get(route('tontines.reports.round', [$t, 1]))->assertOk();
        $row = $res->viewData('memberRows')->firstWhere(fn ($r) => $r['member']->id === $members[2]->id);

        $this->assertSame(1, $row['missed']);
        $this->assertEquals(500, $row['owed']);
        $this->assertEquals(500, $res->viewData('totals')['owed']);
    }

    public function test_a_future_cycle_is_never_counted_as_missed(): void
    {
        $t = $this->start($this->makeTontine());

        $res = $this->actingAs($t->admin)->get(route('tontines.reports.round', [$t, 1]))->assertOk();

        $this->assertEquals(0, $res->viewData('totals')['owed']);
    }

    public function test_csv_export_is_a_utf8_download_with_bom(): void
    {
        $t = $this->complete($this->start($this->makeTontine()));
        $cycle = $t->roundCycles()->first();

        $res = $this->actingAs($t->admin)->get(route('tontines.reports.tour', [$t, $cycle, 'format' => 'csv']));

        $res->assertOk()->assertDownload();
        $content = $res->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Membre 1', $content);
        $this->assertStringContainsString('LajanAnsanm', $content);
        $this->assertStringContainsString('Rapport de tontine', $content);
        $this->assertStringContainsString('Detay pa manm', $content);
        $this->assertStringContainsString('Lajan atann (HTG)', $content);
        $lines = explode("\n", substr($content, 3));
        $inDetailTable = false;
        $detailRowCount = 0;
        foreach ($lines as $line) {
            $fields = str_getcsv(trim($line), ',');
            if ($fields[0] ?? '' === 'Detay pa manm') {
                $inDetailTable = true;
                continue;
            }
            if ($inDetailTable && $fields[0] ?? '' !== '' && !str_starts_with($fields[0] ?? '', '#')) {
                $this->assertCount(7, $fields, 'Ligne détaillée doit avoir 7 colonnes: ' . $line);
                $detailRowCount++;
            }
            if ($inDetailTable && $fields[0] ?? '' === '') {
                break;
            }
        }
        $this->assertGreaterThan(0, $detailRowCount);

        $res = $this->actingAs($t->admin)->get(route('tontines.reports.round', [$t, 1, 'format' => 'csv']));
        $res->assertOk()->assertDownload();
        $roundContent = $res->streamedContent();
        $this->assertStringContainsString('Detay pa manm', $roundContent);
        $this->assertStringContainsString('Ranmase total (HTG)', $roundContent);
        $lines = explode("\n", substr($roundContent, 3));
        $inDetailTable = false;
        $detailRowCount = 0;
        foreach ($lines as $line) {
            $fields = str_getcsv(trim($line), ',');
            if ($fields[0] ?? '' === 'Detay pa manm') {
                $inDetailTable = true;
                continue;
            }
            if ($inDetailTable && $fields[0] ?? '' !== '' && !str_starts_with($fields[0] ?? '', '#')) {
                $this->assertCount(8, $fields, 'Ligne détaillée doit avoir 8 colonnes: ' . $line);
                $detailRowCount++;
            }
            if ($inDetailTable && $fields[0] ?? '' === '') {
                break;
            }
        }
        $this->assertGreaterThan(0, $detailRowCount);
    }

    public function test_csv_neutralises_spreadsheet_formulas(): void
    {
        $t = $this->complete($this->start($this->makeTontine()));
        $t->members()->first()->update(['name' => "=HYPERLINK(\"http://evil\")"]);
        $cycle = $t->roundCycles()->first();

        $content = $this->actingAs($t->admin)
            ->get(route('tontines.reports.tour', [$t, $cycle, 'format' => 'csv']))
            ->streamedContent();

        $this->assertStringNotContainsString(',=HYPERLINK', $content);
        $this->assertStringNotContainsString('"=HYPERLINK', $content);
        $this->assertStringContainsString("'=HYPERLINK", $content);
    }

    public function test_unknown_round_returns_404(): void
    {
        $t = $this->start($this->makeTontine());

        $this->actingAs($t->admin)->get(route('tontines.reports.round', [$t, 99]))->assertNotFound();
    }
}
