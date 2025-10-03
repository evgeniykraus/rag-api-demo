<?php

namespace App\Services;

use App\AiAgents\ProposalClassifierAgent;
use App\AiAgents\ProposalResponseGeneratorAgent;
use App\Models\Category;
use App\Models\Proposal;
use App\Models\ProposalResponse;
use App\Repositories\ProposalRepository;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Nette\Utils\Random;
use Pgvector\Laravel\Vector as PgVector;
use Pgvector\Vector;
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
        throw_if(Category::query()->where('id', $categoryId)->doesntExist(), new Exception("Категория с ID: {$categoryId} не найдена"));

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
     * @param string $query
     * @return Collection
     */
    public function search(string $query): Collection
    {
        return $this->proposalRepository->getTopKByVector(
            new Vector($this->embeddingsService->embedOne($query))
        );
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
        return $this->proposalRepository->getTopK($proposal, $limit, ['category', 'category.parent', 'city']);
    }

    /**
     * @param Proposal $proposal
     * @param array $data
     * @return Proposal
     */
    public function storeResponse(Proposal $proposal, array $data): Proposal
    {
        $this->proposalRepository->storeResponse($proposal, $data);

        return $proposal->load(['category', 'category.parent', 'city', 'response']);
    }

    /**
     * @param Proposal $proposal
     * @return string
     * @throws Throwable
     */
    public function generateResponse(Proposal $proposal): string
    {
        $message = ProposalResponseGeneratorAgent::buildMessage($proposal, $this->findSimilarResponses($proposal, 3));

        return ProposalResponseGeneratorAgent::for(uniqid())->message($message)->respond();
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
}


