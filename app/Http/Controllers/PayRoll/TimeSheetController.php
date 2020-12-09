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
                'message' => $validator->messages()->first()
            ], 422);
        }

        try {
            $timeSheet =  new TimeSheetService($request->file('file'));
            $data = $timeSheet->process();
        } catch (\Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage()
            ], 422);
        }

        return response()->json($data);
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
            'file.*' => 'required|mimes:json'
        ]);
    }
}
