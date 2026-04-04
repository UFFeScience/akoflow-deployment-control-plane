<?php

namespace App\Http\Controllers;

use App\Http\Resources\AnsiblePlaybookTaskResource;
use App\Http\Resources\RunbookResource;
use App\Services\CreateRunbookService;
use App\Services\DeleteRunbookService;
use App\Services\GetRunbookService;
use App\Services\ListRunbooksService;
use App\Services\SyncPlaybookTasksService;
use App\Services\SyncRunbookTasksService;
use App\Services\UpdateRunbookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RunbookController extends Controller
{
    public function __construct(
        private ListRunbooksService      $listRunbooks,
        private CreateRunbookService     $createRunbook,
        private GetRunbookService        $getRunbook,
        private UpdateRunbookService     $updateRunbook,
        private DeleteRunbookService     $deleteRunbook,
        private SyncRunbookTasksService  $syncRunbookTasks,
        private SyncPlaybookTasksService $syncPlaybookTasks,
    ) {}

    public function index(string $templateId, string $versionId, string $configId): JsonResponse
    {
        $runbooks = $this->listRunbooks->handle($configId);

        return response()->json(RunbookResource::collection($runbooks));
    }

    public function store(string $templateId, string $versionId, string $configId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                     => 'required|string|max:255',
            'description'              => 'nullable|string',
            'playbook_yaml'            => 'nullable|string',
            'vars_mapping_json'        => 'nullable|array',
            'credential_env_keys'      => 'nullable|array',
            'roles_json'               => 'nullable|array',
            'position'                 => 'nullable|integer|min:0',
            'tasks'                    => 'nullable|array',
            'tasks.*.name'             => 'required_with:tasks|string|max:500',
            'tasks.*.module'           => 'nullable|string|max:100',
            'tasks.*.module_args_json' => 'nullable|array',
            'tasks.*.when_condition'   => 'nullable|string|max:500',
            'tasks.*.become'           => 'nullable|boolean',
            'tasks.*.position'         => 'nullable|integer|min:0',
            'tasks.*.enabled'          => 'nullable|boolean',
        ]);

        $runbook = $this->createRunbook->handle($configId, $validated);

        return response()->json(new RunbookResource($runbook), 201);
    }

    public function show(string $templateId, string $versionId, string $configId, string $runbookId): JsonResponse
    {
        $runbook = $this->getRunbook->handle($runbookId);

        return response()->json(new RunbookResource($runbook));
    }

    public function update(string $templateId, string $versionId, string $configId, string $runbookId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                => 'sometimes|string|max:255',
            'description'         => 'nullable|string',
            'playbook_yaml'       => 'nullable|string',
            'vars_mapping_json'   => 'nullable|array',
            'credential_env_keys' => 'nullable|array',
            'roles_json'          => 'nullable|array',
            'position'            => 'nullable|integer|min:0',
        ]);

        $runbook = $this->updateRunbook->handle($runbookId, $validated);

        return response()->json(new RunbookResource($runbook));
    }

    public function destroy(string $templateId, string $versionId, string $configId, string $runbookId): JsonResponse
    {
        $this->deleteRunbook->handle($runbookId);

        return response()->json(null, 204);
    }

    public function syncTasks(string $templateId, string $versionId, string $configId, string $runbookId, Request $request): JsonResponse
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

        $tasks = $this->syncRunbookTasks->handle($runbookId, $request->input('tasks'));

        return response()->json(AnsiblePlaybookTaskResource::collection($tasks));
    }

    public function syncPlaybookTasks(string $templateId, string $versionId, string $configId, Request $request): JsonResponse
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

        $tasks = $this->syncPlaybookTasks->handle($configId, $request->input('tasks'));

        if ($tasks === null) {
            return response()->json(['message' => 'No ansible playbook configured for this provider configuration.'], 404);
        }

        return response()->json(AnsiblePlaybookTaskResource::collection($tasks));
    }
}
