<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Domain\CurrencyHelper;
use App\Domain\TimezoneHelper;
use App\Repositories\ActivityRepository;
use App\Repositories\SettingRepository;
use App\Repositories\StaffRepository;
use App\Services\AuthService;
use App\Services\AuthorizationMiddleware;

class SettingController
{
    private SettingRepository $settingRepo;
    private StaffRepository $staffRepo;
    private AuthService $authService;
    private ActivityRepository $activityRepo;

    public function __construct(
        ?SettingRepository $settingRepo = null,
        ?StaffRepository $staffRepo = null,
        ?AuthService $authService = null,
        ?ActivityRepository $activityRepo = null
    ) {
        $this->settingRepo  = $settingRepo ?? new SettingRepository();
        $this->staffRepo    = $staffRepo ?? new StaffRepository();
        $this->authService  = $authService ?? new AuthService();
        $this->activityRepo = $activityRepo ?? new ActivityRepository();
    }

    public function index(Request $request): void
    {
        Session::requireStaff();
        AuthorizationMiddleware::requireRole(['manager', 'admin', 'owner'], 'index.php');

        $saved = false;
        $error = '';
        $tab   = $request->getString('tab', 'Course');

        // Instant AJAX weather test (supports city query or live GPS lat/lng)
        if ($request->getString('action') === 'weather_check') {
            $loc = $request->getString('location') ?: null;
            $lat = $request->input('lat') !== null && is_numeric($request->input('lat')) ? (float)$request->input('lat') : null;
            $lng = $request->input('lng') !== null && is_numeric($request->input('lng')) ? (float)$request->input('lng') : null;
            $unit = $request->getString('unit', 'F');
            $weatherService = new \App\Services\WeatherService($this->settingRepo);
            $data = $weatherService->getConditions($loc, $unit, true, $lat, $lng);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'ok', 'data' => $data]);
            exit;
        }

        if ($request->isPost()) {
            Session::validateRequestCsrf($request);
            $action = $request->getString('action');

            if ($action === 'save') {
                AuthorizationMiddleware::requireRole(['manager', 'admin', 'owner']);
                $fields = [
                    'course_name', 'phone', 'address', 'timezone', 'currency',
                    'course_location', 'course_lat', 'course_lng',
                    'green_speed', 'speed_desc', 'carts', 'carts_desc', 'weather_unit',
                    'first_tee', 'last_tee', 'interval', 'max_party', 'advance_days', 'cancel_hours',
                    'price_weekday_18', 'price_weekday_9', 'price_weekend_18', 'price_weekend_9',
                    'price_twilight_18', 'price_twilight_9',
                ];

                foreach ($fields as $field) {
                    $val = $request->input($field);
                    if ($val !== null) {
                        $this->settingRepo->set($field, trim((string)$val));
                    }
                }

                // Open days
                $openDays = $request->input('open_days', []);
                if (is_array($openDays)) {
                    $this->settingRepo->set('open_days', json_encode(array_values($openDays)));
                }

                // Twilight start
                if ($request->input('twilight_start') !== null) {
                    $this->settingRepo->set('twilight_start', (string)$request->getInt('twilight_start'));
                }

                // Feature and notification flags
                $flags = [
                    'flag_waitlist', 'flag_nine', 'flag_guest', 'flag_deposit', 'flag_twilight',
                    'flag_dynamic', 'flag_auto_offer', 'flag_n_book', 'flag_n_cancel', 'flag_n_wait',
                    'flag_n_daily', 'flag_n_sms',
                ];

                foreach ($flags as $flag) {
                    $this->settingRepo->set($flag, $request->input($flag) !== null ? '1' : '0');
                }

                // Invalidate weather cache and refresh conditions so changes propagate immediately
                $weatherService = new \App\Services\WeatherService($this->settingRepo);
                $weatherService->clearCache();
                $weatherService->getConditions(forceRefresh: true);

                $this->activityRepo->log('Settings updated', "{$tab} preferences changed", 'sand');
                Session::setFlash('settings_saved', '1');
                Response::redirect('settings.php?tab=' . urlencode($tab));
            }

            if ($action === 'invite') {
                AuthorizationMiddleware::requireRole(['admin', 'owner']);
                $name  = $request->getString('name');
                $email = $request->getString('email');
                $role  = $request->getString('role', 'Front Desk');

                if ($this->authService->inviteStaff($name, $email, $role)) {
                    Session::setFlash('staff_saved', '1');
                    Response::redirect('settings.php?tab=Staff');
                } else {
                    Session::setFlash('staff_error', 'Email already exists or invalid data.');
                    Response::redirect('settings.php?tab=Staff');
                }
            }

            if ($action === 'remove_staff') {
                AuthorizationMiddleware::requireRole(['admin', 'owner']);
                $staffId = $request->getInt('staff_id');
                $myId = (int)Session::getStaffId();
                $this->authService->removeStaff($staffId, $myId);
                Session::setFlash('staff_saved', '1');
                Response::redirect('settings.php?tab=Staff');
            }

            Response::redirect('settings.php?tab=' . urlencode($tab));
        }

        $saved = Session::getFlash('settings_saved') === '1' || Session::getFlash('staff_saved') === '1' || $request->getString('saved') === '1';
        $error = Session::getFlash('staff_error') ?? '';

        $settings = $this->settingRepo->getAll();
        $openDays = json_decode($settings['open_days'] ?? '[]', true) ?: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $days     = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $staff    = $this->staffRepo->getAll();
        $tabs     = ['Course', 'Tee Sheet', 'Pricing', 'Feature flags', 'Notifications', 'Staff'];
        $slots      = range(420, 1070, 30);
        $timezones  = TimezoneHelper::getAllGrouped();
        $currencies = CurrencyHelper::getAll();

        $weatherService = new \App\Services\WeatherService($this->settingRepo);
        $conditions     = $weatherService->getConditions($settings['course_location'] ?? 'Pine Valley, NJ', $settings['weather_unit'] ?? 'F');

        View::render('settings', [
            'currentPage' => 'settings',
            'tab'         => $tab,
            'saved'       => $saved,
            'error'       => $error,
            'settings'    => $settings,
            'conditions'  => $conditions,
            'openDays'    => $openDays,
            'days'        => $days,
            'staff'       => $staff,
            'tabs'        => $tabs,
            'slots'       => $slots,
            'timezones'   => $timezones,
            'currencies'  => $currencies,
        ]);
    }
}
