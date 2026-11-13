<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\ProgramOffering;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_is_public(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_register_sma_goes_to_beranda(): void
    {
        $this->post('/daftar', [
            'username' => 'siswa.uji', 'email' => 'siswa@contoh.id', 'phone' => '+62 812-3456-7890',
            'level' => 'sma_smk', 'password' => 'rahasia-panjang', 'password_confirmation' => 'rahasia-panjang',
        ])->assertRedirect('/beranda');

        $this->assertDatabaseHas('users', ['username' => 'siswa.uji', 'phone' => '081234567890', 'role' => 'user']);
    }

    public function test_login_with_phone_username_or_email(): void
    {
        $u = User::factory()->create(['username' => 'budi', 'email' => 'budi@contoh.id', 'phone' => '081111111111']);
        foreach (['budi', 'BUDI@contoh.id', '62 811-1111-1111'] as $id) {
            $this->post('/masuk', ['identifier' => $id, 'password' => 'rahasia-panjang-123'])->assertRedirect('/beranda');
            $this->assertAuthenticatedAs($u);
            $this->post('/keluar');
        }
    }

    public function test_umb_student_must_pick_program_first(): void
    {
        $campus = Campus::create(['pt_code' => '031019', 'name' => 'Universitas Mercu Buana']);
        $s1 = ProgramOffering::create(['campus_id' => $campus->id, 'program_code' => '55201', 'original_name' => 'Teknik Informatika', 'level' => 'S1']);
        $s2 = ProgramOffering::create(['campus_id' => $campus->id, 'program_code' => '61101', 'original_name' => 'Magister Manajemen', 'level' => 'S2']);
        $u = User::factory()->create(['level' => 'mhs_umb']);
        $u->profile()->create();

        $this->actingAs($u)->get('/beranda')->assertRedirect('/mulai/prodi-umb');
        $this->post('/mulai/prodi-umb', ['program_offering_id' => $s2->id, 'semester' => 3, 'self_declared' => true])->assertSessionHasErrors('program_offering_id');
        $this->post('/mulai/prodi-umb', ['program_offering_id' => $s1->id, 'semester' => 3, 'self_declared' => true])->assertRedirect('/beranda');
        $this->get('/beranda')->assertOk();
    }
}
