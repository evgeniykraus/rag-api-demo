<?php

namespace App\Services;

use App\AiAgents\ProposalClassifierAgent;
use App\DTO\SimilarProposalData;
use App\Models\Category;
use App\Models\Proposal;
use App\Repositories\ProposalRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Nette\Utils\Random;
use Pgvector\Laravel\Vector as PgVector;
use Throwable;


readonly class ProposalService
{
    public function __construct(
        private ProposalRepository $proposalRepository,
        private EmbeddingService   $embeddingsService
    )
    {
    }

    public function index(): LengthAwarePaginator
    {
        return $this->proposalRepository->index();
    }


    /**
     * @param array $data
     * @return Proposal
     * @throws Throwable
     */
    public function store(array $data): Proposal
    {
        $content = $data['content'];
        $vector = new PgVector($this->embeddingsService->embedOne($content));
        $categories = $this->proposalRepository->getSimilarCategoriesByVector($vector);

        $respond = ProposalClassifierAgent::for(Random::generate())->message(ProposalClassifierAgent::buildMessage($content, $categories))->respond();

        $categoryId = $respond['id'];
        throw_if(Category::query()->whereId($categoryId)->doesntExist(), new \Exception("Category {$categoryId} not found"));

        $data['category_id'] = $categoryId;

        $proposal = $this->proposalRepository->store($data);
        $this->proposalRepository->updateOrCreateVector($proposal, $vector);

        return $proposal;
    }

    /**
     * @param Proposal $proposal
     * @param array $data
     * @return Proposal
     * @throws Throwable
     */
    public function update(Proposal $proposal, array $data): Proposal
    {
        if (mb_strtolower($data['content']) !== mb_strtolower($proposal->content)) {
            $vector = new PgVector($this->embeddingsService->embedOne($data['content']));
            $this->proposalRepository->updateOrCreateVector($proposal, $vector);

            $categories = $this->proposalRepository->getSimilarCategoriesByVector($vector);

            $respond = ProposalClassifierAgent::for(Random::generate())->message(ProposalClassifierAgent::buildMessage($data['content'], $categories))->respond();
            $categoryId = $respond['id'];
            throw_if(Category::query()->whereId($categoryId)->doesntExist(), new \Exception("Category {$categoryId} not found"));
            $data['category_id'] = $categoryId;
        }

        $this->proposalRepository->update($proposal, $data);

        return $proposal->load(['category', 'category.parent', 'city']);
    }

    /**
     * @param Proposal $proposal
     * @return void
     */
    public function destroy(Proposal $proposal): void
    {
        $this->proposalRepository->delete($proposal);
    }

    /**
     * Найти похожие proposal по proposal_id.
     * Возвращает top-N похожих proposal.
     *
     * @param Proposal $proposal
     * @param int $limit
     * @return Collection
     */
    public function findSimilarProposals(Proposal $proposal, int $limit = 10): Collection
    {
        $proposals = $this->proposalRepository->getTopK($proposal, $limit);
        return SimilarProposalData::fromCollection($proposals);
    }

    /**
     * Найти ответы (proposalResponses) из похожих proposal
     *
     * @param Proposal $proposal
     * @param int $limit
     * @return Collection
     */
    public function findSimilarResponses(Proposal $proposal, int $limit = 10): Collection
    {
        return $this->proposalRepository->getSimilarWithResponse($proposal, $limit)->pluck('response');
    }

    /**
     * Найти ответы (proposalResponses) из похожих proposal
     *
     * @param Proposal $proposal
     * @param int $limit
     * @return Collection
     */
    public function findSimilarCategories(Proposal $proposal, int $limit = 10): Collection
    {
        return $this->proposalRepository->getSimilarCategories($proposal, $limit);
    }

    /**
     * Построить/обновить вектор для proposal.
     *
     * @param Proposal $proposal
     * @return bool
     */
    public function buildVector(Proposal $proposal): bool
    {
        if (!$proposal->content) {
            return false;
        }

        $vector = $this->embeddingsService->embedOne('passage: ' . $proposal->content);
        if (empty($vector)) {
            return false;
        }

        $this->proposalRepository->updateOrCreateVector($proposal, new PgVector($vector));

        return true;
    }
}


