<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordRulesController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ProviderCredentialController;
use App\Http\Controllers\ProviderVariableSchemaController;
use App\Http\Controllers\InstanceTypeController;
use App\Http\Controllers\EnvironmentTemplateController;
use App\Http\Controllers\EnvironmentController;
use App\Http\Controllers\ClusterController;
use App\Http\Controllers\ProvisionedInstanceController;
use App\Http\Controllers\TerraformRunController;
use App\Http\Controllers\EnvironmentTemplateTerraformModuleController;
use App\Http\Middleware\AuthMiddleware;


Route::post('/auth/register', [AuthController::class, 'register'])->name('register');
Route::post('/auth/login', [AuthController::class, 'login'])->name('login');
Route::get('/lost', [AuthController::class, 'lost'])->name('lost');

// Public UI endpoints for rendering and rules
Route::prefix('ui')->group(function () {
    Route::get('/password-rules', [PasswordRulesController::class, 'rules'])->name('ui.passwordRules');
    Route::get('/render/password-rules', [PasswordRulesController::class, 'render'])->name('ui.passwordRules.render');
});

Route::middleware([AuthMiddleware::class])->group(function () {
    Route::post('/auth/refresh', [AuthController::class, 'refresh'])->name('refresh');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('logout');

    // User routes
    Route::get('/user', [UserController::class, 'getCurrentUser'])->name('user.current');
    Route::patch('/user', [UserController::class, 'updateCurrentUser'])->name('user.update');
    Route::delete('/user', [UserController::class, 'deleteCurrentUser'])->name('user.delete');
    Route::patch('/user/password', [UserController::class, 'changePassword'])->name('user.changePassword');

    // Organization routes
    Route::get('/organizations', [OrganizationController::class, 'list'])->name('organizations.list');
    Route::post('/organizations', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::get('/organizations/{organizationId}', [OrganizationController::class, 'getById'])->name('organizations.getById');
    Route::patch('/organizations/{organizationId}', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::delete('/organizations/{organizationId}', [OrganizationController::class, 'delete'])->name('organizations.delete');

    // Organization Members routes
    Route::post('/organizations/{organizationId}/members', [OrganizationController::class, 'addMember'])->name('organizations.addMember');
    Route::delete('/organizations/{organizationId}/members/{userId}', [OrganizationController::class, 'removeMember'])->name('organizations.removeMember');
    Route::patch('/organizations/{organizationId}/members/{userId}/role', [OrganizationController::class, 'updateMemberRole'])->name('organizations.updateMemberRole');
    Route::get('/organizations/{organizationId}/members', [OrganizationController::class, 'listMembers'])->name('organizations.listMembers');

    // Project routes
    Route::get('/organizations/{organizationId}/projects', [ProjectController::class, 'listByOrganization'])->name('projects.listByOrganization');
    Route::post('/organizations/{organizationId}/projects', [ProjectController::class, 'create'])->name('projects.create');
    Route::get('/organizations/{organizationId}/projects/{projectId}', [ProjectController::class, 'getById'])->name('projects.getById');
    Route::patch('/organizations/{organizationId}/projects/{projectId}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/organizations/{organizationId}/projects/{projectId}', [ProjectController::class, 'delete'])->name('projects.delete');

    // AkôCloud core infrastructure module
    Route::get('/providers', [ProviderController::class, 'index']);
    Route::post('/providers', [ProviderController::class, 'store']);
    Route::get('/providers/{id}', [ProviderController::class, 'show']);
    Route::patch('/providers/{id}/health', [ProviderController::class, 'updateHealth']);
    Route::post('/providers/{id}/health/check', [ProviderController::class, 'runHealthCheck']);

    // Provider credentials
    Route::get('/providers/{providerId}/credentials', [ProviderCredentialController::class, 'index']);
    Route::post('/providers/{providerId}/credentials', [ProviderCredentialController::class, 'store']);
    Route::delete('/providers/{providerId}/credentials/{credentialId}', [ProviderCredentialController::class, 'destroy']);

    // Provider variable schemas
    Route::get('/provider-type-schemas', [ProviderVariableSchemaController::class, 'index']);
    Route::get('/provider-type-schemas/{slug}', [ProviderVariableSchemaController::class, 'show']);

    Route::get('/instance-types', [InstanceTypeController::class, 'index']);
    Route::post('/instance-types', [InstanceTypeController::class, 'store']);
    Route::patch('/instance-types/{id}/status', [InstanceTypeController::class, 'updateStatus']);

    Route::get('/environment-templates', [EnvironmentTemplateController::class, 'index']);
    Route::post('/environment-templates', [EnvironmentTemplateController::class, 'store']);
    Route::get('/environment-templates/{id}', [EnvironmentTemplateController::class, 'show']);
    Route::get('/environment-templates/{id}/versions', [EnvironmentTemplateController::class, 'listVersions']);
    Route::get('/environment-templates/{id}/versions/active', [EnvironmentTemplateController::class, 'showActiveVersion']);
    Route::post('/environment-templates/{id}/versions', [EnvironmentTemplateController::class, 'addVersion']);
    Route::get('/environment-templates/{id}/versions/{versionId}', [EnvironmentTemplateController::class, 'showVersion']);
    Route::patch('/environment-templates/{id}/versions/{versionId}/activate', [EnvironmentTemplateController::class, 'activateVersion']);

    // Terraform modules for a template version (one per cloud provider)
    Route::get(
        '/environment-templates/{templateId}/versions/{versionId}/terraform-modules',
        [EnvironmentTemplateTerraformModuleController::class, 'index']
    )->name('template-versions.terraform-modules.index');
    Route::get(
        '/environment-templates/{templateId}/versions/{versionId}/terraform-module',
        [EnvironmentTemplateTerraformModuleController::class, 'showByVersion']
    )->name('template-versions.terraform-module.show');
    Route::get(
        '/environment-templates/{templateId}/versions/{versionId}/terraform-modules/{providerType}',
        [EnvironmentTemplateTerraformModuleController::class, 'show']
    )->name('template-versions.terraform-modules.show');
    Route::put(
        '/environment-templates/{templateId}/versions/{versionId}/terraform-module',
        [EnvironmentTemplateTerraformModuleController::class, 'upsertByVersion']
    )->name('template-versions.terraform-module.upsert');
    Route::put(
        '/environment-templates/{templateId}/versions/{versionId}/terraform-modules/{providerType}',
        [EnvironmentTemplateTerraformModuleController::class, 'upsert']
    )->name('template-versions.terraform-modules.upsert');

    Route::get('/projects/{projectId}/environments', [EnvironmentController::class, 'index']);
    Route::post('/projects/{projectId}/environments', [EnvironmentController::class, 'store']);
    Route::get('/projects/{projectId}/environments/{id}', [EnvironmentController::class, 'show']);

    // Terraform provisioning runs
    Route::get('/projects/{projectId}/environments/{environmentId}/terraform-runs', [TerraformRunController::class, 'index']);
    Route::post('/projects/{projectId}/environments/{environmentId}/terraform-runs', [TerraformRunController::class, 'store']);
    Route::get('/projects/{projectId}/environments/{environmentId}/terraform-runs/{runId}', [TerraformRunController::class, 'show']);
    Route::post('/projects/{projectId}/environments/{environmentId}/terraform-runs/destroy', [TerraformRunController::class, 'destroy']);

    Route::get('/environments/{id}/clusters', [ClusterController::class, 'index']);
    Route::post('/environments/{id}/clusters', [ClusterController::class, 'store']);
    Route::post('/clusters/{id}/scale', [ClusterController::class, 'scale']);
    Route::patch('/clusters/{id}/nodes', [ClusterController::class, 'updateNodes']);
    Route::delete('/clusters/{id}', [ClusterController::class, 'destroy']);

    Route::get('/clusters/{id}/instances', [ProvisionedInstanceController::class, 'listByCluster']);
    Route::get('/instances/{id}', [ProvisionedInstanceController::class, 'show']);
    Route::get('/instances/{id}/logs', [ProvisionedInstanceController::class, 'logs']);

    });
