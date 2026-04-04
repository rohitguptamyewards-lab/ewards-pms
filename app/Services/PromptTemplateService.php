<?php

namespace App\Services;

use App\Repositories\PromptTemplateRepository;

class PromptTemplateService
{
    public function __construct(
        private readonly PromptTemplateRepository $promptTemplateRepository,
    ) {}

    public function create(array $data): int
    {
        if (isset($data['tags']) && is_array($data['tags'])) {
            $data['tags'] = json_encode($data['tags']);
        }

        return $this->promptTemplateRepository->create($data);
    }

    public function update(int $id, array $data): int
    {
        if (isset($data['tags']) && is_array($data['tags'])) {
            $data['tags'] = json_encode($data['tags']);
        }

        return $this->promptTemplateRepository->update($id, $data);
    }

    public function recordUsage(int $id): void
    {
        $this->promptTemplateRepository->incrementUsage($id);
    }
}
