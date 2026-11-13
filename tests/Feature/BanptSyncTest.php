<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BanptSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_matches_campuses_and_programs_from_raw_directory_json(): void
    {
        $now = now();
        $umb = DB::table('campuses')->insertGetId(['pt_code' => '031019', 'name' => 'Universitas Mercu Buana', 'created_at' => $now, 'updated_at' => $now]);
        $ti = DB::table('program_offerings')->insertGetId(['campus_id' => $umb, 'program_code' => '55201', 'original_name' => 'Teknik Informatika', 'level' => 'S1', 'created_at' => $now, 'updated_at' => $now]);
        $mn = DB::table('program_offerings')->insertGetId(['campus_id' => $umb, 'program_code' => '61201', 'original_name' => 'Manajemen', 'level' => 'S1', 'created_at' => $now, 'updated_at' => $now]);
        $ak = DB::table('program_offerings')->insertGetId(['campus_id' => $umb, 'program_code' => '62401', 'original_name' => 'Akuntansi', 'level' => 'D3', 'created_at' => $now, 'updated_at' => $now]);

        $dir = sys_get_temp_dir();
        // Format asli BAN-PT: daftar nilai berurutan.
        file_put_contents("$dir/prodi.json", json_encode(['data' => [
            ['Universitas Mercu Buana', 'Teknik Informatika', 'S1', '03', '038/SK/LAM-INFOKOM/Ak/S/IV/2023', '2023', 'Unggul', '2028-04-07', 'Masih berlaku'],
            ['Universitas Mercu Buana', 'Teknik Informatika', 'S1', '03', 'LAMA', '2018', 'A', '2023-01-01', 'Sudah kedaluarsa'],
            ['Universitas Mercu Buana', 'Akuntansi', 'D-III', '03', '864/DE/A.5/AR.10/XI/2023', '2023', 'Baik Sekali', '2028-12-12', 'Masih berlaku'],
            ['Universitas Tidak Dikenal', 'Hukum', 'S1', '03', 'X', '2024', 'Baik', '2029-01-01', 'Masih berlaku'],
            ['Universitas Mercu Buana', 'Manajemen', 'S1', '03', 'Y', '2015', 'B', '0000-00-00', ''],
        ]]));
        // Format tool lokal banpt-prodi-api: objek bernama.
        file_put_contents("$dir/inst.json", json_encode(['data' => [
            ['nama_kampus' => 'Universitas Mercu Buana', 'peringkat_akreditasi' => 'Unggul', 'nomor_sk' => '123/SK/BAN-PT/2024', 'tahun_sk' => '2024', 'wilayah' => '03', 'tanggal_kedaluwarsa' => '2029-01-01', 'status_kedaluwarsa' => 'Masih berlaku'],
        ]]));

        $this->artisan('banpt:sync', ['--prodi-file' => "$dir/prodi.json", '--institusi-file' => "$dir/inst.json"])->assertSuccessful();

        $p = DB::table('accreditations')->where('subject_type', 'program_offering')->where('subject_id', $ti)->first();
        $this->assertSame(['Unggul', 6, 'LAM-INFOKOM', '2028-04-07'], [$p->rating_raw, (int) $p->rating_score, $p->body, substr($p->expires_at, 0, 10)]);
        $this->assertSame('Baik Sekali', DB::table('accreditations')->where('subject_id', $ak)->where('subject_type', 'program_offering')->value('rating_raw'));
        $this->assertSame(6, (int) DB::table('accreditations')->where('subject_type', 'campus')->where('subject_id', $umb)->value('rating_score'));
        $this->assertSame(1, DB::table('accreditation_unmatched')->count());
        // Tanggal 0000-00-00 disimpan sebagai kosong, bukan tanggal rusak.
        $mnAcc = DB::table('accreditations')->where('subject_type', 'program_offering')->where('subject_id', $mn)->first();
        $this->assertNull($mnAcc->expires_at);
        $this->assertSame(0, (int) $mnAcc->rating_score); // SK 2015 tanpa tanggal: dianggap tidak berlaku
        $this->assertSame('ok', DB::table('sync_runs')->where('kind', 'banpt')->value('status'));
    }

    public function test_sync_tolerates_typos_city_suffixes_and_renamed_campuses_but_not_lookalikes(): void
    {
        $now = now();
        $c = fn ($code, $name, $city = null) => DB::table('campuses')->insertGetId(['pt_code' => $code, 'name' => $name, 'city' => $city, 'created_at' => $now, 'updated_at' => $now]);
        $o = fn ($campus, $name, $level = 'S1') => DB::table('program_offerings')->insertGetId(['campus_id' => $campus, 'program_code' => (string) random_int(10000, 99999), 'original_name' => $name, 'level' => $level, 'created_at' => $now, 'updated_at' => $now]);
        $its = $c('001002', 'Institut Teknologi Sepuluh Nopember', 'Kota Surabaya');
        $saw = $c('091013', 'Universitas Sawerigading', 'Kota Makassar');
        $stt = $c('051001', 'Sekolah Tinggi Teknologi Nasional', 'Kota Yogyakarta');
        $teo = $c('051002', 'Sekolah Tinggi Teologi Bandung', 'Kota Bandung');
        $uin = $c('201038', 'Universitas Islam Negeri Abdul Muthalib Sangadji Ambon', 'Kota Ambon');
        $itsTi = $o($its, 'Teknik Industri');
        $sawHk = $o($saw, 'Hukum');
        $uinPai = $o($uin, 'Pendidikan Bahasa Arab');
        $o($stt, 'Teknik Mesin');
        $o($teo, 'Teologi');

        $row = fn ($campus, $prodi) => [$campus, $prodi, 'S1', '07', 'SK', '2024', 'Baik', '2029-01-01', 'Masih berlaku'];
        $dir = sys_get_temp_dir();
        file_put_contents("$dir/prodi2.json", json_encode(['data' => [
            $row('Institut Teknologi Sepuluh November', 'Teknik lndustri'),   // salah ketik nama kampus & prodi ("l" untuk "I")
            $row('Universitas Sawerigading Makassar', 'Ilmu Hukum'),          // tambahan kota + awalan "Ilmu"
            $row('Institut Agama Islam Negeri Ambon', 'Pendidikan Bahasa Arab'), // nama lama sebelum menjadi UIN
            $row('Sekolah Tinggi Teknologi Nasional Jambi', 'Teknik Mesin'),  // kota tidak cocok → bukan STTNAS Yogyakarta
            $row('Sekolah Tinggi Teknologi Bandung', 'Teologi'),             // mirip, tapi kampus berbeda
        ]]));
        file_put_contents("$dir/inst2.json", json_encode(['data' => []]));

        $this->artisan('banpt:sync', ['--prodi-file' => "$dir/prodi2.json", '--institusi-file' => "$dir/inst2.json"])->assertSuccessful();

        $matched = DB::table('accreditations')->where('subject_type', 'program_offering')->pluck('subject_id')->all();
        $this->assertEqualsCanonicalizing([$itsTi, $sawHk, $uinPai], $matched);
        $this->assertEqualsCanonicalizing(['Sekolah Tinggi Teknologi Nasional Jambi', 'Sekolah Tinggi Teknologi Bandung'],
            DB::table('accreditation_unmatched')->pluck('campus_name')->all());
    }
}
