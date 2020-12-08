<?php

namespace App\Http\Controllers\PayRoll;

use Illuminate\Http\Request;
use App\Services\PayRoll\TimeSheetService;

class TimeSheetController
{
    /**
     * Validates request file and calls the Timesheet Service
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     * @author Argenis Barraza Guillen
     */
    public function calculate(Request $request)
    {
        $validator = $this->validateRequest($request);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'data' => '',
                'message'=> $validator->messages()
            ], 422);
        }

        $timeSheet =  new TimeSheetService($request->file('file'));
        $isValidJson =  $timeSheet->validateJsonFormat();

        if (!$isValidJson) {
            return response()->json([
                'status' => false,
                'message' => 'The file is not a valid JSON file'
            ], 422);
        }

        $data = $timeSheet->process();

        return response()->json([
            'status' => true,
            'message' => $data->toArray(),
        ], 200);
    }

    /**
     * Validate request method
     * @param Request $request
     * @return \Illuminate\Contracts\Validation\Validator
     * @author Argenis Barraza Guillen
     */
    protected function validateRequest(Request $request)
    {
        return \Validator::make($request->all(), [
            'file' => 'required'
        ]);
    }
}
