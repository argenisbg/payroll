<?php

namespace Tests\Feature\PayRoll;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CalculateTimeSheetTest extends TestCase
{
    private $demoFile;
    private $mimeType = 'application/json';
    private $size = '200';
    private $dataStructure = [
        "id" => "574920",
        "start_date" => "2020-11-29",
        "start_time" => "18:00:00",
        "start_datetime" => "2020-11-29T23:00:00Z",
        "end_datetime" => "2020-11-29T23:45:00Z",
        "duration" => 45,
        "instructor_id" => "3334",
    ];

    public function setUp() : void
    {
        parent::setUp();

        $this->demoFile = self::demoJson();
    }

    public function testFileUploadFileWithPdfExtension()
    {
        Storage::fake('json');

        $fakeFile = UploadedFile::fake()->create('document.pdf', $this->size);
        $response = $this->json('POST', 'api/payroll/calculate', [
            'file' => $fakeFile
        ]);
        $response->assertStatus(422);
        $response->assertJson([
            'status' => false,
            'data' => '',
            'message' => [
                'file' => [
                    'The file must be a file of type: application/json.'
                ]
            ]
        ]);
    }

    public function testFileUploadFileWithJsonExtension()
    {
        Storage::fake('json');

        $fakeFile = UploadedFile::fake()->create('document.json', $this->size);
        $response = $this->json('POST', 'api/payroll/calculate', [
            'file' => $fakeFile
        ]);
        $response->assertStatus(200);
    }

    private static function demoJson(): string
    {
        return file_get_contents(base_path('tests/Helpers/TimeSheetData.json'));
    }
}
