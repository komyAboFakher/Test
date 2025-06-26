<?php

namespace App\Http\Controllers;

use App\Models\Borrow;
use App\Models\Library;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class libraryController extends Controller
{
    public function createBook(Request $request)
    {
        try {


            $validator = Validator::make(
                $request->all(),
                [
                    'title' => 'required|string',
                    'author' => 'required|string',
                    'category' => 'required|string',
                    'publisher' => 'nullable|string',
                    'serial_number' => 'required|string|unique:libraries,serial_number',
                    'shelf_location' => 'nullable|string',
                    'description' => 'nullable|string',
                ],
                [
                    'serial_number.unique' => 'This serial number already exists'
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $book = Library::create([
                'title' => $request->title,
                'author' => $request->author,
                'category' => $request->category,
                'publisher' => $request->publisher,
                'serial_number' => $request->serial_number,
                'shelf_location' => $request->shelf_location,
                'description' => $request->description,
            ]);

            return response()->json([

                'status' => true,
                'message' => 'book added successfully !!',
                'data' => $book
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //______________________________________________
    public function updateBook(Request $request, $bookID)
    {
        try {
            $updatedBook = Library::findOrFail($bookID);
            $validator = Validator::make(
                $request->all(),
                [
                    'title' => 'sometimes|string|max:255',
                    'author' => 'sometimes|string|max:255',
                    'category' => 'sometimes|string|max:100',
                    'publisher' => 'nullable|string|max:255',
                    'serial_number' => [
                        'sometimes',
                        'string',
                        'max:50',
                        Rule::unique('libraries')->ignore($updatedBook->id)
                    ],
                    'shelf_location' => 'nullable|string|max:50',
                    'description' => 'nullable|string'
                ],
                [
                    'serial_number.unique' => 'This serial number is already in use'
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }


            $updatedBook->fill($request->only([
                'title',
                'author',
                'category',
                'publisher',
                'serial_number',
                'shelf_location',
                'description'
            ]))->save();


            return response()->json([
                'status' => true,
                'message' => 'the book updated successfully !!',
                'data' => $updatedBook
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    //______________________________________________
    public function deleteBook($bookID)
    {
        try {
            $deletedBook = Library::findOrFail($bookID);
            // check if the book is borrowed by someone !!
            if ($deletedBook->borrow()->where('status', 'borrowed')->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot delete book: It is currently borrowed'
                ], 422);
            }
            $deletedBook->delete();
            return response()->json([
                'status' => true,
                'message' => 'book deleted !!',
                'book' => $deletedBook,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //______________________________________________
    public function borrow(Request $request)
    {
        try {

            $validator = Validator::make(
                $request->all(),
                [
                    'user_id' => 'required|integer|exists:users,id',
                    'book_id' => 'required|integer|exists:libraries,id',
                    'borrow_date' => 'required|date|after_or_equal:today',
                    'due_date' => 'required|date|after:borrow_date',
                    //'returned_date' => 'nullable|date|after_or_equal:borrow_date',
                    //'status' => 'sometimes|string|in:borrowed,returned,overdue,lost',
                    'notes' => 'nullable|string',
                ],
                [
                    'book_id.exists' => 'The specified book does not exist',
                    'due_date.after' => 'Due date must be after borrow date',
                    //'returned_date.after_or_equal' => 'Due date must be after borrow date',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $book = Library::findOrFail($request->book_id);
            if ($book->borrow()->whereNull('returned_date')->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'This book is currently borrowed'
                ], 409); // 409 Conflict
            }

            $borrow = Borrow::create([
                'user_id' => $request->user_id,
                'book_id' => $request->book_id,
                'borrow_date' => $request->borrow_date,
                'due_date' => $request->due_date,
                //'returned_date' => $request->returned_date,
                //'status' =>  'borrowed', //default status
                'notes' => $request->notes,
            ]);

            return response()->json([

                'status' => true,
                'message' => 'the book borrowed successfully !!',
                'data' => $borrow->load('user', 'book')
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //_______________________________________________________________

    public function modifyBorrow(Request $request)
    {


        try {

            $validator = Validator::make(
                $request->all(),
                [
                    'borrow_id' => 'required|integer|exists:borrows,id',
                    'user_id' => 'sometimes|integer|exists:users,id',
                    'book_id' => 'sometimes|integer|exists:libraries,id',
                    'borrow_date' => 'sometimes|date|after_or_equal:today',
                    'due_date' => 'sometimes|date|after:borrow_date',
                    'returned_date' => 'sometimes|date|after_or_equal:borrow_date',
                    'status' => [
                        'sometimes',
                        'string',
                        Rule::in(['borrowed', 'returned', 'overdue', 'lost'])
                    ],
                    'notes' => 'nullable|string',
                ],
                [
                    'borrow_id.exists' => 'The specified borrowing record does not exist',
                    'book_id.exists' => 'The specified book does not exist',
                    'due_date.after' => 'Due date must be after borrow date',
                    'returned_date.after_or_equal' => 'Due date must be after borrow date',
                    'status.in' => 'Invalid status. Allowed values: borrowed, returned, overdue, lost'
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $borrow = Borrow::findOrFail($request->borrow_id);


            $borrow->fill($request->only([

                'borrow_id',
                'user_id',
                'book_id',
                'borrow_date',
                'due_date',
                'returned_date',
                'status',
                'notes'
            ]));



            // if ($request->has('returned_date')) {
            //     $returnedDate = Carbon::parse($request->returned_date);
            //     $dueDate = Carbon::parse($borrow->due_date);
            //
            //     $borrow->status = $returnedDate->greaterThan($dueDate)
            //         ? borrowStatus::OVERDUE->value
            //         : borrowStatus::RETURNED->value;
            // }



            $borrow->save();

            return response()->json([

                'status' => true,
                'message' => 'updated successfully!!',
                'data' => $borrow->load('user', 'book')
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //______________________________________________
    public function showBook()
    {
        try {
            $books = Library::with('borrow.user')
                ->get()
                ->map(function ($library) {
                    return [
                        'title' => $library->title,
                        'author' => $library->author,
                        'category' => $library->category,
                        'publisher' => $library->publisher,
                        'serial_number' => $library->serial_number,
                        'shelf_location' => $library->shelf_location,
                        'description' => $library->description,
                        'created_at' => $library->created_at,
                        'updated_at' => $library->updated_at,
                        'borrower_info' => $library->borrow->map(function ($borrow) {
                            return [
                                'id' => $borrow->user_id,
                                'full_name' => trim(implode(' ', array_filter([$borrow->user->name, $borrow->user->middleName, $borrow->user->lastName]))),
                                'borrower_role' => $borrow->user->role,
                                'borrower_phone_number' => $borrow->user->phoneNumber,
                                'borrower_email' => $borrow->user->email,
                                'borrow_date' => $borrow->borrow_date,
                                'due_date' => $borrow->due_date,
                                'returned_date' => $borrow->returned_date,
                                'status' => $borrow->status,
                                'notes' => $borrow->notes,
                            ];
                        })
                    ];
                });
            return response()->json([

                'status' => true,
                'message' => 'books :',
                'data' => $books
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
