<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ActivityRepository;
use App\Repositories\BookingRepository;
use App\Repositories\MemberRepository;

class MemberService
{
    private MemberRepository $memberRepo;
    private BookingRepository $bookingRepo;
    private ActivityRepository $activityRepo;

    public function __construct(
        ?MemberRepository $memberRepo = null,
        ?BookingRepository $bookingRepo = null,
        ?ActivityRepository $activityRepo = null
    ) {
        $this->memberRepo   = $memberRepo ?? new MemberRepository();
        $this->bookingRepo  = $bookingRepo ?? new BookingRepository();
        $this->activityRepo = $activityRepo ?? new ActivityRepository();
    }

    public function listMembers(
        string $query = '',
        string $tier = 'All',
        string $status = 'All',
        string $sort = 'name',
        string $dir = 'asc',
        ?int $selectedId = null
    ): array {
        $members = $this->memberRepo->queryMembers($query, $tier, $status, $sort, $dir);
        $counts = $this->memberRepo->getStatusCounts();
        $total = $this->memberRepo->getTotalCount();

        $selectedMember = null;
        if ($selectedId) {
            $selectedMember = $this->memberRepo->findById($selectedId);
        }
        if (!$selectedMember && !empty($members)) {
            $selectedMember = $members[0];
            $selectedId = (int)$selectedMember['id'];
        }

        $bookingHistory = [];
        if ($selectedMember) {
            $bookingHistory = $this->bookingRepo->getByMemberId((int)$selectedMember['id'], 5);
        }

        return [
            'members'        => $members,
            'counts'         => $counts,
            'total'          => $total,
            'selectedMember' => $selectedMember,
            'selectedId'     => $selectedId,
            'bookingHistory' => $bookingHistory,
            'query'          => $query,
            'tier'           => $tier,
            'status'         => $status,
            'sort'           => $sort,
            'dir'            => $dir,
        ];
    }

    public function addMember(array $input): int
    {
        $data = [
            'name'   => trim($input['name'] ?? ''),
            'email'  => trim($input['email'] ?? ''),
            'phone'  => trim($input['phone'] ?? ''),
            'tier'   => $input['tier'] ?? 'Standard',
            'hcp'    => (float)($input['hcp'] ?? 36.0),
            'status' => $input['status'] ?? 'Pending',
            'joined' => date('M Y'),
        ];

        $id = $this->memberRepo->create($data);
        $this->activityRepo->log('New member added', "{$data['name']} · {$data['tier']}", 'forest');
        return $id;
    }

    public function updateMember(int $id, array $input): bool
    {
        $data = [
            'name'   => trim($input['name'] ?? ''),
            'email'  => trim($input['email'] ?? ''),
            'phone'  => trim($input['phone'] ?? ''),
            'tier'   => $input['tier'] ?? 'Standard',
            'hcp'    => (float)($input['hcp'] ?? 36.0),
            'status' => $input['status'] ?? 'Active',
        ];

        $success = $this->memberRepo->update($id, $data);
        if ($success) {
            $this->activityRepo->log('Member updated', $data['name'], 'sand');
        }
        return $success;
    }

    public function approveMember(int $id): bool
    {
        $member = $this->memberRepo->findById($id);
        if (!$member) {
            return false;
        }

        $this->memberRepo->updateStatus($id, 'Active');
        $this->activityRepo->log('Member approved', $member['name'], 'forest');
        return true;
    }

    public function suspendMember(int $id): bool
    {
        return $this->memberRepo->updateStatus($id, 'Suspended');
    }

    public function deleteMember(int $id): bool
    {
        return $this->memberRepo->delete($id);
    }

    public function exportCsv(string $query = '', string $tier = 'All', string $status = 'All'): array
    {
        $members = $this->memberRepo->queryMembers($query, $tier, $status);
        $rows = [];
        foreach ($members as $m) {
            $rows[] = [
                $m['id'],
                $m['name'],
                $m['email'],
                $m['phone'],
                $m['tier'],
                $m['status'],
                $m['hcp'],
                $m['rounds'],
                $m['spend'],
                $m['joined'],
                $m['last_play'] ?? '',
            ];
        }
        return $rows;
    }
}
