<?php

namespace Tests\Feature;

use App\Models\TestSession;
use App\Models\User;
use App\Services\RiasecScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestFlowTest extends TestCase
{
    use RefreshDatabase;

    /** Jawaban v14 yang menghasilkan IAS: I=5, A=4, S=3, R/E=2, C=1. */
    private function answers(): array
    {
        $v = ['R' => 2, 'I' => 5, 'A' => 4, 'S' => 3, 'E' => 2, 'C' => 1];
        $out = [];
        foreach (config('riasec.items') as $no => [$dim]) {
            $out[$no] = $v[$dim];
        }

        return $out;
    }

    public function test_scorer_matches_v14(): void
    {
        $s = app(RiasecScorer::class)->score($this->answers());
        $this->assertSame('IAS', $s['code']);
        $this->assertSame(40, $s['raw']['i']);
        $this->assertSame(10, $s['raw']['c']);
        $this->assertSame(0.2, $s['norm']['c']);
        // Seri R=E (0,4): urutan R sebelum E.
        $this->assertSame(['i', 'a', 's', 'r', 'e', 'c'], $s['rank']);
    }

    public function test_full_flow_saves_answers_and_result(): void
    {
        $u = User::factory()->create();
        $u->profile()->create();
        $this->actingAs($u);

        $this->post('/tes/mulai')->assertRedirect('/tes');
        $session = TestSession::firstOrFail();

        $this->postJson("/tes/{$session->id}/jawab", ['item_no' => 1, 'value' => 4])->assertOk();
        $this->postJson("/tes/{$session->id}/jawab", ['item_no' => 1, 'value' => 2])->assertOk();
        $this->assertDatabaseHas('test_answers', ['test_session_id' => $session->id, 'item_no' => 1, 'value' => 2]);

        $this->post("/tes/{$session->id}/selesai", ['answers' => $this->answers()])->assertRedirect('/hasil');
        $this->assertDatabaseCount('test_answers', 50);
        $this->assertDatabaseHas('test_results', ['test_session_id' => $session->id, 'holland_code' => 'IAS', 'score_i' => 40]);
        $this->assertSame('completed', $session->fresh()->status);

        $this->get('/hasil')->assertOk();
        $this->get('/beranda')->assertOk();

        // Sesi selesai tidak bisa diubah lagi.
        $this->postJson("/tes/{$session->id}/jawab", ['item_no' => 1, 'value' => 5])->assertStatus(409);
    }

    public function test_incomplete_answers_are_rejected(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->post('/tes/mulai');
        $session = TestSession::firstOrFail();
        $a = $this->answers();
        unset($a[50]);

        $this->post("/tes/{$session->id}/selesai", ['answers' => $a])->assertSessionHasErrors('answers');
        $this->assertDatabaseCount('test_results', 0);
    }

    public function test_cannot_touch_someone_elses_session(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->post('/tes/mulai');
        $session = TestSession::firstOrFail();

        // Sesi baru untuk pengguna lain (middleware AuthenticateSession mengikat sesi ke kata sandi pengguna).
        $this->flushSession();
        $this->actingAs(User::factory()->create())
            ->postJson("/tes/{$session->id}/jawab", ['item_no' => 1, 'value' => 3])->assertNotFound();
    }

    public function test_restart_abandons_open_session(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->post('/tes/mulai');
        $this->post('/tes/mulai');

        $this->assertSame(1, $u->testSessions()->where('status', 'in_progress')->count());
        $this->assertSame(1, $u->testSessions()->where('status', 'abandoned')->count());
    }
}
