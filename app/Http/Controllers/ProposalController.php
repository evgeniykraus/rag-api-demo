<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProposalRequest;
use App\Http\Requests\ProposalResponseRequest;
use App\Http\Requests\ProposalSearchRequest;
use App\Http\Requests\ProposalUpdateRequest;
use App\Http\Resources\ProposalResource;
use App\Http\Resources\SimilarProposalResource;
use App\Models\Proposal;
use App\Services\ProposalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Throwable;

class ProposalController extends Controller
{
    public function __construct(
        private readonly ProposalService $proposalService,
    )
    {
    }

    /**
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        return ProposalResource::collection(
            $this->proposalService->index()
        );
    }

    /**
     * @param ProposalRequest $request
     * @return ProposalResource
     * @throws Throwable
     */
    public function store(ProposalRequest $request)
    {
        return ProposalResource::make(
            $this->proposalService->store($request->validated())
        );
    }

    /**
     * @param Proposal $proposal
     * @return ProposalResource
     */
    public function show(Proposal $proposal)
    {
        return ProposalResource::make(
            $proposal->load(['category', 'category.parent', 'city', 'response'])
        );
    }

    /**
     * @param Proposal $proposal
     * @param ProposalUpdateRequest $request
     * @return void
     * @throws Throwable
     */
    public function update(Proposal $proposal, ProposalUpdateRequest $request)
    {
        return ProposalResource::make(
            $this->proposalService->update($proposal, $request->validated())
        );
    }

    /**
     * @param Proposal $proposal
     * @return Response
     */
    public function destroy(Proposal $proposal)
    {
        $this->proposalService->destroy($proposal);
        return response()->noContent();
    }

    /**
     * @param ProposalSearchRequest $request
     * @return AnonymousResourceCollection
     */
    public function search(ProposalSearchRequest $request): AnonymousResourceCollection
    {
        return SimilarProposalResource::collection(
            $this->proposalService->search($request->get('query'))
        );
    }

    /**
     * @param Proposal $proposal
     * @return AnonymousResourceCollection
     */
    public function similar(Proposal $proposal): AnonymousResourceCollection
    {
        return SimilarProposalResource::collection(
            $this->proposalService->findSimilarProposals($proposal, 3)
        );
    }

    /**
     *  Ответить на обращение
     *
     * @param Proposal $proposal
     * @param ProposalResponseRequest $request
     * @return ProposalResource
     */
    public function storeResponse(Proposal $proposal, ProposalResponseRequest $request): ProposalResource
    {
        return ProposalResource::make(
            $this->proposalService->storeResponse($proposal, $request->validated())
        );
    }

    /**
     * @param Proposal $proposal
     * @return JsonResponse
     * @throws Throwable
     */
    public function generateResponse(Proposal $proposal): JsonResponse
    {
        return response()->json([
            'response' => $this->proposalService->generateResponse($proposal)
        ]);
    }
}
