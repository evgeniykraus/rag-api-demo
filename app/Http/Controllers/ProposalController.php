<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProposalRequest;
use App\Http\Requests\ProposalUpdateRequest;
use App\Http\Resources\ProposalResource;
use App\Models\Proposal;
use App\Services\ProposalService;
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
        return ProposalResource::make($proposal);
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
}
