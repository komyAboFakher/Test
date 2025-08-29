<?php

namespace App\Http\Controllers;

use App\Models\Borrow;
use App\Models\Library;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class libraryController extends Controller
{
    public function createBook(Request $request)
    {
        try {

            $currentUser = Auth()->user();


            $validator = Validator::make(
                $request->all(),
                [
                    'title' => 'required|string',
                    'author' => 'required|string',
                    'category' => 'required|string',
                    'publisher' => 'required|string',
                    'serrial_number' => 'required|string|unique:libraries,serrial_number',
                    'shelf_location' => 'required|string',
                    'description' => 'nullable|string',
                ],
                [
                    'serrial_number.unique' => 'This serrial number already exists'
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $book = Library::create([
                'user_id' => $currentUser->id,
                'title' => $request->title,
                'author' => $request->author,
                'category' => $request->category,
                'publisher' => $request->publisher,
                'serrial_number' => $request->serrial_number,
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
            $currentUser = Auth()->user()->id;
            $updatedBook = Library::findOrFail($bookID);
            $validator = Validator::make(
                $request->all(),
                [
                    'title' => 'nullable|string|max:255',
                    'author' => 'nullable|string|max:255',
                    'category' => 'nullable|string|max:100',
                    'publisher' => 'nullable|string|max:255',
                    'shelf_location' => 'nullable|string|max:50',
                    'description' => 'nullable|string'
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }


            $updatedBook->fill($request->only([
                'user_id' => $currentUser,
                'title',
                'author',
                'category',
                'publisher',
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
            if ($deletedBook->borrow()->where('book_status', 'borrowed')->exists()) {
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

    public function showBooks()
    {
        try {
            $books = Library::with('borrow')
                ->get()
                ->map(function ($library) {


                    return [
                        'book_id' => $library->id,
                        'title' => $library->title,
                        'author' => $library->author,
                        'category' => $library->category,
                        'publisher' => $library->publisher,
                        'serrial_number' => $library->serrial_number,
                        'shelf_location' => $library->shelf_location,
                        'description' => $library->description,
                        'created_at' => $library->created_at,
                        'updated_at' => $library->updated_at,
                        'book_status' => $library->borrow->pluck('book_status'),
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
    //______________________________________________________________________________

    public function showBookBySerrialNumber(Request $request)
    {

        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'serrial_number' => 'required|integer|exists:libraries,serrial_number'
                ],
                [
                    'serrial_number.exists' => 'the serrial number does not exist'
                ]
            );

            $book = Library::findOrFail($request->serrial_number);
            return response()->json([
                'status' => true,
                'message' => 'the book info',
                'book' => $book,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    //______________________________________________________________________________

    public function showBookBorrowers(Request $request)
    {
        try {

            $validator = Validator::make(
                $request->all(),
                [
                    'serrial_number' => 'required|integer|exists:libraries,serrial_number'
                ],
                [
                    'serrial_number.exists' => 'the serrial number does not exist'
                ]
            );
            $books = Library::with('borrow.user')
                ->where('serrial_number', $request->serrial_number)
                ->get()
                ->map(function ($library) {


                    return [
                        'title' => $library->title,
                        'author' => $library->author,
                        'category' => $library->category,
                        'publisher' => $library->publisher,
                        'serrial_number' => $library->serrial_number,
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
                                'book_status' => $borrow->book_status,
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

    //______________________________________________________________________________
    public function getBorrowOrder(Request $request)
    {
        try {

            $borrows = Borrow::with('library')
                ->get()
                ->map(function ($borrow) {
                    return [
                        'id'=> $borrow->id,
                        'user_id' => $borrow->user_id,
                        'book_id' => $borrow->book_id,
                        'title' => $borrow->library->title,
                        'author' => $borrow->library->author,
                        'category' => $borrow->library->category,
                        'publisher' => $borrow->library->publisher,
                        'shelf_location' => $borrow->library->shelf_location,
                        'description' => $borrow->library->description,
                        'serrial_number' => $borrow->serrial_number,
                        'borrow_status' => $borrow->borrow_status,
                        'borrow_date' => $borrow->borrow_date,
                        'due_date' => $borrow->due_date,
                        'returned_date' => $borrow->returned_date,
                        'book_status' => $borrow->book_status,
                        'notes' => $borrow->notes,
                        'borrower' => [

                            'full_name' => trim($borrow->user->name . ' ' . $borrow->user->middleName . ' ' . $borrow->user->lastName),
                            'email' => $borrow->user->email,
                            'role' => $borrow->user->role,
                            'class' => $borrow->user->student->SchoolClass->className ?? null

                        ]
                    ];
                });


            if (!$borrows) {
                return response()->json([
                    "message" => "there is no  borrow orders yet !!!"
                ]);
            }

            return response()->json([
                "status" => true,
                "message" => $borrows
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //______________________________________________________________________________
    public function borrow(Request $request)
    {
        try {

            $currentUser = Auth()->user()->id;
            $validator = Validator::make(
                $request->all(),
                [
                    'serrial_number' => 'required|string|exists:libraries,serrial_number',
                ],
                [
                    'serrial_number.exists' => 'The specified serrial number does not exist',

                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $book = Library::where('serrial_number', $request->serrial_number)->firstOrFail();

            if ($book->borrow()->where('book_status', 'borrowed')->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'This book is currently borrowed !!'
                ], 409);
            }

            if ($book->borrow()->where('borrow_status', 'pending')->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'somebody else have a pending order on that book !!'
                ], 409);
            }

            //wrong !!!!
            // $alreadyHasIt = Borrow::where('user_id', $currentUser)
            //     ->where('book_id', $book->id)
            //     ->whereIn('book_status', ['borrowed'])
            //     ->orWhere(function ($q) {
            //         $q->where('borrow_status', 'pending');
            //     })
            //     ->exists();


            $alreadyHasIt = Borrow::where(function ($query) use ($currentUser, $book) {
                $query->where('user_id', $currentUser)
                    ->where('book_id', $book->id)
                    ->where(function ($q) {
                        $q->whereIn('book_status', ['borrowed'])
                            ->orWhere('borrow_status', 'pending');
                    });
            })->exists();

            if ($alreadyHasIt) {
                return response()->json([
                    'status'  => false,
                    'message' => 'You already have an active or pending order for this book.'
                ], 409);
            }


            $borrow = Borrow::create([
                'user_id' => $currentUser,
                'book_id' => $book->id,
                'serrial_number' => $book->serrial_number,
                'borrow_date'=> now(),
            ]);

            return response()->json([

                'status' => true,
                'message' => 'the book borrow order made successfully !!',
                'data' => $borrow
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

            $currentUser = Auth()->user();
            $validator = Validator::make(
                $request->all(),
                [
                    'borrow_id' => 'required|integer|exists:borrows,id',
                    'borrow_status' => 'nullable|string|in:pending,accepted,rejected',
                    'due_date' => 'nullable|date|after:today',
                    'returned_date' => 'nullable|date|after_or_equal:today',
                    'book_status' => [
                        'nullable',
                        'string',
                        Rule::in(['borrowed', 'returned', 'overdue', 'lost'])
                    ],
                    'notes' => 'nullable|string',
                ],
                [
                    'borrow_id.exists' => 'The specified borrowing record does not exist',
                    'borrow_status.in' => 'The borrow status must be pending or accepted or rejected ',
                    'due_date.after' => 'Due date must be after borrow date',
                    'returned_date.after_or_equal' => 'Due date must be after borrow date',
                    'book_status.in' => 'the book status must be borrowed, returned, overdue or lost'
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
                'borrow_status',
                'borrow_date' => today(),
                'due_date',
                'returned_date',
                'book_status',
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



            $borrow->update();

            return response()->json([

                'status' => true,
                'message' => 'updated successfully!!',
                'data' => $borrow
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    //______________________________________________

}
