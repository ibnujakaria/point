<?php

namespace App\Http\Controllers\Api\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\Project\DeleteProjectRequest;
use App\Http\Requests\Project\Project\StoreProjectRequest;
use App\Http\Requests\Project\Project\UpdateProjectRequest;
use App\Http\Resources\ApiCollection;
use App\Http\Resources\ApiResource;
use App\Http\Resources\Project\Project\ProjectResource;
use App\Model\Master\User;
use App\Model\Project\Project;
use App\Model\Project\ProjectUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return ApiCollection
     */
    public function index(Request $request)
    {
        $projects = Project::join('project_user', 'projects.id', '=', 'project_user.project_id')
            ->where('project_user.user_id', auth()->user()->id)
            ->select('projects.*', 'user_id', 'user_name', 'user_email', 'joined', 'request_join_at', 'project_user.id as user_invitation_id');

        if ($request->get('search')) {
            $projects = $projects->where(function ($q) use ($request) {
                $q->where('code', 'like', '%'.$request->get('search').'%')
                    ->orWhere('name', 'like', '%'.$request->get('search').'%');
            });
        }

        $projects = pagination($projects, $request->input('limit'));

        return new ApiCollection($projects);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProjectRequest $request
     * @return \App\Http\Resources\Project\Project\ProjectResource
     */
    public function store(StoreProjectRequest $request)
    {
        // User only allowed to create max 1 project
        $numberOfProject = Project::where('owner_id', auth()->user()->id)->count();
        // TODO: disable new project creation
        if ($numberOfProject >= 100) {
            return response()->json([
                'code' => 422,
                'message' => 'We are updating our server, currently you cannot create new project',
            ], 422);
        }

        // Create new database for tenant project
        $dbName = env('DB_DATABASE').'_'.strtolower($request->get('code'));
        Artisan::call('tenant:database:create', ['db_name' => $dbName]);

        // Update tenant database name in configuration
        config()->set('database.connections.tenant.database', $dbName);
        DB::connection('tenant')->reconnect();
        DB::connection('tenant')->beginTransaction();

        $project = new Project;
        $project->owner_id = auth()->user()->id;
        $project->code = $request->get('code');
        $project->name = $request->get('name');
        $project->group = $request->get('group');
        $project->timezone = $request->get('timezone');
        $project->address = $request->get('address');
        $project->phone = $request->get('phone');
        $project->whatsapp = $request->get('whatsapp');
        $project->website = $request->get('website');
        $project->marketplace_notes = $request->get('marketplace_notes');
        $project->vat_id_number = $request->get('vat_id_number');
        $project->invitation_code = get_invitation_code();
        $project->save();

        $projectUser = new ProjectUser;
        $projectUser->project_id = $project->id;
        $projectUser->user_id = $project->owner_id;
        $projectUser->user_name = $project->owner->name;
        $projectUser->user_email = $project->owner->email;
        $projectUser->joined = true;
        $projectUser->save();

        // Migrate database
        Artisan::call('tenant:migrate', ['db_name' => $dbName]);

        // Clone user point into their database
        $user = new User;
        $user->id = auth()->user()->id;
        $user->name = auth()->user()->name;
        $user->first_name = auth()->user()->first_name;
        $user->last_name = auth()->user()->last_name;
        $user->email = auth()->user()->email;
        $user->save();

        Artisan::call('tenant:seed:first', ['db_name' => $dbName]);

        DB::connection('tenant')->commit();

        return new ProjectResource($project);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return ApiResource
     */
    public function show($id)
    {
        $project = Project::findOrFail($id)->load('users');

        $dbName = env('DB_DATABASE').'_'.strtolower($project->code);

        $project->db_size = dbm_get_size($dbName, 'tenant');

        return new ApiResource($project);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Project\Project\UpdateProjectRequest $request
     * @param  int                                                    $id
     *
     * @return \App\Http\Resources\Project\Project\ProjectResource
     */
    public function update(UpdateProjectRequest $request, $id)
    {
        // Update tenant database name in configuration
        $project = Project::findOrFail($id);
        $project->name = $request->get('name');
        $project->group = $request->get('group');
        $project->address = $request->get('address');
        $project->phone = $request->get('phone');
        $project->whatsapp = $request->get('whatsapp');
        $project->website = $request->get('website');
        $project->marketplace_notes = $request->get('marketplace_notes');
        $project->vat_id_number = $request->get('vat_id_number');
        $project->invitation_code = $request->get('invitation_code');
        $project->invitation_code_enabled = $request->get('invitation_code_enabled');
        $project->save();

        return new ProjectResource($project);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Http\Requests\Project\Project\DeleteProjectRequest $request
     * @param  int                                                    $id
     *
     * @return \App\Http\Resources\Project\Project\ProjectResource
     */
    public function destroy(DeleteProjectRequest $request, $id)
    {
        $project = Project::findOrFail($id);

        $project->delete();

        // Delete database tenant
        Artisan::call('tenant:database:delete', [
            'db_name' => env('DB_DATABASE').'_'.strtolower($project->code),
        ]);

        return new ProjectResource($project);
    }
}
