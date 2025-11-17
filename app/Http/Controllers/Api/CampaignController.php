<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Requests\Campaign\UpdateCampaignRequest;
use App\Http\Requests\WhatsappTemplateStoreRequest;
use App\Http\Requests\WhatsappTemplateUpdateRequest;
use App\Http\Resources\Campaign\CampaignCollection;
use App\Http\Resources\Campaign\CampaignResource;
use App\Http\Resources\CampaignWhatsappTemplateResource;
use App\Http\Traits\ApiResponse;
use App\Services\Campaign\CampaignService;
use App\Services\Campaign\CampaignWhatsappTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CampaignService $campaignService,
        private CampaignWhatsappTemplateService $templateService
    ) {}

    public function index(Request $request): CampaignCollection
    {
        $filters = $request->only(['client_id', 'status', 'search']);
        $perPage = $request->input('per_page', 15);

        $campaigns = $this->campaignService->getCampaigns($filters, $perPage);

        return new CampaignCollection($campaigns);
    }

    public function show(string $id): JsonResponse
    {
        $campaign = $this->campaignService->getCampaignById($id);

        if (! $campaign) {
            return $this->notFoundResponse('Campaign not found');
        }

        return $this->successResponse(
            new CampaignResource($campaign),
            'Campaign retrieved successfully'
        );
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        try {
            $campaign = $this->campaignService->createCampaign(
                array_merge($request->validated(), [
                    'created_by' => auth()->id(),
                ])
            );

            return $this->createdResponse(
                new CampaignResource($campaign),
                'Campaign created successfully'
            );

        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse('Validation error', $e->getMessage(), 400);
        }
    }

    public function update(UpdateCampaignRequest $request, string $id): JsonResponse
    {
        try {
            $campaign = $this->campaignService->updateCampaign($id, $request->validated());

            return $this->updatedResponse(
                new CampaignResource($campaign),
                'Campaign updated successfully'
            );

        } catch (\InvalidArgumentException $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->campaignService->deleteCampaign($id);

            return $this->deletedResponse('Campaign deleted successfully. All related leads have been deleted.');

        } catch (\InvalidArgumentException $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }

    /**
     * Obtener todos los templates de WhatsApp de una campaña
     */
    public function getTemplates(string $campaignId): JsonResponse
    {
        try {
            $templates = $this->templateService->getTemplatesByCampaign($campaignId);

            return $this->successResponse(
                CampaignWhatsappTemplateResource::collection($templates),
                'Templates retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }

    /**
     * Crear un nuevo template de WhatsApp para una campaña
     */
    public function storeTemplate(string $campaignId, WhatsappTemplateStoreRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['campaign_id'] = $campaignId;

            $template = $this->templateService->createTemplate($data);

            return $this->createdResponse(
                new CampaignWhatsappTemplateResource($template),
                'Template creado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Error creating template', $e->getMessage());
        }
    }

    /**
     * Actualizar un template de WhatsApp
     */
    public function updateTemplate(string $campaignId, string $templateId, WhatsappTemplateUpdateRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $template = $this->templateService->updateTemplate($templateId, $data);

            return $this->updatedResponse(
                new CampaignWhatsappTemplateResource($template),
                'Template actualizado exitosamente'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Error updating template', $e->getMessage());
        }
    }

    /**
     * Eliminar un template de WhatsApp
     */
    public function destroyTemplate(string $campaignId, string $templateId): JsonResponse
    {
        try {
            $this->templateService->deleteTemplate($templateId);

            return $this->deletedResponse('Template eliminado exitosamente');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Error deleting template', $e->getMessage());
        }
    }
}
