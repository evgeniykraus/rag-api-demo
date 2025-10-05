<?php

namespace App\Services;

use App\AiAgents\ImageToTextAgent;
use App\AiAgents\MetaDataExtractor\ComplianceAgent;
use App\AiAgents\MetaDataExtractor\CorrectnessAgent;
use App\AiAgents\MetaDataExtractor\EntitiesTagsAgent;
use App\AiAgents\MetaDataExtractor\ResolutionAgent;
use App\AiAgents\MetaDataExtractor\ToneClarityAgent;
use App\AiAgents\ProposalClassifierAgent;
use App\AiAgents\ProposalResponseGeneratorAgent;
use App\Jobs\AnalyzeProposalJob;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Proposal;
use App\Repositories\ProposalRepository;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Drivers\Gd\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use Nette\Utils\Random;
use Pgvector\Laravel\Vector as PgVector;
use Pgvector\Vector;
use Storage;
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
        $attachments = $data['attachments'] ?? [];

        // Обработка изображений через ImageToTextAgent
        $imageDescription = '';
        if (!empty($attachments)) {
            $imageDescription = $this->processAttachments($content, $attachments);
        }

        if (!empty($imageDescription)) {
            $content = $content . "\n\n[Фото]: " . trim($imageDescription);
        }

        $vector = new PgVector($this->embeddingsService->embedOne('passage: ' . $content));
        $categories = $this->proposalRepository->getSimilarCategoriesByVector($vector);

        $respond = ProposalClassifierAgent::for(uniqid())->message(ProposalClassifierAgent::buildMessage($content, $categories))->respond();

        $categoryId = $respond['id'];
        throw_if(Category::query()->where('id', $categoryId)->doesntExist(), new Exception("Категория с ID: {$categoryId} не найдена"));

        $data['category_id'] = $categoryId;
        // Удаляем attachments из данных для создания proposal
        unset($data['attachments']);

        $proposal = $this->proposalRepository->store($data);
        $this->proposalRepository->updateOrCreateVector($proposal, $vector);

        // Сохраняем файлы после создания proposal
        if (!empty($attachments)) {
            $this->saveAttachments($proposal, $attachments, $imageDescription);
        }

        return $proposal->load('attachments');
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

        $imageBase64 = [];
        $manager = new ImageManager(new Driver());

        foreach ($proposal->attachments as $attachment) {
            $filePath = Storage::disk('public')->path($attachment->path);

            if (file_exists($filePath)) {
                $image = $manager->read($filePath)->scale(width: 1024);
                $encoded = $image->encode(new JpegEncoder(quality: 25));
                $imageBase64[] = 'data:image/jpeg;base64,' . base64_encode((string)$encoded);
            }
        }

        return ProposalResponseGeneratorAgent::for(uniqid())->withImages($imageBase64)->message($message)->respond();
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

    public function dispatchAnalyzeProposalJob(Proposal $proposal): void
    {
        AnalyzeProposalJob::dispatch($proposal);
    }

    /**
     * @param Proposal $proposal
     * @return void
     * @throws Throwable
     */
    public function analyzeProposal(Proposal $proposal): void
    {
        throw_if(!$proposal->response, new Exception('Обращение без ответа не может быть проанализировано'));

        $question = $proposal->content;
        $answer = (string)$proposal->response->content;

        // Единый payload для агентов
        $pairMessage = "Вопрос (обращение):\n{$question}\n\nОтвет менеджера:\n{$answer}";

        // Запуск агентов
        $correctness = CorrectnessAgent::for('meta')->message($pairMessage)->respond();
        $toneClarity = ToneClarityAgent::for('meta')->message($answer)->respond();
        $compliance = ComplianceAgent::for('meta')->message($answer)->respond();
        $entitiesTags = EntitiesTagsAgent::for('meta')->message($pairMessage)->respond();
        $resolution = ResolutionAgent::for('meta')->message($pairMessage)->respond();

        // Все агенты отработали — создаем запись метаданных
        $data = [
            ...$correctness,
            ...$toneClarity,
            ...$compliance,
            ...$resolution,
            'proposal_id' => $proposal->id,
            'intent_tags' => $entitiesTags['intent_tags'] ?? null,
            'entities_locations' => $entitiesTags['entities']['locations'] ?? null,
            'entities_objects' => $entitiesTags['entities']['objects'] ?? null,
            'processed_at' => now(),
        ];

        $this->proposalRepository->storeProposalMetadata($data);
    }

    /**
     * @param string $content
     * @param array $attachments
     * @return string
     * @throws Throwable
     */
    private function processAttachments(string $content, array $attachments): string
    {
        $imageBase64 = [];
        $manager = new ImageManager(new Driver());

        foreach ($attachments as $file) {
            if ($file->isValid() && str_starts_with($file->getMimeType(), 'image/')) {
                $image = $manager->read($file->getRealPath())->scale(width: 1024);
                $encoded = $image->encode(new JpegEncoder(quality: 25));
                $imageBase64[] = 'data:image/jpeg;base64,' . base64_encode((string)$encoded);
            }
        }

        return empty($imageBase64)
            ? ''
            : ImageToTextAgent::for(uniqid())
                ->message("Сообщение от пользователя: \n{$content}")
                ->withImages($imageBase64)
                ->respond()['description'];
    }

    /**
     * Сохранение прикрепленных файлов
     */
    private function saveAttachments(Proposal $proposal, array $attachments, ?string $description = null): void
    {
        foreach ($attachments as $file) {
            if (!$file->isValid()) {
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $filename = uniqid() . '_' . $originalName;
            $path = $file->storeAs('attachments/' . date('Y/m'), $filename, 'public');

            Attachment::query()->create([
                'proposal_id' => $proposal->id,
                'original_name' => $originalName,
                'description' => $description,
                'filename' => $filename,
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }
}


