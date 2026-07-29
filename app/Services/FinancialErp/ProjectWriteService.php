<?php

namespace App\Services\FinancialErp;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ProjectWriteService
{
    public function __construct(
        private readonly ProjectReadService $read,
    ) {}

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function save(array $data, int $adminId, ?string $role = null): array
    {
        $id = (int) ($data['id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Project name is required.');
        }

        $type = strtolower(trim((string) ($data['project_type'] ?? 'general'))) ?: 'general';
        if (! in_array($type, ProjectReadService::PROJECT_TYPES, true)) {
            throw new InvalidArgumentException('Invalid project type.');
        }

        $status = strtolower(trim((string) ($data['status'] ?? 'active'))) ?: 'active';
        if (! in_array($status, ProjectReadService::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid project status.');
        }

        $code = trim((string) ($data['code'] ?? ''));
        if ($code === '') {
            $code = ErpSupport::nextDocNo('PRJ', 'erp_projects', 'code');
        }

        $payload = [
            'code' => $code,
            'name' => $name,
            'project_type' => $type,
            'budget_amount' => round((float) ($data['budget_amount'] ?? 0), 2),
            'status' => $status,
            'start_date' => trim((string) ($data['start_date'] ?? '')) ?: null,
            'end_date' => trim((string) ($data['end_date'] ?? '')) ?: null,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'is_active' => ! isset($data['is_active']) || ! empty($data['is_active']),
            'updated_at' => now(),
        ];

        if ($id > 0) {
            DB::table('erp_projects')->where('id', $id)->whereNull('deleted_at')->update($payload);
            ErpSupport::logAudit('project', $id, 'update', null, $payload, $adminId, $role);
        } else {
            $payload['created_by'] = $adminId ?: null;
            $payload['created_at'] = now();
            $id = (int) DB::table('erp_projects')->insertGetId($payload);
            ErpSupport::logAudit('project', $id, 'create', null, $payload, $adminId, $role);
        }

        $project = $this->read->getProject($id);
        if ($project === null) {
            throw new InvalidArgumentException('Project not found after save.');
        }

        return $project;
    }
}
