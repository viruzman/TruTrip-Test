<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use Symfony\Component\HttpFoundation\Response;
use App\Models\Trip;
use App\Http\Resources\TripCollection;
// use Illuminate\Support\Facades\Cache;


class TripController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $pageSize = $request->page_size ?? 1000;

        // Check data in cache
        if (cache()->has('my_trip')) {
            $response = cache()->get('my_trip'); // Get data from cache
        } else {
            $response = Trip::orderBy('updated_at', 'DESC')->where('user_id', auth()->user()->id)->paginate($pageSize);
            $response = new TripCollection($response);
            cache()->put('my_trip', $response, 60*60); // Set data in cache for 1 hour
        }

        return $response;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'between:3,1000'],
            'origin' => ['required'],
            'destination' => ['required'],
            'type' => ['required', Rule::in(['economy', 'regular', 'premium'])],
            'description' => ['required'],
            'start_date' => ['required'],
            'end_date' => ['required']
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(),
            Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $request['user_id'] = auth()->user()->id;
        try {
            $trip = Trip::create($request->all());

            $response = [
                'message' => 'Trip success created',
                'data' => $trip
            ];
    
            // Remove cache my_trip because there is a new data
            cache()->forget('my_trip');

            return response()->json($response, Response::HTTP_CREATED);
        } catch (QueryException $e) {
            return response()->json([
                'message' => "Failed" . $e->errorInfo
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $trip = Trip::findOrFail($id);
            
            if ($trip->user_id == auth()->user()->id) {
                $response = [
                    'message' => 'Detail data trip',
                    'data' => $trip
                ];
                $httpResponse = Response::HTTP_OK;
            } else {
                $response = [
                    'message' => 'Not allow to access'
                ];
                $httpResponse = Response::HTTP_METHOD_NOT_ALLOWED;
            }
            
            return response()->json($response, $httpResponse);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'between:3,1000'],
            'origin' => ['required'],
            'destination' => ['required'],
            'type' => ['required', Rule::in(['economy', 'regular', 'premium'])],
            'description' => ['required'],
            'start_date' => ['required'],
            'end_date' => ['required']
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(),
            Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        $request['user_id'] = auth()->user()->id;
        
        try {
            $trip = Trip::findOrFail($id);

            if ($trip->user_id != auth()->user()->id) {
                $response = [
                    'message' => 'Not allow to access'
                ];
                return response()->json($response, Response::HTTP_METHOD_NOT_ALLOWED);
            }

            try {
                $trip->update($request->all());

                $response = [
                    'message' => 'Trip success updated',
                    'data' => $trip
                ];

                // Remove cache my_trip because there is an update data
                cache()->forget('my_trip');
                
                return response()->json($response, Response::HTTP_OK);
            } catch (QueryException $e) {
                return response()->json([
                    'message' => "Failed" . $e->errorInfo
                ]);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $trip = Trip::findOrFail($id);

            if ($trip->user_id != auth()->user()->id) {
                $response = [
                    'message' => 'Not allow to access'
                ];
                return response()->json($response, Response::HTTP_METHOD_NOT_ALLOWED);
            }
            
            try {
                $trip->delete();

                $response = [
                    'message' => 'Trip success deleted'
                ];
                
                // Remove cache my_trip because there is a deleted data
                cache()->forget('my_trip');

                return response()->json($response, Response::HTTP_OK);
            } catch (QueryException $e) {
                return response()->json([
                    'message' => "Failed" . $e->errorInfo
                ]);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
