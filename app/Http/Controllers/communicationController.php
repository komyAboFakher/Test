<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Media;
use App\Models\Vedio;
use App\Models\Comment;
use App\Models\Reaction;
use Illuminate\Http\Request;
use App\Models\ReportedComment;
use App\Rules\ValidReaction;
use App\Services\commentFilter;
use Blaspsoft\Blasp\Facades\Blasp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CommunicationController extends Controller
{
    // for supervisors only, or what ever you like


    public function addEvent(Request $request)
    {
        try {

            $currentUser = auth()->user();
            $validator = Validator::make($request->all(), [
                'event_name' => 'required|string|max:255',
                'post' => 'required|string',
                'photos.*' => 'image|mimes:jpeg,png,jpg',
                'videos.*' => 'mimetypes:video/mp4,video/quicktime,video/x-msvideo|max:10240',
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
                'user_id' => $currentUser->id,
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



            return response()->json([
                'status' => true,
                'message' => 'event created successfully !!',
                'event' =>
                [
                    'id' => $event->id,
                    'user_id' => $event->user_id,
                    'full_name' => trim("{$event->user->name} {$event->user->middleName} {$event->user->lastName}"),
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

            $currentUser = auth()->user();
            $event = Event::findOrFail($eventID);

            $validator = Validator::make($request->all(), [
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
                    'full_name' => trim("{$event->user->name} {$event->user->middleName} {$event->user->lastName}"),
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

    public function getEvents()
    {
        try {
            // Validate the user ID first
            $currentUser = auth()->user()->id;

            $events = Event::with(['User'])
                ->where('user_id', $currentUser)
                ->orderBy('created_at', 'desc')
                ->paginate(10);





            // formating the json response only 

            $Events = $events->map(function ($event) {

                $reactionTypes = $event->reactions->groupBy('type')->map->count();

                return [
                    'id' => $event->id,
                    'user_id' => $event->user_id,
                    'full_name' => trim("{$event->user->name} {$event->user->middleName} {$event->user->lastName}"),
                    'email' => $event->user->email,
                    'event_name' => $event->event_name,
                    'post' => $event->post,
                    'is_published' => $event->is_published,
                    'comments_number' => $event->comment->count(),
                    'reactions' => [
                        'reaction_number' => $event->reactions->count(),
                        'types' => $reactionTypes
                    ],
                    'created_at' => $event->created_at,
                    'updated_at' => $event->updated_at,
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
                    'message' => 'No events published yet !!!',
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
            $currentUser = Auth()->user();
            $events = Event::with([
                'user' => function ($query) {
                    $query->select('id', 'name', 'middleName', 'lastName', 'email', 'role');
                },
                'reactions'

            ])
                ->where('is_published', true)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $userReactions = DB::table('reactables')
                ->join('reactions', 'reactables.reaction_id', '=', 'reactions.id')
                ->where('reactables.user_id', $currentUser->id)
                ->where('reactables.reactable_type', Event::class)
                ->select('reactables.reactable_id as event_id', 'reactions.type as reaction_type')
                ->get()
                ->keyBy('event_id');


            $Events = $events->map(function ($event) use ($userReactions) {
                $reactionTypes = $event->reactions->groupBy('type')->map->count();
                $userReaction = $userReactions->get($event->id);
                return [
                    'id' => $event->id,
                    'user_id' => $event->user_id,
                    'publisherName' => trim("{$event->user->name} {$event->user->middleName} {$event->user->lastName}"),
                    'email' => $event->user->email,
                    'event_name' => $event->event_name,
                    'post' => $event->post,
                    'is_published' => $event->is_published,
                    'comment_number' => $event->comment->count(),
                    'reactions' => [
                        'reaction_number' => $event->reactions->count(),
                        'types' => $reactionTypes
                    ],
                    'is_reacted' => isset($userReaction),
                    'user_reaction_type' => $userReaction->reaction_type ?? null,
                    'created_at' => $event->created_at,
                    'updated_at' => $event->updated_at,
                    'role' => $event->user->role,
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
    //_________________________________________________________________________________________________
    public function shareEvent(Request $request)
    {

        try {

            $currentUser = Auth()->user()->id;

            $validator = Validator::make($request->all(), [
                'event_id' => 'required|integer|exists:events,id'
            ]);

            $events = Event::with(['user' => function ($query) {
                $query->select('id', 'name', 'middleName', 'lastName', 'email', 'role');
            }])
                ->where('is_published', true)
                ->where('id', $request->event_id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $userReactions = DB::table('reactables')
                ->join('reactions', 'reactables.reaction_id', '=', 'reactions.id')
                ->where('reactables.user_id', $currentUser)
                ->where('reactables.reactable_type', Event::class)
                ->select('reactables.reactable_id as event_id', 'reactions.type as reaction_type')
                ->get()
                ->keyBy('event_id');

            $Events = $events->map(function ($event) use ($userReactions) {
                $reactionTypes = $event->reactions->groupBy('type')->map->count();
                $userReaction = $userReactions->get($event->id);

                return [
                    'id' => $event->id,
                    'user_id' => $event->user_id,
                    'publisherName' => trim("{$event->user->name} {$event->user->middleName} {$event->user->lastName}"),
                    'email' => $event->user->email,
                    'event_name' => $event->event_name,
                    'post' => $event->post,
                    'is_published' => $event->is_published,
                    'comment_number' => $event->comment->count(),
                    'reaction_number' => $event->reactions->count(),
                    'reactions' => [
                        'reaction_number' => $event->reactions->count(),
                        'types' => $reactionTypes
                    ],
                    'is_reacted' => isset($userReaction),
                    'user_reaction_type' => $userReaction->reaction_type ?? null,
                    'created_at' => $event->created_at,
                    'updated_at' => $event->updated_at,
                    'role' => $event->user->role,
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
                'message' => 'Published event retrieved successfully',
                'events' => $Events
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve events: ' . $th->getMessage()
            ], 500);
        }
    }
    //_________________________________________________________________________________________________
    public function getUserEvents(Request $request)
    {

        try {
            // Validate the user ID first
            $currentUser = Auth()->user()->id;
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:events, user_id'
            ]);


            $events = Event::with(['User'])
                ->where('user_id', $request->user_id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $userReactions = DB::table('reactables')
                ->join('reactions', 'reactables.reaction_id', '=', 'reactions.id')
                ->where('reactables.user_id', $currentUser)
                ->where('reactables.reactable_type', Event::class)
                ->select('reactables.reactable_id as event_id', 'reactions.type as reaction_type')
                ->get()
                ->keyBy('event_id');



            // formating the json response only 

            $Events = $events->map(function ($event) use ($userReactions) {

                $reactionTypes = $event->reactions->groupBy('type')->map->count();
                $userReaction = $userReactions->get($event->id);

                return [
                    'id' => $event->id,
                    'user_id' => $event->user_id,
                    'publisherName' => trim("{$event->user->name} {$event->user->middleName} {$event->user->lastName}"),
                    'email' => $event->user->email,
                    'event_name' => $event->event_name,
                    'post' => $event->post,
                    'is_published' => $event->is_published,
                    'comments_number' => $event->comment->count(),
                    'reactions' => [
                        'reaction_number' => $event->reactions->count(),
                        'types' => $reactionTypes
                    ],
                    'is_reacted' => isset($userReaction),
                    'user_reaction_type' => $userReaction->reaction_type ?? null,
                    'created_at' => $event->created_at,
                    'updated_at' => $event->updated_at,
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
                    'message' => 'No events published yet !!!',
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

    //________________________________________________________________________________________________
    //                               the commments managements                                        |
    //________________________________________________________________________________________________|

    public function addComment(Request $request)
    {

        try {

            $currentUser = auth()->user()->id;

            $validator = Validator::make($request->all(), [
                'event_id' => 'required|integer|exists:events,id',
                'parent_id' => 'nullable|integer',
                'content' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

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

            $commentData = [
                'event_id' => $request->event_id,
                'user_id' => $currentUser,
                'parent_id' => $request->parent_id,
                'content' => $request->content
            ];

            $comment = Comment::create($commentData);

            return response()->json([
                'status' => true,
                'message' => 'comment published successfully !!',
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

            $currentUser = auth()->user();


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

            $blaspResult = Blasp::check($request->content);

            if ($blaspResult->hasProfanity()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please avoid using inappropriate language.',
                    'by the way :' => 'go fuck your self !! hahahahahahahaa '
                ], 403);
            }

            if ($currentUser->id != $comment->user_id) {
                return response()->json([
                    'message' => 'you are not allowed to modify anything else than your comments !!'
                ], 401);
            }

            $comment->fill($request->only([
                'content',
            ]))->update();



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
            $currentUser = auth()->user();
            $comment = Comment::findOrFail($commentID);

            if ($currentUser->id != $comment->user_id && $currentUser->role !== 'supervisor') {
                return response()->json([
                    'message' => 'you are not allowed to delete  anything else than your comments !!'
                ], 401);
            } else {
                $comment->deleteWithReplies();
            }

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
    //_________________________________________________________________________________________________

    public function getEventComments($eventId)
    {
        try {

            $currentUser = Auth()->user()->id;

            $allComments = Comment::with('user', 'reactions')
                ->where('event_id', $eventId)
                ->get();

            $userReactions = DB::table('reactables')
                ->join('reactions', 'reactables.reaction_id', '=', 'reactions.id')
                ->where('reactables.user_id', $currentUser)
                ->where('reactables.reactable_type', Comment::class)
                ->select('reactables.reactable_id as comment_id', 'reactions.type as reaction_type')
                ->get()
                ->keyBy('comment_id');



            // Build the threaded comment tree
            $buildTree = function ($comments, $parentId = null) use (&$buildTree, $allComments, $userReactions) {
                $branch = [];


                foreach ($comments as $comment) {
                    $reactionTypes = $comment->reactions->groupBy('type')->map->count();
                    $userReaction = $userReactions->get($comment->id);
                    if ($comment->parent_id == $parentId) {
                        $commentData = [
                            'id' => $comment->id,
                            'user_id' => $comment->user_id,
                            'parent_id' => $comment->parent_id,
                            'name' => $comment->user->name,
                            'middle_name' => $comment->user->middleName,
                            'last_name' => $comment->user->lastName,
                            'email' => $comment->user->email,
                            'role' => $comment->user->role,
                            'content' => $comment->content,
                            'reactions' => [
                                'reaction_number' => $comment->reactions->count(),
                                'types' => $reactionTypes
                            ],
                            'is_reacted' => isset($userReaction),
                            'user_reaction_type' => $userReaction->reaction_type ?? null,
                            'created_at' => $comment->created_at->toIso8601String(),
                            'replies' => $buildTree($allComments, $comment->id)
                        ];

                        $branch[] = $commentData;
                    }
                }

                return $branch;
            };

            // Build the tree starting from top-level comments
            $threadedComments = $buildTree($allComments, null);

            if (!$threadedComments) {
                return response()->json([
                    "message" => "no comments yet !!"
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => $threadedComments
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch reported comments: ' . $th->getMessage()
            ], 500);
        }
    }
    //________________________________________________________________________________

    // old version
    //public function getEventComments($eventId)
    //{
    //    try {
    //        // Fetch all comments for the event
    //        $allComments = Comment::with('user')
    //            ->where('event_id', $eventId)
    //            ->orderBy('created_at', 'desc')
    //            ->get();
    //
    //        // Build the threaded comment tree recursively
    //        $buildTree = function ($comments, $parentId = null) use (&$buildTree, $allComments) {
    //            $branch = [];
    //
    //            foreach ($comments as $comment) {
    //                if ($comment->parent_id == $parentId) {
    //                    // Recursively find replies to this comment
    //                    $replies = $buildTree($allComments, $comment->id);
    //                    if (!empty($replies)) {
    //                        $comment->replies = $replies;
    //                    }
    //                    $branch[] = $comment;
    //                }
    //            }
    //
    //            return $branch;
    //        };
    //
    //        // Start building the tree from top-level comments (parent_id = NULL)
    //        $threadedComments = $buildTree($allComments, null);
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

    //_____________________________________________________________________________________________
    //                             REPORTING COMMENTS
    //_____________________________________________________________________________________________

    public function reportComment(Request $request)
    {

        try {
            $currentUser = auth()->user()->id;

            $validator = Validator::make($request->all(), [
                'comment_id' => 'required|integer|exists:comments,id',
                'reason' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $commentID = Comment::findOrFail($request->comment_id);

            $alreadyReported = ReportedComment::where([
                'comment_id' => $commentID->id,
                'reporter_id' => $currentUser
            ])->exists();

            if ($alreadyReported) {
                return response()->json([
                    'status' => false,
                    'message' => 'you already reported this comment !!'
                ], 409); // 409 = Conflict
            }


            $reportedComment = [
                'reporter_id' => $currentUser,
                'comment_id' => $commentID->id,
                'reason' => $request->reason,
            ];



            $comment = ReportedComment::create($reportedComment);

            return response()->json([
                'status' => true,
                'message' => 'the comment has been reported successfully !!'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 404);
        }
    }
    //_____________________________________________________________________________________________

    public function showReportedComments()
    {

        try {

            $rep = ReportedComment::with([
                'reporter',
                'Comment.user',
                'Comment.event',
            ])->get()->

                //->groupBy(function ($k) {
                //    $eventID = $k->comment->event->id;
                //    return " event_id: {$eventID} ";
                //})
                //    ->map(function ($group) {
                //        return $group->



                groupBy(function ($r) {
                    $commentId = $r->comment->id;
                    return " comment_id: {$commentId} ";
                })->map(function ($group) {
                    return $group->map(function ($r) {
                        return [
                            'report_id' => $r->id,
                            'event_id' => $r->comment->event_id,
                            'reporter' => trim("{$r->reporter->name} {$r->reporter->middleName} {$r->reporter->lastName}"),
                            'reporter role' => $r->reporter->role,
                            'reporter email' => $r->reporter->email,
                            'reporter phone' => $r->reporter->phoneNumber,
                            'reason' => $r->reason,
                            'reported_at' => $r->created_at,
                            'comment_content' => $r->Comment->content,
                            'author' => trim("{$r->Comment->user->name} {$r->Comment->user->middleName} {$r->Comment->user->lastName}"),
                            'author role' => $r->Comment->user->role,
                            'author email' => $r->Comment->user->email,
                        ];
                    });
                });
            //});

            if (!$rep) {
                return response()->json([
                    "status" => true,
                    "message" => "no reported comments yet !!"
                ]);
            }

            return response()->json([
                "status" => true,
                "message" => $rep
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
    //_____________________________________________________________________________________

    public function react(Request $request)
    {
        try {
            $currentUser = auth()->user();

            $validator = Validator::make($request->all(), [
                'reaction' => ['required', 'string', new ValidReaction],
                'reactable_id' => 'required|integer',
                'reactable_type' => 'required|string|in:event,comment',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // Determine reactable model and relationship
            $reactableType = $request->reactable_type === 'event'
                ? Event::class
                : Comment::class;

            $relationshipMethod = $request->reactable_type === 'event'
                ? 'reactedEvents'
                : 'reactedComments';

            // Find or create reaction
            $reaction = Reaction::firstOrCreate(['type' => $request->reaction]);

            // Check for any existing reaction 
            $existingReaction = $currentUser->{$relationshipMethod}()
                ->wherePivot('reactable_id', $request->reactable_id)
                ->first();

            // Start database transaction
            DB::beginTransaction();

            if ($existingReaction) {
                // toggle
                if ($existingReaction->pivot->reaction_id == $reaction->id) {
                    $currentUser->{$relationshipMethod}()
                        ->wherePivot('reactable_id', $request->reactable_id)
                        ->detach();

                    DB::commit();
                    return response()->json([
                        'status' => true,
                        'action' => 'removed',
                        'reaction' => null
                    ]);
                }

                // update if different
                $currentUser->{$relationshipMethod}()
                    ->wherePivot('reactable_id', $request->reactable_id)
                    ->updateExistingPivot($request->reactable_id, [
                        'reaction_id' => $reaction->id,
                        'updated_at' => now()
                    ]);

                DB::commit();
                return response()->json([
                    'status' => true,
                    'action' => 'updated',
                    'reaction' => $request->reaction
                ]);
            }

            // Add new reaction if it is not update or remove
            $currentUser->{$relationshipMethod}()->attach($request->reactable_id, [
                'reaction_id' => $reaction->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();
            return response()->json([
                'status' => true,
                'action' => 'added',
                'reaction' => $request->reaction
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to process reaction',
                'error' => $th->getMessage()
            ], 404);
        }
    }
    //_______________________________________________________________________

    public function getReactions(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reactable_id' => 'required|integer',
                'reactable_type' => 'required|string|in:event,comment',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }


            $reactableType = $request->reactable_type === 'event'
                ? Event::class
                : Comment::class;


            $reactable = $reactableType::with(['reactions.users' => function ($query) {
                $query->select('users.id', 'users.name', 'users.middleName', 'users.lastName', 'users.email', 'users.role');
            }])->findOrFail($request->reactable_id);


            $reactionEntries = DB::table('reactables')
                ->where('reactable_id', $request->reactable_id)
                ->where('reactable_type', $reactableType)
                ->join('users', 'reactables.user_id', '=', 'users.id')
                ->join('reactions', 'reactables.reaction_id', '=', 'reactions.id')
                ->select(
                    'users.id as user_id',
                    'users.name as user_name',
                    'users.middleName as middle_name',
                    'users.lastName as last_name',
                    'users.email as email',
                    'users.role as user_role',
                    'reactions.id as reaction_id',
                    'reactions.type as reaction_type',
                    'reactables.created_at',
                    'reactables.updated_at'
                )
                ->get();


            $formattedReactions = $reactionEntries->map(function ($entry) {
                return [
                    'user' => [
                        'id' => $entry->user_id,
                        'name' => $entry->user_name,
                        'middleName' => $entry->middle_name,
                        'lastName' => $entry->last_name,
                        'email' => $entry->email,
                        'role' => $entry->user_role,
                    ],
                    'reaction_id' => $entry->reaction_id,
                    'reaction_type' => $entry->reaction_type,
                    'created_at' => $entry->created_at,
                    'updated_at' => $entry->updated_at,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => $formattedReactions,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to process reaction',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}



//_________________________________________________________________________________________________
//                         OLD VERSIONS OF MY APIS
//________________________________________________________________________________________________


    //_________________________________________________________________________________________
    //version 2
    // not used
    //
    //public function getReportedComments($eventId)
    //{
    //    try {
    //        // Fetch all reported comments with their related comment threads
    //        $reportedComments = ReportedComment::with([
    //            'reporter:id,name,email',
    //            'comment.user:id,name,email',
    //            'comment.event:id,event_name'
    //        ])->get();
    //
    //        // Get all comments that are either reported or part of reported threads
    //        $commentIds = $reportedComments->pluck('comment_id')->toArray();
    //        $allComments = Comment::with('event')
    //            ->with('user')
    //            ->whereIn('id', $commentIds)
    //            ->orWhereHas('reportedComment')
    //            ->orWhereHas('descendants')
    //            ->orWhereHas('ancestors')
    //            ->get();
    //
    //
    //        // Build the threaded comment tree
    //        $buildTree = function ($comments, $parentId = null) use (&$buildTree, $allComments) {
    //            $branch = [];
    //
    //            foreach ($comments as $comment) {
    //                if ($comment->parent_id == $parentId) {
    //                    $commentData = [
    //                        'id' => $comment->id,
    //                        'user_id' => $comment->user_id,
    //                        'parent_id' => $comment->parent_id,
    //                        'name' => $comment->user->name,
    //                        'middle_name' => $comment->user->middleName,
    //                        'last_name' => $comment->user->lastName,
    //                        'email' => $comment->user->email,
    //                        'role' => $comment->user->role,
    //                        'content' => $comment->content,
    //                        'created_at' => $comment->created_at->toIso8601String(),
    //                        'is_reported' => $comment->reportedComment->isNotEmpty(),
    //                        'reports' => $comment->reportedComment->map(function ($report) {
    //                            return [
    //
    //                                'report_id' => $report->id,
    //                                'reporter_id' => $report->reporter->id,
    //                                'author' => trim("{$report->reporter->name} {$report->reporter->middleName} {$report->reporter->lastName}"),
    //                                'email' => $report->reporter->email,
    //                                'role' => $report->reporter->role,
    //                                'reason' => $report->reason,
    //
    //                            ];
    //                        }),
    //                        'replies' => $buildTree($allComments, $comment->id)
    //                    ];
    //
    //                    $branch[] = $commentData;
    //                }
    //            }
    //
    //            return $branch;
    //        };
    //
    //        // Build the tree starting from top-level comments
    //        $threadedComments = $buildTree($allComments, null);
    //
    //        // Filter to only include trees with reported comments
    //        $filterReportedTrees = function ($comments) use (&$filterReportedTrees) {
    //            return collect($comments)
    //                ->filter(function ($comment) use ($filterReportedTrees) {
    //                    if ($comment['is_reported']) return true;
    //                    return !empty($filterReportedTrees($comment['replies']));
    //                })
    //                ->values()
    //                ->toArray();
    //        };
    //
    //        $filteredComments = $filterReportedTrees($threadedComments);
    //
    //
    //        return response()->json([
    //            'status' => true,
    //            'data' => $filteredComments
    //        ]);
    //    } catch (\Throwable $th) {
    //        return response()->json([
    //            'status' => false,
    //            'message' => 'Failed to fetch reported comments: ' . $th->getMessage()
    //        ], 500);
    //    }
    //}
    ////_______________________________________________________________________________________
    ////version 3
    ////not used
    //public function get($eventId)
    //{
    //    try {
    //        // 1. First get all reported comments for this specific event
    //        $reportedComments = ReportedComment::with([
    //            'reporter:id,name,middleName,lastName,email,role', // the user indeed
    //            'comment.user:id,name,middleName,lastName,email,role'
    //        ])
    //            ->whereHas('comment', function ($q) use ($eventId) {
    //                $q->where('event_id', $eventId);
    //            })
    //            ->get();
    //
    //        // 2. Get all reported comment ids 
    //        $reportedCommentIds = $reportedComments->pluck('comment_id');
    //
    //        $allComments = Comment::with([
    //            'user:id,name,middleName,lastName,email,role',
    //            'reportedComment.reporter:id,name,middleName,lastName,email,role'
    //        ])
    //            ->where('event_id', $eventId)
    //            ->where(function ($query) use ($reportedCommentIds) {
    //                $query->whereIn('id', $reportedCommentIds)
    //                    ->orWhereHas('allDescendants', function ($q) use ($reportedCommentIds) {
    //                        $q->whereIn('id', $reportedCommentIds);
    //                    })
    //                    ->orWhereHas('allAncestors', function ($q) use ($reportedCommentIds) {
    //                        $q->whereIn('id', $reportedCommentIds);
    //                    });
    //            })
    //            ->get();
    //
    //        // 4. Build the complete threaded comment tree
    //        $buildTree = function ($parentId = null) use (&$buildTree, $allComments) {
    //            return $allComments
    //                ->where('parent_id', $parentId)
    //                ->map(function ($comment) use ($buildTree) {
    //                    return [
    //                        'id' => $comment->id,
    //                        'user_id' => $comment->user_id,
    //                        'parent_id' => $comment->parent_id,
    //                        'full_name' => trim("{$comment->user->name} {$comment->user->middleName} {$comment->user->lastName}"),
    //                        'email' => $comment->user->email,
    //                        'role' => $comment->user->role,
    //                        'content' => $comment->content,
    //                        'created_at' => $comment->created_at->toIso8601String(),
    //                        'is_reported' => $comment->reportedComment->isNotEmpty(),
    //                        'reports' => $comment->reportedComment->map(function ($report) {
    //                            return [
    //                                'report_id' => $report->id,
    //                                'reporter_id' => $report->reporter->id,
    //                                'full_name' => trim("{$report->reporter->name} {$report->reporter->middleName} {$report->reporter->lastName}"),
    //                                'email' => $report->reporter->email,
    //                                'role' => $report->reporter->role,
    //                                'reason' => $report->reason,
    //                                'reported_at' => $report->created_at->toIso8601String()
    //                            ];
    //                        }),
    //                        'replies' => $buildTree($comment->id)
    //                    ];
    //                })
    //                ->values()
    //                ->toArray();
    //        };
    //
    //        // 5. Filter to only include trees with reported comments
    //        $filterReportedTrees = function ($comments) use (&$filterReportedTrees) {
    //            return collect($comments)
    //                ->filter(function ($comment) use ($filterReportedTrees) {
    //                    return $comment['is_reported'] || !empty($filterReportedTrees($comment['replies']));
    //                })
    //                ->values()
    //                ->toArray();
    //        };
    //
    //        // 6. Build and filter the tree
    //        $fullTree = $buildTree(null);
    //        $filteredComments = $filterReportedTrees($fullTree);
    //
    //        return response()->json([
    //            'status' => true,
    //            'data' => $filteredComments
    //        ]);
    //    } catch (\Throwable $th) {
    //        return response()->json([
    //            'status' => false,
    //            'message' => 'Failed to fetch reported comments: ' . $th->getMessage()
    //        ], 500);
    //    }
    //}
    // version 1 'not used'
    //public function showReportedComments($eventID)
    //{
    //    try {
    //
    //        $reportedComments = ReportedComment::with([
    //            'reporter',
    //            'Comment.user',
    //            'Comment.parent.user',
    //        ])->whereHas('comment', function ($q) use ($eventID) {
    //            $q->where('event_id', $eventID);
    //        })->get();
    //
    //        //$event_name= Event::select('event_name')->where('id', $eventID)->get();
    //        $events = Event::where('id', $eventID)->get();
    //
    //        $Events = $events->map(function ($event) {
    //            return [
    //                'id' => $event->id,
    //                'user_id' => $event->user_id,
    //                'full_name' => trim("{$event->user->name} {$event->user->middleName} {$event->user->lastName}"),
    //                'email' => $event->user->email,
    //                'event_name' => $event->event_name,
    //                'post' => $event->post,
    //                'is_published' => $event->is_published,
    //                'created_at' => $event->created_at,
    //                'updated_at' => $event->updated_at,
    //                'media' => $event->media->map(function ($media) {
    //                    return [
    //                        'id' => $media->id,
    //                        'url' => asset(Storage::url($media->photo_path)),
    //                    ];
    //                })
    //            ];
    //        });
    //
    //        $cleanResponse = $reportedComments->map(function ($report) {
    //            return [
    //                'report_id' => $report->id,
    //                'reporter' => trim("{$report->reporter->name} {$report->reporter->middleName} {$report->reporter->lastName}"),
    //                'reporter role' => $report->reporter->role,
    //                'reporter email' => $report->reporter->email,
    //                'reporter phone' => $report->reporter->phoneNumber,
    //                'reason' => $report->reason,
    //                'reported_at' => $report->created_at,
    //                'reported comment' => [
    //                    'id' => $report->Comment->id,
    //                    'parent_id' => $report->Comment->parent_id,
    //                    'content' => $report->Comment->content,
    //                    'author' => trim("{$report->Comment->user->name} {$report->Comment->user->middleName} {$report->Comment->user->lastName}"),
    //                    'author role' => $report->Comment->user->role,
    //                    'author email' => $report->Comment->user->email,
    //                    'author phone' => $report->Comment->user->phoneNumber,
    //                    'parent_comment' => $report->Comment->parent ? [
    //                        'id' => $report->Comment->parent->id,
    //                        'content' => $report->Comment->parent->content,
    //                        'author' => trim("{$report->Comment->parent->user->name} {$report->Comment->parent->user->middleName} {$report->Comment->parent->user->lastName}"),
    //                        'author role' => $report->Comment->parent->user->role,
    //                        'author email' => $report->Comment->parent->user->email,
    //                        'author phone' => $report->Comment->parent->user->phoneNumber,
    //                    ] : null,
    //                ],
    //            ];
    //        });
    //
    //
    //        return response()->json([
    //            'event' => $Events,
    //            'data' => $cleanResponse
    //        ]);
    //    } catch (\Throwable $th) {
    //        return response()->json([
    //            'status' => false,
    //            'message' => $th->getMessage(),
    //        ], 500);
    //    }
    //}
    //__________________________________________________________________________________________
