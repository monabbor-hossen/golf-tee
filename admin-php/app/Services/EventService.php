<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\ActivityRepository;
use App\Repositories\EventRegistrationRepository;
use App\Repositories\EventRepository;
use App\Repositories\MemberRepository;

class EventService
{
    private EventRepository $eventRepo;
    private EventRegistrationRepository $regRepo;
    private ActivityRepository $activityRepo;
    private MemberRepository $memberRepo;

    public function __construct(
        ?EventRepository $eventRepo = null,
        ?EventRegistrationRepository $regRepo = null,
        ?ActivityRepository $activityRepo = null,
        ?MemberRepository $memberRepo = null
    ) {
        $this->eventRepo    = $eventRepo ?? new EventRepository();
        $this->regRepo      = $regRepo ?? new EventRegistrationRepository();
        $this->activityRepo = $activityRepo ?? new ActivityRepository();
        $this->memberRepo   = $memberRepo ?? new MemberRepository();
    }

    public function listEvents(string $tab = 'upcoming', ?int $selectedId = null): array
    {
        $today = date('Y-m-d');
        $events = $this->eventRepo->getEvents($tab, $today);

        $selectedEvent = null;
        $registrations = [];

        if ($selectedId) {
            $selectedEvent = $this->eventRepo->findById($selectedId);
            if ($selectedEvent) {
                $registrations = $this->regRepo->getByEventId($selectedId);
            }
        }

        if (!$selectedEvent && !empty($events)) {
            $futureEvents = array_filter($events, fn($e) => $e['event_date'] >= $today);
            $selectedEvent = reset($futureEvents) ?: $events[0];
            $selectedId = (int)$selectedEvent['id'];
            $registrations = $this->regRepo->getByEventId($selectedId);
        }

        $allMemberNames = $this->memberRepo->getAllNames();

        return [
            'events'         => $events,
            'selectedEvent'  => $selectedEvent,
            'selectedId'     => $selectedId,
            'registrations'  => $registrations,
            'tab'            => $tab,
            'today'          => $today,
            'allMemberNames' => $allMemberNames,
        ];
    }

    public function createEvent(array $input): int
    {
        $id = $this->eventRepo->create($input);
        $this->activityRepo->log('Event created', $input['name'], 'forest');
        return $id;
    }

    public function toggleOpen(int $id, bool $currentStatus): bool
    {
        return $this->eventRepo->toggleOpen($id, !$currentStatus);
    }

    public function registerAttendee(int $eventId, string $name): bool
    {
        $name = trim($name);
        if ($name === '') {
            return false;
        }

        $event = $this->eventRepo->findById($eventId);
        if (!$event) {
            return false;
        }

        $count = $this->regRepo->getCount($eventId);
        if ($count < (int)$event['capacity']) {
            return $this->regRepo->register($eventId, $name);
        }

        return false;
    }

    public function unregisterAttendee(int $registrationId): bool
    {
        return $this->regRepo->unregister($registrationId);
    }

    public function deleteEvent(int $id): bool
    {
        return $this->eventRepo->delete($id);
    }
}
