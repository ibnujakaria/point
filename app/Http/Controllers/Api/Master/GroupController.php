<?php

namespace App\Http\Controllers\Api\Master;

use App\Helpers\Master\GroupClassReference;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Group\StoreGroupRequest;
use App\Http\Requests\Master\Group\UpdateGroupRequest;
use App\Http\Resources\ApiCollection;
use App\Http\Resources\ApiResource;
use App\Model\Master\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return ApiCollection
     */
    public function index(Request $request)
    {
        $groupClassReference = $request->get('class_reference');

        if (is_array($groupClassReference)) {
            foreach ($groupClassReference as $groupClassRef) {
                if (! GroupClassReference::isAvailable($groupClassRef)) {
                    return response()->json(GroupClassReference::$isNotAvailableResponse);
                }
            }
            $groups = Group::eloquentFilter($request);
        } else {
            if (! GroupClassReference::isAvailable($groupClassReference)) {
                return response()->json(GroupClassReference::$isNotAvailableResponse);
            }
            $groups = Group::where('class_reference', $groupClassReference)->eloquentFilter($request);
        }

        if ($request->get('type')) {
            $groups = $groups->where('type', $request->get('type'));
        }

        $groups = pagination($groups, $request->get('limit'));

        return new ApiCollection($groups);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreGroupRequest $request
     * @return ApiResource
     */
    public function store(StoreGroupRequest $request)
    {
        $groupClassReference = $request->get('class_reference');

        if (! GroupClassReference::isAvailable($groupClassReference)) {
            return response()->json(GroupClassReference::$isNotAvailableResponse);
        }

        $group = Group::create($request->all());

        return new ApiResource($group);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param  int $id
     * @return ApiResource
     */
    public function show(Request $request, $id)
    {
        $group = Group::eloquentFilter($request)->findOrFail($id);

        return new ApiResource($group);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateGroupRequest $request
     * @param  int $id
     * @return ApiResource
     */
    public function update(UpdateGroupRequest $request, $id)
    {
        $group = Group::findOrFail($id);
        $group->fill($request->all());
        $group->save();

        return new ApiResource($group);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $group = Group::findOrFail($id);
        $group->delete();

        return response()->json([], 204);
    }
}
