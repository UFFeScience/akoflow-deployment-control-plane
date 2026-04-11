<?php

namespace App\Http\Controllers;

use App\Http\Resources\AnsiblePlaybookResource;
use App\Http\Resources\AnsiblePlaybookTaskResource;
use App\Models\AnsiblePlaybook;
use App\Services\CreateAnsiblePlaybookService;
use App\Services\DeleteAnsiblePlaybookService;
use App\Services\GetAnsiblePlaybookService;
use App\Services\ListAnsiblePlaybooksService;
use App\Services\SyncAnsiblePlaybookTasksService;
use App\Services\UpdateAnsiblePlaybookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnsiblePlaybookController extends Controller
{
    public function __construct(
        private ListAnsiblePlaybooksService    $listActivities,
        private CreateAnsiblePlaybookService    $createActivity,
        private GetAnsiblePlaybookService       $getActivity,
        private UpdateAnsiblePlaybookService    $updateActivity,
        private DeleteAnsiblePlaybookService    $deleteActivity,
        private SyncAnsiblePlaybookTasksService $syncTasks,
    ) {}

    public function index(string $templateId, string $versionId, string $configId): JsonResponse
    {
        $playbooks = $this->listActivities->handle($configId);

        return response()->json(AnsiblePlaybookResource::collection($playbooks));
    }

    public function store(string $templateId, string $versionId, string $configId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                     => 'required|string|max:255',
            'description'              => 'nullable|string',
            'trigger'                  => 'required|string|in:' . implode(',', AnsiblePlaybook::triggers()),
            'playbook_slug'            => 'nullable|string|max:100',
            'playbook_yaml'            => 'nullable|string',
            'inventory_template'       => 'nullable|string',
            'vars_mapping_json'        => 'nullable|array',
            'outputs_mapping_json'     => 'nullable|array',
            'credential_env_keys'      => 'nullable|array',
            'roles_json'               => 'nullable|array',
            'position'                 => 'nullable|integer|min:0',
            'enabled'                  => 'nullable|boolean',
            'tasks'                    => 'nullable|array',
            'tasks.*.name'             => 'required_with:tasks|string|max:500',
            'tasks.*.module'           => 'nullable|string|max:100',
            'tasks.*.module_args_json' => 'nullable|array',
            'tasks.*.when_condition'   => 'nullable|string|max:500',
            'tasks.*.become'           => 'nullable|boolean',
            'tasks.*.position'         => 'nullable|integer|min:0',
            'tasks.*.enabled'          => 'nullable|boolean',
        ]);

        $activity = $this->createActivity->handle($configId, $validated);

        return response()->json(new AnsiblePlaybookResource($activity), 201);
    }

    public function show(string $templateId, string $versionId, string $configId, string $playbookId): JsonResponse
    {
        $activity = $this->getActivity->handle($playbookId);

        return response()->json(new AnsiblePlaybookResource($activity));
    }

    public function update(string $templateId, string $versionId, string $configId, string $playbookId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                 => 'sometimes|string|max:255',
            'description'         => 'nullable|string',
            'trigger'             => 'sometimes|string|in:' . implode(',', AnsiblePlaybook::triggers()),
            'playbook_slug'       => 'nullable|string|max:100',
            'playbook_yaml'       => 'nullable|string',
            'inventory_template'  => 'nullable|string',
            'vars_mapping_json'   => 'nullable|array',
            'outputs_mapping_json'=> 'nullable|array',
            'credential_env_keys' => 'nullable|array',
            'roles_json'          => 'nullable|array',
            'position'            => 'nullable|integer|min:0',
            'enabled'             => 'nullable|boolean',
        ]);

        $activity = $this->updateActivity->handle($playbookId, $validated);

        return response()->json(new AnsiblePlaybookResource($activity));
    }

    public function destroy(string $templateId, string $versionId, string $configId, string $playbookId): JsonResponse
    {
        $this->deleteActivity->handle($playbookId);

        return response()->json(null, 204);
    }

    public function syncTasks(string $templateId, string $versionId, string $configId, string $playbookId, Request $request): JsonResponse
    {
        $request->validate([
            'tasks'                    => 'required|array',
            'tasks.*.name'             => 'required|string|max:500',
            'tasks.*.module'           => 'nullable|string|max:100',
            'tasks.*.module_args_json' => 'nullable|array',
            'tasks.*.when_condition'   => 'nullable|string|max:500',
            'tasks.*.become'           => 'nullable|boolean',
            'tasks.*.position'         => 'nullable|integer|min:0',
            'tasks.*.enabled'          => 'nullable|boolean',
        ]);

        $tasks = $this->syncTasks->handle($playbookId, $request->input('tasks'));

        return response()->json(AnsiblePlaybookTaskResource::collection($tasks));
    }
}
