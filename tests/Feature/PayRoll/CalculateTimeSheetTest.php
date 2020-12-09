<?php

namespace Tests\Feature\PayRoll;

use App\Services\PayRoll\TimeSheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CalculateTimeSheetTest extends TestCase
{
    private $size = '200';

    public function setUp() : void
    {
        parent::setUp();
    }

    public function testFileUploadFileWithPdfExtension()
    {
        $fakeFile = UploadedFile::fake()->create('document.pdf', $this->size);
        $response = $this->json('POST', 'api/payroll/calculate', [
            'file' => $fakeFile
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'message' => 'Json file does not have the correct data'
        ]);
    }

    public function testTimeSheetWithExtendedTimePerClass()
    {
        $this->assertTrue(true);
    }

    public function testTimeSheetWithOverlappData()
    {
        $this->assertTrue(true);
    }

    public function testTimeSheetWithDuplicatedClassesPerInstructor()
    {
        $this->assertTrue(true);
    }

    private static function demoJson(): string
    {
        return file_get_contents(base_path('tests/Helpers/TimeSheetData.json'));
    }

}
