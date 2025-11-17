<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Resources\Client\ClientCollection;
use App\Http\Resources\Client\ClientResource;
use App\Http\Traits\ApiResponse;
use App\Services\Client\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ClientService $clientService
    ) {}

    public function index(Request $request): ClientCollection
    {
        $filters = $request->only(['status', 'search']);
        $perPage = $request->input('per_page', 15);

        $clients = $this->clientService->getClients($filters, $perPage);

        return new ClientCollection($clients);
    }

    public function show(string $id): JsonResponse
    {
        $client = $this->clientService->getClientById($id);

        if (! $client) {
            return $this->notFoundResponse('Client not found');
        }

        return $this->successResponse(
            new ClientResource($client),
            'Client retrieved successfully'
        );
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        try {
            $client = $this->clientService->createClient(
                array_merge($request->validated(), [
                    'created_by' => auth()->id(),
                ])
            );

            return $this->createdResponse(
                new ClientResource($client),
                'Client created successfully'
            );

        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse('Validation error', $e->getMessage(), 400);
        }
    }

    public function update(UpdateClientRequest $request, string $id): JsonResponse
    {
        try {
            $client = $this->clientService->updateClient($id, $request->validated());

            return $this->updatedResponse(
                new ClientResource($client),
                'Client updated successfully'
            );

        } catch (\InvalidArgumentException $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->clientService->deleteClient($id);

            return $this->deletedResponse('Client deleted successfully. All related campaigns, leads and calls have been deleted.');

        } catch (\InvalidArgumentException $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }
}
