<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Pgvector\Vector;

class ProposalRepository
{

    /**
     * @return LengthAwarePaginator
     */
    public function index(): LengthAwarePaginator
    {
        return Proposal::query()->with(['category', 'category.parent', 'vector', 'city'])->paginate();
    }

    /**
     * @param array $data
     * @return Proposal
     */
    public function store(array $data): Proposal
    {
        return Proposal::query()->create($data)->load(['category', 'category.parent', 'city']);
    }

    /**
     * @param Proposal $proposal
     * @param array $data
     * @return void
     */
    public function update(Proposal $proposal, array $data): void
    {
        $proposal->update($data);
    }

    /**
     * @param Proposal $proposal
     * @return void
     */
    public function delete(Proposal $proposal): void
    {
        $proposal->delete();
    }

    /**
     * @param Proposal $proposal
     * @param Vector $vectorLiteral
     * @return void
     */
    public function updateOrCreateVector(Proposal $proposal, Vector $vectorLiteral): void
    {
        $proposal->vector()->updateOrCreate(
            ['proposal_id' => $proposal->id],
            ['embedding' => $vectorLiteral]
        );
    }

    public function getSimilarWithResponse(Proposal $proposal, int $k): Collection
    {
        return $this->topKBuilderByProposal($proposal, $k)->with(['response'])->whereHas('response')->get();
    }

    /**
     * Минимальный NNS-запрос: только ids и similarity из векторного хранилища.
     * Вся остальная мета подтягивается из основной MySQL БД.
     *
     * @param Proposal $proposal
     * @param int $k
     * @param array $with
     * @return Collection
     */
    public function getTopK(Proposal $proposal, int $k, array $with = []): Collection
    {
        return $this->topKBuilderByProposal($proposal, $k)->with($with)->get();
    }

    public function getSimilarCategories(Proposal $proposal, int $limit = 10): Collection
    {
        $sub = $this->topKBuilderByProposal($proposal, max($limit * 10, 100))
            ->select('proposals.category_id');

        return Category::query()
            ->with(['parent'])
            ->select('categories.*')
            ->selectRaw('COUNT(*) AS freq')
            ->joinSub($sub, 't', 't.category_id', '=', 'categories.id')
            ->whereNotNull('categories.parent_id')
            ->groupBy('categories.id')
            ->orderByDesc('freq')
            ->limit($limit)
            ->get();
    }

    public function getSimilarCategoriesByVector(Vector $vector, int $limit = 10): Collection
    {
        $sub = $this->topKBuilder($vector, max($limit * 10, 100))
            ->select('proposals.category_id');

        return Category::query()
            ->with(['parent'])
            ->select('categories.*')
            ->selectRaw('COUNT(*) AS freq')
            ->joinSub($sub, 't', 't.category_id', '=', 'categories.id')
            ->whereNotNull('categories.parent_id')
            ->groupBy('categories.id')
            ->orderByDesc('freq')
            ->limit($limit)
            ->get();
    }

    private function topKBuilderByProposal(Proposal $proposal, int $k): Builder
    {
        return $this->topKBuilder($proposal->vector->embedding, $k)
            ->where('proposals.id', '!=', $proposal->id);
    }

    private function topKBuilder(Vector $vector, int $k): Builder
    {
        return Proposal::query()
            ->select('proposals.*')
            ->selectRaw('1 - (proposal_vectors.embedding <=> ?::vector) AS similarity', [$vector])
            ->join('proposal_vectors', 'proposal_vectors.proposal_id', '=', 'proposals.id')
            ->orderByRaw('proposal_vectors.embedding <=> ?::vector ASC', [$vector])
            ->limit($k);
    }
}


