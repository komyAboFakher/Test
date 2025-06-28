<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Media;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Blaspsoft\Blasp\Facades\Blasp;
use App\Services\commentFilter;

class CommunicationController extends Controller
{
    // for supervisors only, or what ever you like


    public function addEvent(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'event_name' => 'required|string|max:255',
                'post' => 'required|string',
                'photos.*' => 'image|mimes:jpeg,png,jpg', // Validate multiple photos
                'is_published' => 'boolean'
            ]);



            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // 1. Create the event
            $event = Event::create([
                'user_id' => $request->user_id,
                'event_name' => $request->event_name,
                'post' => $request->post,
                'is_published' => $request->boolean('is_published', false)
            ]);

            // 2. Store photos if they exist
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('photos', 'public');



                    Media::create([
                        'event_id' => $event->id,
                        'photo_path' => $path,
                    ]);
                }
            }

            DB::commit();


            // format the response in goog shape

            return response()->json([
                'status' => true,
                'message' => 'event created successfully !!',
                'event' =>
                [
                    'id' => $event->id,
                    'user_id' => $event->user_id,
                    'event_name' => $event->event_name,
                    'post' => $event->post,
                    'is_published' => $event->is_published,
                    'created_at' => $event->created_at,
                    'updated_at' => $event->updated_at,
                    'media' => $event->media->map(function ($media) {
                        return [
                            'id' => $media->id,
                            'url' => asset(Storage::url($media->photo_path))
                        ];
                    })
                ]
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }



    //______________________________________________________________________________________________

    public function editEvent(Request $request, $eventID)
    {
        try {
            $event = Event::findOrFail($eventID);

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'event_name' => 'string|max:255',
                'post' => 'string',
                'is_published' => 'boolean',
                'photos' => 'sometimes|array',
                'photos.*' => 'image|mimes:jpeg,png,jpg,gif',
                'deleted_media_ids' => 'sometimes|array',
                'deleted_media_ids.*' => 'integer|exists:media,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update event details
            $updateData = $request->only(['event_name', 'post', 'is_published']);
            $event->update($updateData);

            // Handle deleted media
            if ($request->has('deleted_media_ids')) {
                $mediaToDelete = $event->media()->whereIn('id', $request->deleted_media_ids)->get();

                foreach ($mediaToDelete as $media) {
                    // Delete file from storage
                    Storage::delete($media->photo_path);
                    // Delete record from database
                    $media->delete();
                }
            }

            // Handle new photo uploads
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('photos', 'public');
                    $event->media()->create([
                        'photo_path' => $path
                    ]);
                }
            }

            // Load the updated media relationship
            $event->load('media');

            return response()->json([
                'status' => true,
                'message' => 'event updated successfully !!',
                'event' =>
                [
                    'id' => $event->id,
                    'user_id' => $event->user_id,
                    'event_name' => $event->event_name,
                    'post' => $event->post,
                    'is_published' => $event->is_published,
                    'created_at' => $event->created_at,
                    'updated_at' => $event->updated_at,
                    'media' => $event->media->map(function ($media) {
                        return [
                            'id' => $media->id,
                            'url' => asset(Storage::url($media->photo_path))
                        ];
                    })
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }




    //______________________________________________________________________________________________

    public function deleteEvent($eventID)
    {

        try {
            $event = Event::findOrFail($eventID);
            $event->delete();

            return response()->json([
                'status' => true,
                'message' => 'Event deleted successfully !!'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 404);
        }
    }
    //____________________________________________________________________________________-

    public function getEvents($userID)
    {
        try {
            // Validate the user ID first
            if (!is_numeric($userID)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid user ID format'
                ], 400);
            }


            $events = Event::with(['User'])
                ->where('user_id', $userID)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            // formating the json response only 

            $Events = $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'user_id' => $event->user_id,
                    'event_name' => $event->event_name,
                    'post' => $event->post,
                    'is_published' => $event->is_published,
                    'created_at' => $event->created_at,
                    'updated_at' => $event->updated_at,
                    'user' => $event->user, // Include user data if needed
                    'media' => $event->media->map(function ($media) {
                        return [
                            'id' => $media->id,
                            'url' => asset(Storage::url($media->photo_path)),
                        ];
                    })
                ];
            });


            // Check if any events found
            if ($events->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'No events found for this user',
                    'events' => []
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Events retrieved successfully',
                'events' =>  $Events

            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve events: ' . $th->getMessage()
            ], 500);
        }
    }
    //___________________________________________________________________________________________

    public function getAllPublishedEvents()
    {
        try {
            $events = Event::with(['user' => function ($query) {
                $query->select('id', 'name', 'middleName', 'lastname', 'role');
            }])
                ->where('is_published', true)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $Events = $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'user_id' => $event->user_id,
                    'event_name' => $event->event_name,
                    'post' => $event->post,
                    'is_published' => $event->is_published,
                    'created_at' => $event->created_at,
                    'updated_at' => $event->updated_at,
                    'user' => $event->user, // Include user data if needed
                    'media' => $event->media->map(function ($media) {
                        return [
                            'id' => $media->id,
                            'url' => asset(Storage::url($media->photo_path)),
                        ];
                    })
                ];
            });

            if ($events->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'No published events found',
                    'events' => []
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Published events retrieved successfully',
                'events' => $Events
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve events: ' . $th->getMessage()
            ], 500);
        }
    }
    //________________________________________________________________________________________________
    //                               the commments managements                                        |
    //________________________________________________________________________________________________|

    public function addComment(Request $request)
    {

        try {


            $validator = Validator::make($request->all(), [
                'event_id' => 'required|integer',
                'user_id' => 'required|integer',
                'parent_id' => 'nullable|integer',
                'content' => 'required|string'
            ]);

            // my local filter, not used any more

            // $filter = new CommentFilter();
            // if (!$filter->isClean($request->content)) {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'Your comment contains inappropriate language.'
            //     ], 403);
            // }

            // the BLASP library for comment filtering 



            $blaspResult = Blasp::check($request->content);

            if ($blaspResult->hasProfanity()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please avoid using inappropriate language.',
                    'by the way :' => 'go fuck your self !! hahahahahahahaa '
                ], 403);
            }





            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }


            $commentData = [
                'event_id' => $request->event_id,
                'user_id' => $request->user_id,
                'parent_id' => $request->parent_id,
                'content' => $request->content
            ];

            $comment = Comment::create($commentData);

            return response()->json([
                'status' => true,
                'message' => 'comment created successfully !!',
                'data' => $comment->load('user')
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 404);
        }
    }

    //_________________________________________________________________________________________________

    public function editComment(Request $request, $commentID)
    {

        try {


            $comment = Comment::findOrFail($commentID);

            $validator = Validator::make($request->all(), [
                'content' => 'required|string',

            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = $request->only(['content']);
            $comment->update($updateData);

            return response()->json([
                'status' => true,
                'message' => 'comment updated successfully !!!',
                'data' => $comment->load('user')
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 404);
        }
    }
    //________________________________________________________________________________________________
    public function deleteComment($commentID)
    {

        try {
            $comment = Comment::findOrFail($commentID);
            $comment->delete();

            return response()->json([
                'status' => true,
                'message' => 'comment deleted successfully !!',
                'comment' => $comment
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 404);
        }
    }


    //______________________________________________________________________________

    //public function getEventCommentss($eventId)
    //{
    //    try {
    //        // First get all comments for this event
    //        $allComments = Comment::with('user')
    //            ->where('event_id', $eventId)
    //            ->orderBy('created_at', 'desc')
    //            ->get();
    //
    //
    //        // Initialize an empty array to store our structured comments
    //        $threadedComments = [];
    //
    //        // Loop through every comment we fetched from the database
    //        foreach ($allComments as $comment) {
    //            // Check if this is a top-level comment (no parent)
    //            if (is_null($comment->parent_id)) {
    //
    //                // For top-level comments, find all direct replies
    //                $comment->replies = $allComments
    //                    // Filter comments where parent_id matches this comment's ID
    //                    ->where('parent_id', $comment->id)
    //                    // Process each reply
    //                    ->map(function ($reply) use ($allComments) {
    //                        // For each reply, find its own replies (nested replies)
    //                        $reply->replies = $allComments->where('parent_id', $reply->id);
    //                        return $reply; // Return the reply with its nested replies
    //                    });
    //
    //                // Add this fully assembled comment to our structured array
    //                $threadedComments[$comment->id] = $comment;
    //            }
    //        }
    //
    //
    //        return response()->json([
    //            'status' => true,
    //            'data' => $threadedComments
    //        ]);
    //    } catch (\Throwable $th) {
    //        return response()->json([
    //            'status' => false,
    //            'message' => 'Failed to fetch comments: ' . $th->getMessage()
    //        ], 500);
    //    }
    //}
    //////////////////////////////////////////////////////////////////////////////////


    public function getEventComments($eventId)
    {
        try {
            // Fetch all comments for the event
            $allComments = Comment::with('user')
                ->where('event_id', $eventId)
                ->orderBy('created_at', 'desc')
                ->get();

            // Build the threaded comment tree recursively
            $buildTree = function ($comments, $parentId = null) use (&$buildTree, $allComments) {
                $branch = [];

                foreach ($comments as $comment) {
                    if ($comment->parent_id == $parentId) {
                        // Recursively find replies to this comment
                        $replies = $buildTree($allComments, $comment->id);
                        if (!empty($replies)) {
                            $comment->replies = $replies;
                        }
                        $branch[] = $comment;
                    }
                }

                return $branch;
            };

            // Start building the tree from top-level comments (parent_id = NULL)
            $threadedComments = $buildTree($allComments, null);

            return response()->json([
                'status' => true,
                'data' => $threadedComments
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch comments: ' . $th->getMessage()
            ], 500);
        }
    }
}
