<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\EventService;

class EventController
{
    private EventService $eventService;

    public function __construct(?EventService $eventService = null)
    {
        $this->eventService = $eventService ?? new EventService();
    }

    public function index(Request $request): void
    {
        Session::requireStaff();
        \App\Services\AuthorizationMiddleware::requireRole(['staff', 'pro', 'manager', 'admin', 'owner'], 'index.php');

        if ($request->isPost()) {
            Session::validateRequestCsrf($request);
            $action = $request->getString('action');

            if ($action === 'create') {
                \App\Services\AuthorizationMiddleware::requireRole(['manager', 'admin', 'owner']);
                $id = $this->eventService->createEvent($request->all());
                Response::redirect('events.php?id=' . $id);
            }

            if ($action === 'toggle_open') {
                \App\Services\AuthorizationMiddleware::requireRole(['manager', 'admin', 'owner']);
                $id = $request->getInt('id');
                $isOpen = $request->getBool('is_open');
                $this->eventService->toggleOpen($id, $isOpen);
                Response::redirect('events.php?id=' . $id);
            }

            if ($action === 'register') {
                $eventId = $request->getInt('event_id');
                $name = $request->getString('name');
                $this->eventService->registerAttendee($eventId, $name);
                Response::redirect('events.php?id=' . $eventId);
            }

            if ($action === 'unregister') {
                $regId = $request->getInt('reg_id');
                $eventId = $request->getInt('event_id');
                $this->eventService->unregisterAttendee($regId);
                Response::redirect('events.php?id=' . $eventId);
            }

            if ($action === 'delete') {
                \App\Services\AuthorizationMiddleware::requireRole(['manager', 'admin', 'owner']);
                $id = $request->getInt('id');
                $this->eventService->deleteEvent($id);
                Response::redirect('events.php');
            }

            Response::redirect('events.php');
        }

        $tab = $request->getString('tab', 'upcoming');
        $selectedId = $request->getInt('id');

        $data = $this->eventService->listEvents($tab, $selectedId ?: null);
        $data['currentPage'] = 'events';

        View::render('events', $data);
    }
}
